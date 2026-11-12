<?php

namespace App\Filament\Resources;

use App\Enums\DailyLogStatus;
use App\Enums\LessonPhaseType;
use App\Exceptions\AiLessonPlannerException;
use App\Filament\Resources\DailyLogResource\Pages;
use App\Filament\Resources\DailyLogResource\RelationManagers\AttendancesRelationManager;
use App\Models\ActiveLearningStrategy;
use App\Models\BehaviorObservation;
use App\Models\CurriculumResource;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\AiLessonPlannerService;
use App\Services\DailyLogEvaluationWorkSyncService;
use App\Services\DailyLogPedagogicalPlanSyncService;
use App\Services\DailyLogRegisterService;
use App\Services\StudentIncidentReportService;
use Carbon\Carbon;
use Closure;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DailyLogResource extends Resource
{
    protected static ?string $model = DailyLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function getNavigationLabel(): string
    {
        return __('دفتر النصوص');
    }

    public static function getModelLabel(): string
    {
        return __('سجل يومي');
    }

    public static function getPluralModelLabel(): string
    {
        return __('السجلات اليومية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التعليم والتدريس اليومي');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isTeacher() && ! $user->isAdmin();
    }

    protected static ?int $navigationSort = 2;

    /**
     * Teachers see only their logs; admins see everything
     * (record-level abilities are enforced by DailyLogPolicy).
     */
    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = parent::getEloquentQuery()->with([
            'reviewer',
            'schedule.subject',
            'schedule.classroom',
            'schedule.group.classroom',
        ]);

        return auth()->user()?->isAdmin()
            ? $query
            : $query->whereHas('schedule', fn (EloquentBuilder $query) => $query->where('user_id', Auth::id()));
    }

    public static function getNavigationBadge(): ?string
    {
        $plannedToday = static::getEloquentQuery()
            ->forDate(today())
            ->where('status', DailyLogStatus::Planned->value)
            ->count();

        return $plannedToday > 0 ? (string) $plannedToday : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Knowledge-resource options for the selected lesson.
     *
     * Only resources belonging to the schedule's subject and the classroom or
     * group level are offered, so a lesson cannot be attached to another
     * class's annual plan by mistake.
     *
     * @return array<int, string>
     */
    public static function curriculumResourceOptions(mixed $scheduleId): array
    {
        if (! is_numeric($scheduleId) || (int) $scheduleId <= 0) {
            return [];
        }

        $schedule = Schedule::query()
            ->with(['classroom', 'group.classroom'])
            ->find($scheduleId);

        if (! $schedule) {
            return [];
        }

        $levelId = $schedule->classroom?->level_id
            ?? $schedule->group?->classroom?->level_id;

        if (blank($levelId)) {
            return [];
        }

        return CurriculumResource::query()
            ->whereHas('sequence.domain', fn (EloquentBuilder $query) => $query
                ->where('subject_id', $schedule->subject_id)
                ->where('level_id', $levelId))
            ->with(['sequence.domain'])
            ->ordered()
            ->get()
            ->mapWithKeys(fn (CurriculumResource $resource): array => [
                $resource->id => $resource->label(),
            ])
            ->all();
    }

    /**
     * Reject a resource that does not belong to the selected schedule's
     * subject and level. The selector already limits options; this protects
     * against stale or forged form state.
     */
    protected static function curriculumResourceRule(): Closure
    {
        return function (Get $get): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                if (blank($value)) {
                    return;
                }

                $schedule = Schedule::query()
                    ->with(['classroom', 'group.classroom'])
                    ->find($get('schedule_id'));

                if (! $schedule) {
                    $fail(__('اختر الحصة أولاً.'));

                    return;
                }

                $levelId = $schedule->classroom?->level_id
                    ?? $schedule->group?->classroom?->level_id;

                $belongsToSchedule = filled($levelId)
                    && CurriculumResource::query()
                        ->whereKey($value)
                        ->whereHas('sequence.domain', fn (EloquentBuilder $query) => $query
                            ->where('subject_id', $schedule->subject_id)
                            ->where('level_id', $levelId))
                        ->exists();

                if (! $belongsToSchedule) {
                    $fail(__('المورد المعرفي المحدد لا ينتمي إلى مادة الحصة ومستواها.'));
                }
            };
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('daily_log_tabs')
                    ->tabs([
                        Tabs\Tab::make(__('التحضير المسبق'))
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Section::make(__('بيانات الحصة'))
                                    ->columns(2)
                                    ->schema([
                                        Select::make('schedule_id')
                                            ->label(__('الحصة'))
                                            ->default(function (): ?int {
                                                $scheduleId = request()->query('schedule_id');

                                                if (! is_scalar($scheduleId) || (int) $scheduleId <= 0) {
                                                    return null;
                                                }

                                                return Schedule::query()
                                                    ->when(
                                                        ! auth()->user()?->isAdmin(),
                                                        fn (EloquentBuilder $query) => $query->forTeacher((int) Auth::id()),
                                                    )
                                                    ->whereKey((int) $scheduleId)
                                                    ->value('id');
                                            })
                                            ->options(function (): array {
                                                $query = Schedule::query()
                                                    ->with(['subject', 'classroom.level', 'group.classroom', 'user']);

                                                if (! auth()->user()?->isAdmin()) {
                                                    $query->forTeacher(Auth::id());
                                                }

                                                return $query
                                                    ->get()
                                                    ->mapWithKeys(function (Schedule $schedule): array {
                                                        $label = $schedule->label();

                                                        if (auth()->user()?->isAdmin()) {
                                                            $label .= ' — '.($schedule->user?->name ?? '');
                                                        }

                                                        return [$schedule->id => $label];
                                                    })
                                                    ->all();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                                $set('curriculum_resource_id', null);

                                                if (blank($get('title')) && filled($state)) {
                                                    $schedule = Schedule::query()->with('subject')->find($state);

                                                    if ($schedule) {
                                                        $set('title', __('درس: :subject', ['subject' => $schedule->subject?->name ?? '']));
                                                    }
                                                }
                                            })
                                            ->rule(fn (Get $get, ?DailyLog $record): Unique => Rule::unique(DailyLog::class, 'schedule_id')
                                                ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereDate('date', $get('date')))
                                                ->ignore($record?->id)),

                                        DatePicker::make('date')
                                            ->label(__('التاريخ'))
                                            ->required()
                                            ->live()
                                            ->default(function (): string {
                                                $date = request()->query('date');

                                                return is_string($date) && Carbon::hasFormat($date, 'Y-m-d')
                                                    ? $date
                                                    : today()->toDateString();
                                            })
                                            ->maxDate(today()),

                                        TextInput::make('title')
                                            ->label(__('عنوان الحصة'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->helperText(function (Get $get): ?string {
                                                $title = $get('title');
                                                if (blank($title)) {
                                                    return null;
                                                }

                                                $tempLog = new DailyLog([
                                                    'title' => $title,
                                                    'date' => $get('date'),
                                                    'homework' => $get('homework'),
                                                ]);

                                                $evalType = app(DailyLogEvaluationWorkSyncService::class)->detectEvaluationType($tempLog);
                                                if ($evalType) {
                                                    return '💡 ربط ذكي مكتشف: سيتم اعتماد وتأشير هذه الحصة تلقائياً كـ «'.app(DailyLogEvaluationWorkSyncService::class)->getLabel($evalType).'» في سجلك التقويمي المعتمد عند إنجازها.';
                                                }

                                                $planType = app(DailyLogPedagogicalPlanSyncService::class)->detectPlanType($tempLog);
                                                if ($planType) {
                                                    return '💡 ربط ذكي مكتشف: سيتم اعتماد وتأشير هذه الحصة تلقائياً كـ «'.app(DailyLogPedagogicalPlanSyncService::class)->getLabel($planType).'» في سجل المخططات والتدرجات البيداغوجية عند إنجازها.';
                                                }

                                                return null;
                                            })
                                            ->suffixAction(static::generateLessonPlanAction())
                                            ->columnSpanFull(),

                                        Select::make('substitute_teacher_id')
                                            ->label(__('أستاذ المستخلاف'))
                                            ->visible(fn (?DailyLog $record): bool => auth()->user()?->isAdmin())
                                            ->options(function (): array {
                                                return User::query()
                                                    ->where('role', 'teacher')
                                                    ->get()
                                                    ->mapWithKeys(fn (User $u): array => [$u->id => $u->name])
                                                    ->all();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText(__('يمكن للمدير تعيين أستاذ مستخلف لتاريخ الحصة هذا.'))
                                            ->columnSpanFull(),

                                        Select::make('status')
                                            ->label(__('الحالة'))
                                            ->options(DailyLogStatus::options())
                                            ->default(DailyLogStatus::Planned->value)
                                            ->disabledOn('edit')
                                            ->required(),

                                        Select::make('curriculum_resource_id')
                                            ->label(__('المورد المعرفي'))
                                            ->options(fn (Get $get): array => static::curriculumResourceOptions($get('schedule_id')))
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->columnSpanFull()
                                            ->helperText(__('اختياري: اربط الحصة بمورد معرفي من المنهاج الرسمي لحساب تقدم القسم.'))
                                            ->rule(static::curriculumResourceRule()),
                                    ]),

                                Section::make(__('بطاقة الاستخلاف والتواصل البيداغوجي'))
                                    ->description(__('استخدم هذه البطاقة لضمان استمرارية التعلم أثناء الاستخلاف أو عودة الأستاذ الأصلي.'))
                                    ->icon('heroicon-o-user-group')
                                    ->visible(fn (Get $get): bool => filled($get('schedule_id')))
                                    ->columns(2)
                                    ->schema([
                                        Placeholder::make('last_completed_lesson')
                                            ->label(__('آخر درس منجز'))
                                            ->content(fn (Get $get): string => (function () use ($get) {
                                                $schedule = Schedule::query()->find($get('schedule_id'));
                                                if (! $schedule) {
                                                    return '—';
                                                }
                                                $last = DailyLog::query()
                                                    ->where('schedule_id', $schedule->id)
                                                    ->where('status', DailyLogStatus::Completed->value)
                                                    ->orderByDesc('date')
                                                    ->first();

                                                return $last
                                                    ? __(':title بتاريخ :date', ['title' => $last->title, 'date' => $last->date?->format('Y-m-d') ?? '—'])
                                                    : __('لا يوجد درس منجز بعد');
                                            })()),
                                        Placeholder::make('next_curriculum_resource')
                                            ->label(__('المورد المعرفي التالي المقترح'))
                                            ->content(fn (Get $get): string => (function () use ($get) {
                                                $schedule = Schedule::query()->find($get('schedule_id'));
                                                if (! $schedule) {
                                                    return '—';
                                                }
                                                $last = DailyLog::query()
                                                    ->where('schedule_id', $schedule->id)
                                                    ->where('status', DailyLogStatus::Completed->value)
                                                    ->orderByDesc('date')
                                                    ->first();
                                                $lastResourceId = $last?->curriculum_resource_id;
                                                $resources = static::curriculumResourceOptions($get('schedule_id'));
                                                if (! $lastResourceId) {
                                                    $first = array_key_first($resources);

                                                    return $first ? ($resources[$first] ?? '—') : '—';
                                                }
                                                $keys = array_keys($resources);
                                                $pos = array_search((string) $lastResourceId, $keys, true);
                                                $next = ($pos !== false && isset($keys[$pos + 1])) ? $keys[$pos + 1] : null;

                                                return $next ? ($resources[$next] ?? '—') : __('لا يوجد مورد معرفي مقترح بعد');
                                            })()),
                                    ]),

                                Section::make(__('المحتوى المخطط (ديناميكي)'))
                                    ->description(__('ابنِ التحضير عبر كتل مرنة: درس المرحلة المتوسطة، تمهيد، أو درس عام.'))
                                    ->schema([
                                        Builder::make('content_data')
                                            ->label(__('عناصر التحضير'))
                                            ->blocks(static::getContentDataBlocks())
                                            ->addActionLabel(__('إضافة عنصر للتحضير'))
                                            ->columnSpanFull()
                                            ->helperText(__('تُحفظ كل كتلة ديناميكياً في قاعدة البيانات وتُلخَّص تلقائياً عند تأكيد الإنجاز.')),
                                    ]),
                            ]),

                        Tabs\Tab::make(__('مرحلة الإنجاز'))
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Section::make(__('الأداء الفعلي في القسم'))
                                    ->headerActions([
                                        static::studentReportFormAction(),
                                    ])
                                    ->schema([
                                        RichEditor::make('actual_content')
                                            ->label(__('المحتوى المنجز فعلياً'))
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get): bool => $get('status') === DailyLogStatus::Completed->value)
                                            ->nullable()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'bulletedList',
                                                'orderedList',
                                                'link',
                                            ])
                                            ->helperText(__('يُملأ بعد الحصة. عند تأكيد الإنجاز دون إدخال أي نص، يُنسخ ملخص التحضير تلقائياً ليصبح السجل الرسمي.')),

                                        Textarea::make('homework')
                                            ->label(__('الواجب المنزلي'))
                                            ->rows(3)
                                            ->visible(fn (Get $get): bool => $get('status') === DailyLogStatus::Completed->value)
                                            ->columnSpanFull(),

                                        Textarea::make('observations')
                                            ->label(__('ملاحظات المعلم'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make(__('التأشيرة الإدارية'))
                    ->schema([
                        Placeholder::make('administrative_review_stamp')
                            ->label(__('حالة التأشير'))
                            ->content(fn (?DailyLog $record): string => $record?->reviewed_at
                                ? __('تم التأشير من طرف: :name بتاريخ :date', [
                                    'name' => $record->reviewer?->name ?? '—',
                                    'date' => $record->reviewed_at->format('Y-m-d H:i'),
                                ])
                                : '—'),

                        Placeholder::make('administrative_review_notes')
                            ->label(__('ملاحظات الإدارة / التوجيه التربوي'))
                            ->content(fn (?DailyLog $record): string => $record?->admin_notes ?: '—')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?DailyLog $record): bool => $record?->isReviewed() ?? false)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * "Generate preparation with AI" suffix action for the lesson title.
     *
     * Requires a lesson title and a selected schedule, then replaces (after
     * confirmation when content already exists) the dynamic preparation
     * builder with a full lesson-structure block produced by
     * {@see AiLessonPlannerService}. Filament's UUID conventions are honored
     * so the block renders instantly without a page reload.
     */
    public static function generateLessonPlanAction(): FormAction
    {
        return FormAction::make('generateLessonPlan')
            ->label(__('توليد التحضير بالذكاء الاصطناعي'))
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->tooltip(__('توليد بنية درس كاملة موظفاً استراتيجيات التعلم النشط انطلاقاً من عنوان الحصة والمادة والقسم.'))
            ->requiresConfirmation(fn (Get $get): bool => filled($get('content_data')))
            ->modalHeading(__('توليد التحضير بالذكاء الاصطناعي'))
            ->modalDescription(__('سيُستبدل المحتوى المخطط الحالي ببنية درس جديدة مقترحة من الذكاء الاصطناعي مع توظيف استراتيجيات التعلم النشط. يمكنك مراجعتها وتعديلها قبل الحفظ.'))
            ->modalSubmitActionLabel(__('توليد'))
            ->form([
                Select::make('preferred_strategy')
                    ->label(__('استراتيجية التعلم النشط المقترحة (اختياري)'))
                    ->placeholder(__('دع الذكاء الاصطناعي يختار الاستراتيجيات الأنسب'))
                    ->options(fn (): array => ActiveLearningStrategy::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('name', 'name')
                        ->toArray())
                    ->searchable()
                    ->helperText(__('يمكنك تحديد استراتيجية معينة ترغب في تطبيقها في الحصة، أو ترك الخيار للذكاء الاصطناعي لاختيار الأنسب لموضوع الدرس ومستواه.')),
            ])
            ->action(function (array $data, Get $get, Set $set, AiLessonPlannerService $planner): void {
                $title = trim((string) $get('title'));
                $scheduleId = $get('schedule_id');
                $preferredStrategy = filled($data['preferred_strategy'] ?? null)
                    ? (string) $data['preferred_strategy']
                    : null;

                if ($title === '' || blank($scheduleId)) {
                    Notification::make()
                        ->warning()
                        ->title(__('معلومات ناقصة'))
                        ->body(__('حدّد الحصة وأدخل عنوان الدرس أولاً.'))
                        ->send();

                    return;
                }

                $schedule = Schedule::query()
                    ->with(['subject', 'classroom.level', 'group.classroom.level'])
                    ->when(
                        ! Auth::user()?->isAdmin(),
                        fn (EloquentBuilder $query) => $query->forTeacher((int) Auth::id()),
                    )
                    ->find($scheduleId);

                if (! $schedule) {
                    Notification::make()
                        ->warning()
                        ->title(__('الحصة غير متاحة'))
                        ->body(__('تعذر العثور على الحصة المحددة ضمن صلاحياتك.'))
                        ->send();

                    return;
                }

                try {
                    $block = $planner->generateLessonPlan(
                        topic: $title,
                        subject: $schedule->subject?->name ?? '',
                        level: $schedule->classroom?->level?->name
                            ?? $schedule->group?->classroom?->level?->name
                            ?? '',
                        locale: app()->getLocale(),
                        preferredStrategy: $preferredStrategy,
                    );
                } catch (AiLessonPlannerException $exception) {
                    Notification::make()
                        ->danger()
                        ->title(__('تعذر توليد التحضير'))
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();

                    return;
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title(__('تعذر توليد التحضير'))
                        ->body(__('حدث خطأ غير متوقع. حاول مرة أخرى لاحقاً.'))
                        ->persistent()
                        ->send();

                    return;
                }

                // Filament keys Builder blocks and Repeater items by UUID.
                $set('content_data', [(string) Str::uuid() => $block]);

                Notification::make()
                    ->success()
                    ->title(__('تم توليد التحضير'))
                    ->body(__('راجع المراحل المقترحة وعدّلها ثم احفظ السجل.'))
                    ->send();
            });
    }

    /**
     * The dynamic block definitions for the preparation builder.
     *
     * @return list<Builder\Block>
     */
    public static function getContentDataBlocks(): array
    {
        return [
            Builder\Block::make('lesson_structure')
                ->label(__('بنية الدرس'))
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    TextInput::make('competence')
                        ->label(__('الكفاءة المستهدفة'))
                        ->columnSpanFull(),

                    Repeater::make('lesson_phases')
                        ->label(__('مراحل الدرس'))
                        ->schema([
                            TextInput::make('phase_name')
                                ->label(__('اسم المرحلة'))
                                ->required(),

                            Select::make('strategy')
                                ->label(__('استراتيجية التعلم النشط'))
                                ->placeholder(__('اختر استراتيجية (اختياري)...'))
                                ->options(fn (): array => ActiveLearningStrategy::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'name')
                                    ->toArray())
                                ->searchable(),

                            RichEditor::make('content')
                                ->label(__('محتوى المرحلة'))
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'bulletedList',
                                    'orderedList',
                                    'link',
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => filled($state['phase_name'] ?? null)
                            ? ($state['phase_name'].(filled($state['strategy'] ?? null) ? ' — ('.$state['strategy'].')' : ''))
                            : null),
                ])
                ->columnSpanFull(),

            Builder\Block::make('middle_school_lesson')
                ->label(__('درس المرحلة المتوسطة'))
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    TextInput::make('lesson_title')
                        ->label(__('عنوان الدرس'))
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('competence')
                        ->label(__('الكفاءة المستهدفة'))
                        ->placeholder(__('مثال: يحلّ التلميذ مسائل على الكسور'))
                        ->columnSpanFull(),

                    Select::make('pedagogical_situation')
                        ->label(__('الموقف البيداغوجي'))
                        ->options([
                            'daily_life' => __('سياق حياتي'),
                            'problem' => __('وضعية إشكالية'),
                            'project' => __('مشروع'),
                            'review' => __('مراجعة'),
                        ])
                        ->searchable()
                        ->preload(),

                    Repeater::make('phases')
                        ->label(__('مراحل الدرس'))
                        ->schema([
                            Select::make('type')
                                ->label(__('المرحلة'))
                                ->options(LessonPhaseType::options())
                                ->default(LessonPhaseType::Warmup->value)
                                ->required(),

                            TextInput::make('duration')
                                ->label(__('المدة'))
                                ->numeric()
                                ->suffix(__('دقيقة')),

                            Textarea::make('description')
                                ->label(__('الوصف'))
                                ->rows(2)
                                ->columnSpanFull(),

                            Select::make('strategy')
                                ->label(__('استراتيجية التعلم النشط'))
                                ->options(ActiveLearningStrategy::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id')
                                    ->toArray())
                                ->searchable()
                                ->placeholder(__('اختر استراتيجية...'))
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => LessonPhaseType::tryFrom($state['type'] ?? '')?->label()),
                ])
                ->columnSpanFull(),

            Builder\Block::make('warmup')
                ->label(__('تمهيد / تصحيح الفرض'))
                ->icon('heroicon-o-fire')
                ->schema([
                    Textarea::make('description')
                        ->label(__('وصف التمهيد'))
                        ->rows(2)
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('duration')
                        ->label(__('المدة'))
                        ->numeric()
                        ->suffix(__('دقيقة')),
                ]),

            Builder\Block::make('general_lesson')
                ->label(__('درس عام'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('title')
                        ->label(__('عنوان المحتوى'))
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('objectives')
                        ->label(__('الأهداف'))
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('content')
                        ->label(__('محتوى الدرس'))
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Shared "Confirm Completion" configuration.
     *
     * Filament v3 requires actions inside a table to be Tables\Actions\Action
     * (bound to the table via ->table()), while page headers use the unified
     * Actions\Action. Hence the same definition is applied to two
     * context-specific instances.
     */
    protected static function configureConfirmCompletion(Actions\Action|Tables\Actions\Action $action): Actions\Action|Tables\Actions\Action
    {
        return $action
            ->label(__('تأكيد الإنجاز'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('تأكيد إنجاز الحصة'))
            ->modalDescription(__('سيتم تغيير الحالة إلى "منجز" ونسخ ملخص المحتوى المخطط إلى حقل الأداء الفعلي ليصبح السجل الرسمي.'))
            ->modalSubmitActionLabel(__('تأكيد'))
            ->visible(fn (DailyLog $record): bool => (Auth::user()?->isTeacher() ?? false)
                && $record->isPlanned()
                && (Auth::id() === $record->schedule?->user_id || Auth::id() === $record->substitute_teacher_id))
            ->action(function (DailyLog $record): void {
                abort_unless(Auth::user()?->isTeacher() && (Auth::id() === $record->schedule?->user_id || Auth::id() === $record->substitute_teacher_id), 403);

                $record->markAsCompleted(static::generateActualContent($record));

                Notification::make()
                    ->title(__('تم تأكيد الإنجاز'))
                    ->body(__('أصبح هذا السجل هو المحتوى الرسمي لهذه الحصة.'))
                    ->success()
                    ->send();
            });
    }

    public static function generateActualContentFromData(string $title, ?array $contentData): string
    {
        $html = ['<h2>'.e($title).'</h2>'];

        foreach ($contentData ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if ($type === 'lesson_structure' || $type === 'middle_school_lesson') {
                if (filled($data['competence'] ?? null)) {
                    $html[] = '<h3>'.e(__('الكفاءة المستهدفة')).'</h3><p>'.e($data['competence']).'</p>';
                }

                $phases = $data['lesson_phases'] ?? $data['phases'] ?? [];

                foreach (is_array($phases) ? $phases : [] as $index => $phase) {
                    if (! is_array($phase)) {
                        continue;
                    }

                    $phaseType = is_string($phase['type'] ?? null)
                        ? LessonPhaseType::tryFrom($phase['type'])
                        : null;
                    $name = (is_string($phase['phase_name'] ?? null) && filled($phase['phase_name']))
                        ? $phase['phase_name']
                        : $phaseType?->label()
                        ?? __('المرحلة').' '.($index + 1);
                    $content = $phase['content'] ?? $phase['description'] ?? null;
                    $strategy = $phase['strategy'] ?? null;
                    $strategyName = null;
                    if (filled($strategy)) {
                        if (is_numeric($strategy)) {
                            $strategyName = ActiveLearningStrategy::query()->find($strategy)?->name ?? (string) $strategy;
                        } else {
                            $strategyName = (string) $strategy;
                        }
                    }

                    if ($strategyName) {
                        $html[] = '<h4>'.e($name).' <span class="strategy-badge">'.e(__('استراتيجية: :strategy', ['strategy' => $strategyName])).'</span></h4>';
                    } else {
                        $html[] = '<h4>'.e($name).'</h4>';
                    }

                    if (is_scalar($content) && filled($content)) {
                        $html[] = '<p>'.nl2br(e(strip_tags((string) $content))).'</p>';
                    }
                }

                continue;
            }

            if ($type === 'general_lesson') {
                foreach (['title' => __('عنوان المحتوى'), 'objectives' => __('الأهداف'), 'content' => __('محتوى الدرس')] as $key => $label) {
                    if (filled($data[$key] ?? null)) {
                        $html[] = '<h3>'.e($label).'</h3><p>'.nl2br(e(strip_tags((string) $data[$key]))).'</p>';
                    }
                }

                continue;
            }

            if ($type === 'warmup' && filled($data['description'] ?? null)) {
                $html[] = '<h3>'.e(__('تمهيد')).'</h3><p>'.nl2br(e(strip_tags((string) $data['description']))).'</p>';
            }
        }

        return count($html) > 1 ? implode("\n", $html) : '';
    }

    public static function generateActualContent(DailyLog $record): string
    {
        return static::generateActualContentFromData(
            title: (string) $record->title,
            contentData: $record->content_data,
        );
    }

    /**
     * Shared "Confirm Completion" action for page headers (e.g. the edit page).
     */
    public static function confirmCompletionAction(): Actions\Action
    {
        return static::configureConfirmCompletion(
            Actions\Action::make('confirmCompletion'),
        );
    }

    /**
     * Shared "Confirm Completion" action for table rows.
     */
    public static function confirmCompletionTableAction(): Tables\Actions\Action
    {
        return static::configureConfirmCompletion(
            Tables\Actions\Action::make('confirmCompletion'),
        );
    }

    /**
     * @return array<int, Textarea>
     */
    private static function administrativeVisaForm(): array
    {
        return [
            Textarea::make('admin_notes')
                ->label(__('ملاحظات الإدارة / التوجيه التربوي'))
                ->rows(4)
                ->maxLength(10000)
                ->nullable(),
        ];
    }

    private static function authorizeAdministrativeVisa(User $user, DailyLog $record): void
    {
        abort_unless($user->isAdmin(), 403);

        if (! $record->isCompleted()) {
            throw ValidationException::withMessages([
                'record' => __('لا يمكن تأشير سجل غير منجز.'),
            ]);
        }
    }

    public static function administrativeVisaTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('digitalVisa')
            ->label(__('تأشير الدفتر رقمياً'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->form(static::administrativeVisaForm())
            ->modalHeading(__('تأشير الدفتر رقمياً'))
            ->modalSubmitActionLabel(__('تأشير'))
            ->visible(fn (DailyLog $record): bool => Auth::user()?->isAdmin() && $record->isCompleted())
            ->action(function (DailyLog $record, array $data): void {
                $reviewer = Auth::user();
                abort_unless($reviewer instanceof User, 403);
                static::authorizeAdministrativeVisa($reviewer, $record);

                $record->markAsReviewed($reviewer, $data['admin_notes'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('تم التأشير الإداري'))
                    ->send();
            });
    }

    /**
     * Modal form schema for writing a ready-made student report during classroom management.
     *
     * @return array<int, mixed>
     */
    private static function studentReportModalForm(?DailyLog $record): array
    {
        $service = app(StudentIncidentReportService::class);
        $schedule = $record?->schedule;
        $classroomId = $schedule?->classroom_id ?? $schedule?->group?->classroom_id;

        $students = Student::query()
            ->when($classroomId, function ($q) use ($classroomId) {
                $q->whereHas('group', fn ($g) => $g->where('classroom_id', $classroomId));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Student $s) => [
                $s->id => "{$s->full_name} (".($s->group?->name ?? 'الفوج العام').')',
            ])
            ->all();

        return [
            Grid::make(2)->schema([
                Select::make('student_id')
                    ->label(__('التلميذ(ة) المعني(ة) بالتقرير'))
                    ->options($students)
                    ->searchable()
                    ->required(),

                Select::make('category')
                    ->label(__('طبيعة وفئة التقرير'))
                    ->options($service->getCategories())
                    ->default('disciplinary')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('template_facts', []);
                        $set('template_actions', []);
                        $set('template_recommendations', []);
                    }),
            ]),

            CheckboxList::make('template_facts')
                ->label(__('1. الوقائع والسلوكيات المرصودة (حدد ما ينطبق)'))
                ->options(fn (Get $get, StudentIncidentReportService $svc): array => $svc->getObservedFactsOptions($get('category') ?: 'disciplinary'))
                ->maxItems(3)
                ->helperText(__('حد أقصى: 3 خيارات — تتغير الخيارات تلقائياً بحسب فئة التقرير لمنع التعارض.'))
                ->columns(2),

            CheckboxList::make('template_actions')
                ->label(__('2. الإجراءات التربوية الأولية المتخذة من طرفك كأستاذ'))
                ->options(fn (Get $get, StudentIncidentReportService $svc): array => $svc->getPedagogicalActionsOptions($get('category') ?: 'disciplinary'))
                ->maxItems(2)
                ->helperText(__('حد أقصى: خياران'))
                ->columns(2),

            CheckboxList::make('template_recommendations')
                ->label(__('3. المطلب والمقترحات المرفوعة لإدارة المؤسسة'))
                ->options(fn (Get $get, StudentIncidentReportService $svc): array => $svc->getRecommendationsOptions($get('category') ?: 'disciplinary'))
                ->maxItems(2)
                ->helperText(__('حد أقصى: خياران'))
                ->columns(2),

            Textarea::make('custom_notes')
                ->label(__('ملاحظات وتفاصيل إضافية خاصة (اختياري)'))
                ->placeholder(__('أي تفاصيل إضافية خاصة بالواقعة تود إضافتها للتقرير...'))
                ->rows(2),
        ];
    }

    /**
     * Executes report creation from modal data.
     */
    private static function executeStudentReportCreation(DailyLog $record, array $data): void
    {
        $service = app(StudentIncidentReportService::class);
        $student = Student::with('group.classroom')->findOrFail($data['student_id']);
        $teacher = Auth::user();

        $assembled = $service->assembleReportText(
            student: $student,
            teacher: $teacher instanceof User ? $teacher : null,
            category: $data['category'] ?? 'disciplinary',
            facts: $data['template_facts'] ?? [],
            actions: $data['template_actions'] ?? [],
            recommendations: $data['template_recommendations'] ?? [],
            incidentDate: $record->date?->format('Y/m/d') ?? today()->format('Y/m/d'),
            subjectName: $record->schedule?->subject?->name,
            customNotes: $data['custom_notes'] ?? null,
        );

        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'schedule_id' => $record->schedule_id,
            'recorded_by_id' => $teacher?->id,
            'category' => $data['category'] ?? 'disciplinary',
            'notes' => $assembled,
            'date' => $record->date ?? today(),
            'is_followed_up' => false,
        ]);

        $printUrl = route('print.student-behavior-report', [
            'locale' => app()->getLocale(),
            'behaviorObservation' => $observation,
        ]);

        Notification::make()
            ->success()
            ->title(__('تم تسجيل تقرير التلميذ(ة) بنجاح وحفظه رسمياً'))
            ->body(__('تم دمج الخيارات وصياغة التقرير الإداري للتلميذ(ة): :name.', ['name' => $student->full_name]))
            ->actions([
                Action::make('print')
                    ->label(__('طباعة التقرير (A4)'))
                    ->url($printUrl)
                    ->openUrlInNewTab(),
            ])
            ->persistent()
            ->send();
    }

    /**
     * Table row action to write a student report for that session.
     */
    public static function studentReportTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reportStudent')
            ->label(__('تقرير عن تلميذ'))
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->tooltip(__('كتابة تقرير بيداغوجي / سلوكي عن تلميذ في هذه الحصة بنقرة زر'))
            ->modalHeading(fn (DailyLog $record): string => __('كتابة تقرير عن تلميذ — :classroom (:subject)', [
                'classroom' => $record->schedule?->classroom?->name ?? $record->schedule?->group?->classroom?->name ?? '—',
                'subject' => $record->schedule?->subject?->name ?? '—',
            ]))
            ->modalDescription(__('اختر التلميذ وحدد البنود المناسبة من النماذج الجاهزة ليتم دمجها في تقرير إداري وبيداغوجي شامل.'))
            ->modalSubmitActionLabel(__('⚡ دمج وتوليد التقرير وحفظه رسمياً'))
            ->modalWidth('4xl')
            ->form(fn (DailyLog $record): array => static::studentReportModalForm($record))
            ->action(fn (DailyLog $record, array $data) => static::executeStudentReportCreation($record, $data));
    }

    /**
     * Form action to write a student report directly inside the DailyLog execution tab.
     */
    public static function studentReportFormAction(): FormAction
    {
        return FormAction::make('reportStudentForm')
            ->label(__('📝 كتابة تقرير عن تلميذ في هذه الحصة'))
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->modalHeading(__('كتابة تقرير بيداغوجي / سلوكي عن تلميذ'))
            ->modalDescription(__('حدد التلميذ والخيارات المناسبة من النماذج الجاهزة ليتم دمجها في تقرير إداري شامل.'))
            ->modalSubmitActionLabel(__('⚡ دمج وتوليد التقرير وحفظه رسمياً'))
            ->modalWidth('4xl')
            ->visible(fn (?DailyLog $record): bool => $record !== null)
            ->form(fn (?DailyLog $record): array => static::studentReportModalForm($record))
            ->action(fn (DailyLog $record, array $data) => static::executeStudentReportCreation($record, $data));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            // Keep the teacher's selection when they search or filter after
            // ticking rows. Otherwise the checkboxes were cleared before the
            // bulk print/visa action could read them.
            ->deselectAllRecordsWhenFiltered(false)
            ->headerActions([
                // Filament's built-in "select all" link only appears after a
                // row is ticked and can be easy to miss. This exposes the same
                // action directly in the table toolbar, covering every record
                // that matches the current search and filters, across pages.
                Tables\Actions\Action::make('selectAllFiltered')
                    ->label(__('تحديد كل السجلات'))
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->tooltip(__('تحديد جميع السجلات المطابقة للبحث والفلاتر، بما في ذلك السجلات في الصفحات الأخرى.'))
                    ->alpineClickHandler('selectAllRecords'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('التاريخ'))
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('العنوان'))
                    ->searchable()
                    ->limit(35)
                    ->description(fn (DailyLog $record): ?string => $record->schedule?->label()),

                Tables\Columns\TextColumn::make('schedule.subject.name')
                    ->label(__('المادة'))
                    ->badge()
                    ->color(fn (DailyLog $record): string => $record->schedule?->subject?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('schedule_section')
                    ->label(__('القسم'))
                    ->getStateUsing(fn (DailyLog $record): ?string => $record->schedule?->sectionName())
                    ->description(fn (DailyLog $record): ?string => $record->schedule?->resolvedClassroom()
                        ? $record->schedule?->group?->name
                        : null)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('evaluation_work')
                    ->label(__('العمل التقويمي'))
                    ->getStateUsing(function (DailyLog $record): ?string {
                        $type = app(DailyLogEvaluationWorkSyncService::class)->detectEvaluationType($record);

                        return $type ? app(DailyLogEvaluationWorkSyncService::class)->getLabel($type) : null;
                    })
                    ->badge()
                    ->color('amber')
                    ->icon('heroicon-o-document-check')
                    ->tooltip(function (DailyLog $record): ?string {
                        $type = app(DailyLogEvaluationWorkSyncService::class)->detectEvaluationType($record);
                        if (! $type) {
                            return null;
                        }

                        return $record->isCompleted()
                            ? __('مؤشَّر تلقائياً في السجل التقويمي')
                            : __('سيتم التأشير في السجل التقويمي فور تأكيد الإنجاز');
                    })
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pedagogical_plan')
                    ->label(__('المخطط البيداغوجي'))
                    ->getStateUsing(function (DailyLog $record): ?string {
                        $planType = app(DailyLogPedagogicalPlanSyncService::class)->detectPlanType($record);

                        return $planType ? app(DailyLogPedagogicalPlanSyncService::class)->getLabel($planType) : null;
                    })
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-calendar-days')
                    ->tooltip(function (DailyLog $record): ?string {
                        $planType = app(DailyLogPedagogicalPlanSyncService::class)->detectPlanType($record);
                        if (! $planType) {
                            return null;
                        }

                        return $record->isCompleted()
                            ? __('مؤشَّر تلقائياً في سجل المخططات')
                            : __('سيتم التأشير في سجل المخططات فور تأكيد الإنجاز');
                    })
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('curriculum_resource')
                    ->label(__('المورد المعرفي'))
                    ->getStateUsing(fn (DailyLog $record): ?string => $record->curriculumResource?->label())
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (DailyLogStatus $state): string => $state->label())
                    ->color(fn (DailyLogStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('administrative_review')
                    ->label(__('التأشير الإداري'))
                    ->getStateUsing(fn (DailyLog $record): ?string => $record->isCompleted()
                        ? ($record->isReviewed() ? __('مؤشَّر إدارياً') : __('بانتظار التأشير'))
                        : null)
                    ->badge()
                    ->color(fn (DailyLog $record): string => $record->isReviewed() ? 'success' : 'gray')
                    ->tooltip(fn (DailyLog $record): ?string => $record->isReviewed()
                        ? __('تم التأشير من طرف: :name بتاريخ :date', [
                            'name' => $record->reviewer?->name ?? '—',
                            'date' => $record->reviewed_at?->format('Y-m-d'),
                        ])
                        : null)
                    ->sortable(query: fn (EloquentBuilder $query, string $direction): EloquentBuilder => $query
                        ->orderBy('reviewed_at', $direction)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('الحالة'))
                    ->options(DailyLogStatus::options()),

                Tables\Filters\SelectFilter::make('administrative_review')
                    ->label(__('التأشير الإداري'))
                    ->placeholder(__('الكل'))
                    ->options([
                        'reviewed' => __('مؤشَّر'),
                        'pending' => __('غير مؤشَّر'),
                    ])
                    ->query(fn (EloquentBuilder $query, array $data): EloquentBuilder => match ($data['value'] ?? null) {
                        'reviewed' => $query->whereNotNull('reviewed_at'),
                        'pending' => $query
                            ->where('status', DailyLogStatus::Completed->value)
                            ->whereNull('reviewed_at'),
                        default => $query,
                    }),

                Tables\Filters\Filter::make('today')
                    ->label(__('سجلات اليوم'))
                    ->query(fn (EloquentBuilder $query) => $query->forDate(today())),
            ])
            ->actions([
                static::confirmCompletionTableAction(),
                static::administrativeVisaTableAction(),
                static::studentReportTableAction(),
                Tables\Actions\Action::make('printRegister')
                    ->label(__('دفتر النصوص'))
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->tooltip(__('معاينة الحصة في دفتر نصوص هذا القسم'))
                    ->url(function (DailyLog $record): string {
                        $classroomId = $record->schedule?->classroom_id
                            ?? $record->schedule?->group?->classroom_id;

                        return route('print.register', array_filter([
                            'classroom_id' => $classroomId,
                            'ids' => [$record->id],
                        ]));
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (DailyLog $record): bool => $record->isCompleted()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printRegister')
                        ->label(__('معاينة / طباعة دفتر النصوص'))
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (EloquentCollection $records, Component $livewire): void {
                            $user = Auth::user();
                            abort_unless($user instanceof User, 403);

                            $register = app(DailyLogRegisterService::class);
                            $logs = $register->completedLogs($records->modelKeys(), $user);

                            if ($logs->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('لا توجد سجلات منجزة مطابقة للاختيار.'))
                                    ->send();

                                return;
                            }

                            $firstClassroomId = $logs->first()?->schedule?->classroom_id
                                ?? $logs->first()?->schedule?->group?->classroom_id;

                            $url = route('print.register', array_filter([
                                'classroom_id' => $firstClassroomId,
                                'ids' => $logs->modelKeys(),
                            ]));
                            $livewire->js("window.open('{$url}', '_blank')");
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('digitalVisa')
                        ->label(__('تأشير الدفتر رقمياً'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->form(static::administrativeVisaForm())
                        ->modalHeading(__('تأشير الدفتر رقمياً'))
                        ->modalSubmitActionLabel(__('تأشير'))
                        ->modalDescription(function (EloquentCollection $records): string {
                            $preview = $records
                                ->take(4)
                                ->map(fn (DailyLog $record): string => trim(
                                    ($record->date?->format('Y/m/d') ?? '').' — '.$record->title
                                ))
                                ->implode(' · ');

                            $summary = __('تم تحديد :count سجل.', ['count' => $records->count()]);

                            return trim($summary.' — '.$preview.($records->count() > 4 ? ' …' : ''));
                        })
                        ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false)
                        ->action(function (EloquentCollection $records, array $data): void {
                            $reviewer = Auth::user();
                            abort_unless($reviewer instanceof User && $reviewer->isAdmin(), 403);

                            // A "select all" can include planned or cancelled
                            // logs. Stamp only the completed ones instead of
                            // failing the whole batch on the first incomplete
                            // record, and report what was left out.
                            $completed = $records->filter(
                                fn (DailyLog $record): bool => $record->isCompleted()
                            );
                            $skipped = $records->count() - $completed->count();

                            if ($completed->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('لا توجد سجلات منجزة مطابقة للاختيار.'))
                                    ->body(__('تم تجاهل :count سجل غير منجز.', ['count' => $skipped]))
                                    ->send();

                                return;
                            }

                            foreach ($completed as $record) {
                                static::authorizeAdministrativeVisa($reviewer, $record);
                            }

                            DB::transaction(function () use ($completed, $reviewer, $data): void {
                                foreach ($completed as $record) {
                                    $record->markAsReviewed($reviewer, $data['admin_notes'] ?? null);
                                }
                            });

                            $notification = Notification::make()
                                ->success()
                                ->title(__('تم تأشير :count سجلات بنجاح.', ['count' => $completed->count()]));

                            if ($skipped > 0) {
                                $notification->body(__('تم تجاهل :count سجل غير منجز.', ['count' => $skipped]));
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('downloadRegisterPdf')
                        ->label(__('تحميل دفتر النصوص (PDF)'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->action(function (EloquentCollection $records, Tables\Actions\BulkAction $action): ?StreamedResponse {
                            $user = Auth::user();

                            abort_unless($user instanceof User, 403);

                            $register = app(DailyLogRegisterService::class);

                            $logs = $register->completedLogs($records->modelKeys(), $user);

                            if ($logs->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('لا توجد سجلات منجزة مطابقة للاختيار.'))
                                    ->send();

                                $action->cancel();

                                return null;
                            }

                            try {
                                return $register->pdfResponse($logs, $user);
                            } catch (Throwable $exception) {
                                report($exception);

                                Notification::make()
                                    ->danger()
                                    ->title(__('تعذر إنشاء ملف PDF.'))
                                    ->body(__('تأكد من تثبيت Node.js و Puppeteer و Chrome على الخادم.'))
                                    ->send();

                                return null;
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        // Selections survive filter changes, so list the rows
                        // that will actually be deleted instead of relying on
                        // the current (possibly narrower) view.
                        ->modalDescription(function (EloquentCollection $records): string {
                            $preview = $records
                                ->take(4)
                                ->map(fn (DailyLog $record): string => trim(
                                    ($record->date?->format('Y/m/d') ?? '').' — '.$record->title
                                ))
                                ->implode(' · ');

                            $summary = __('سيتم حذف :count سجل نهائياً.', ['count' => $records->count()]);

                            return trim($summary.' — '.$preview.($records->count() > 4 ? ' …' : ''));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyLogs::route('/'),
            'create' => Pages\CreateDailyLog::route('/create'),
            'edit' => Pages\EditDailyLog::route('/{record}/edit'),
        ];
    }
}
