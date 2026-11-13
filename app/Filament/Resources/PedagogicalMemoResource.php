<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Exceptions\AiLessonPlannerException;
use App\Filament\Resources\PedagogicalMemoResource\Pages;
use App\Models\ActiveLearningStrategy;
use App\Models\CurriculumResource;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiLessonPlannerService;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PedagogicalMemoResource extends Resource
{
    protected static ?string $model = PedagogicalMemo::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Keep the sidebar entry, page title, and breadcrumb in sync.
     *
     * The URL slug intentionally stays "pedagogical-memos" so localized panel
     * routes remain stable (/ar/auth/pedagogical-memos, ...).
     */
    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return __('مذكرة بيداغوجية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المذكرات البيداغوجية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج والتفتيش');
    }

    protected static ?int $navigationSort = 2;

    /**
     * Teachers see only their own memo bank; admins see every memo.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['subject', 'level.educationPhase', 'user']);

        return auth()->user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
    }

    /**
     * Build a locale-aware URL for the printable memo.
     */
    public static function getPrintUrl(PedagogicalMemo $memo): string
    {
        return route('print.pedagogical-memo', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);
    }

    /**
     * Build a locale-aware URL for the downloadable memo PDF.
     */
    public static function getPdfUrl(PedagogicalMemo $memo): string
    {
        return route('print.pedagogical-memo.pdf', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);
    }

    /**
     * Build a locale-aware URL for the pedagogical preparation booklet.
     */
    public static function getBookletPrintUrl(array $params = []): string
    {
        return route('print.pedagogical-memo.booklet', array_merge([
            'locale' => app()->getLocale(),
        ], $params));
    }

    /**
     * Build a locale-aware URL for the pedagogical preparation booklet PDF.
     */
    public static function getBookletPdfUrl(array $params = []): string
    {
        return route('print.pedagogical-memo.booklet.pdf', array_merge([
            'locale' => app()->getLocale(),
        ], $params));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات المذكرة'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('عنوان المذكرة'))
                            ->placeholder(__('مثال: مذكرة الأعداد الطبيعية'))
                            ->required()
                            ->maxLength(255)
                            ->suffixAction(static::generateAiContentFormAction())
                            ->columnSpanFull(),

                        Select::make('subject_id')
                            ->label(__('المادة'))
                            ->options(fn (Get $get): array => static::getTeacherSubjectOptions($get('user_id')))
                            ->default(fn (): ?int => auth()->user()?->primary_subject_id ?? array_key_first(static::getTeacherSubjectOptions()))
                            ->searchable()
                            ->live()
                            ->required()
                            ->helperText(fn (): ?string => auth()->user()?->isTeacher() ? __('تقتصر القائمة على مادتك الأساسية والثانوية فقط.') : null),

                        Select::make('education_phase_id')
                            ->label(__('الطور التعليمي'))
                            ->options(fn (): array => static::getEducationPhaseOptions())
                            ->default(fn ($record): ?int => $record?->level?->education_phase_id ?? static::getDefaultEducationPhaseId())
                            ->live()
                            ->searchable()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $set('level_id', static::getDefaultLevelId($state));
                            })
                            ->helperText(__('تحديد الطور لعرض المستويات المقابلة (متوسطة، ابتدائية، ثانوية).')),

                        Select::make('level_id')
                            ->label(__('المستوى'))
                            ->options(fn (Get $get): array => static::getLevelOptions($get('education_phase_id'), $get('user_id')))
                            ->default(fn (Get $get): ?int => static::getDefaultLevelId($get('education_phase_id')))
                            ->searchable()
                            ->live()
                            ->required(),

                        TextInput::make('phase')
                            ->label(__('المقطع التعلمي'))
                            ->placeholder(__('مثال: المقطع الأول'))
                            ->maxLength(255)
                            ->helperText(__('المرحلة أو المقطع ضمن البرنامج السنوي.')),

                        Toggle::make('is_template')
                            ->label(__('نموذج قابل لإعادة الاستخدام'))
                            ->helperText(__('يظهر في بنك المذكرات النموذجية لنسخه بسرعة.'))
                            ->default(false),

                        Select::make('user_id')
                            ->label(__('المعلم'))
                            ->options(fn (): array => User::query()
                                ->whereIn('role', [Role::Teacher->value, Role::HeadTeacher->value])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->isAdmin() ? null : Auth::id())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->dehydratedWhenHidden()
                            ->helperText(__('عند إنشاء المذكرة بصفتك مديراً، يمكنك إسنادها لأستاذ.')),
                    ]),

                Section::make(__('المحتوى البيداغوجي'))
                    ->description(__('يمكنك تحرير عناصر المذكرة يدوياً أو توليدها مباشرة بالذكاء الاصطناعي موظفاً استراتيجيات التعلم النشط.'))
                    ->headerActions([
                        static::generateAiContentFormAction(),
                    ])
                    ->columns(2)
                    ->schema([
                        Textarea::make('competence')
                            ->label(__('الكفاءة المستهدفة'))
                            ->placeholder(__('مثال: يحلّ التلميذ وضعيات مشكلة باستعمال الموارد المكتسبة.'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('starting_situation')
                            ->label(__('الوضعية الانطلاقية'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Repeater::make('activities')
                            ->label(__('أنشطة الدرس'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('اسم النشاط'))
                                    ->required(),

                                TextInput::make('duration')
                                    ->label(__('المدة'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(600)
                                    ->suffix(__('دقيقة')),

                                TextInput::make('strategy')
                                    ->label(__('استراتيجية التدريس'))
                                    ->placeholder(__('حوار، عمل في أفواج، عمل فردي...')),

                                Textarea::make('content')
                                    ->label(__('محتوى النشاط'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('إضافة نشاط'))
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                                ? (string) $state['name']
                                : null)
                            ->columnSpanFull(),

                        Textarea::make('resources')
                            ->label(__('الموارد المعرفية'))
                            ->rows(3),

                        Textarea::make('evaluation')
                            ->label(__('التقويم'))
                            ->rows(3),

                        Textarea::make('notes')
                            ->label(__('ملاحظات المذكرة'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('عنوان المذكرة'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PedagogicalMemo $record): ?string => filled($record->competence)
                        ? Str::limit($record->competence, 70)
                        : null),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('المادة'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('level.name')
                    ->label(__('المستوى'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phase')
                    ->label(__('المقطع التعلمي'))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activity_count')
                    ->label(__('عدد الأنشطة'))
                    ->getStateUsing(fn (PedagogicalMemo $record): int => $record->activityCount())
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('is_template')
                    ->label(__('النماذج'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('نموذج') : __('عادية'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('is_archived')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('مؤرشفة') : __('نشطة'))
                    ->color(fn (bool $state): string => $state ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('آخر تحديث'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('المادة'))
                    ->options(fn (): array => Subject::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('level_id')
                    ->label(__('المستوى'))
                    ->options(fn (): array => Level::query()
                        ->with('educationPhase')
                        ->get()
                        ->mapWithKeys(fn (Level $level): array => [
                            $level->id => $level->display_name(),
                        ])
                        ->all())
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_template')
                    ->label(__('النماذج'))
                    ->trueLabel(__('النماذج فقط'))
                    ->falseLabel(__('المذكرات العادية'))
                    ->placeholder(__('الكل')),

                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label(__('الحالة'))
                    ->trueLabel(__('المؤرشفة'))
                    ->falseLabel(__('النشطة'))
                    ->placeholder(__('الكل')),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label(__('طباعة المذكرة'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (PedagogicalMemo $record): string => static::getPrintUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('print', $record) ?? false),

                Tables\Actions\Action::make('downloadPdf')
                    ->label(__('تحميل PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (PedagogicalMemo $record): string => static::getPdfUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('downloadPdf', $record) ?? false),

                static::duplicateTableAction(),

                Tables\Actions\Action::make('archive')
                    ->label(__('أرشفة'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('أرشفة المذكرة'))
                    ->modalDescription(__('ستبقى المذكرة محفوظة ويمكن استعادتها لاحقاً.'))
                    ->modalSubmitActionLabel(__('أرشفة'))
                    ->visible(fn (PedagogicalMemo $record): bool => ! $record->is_archived
                        && (auth()->user()?->can('archive', $record) ?? false))
                    ->action(function (PedagogicalMemo $record): void {
                        $record->archive();

                        Notification::make()
                            ->success()
                            ->title(__('تمت أرشفة المذكرة'))
                            ->send();
                    }),

                Tables\Actions\Action::make('restore')
                    ->label(__('استعادة'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (PedagogicalMemo $record): bool => $record->is_archived
                        && (auth()->user()?->can('archive', $record) ?? false))
                    ->action(function (PedagogicalMemo $record): void {
                        $record->restore();

                        Notification::make()
                            ->success()
                            ->title(__('تمت استعادة المذكرة'))
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    static::printBookletBulkAction(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                static::printBookletHeaderAction(),
                static::generateWithAiTableAction(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateIcon('heroicon-o-document-duplicate')
            ->emptyStateHeading(__('لا توجد مذكرات بعد'))
            ->emptyStateDescription(__('أنشئ أول مذكرة بيداغوجية واجعلها نموذجاً قابلاً لإعادة الاستخدام.'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    /**
     * Duplicate a memo into the current user's bank.
     */
    public static function duplicateTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('duplicate')
            ->label(__('نسخ المذكرة'))
            ->icon('heroicon-o-document-duplicate')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__('نسخ المذكرة'))
            ->modalDescription(__('سيتم إنشاء نسخة جديدة من المذكرة في بنكك، ويمكنك تعديلها قبل الطباعة.'))
            ->modalSubmitActionLabel(__('نسخ'))
            ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
            ->action(function (PedagogicalMemo $record): void {
                $user = Auth::user();

                abort_unless($user instanceof User, 403);

                $copy = $record->duplicateFor($user);

                Notification::make()
                    ->success()
                    ->title(__('تم نسخ المذكرة'))
                    ->body($copy->title)
                    ->actions([
                        NotificationAction::make('edit')
                            ->label(__('فتح النسخة'))
                            ->url(static::getUrl('edit', ['record' => $copy])),
                    ])
                    ->send();
            });
    }

    /**
     * Header action to open or print the pedagogical preparation booklet.
     */
    public static function printBookletHeaderAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('print_booklet')
            ->label(__('دفتر التحضير البيداغوجي (Booklet)'))
            ->icon('heroicon-o-book-open')
            ->color('success')
            ->form([
                Select::make('subject_id')
                    ->label(__('المادة'))
                    ->options(fn () => static::getTeacherSubjectOptions(auth()->user()))
                    ->default(fn () => auth()->user()?->primarySubject?->id ?? array_key_first(static::getTeacherSubjectOptions(auth()->user())))
                    ->required(),
                Select::make('level_id')
                    ->label(__('المستوى التعليمي'))
                    ->options(fn () => static::getLevelOptions(null, auth()->user()))
                    ->placeholder(__('جميع المستويات (كراس تحضير شامل)')),
            ])
            ->modalHeading(__('دفتر التحضير البيداغوجي المجمع'))
            ->modalDescription(__('طباعة كراس مذكرات الدروس كاملاً متضمناً صفحة الغلاف الرسمية وفهرس متابعة إرساء الموارد والمذكرات البيداغوجية المتتالية.'))
            ->modalSubmitActionLabel(__('عرض وطباعة الكراس'))
            ->action(function (array $data, Tables\Actions\Action $action): void {
                $url = static::getBookletPrintUrl(array_filter([
                    'subject' => $data['subject_id'] ?? null,
                    'level' => $data['level_id'] ?? null,
                ]));

                $action->getLivewire()->js("window.open('{$url}', '_blank')");
            });
    }

    /**
     * Bulk action to print a customized booklet for selected memos.
     */
    public static function printBookletBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('print_booklet_bulk')
            ->label(__('تجميع وطباعة كراس مذكرات (Booklet)'))
            ->icon('heroicon-o-book-open')
            ->color('success')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, Tables\Actions\BulkAction $action): void {
                $memoIds = $records->pluck('id')->join(',');
                $url = static::getBookletPrintUrl([
                    'memos' => $memoIds,
                ]);

                $action->getLivewire()->js("window.open('{$url}', '_blank')");
            });
    }

    /**
     * Retrieve subject options filtered to teacher's primary & secondary subjects.
     *
     * @return array<int, string>
     */
    public static function getTeacherSubjectOptions(User|int|null $user = null): array
    {
        if (is_int($user)) {
            $user = User::find($user);
        }

        $user = $user ?? auth()->user();

        if (! $user) {
            return Subject::query()->orderBy('name')->pluck('name', 'id')->all();
        }

        if ($user->isTeacher()) {
            $subjectIds = array_filter([
                $user->primary_subject_id,
                $user->secondary_subject_id,
            ]);

            $scheduledSubjectIds = $user->schedules()->pluck('subject_id')->filter()->all();
            $subjectIds = array_unique(array_merge($subjectIds, $scheduledSubjectIds));

            if (! empty($subjectIds)) {
                $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
                $options = [];
                foreach ($subjects as $s) {
                    $badge = '';
                    if ($s->id === $user->primary_subject_id) {
                        $badge = ' ('.__('المادة الأساسية').')';
                    } elseif ($s->id === $user->secondary_subject_id) {
                        $badge = ' ('.__('المادة الثانوية').')';
                    }
                    $options[$s->id] = $s->name.$badge;
                }

                return $options;
            }

            return Subject::query()->orderBy('name')->pluck('name', 'id')->all();
        }

        return Subject::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Retrieve education phases available for middle, primary, and secondary school.
     *
     * @return array<int, string>
     */
    public static function getEducationPhaseOptions(): array
    {
        $phases = EducationPhase::query()
            ->whereIn('code', ['PRI', 'CEM', 'LYC'])
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();

        if (! empty($phases)) {
            return $phases;
        }

        return EducationPhase::query()->orderBy('id')->pluck('name', 'id')->all();
    }

    /**
     * Resolve default education phase according to the teacher or institution.
     */
    public static function getDefaultEducationPhaseId(User|int|null $user = null): ?int
    {
        if (is_int($user)) {
            $user = User::find($user);
        }
        $user = $user ?? auth()->user();

        if ($user) {
            $userPhaseId = Level::whereHas('classrooms.schedules', fn ($q) => $q->where('user_id', $user->id))
                ->value('education_phase_id');
            if ($userPhaseId) {
                return (int) $userPhaseId;
            }
        }

        $setting = SchoolSetting::current();
        if ($setting->school_name) {
            if (str_contains($setting->school_name, 'ابتدائ')) {
                $p = EducationPhase::where('code', 'PRI')->orWhere('name', 'like', '%ابتدائ%')->first();
                if ($p) {
                    return $p->id;
                }
            }
            if (str_contains($setting->school_name, 'ثانوي')) {
                $p = EducationPhase::where('code', 'LYC')->orWhere('name', 'like', '%ثانوي%')->first();
                if ($p) {
                    return $p->id;
                }
            }
        }

        $cem = EducationPhase::where('code', 'CEM')->orWhere('name', 'like', '%متوسط%')->first();

        return $cem?->id ?? EducationPhase::query()->first()?->id;
    }

    /**
     * Retrieve levels filtered by selected education phase.
     *
     * @return array<int, string>
     */
    public static function getLevelOptions(?int $phaseId = null, User|int|null $user = null): array
    {
        if (is_int($user)) {
            $user = User::find($user);
        }
        $user = $user ?? auth()->user();

        $query = Level::query()->with('educationPhase');

        if ($phaseId) {
            $query->where('education_phase_id', $phaseId);
        } else {
            $defaultPhaseId = static::getDefaultEducationPhaseId($user);
            if ($defaultPhaseId) {
                $query->where('education_phase_id', $defaultPhaseId);
            }
        }

        $levels = $query->orderBy('grade_number')->orderBy('name')->get();

        if ($levels->isEmpty()) {
            $levels = Level::query()->with('educationPhase')->orderBy('grade_number')->get();
        }

        return $levels->mapWithKeys(fn (Level $l): array => [
            $l->id => $l->display_name(),
        ])->all();
    }

    /**
     * Resolve default level for selected phase and teacher.
     */
    public static function getDefaultLevelId(?int $phaseId = null, User|int|null $user = null): ?int
    {
        if (is_int($user)) {
            $user = User::find($user);
        }
        $user = $user ?? auth()->user();

        if ($user) {
            $userLevelId = Level::whereHas('classrooms.schedules', fn ($q) => $q->where('user_id', $user->id))
                ->when($phaseId, fn ($q) => $q->where('education_phase_id', $phaseId))
                ->value('id');
            if ($userLevelId) {
                return (int) $userLevelId;
            }
        }

        $targetPhaseId = $phaseId ?: static::getDefaultEducationPhaseId($user);

        return Level::where('education_phase_id', $targetPhaseId)->orderBy('grade_number')->value('id')
            ?? Level::query()->value('id');
    }

    /**
     * Action to generate a new pedagogical memo with AI directly from the memo bank.
     */
    public static function generateWithAiTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generateWithAi')
            ->label(__('إنجاز مذكرة بالذكاء الاصطناعي'))
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->modalHeading(__('إعداد مذكرة درس جديدة بالذكاء الاصطناعي'))
            ->modalDescription(__('توليد مذكرة بيداغوجية جزائرية رسمية وفق المقاربة بالكفاءات واستراتيجيات التعلم النشط وحفظها مباشرة في بنك المذكرات.'))
            ->modalSubmitActionLabel(__('توليد وحفظ المذكرة'))
            ->form([
                Select::make('subject_id')
                    ->label(__('المادة'))
                    ->options(fn (): array => static::getTeacherSubjectOptions())
                    ->default(fn (): ?int => auth()->user()?->primary_subject_id ?? array_key_first(static::getTeacherSubjectOptions()))
                    ->searchable()
                    ->live()
                    ->required()
                    ->helperText(fn (): ?string => auth()->user()?->isTeacher() ? __('تقتصر القائمة على مادتك الأساسية والثانوية المسندة إليك.') : null),

                Select::make('education_phase_id')
                    ->label(__('الطور التعليمي'))
                    ->options(fn (): array => static::getEducationPhaseOptions())
                    ->default(fn (): ?int => static::getDefaultEducationPhaseId())
                    ->live()
                    ->searchable()
                    ->dehydrated(false)
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('level_id', static::getDefaultLevelId($state));
                        $set('curriculum_resource_id', null);
                    })
                    ->helperText(__('يمكنك التبديل بين أطوار التعليم (المتوسطة، الابتدائية، الثانوية) لعرض مستويات كل طور.')),

                Select::make('level_id')
                    ->label(__('المستوى'))
                    ->options(fn (Get $get): array => static::getLevelOptions($get('education_phase_id')))
                    ->default(fn (Get $get): ?int => static::getDefaultLevelId($get('education_phase_id')))
                    ->searchable()
                    ->live()
                    ->required(),

                Select::make('curriculum_resource_id')
                    ->label(__('الدرس / المورد المعرفي من المنهاج الرسمي'))
                    ->options(function (Get $get): array {
                        $subjectId = $get('subject_id');
                        $levelId = $get('level_id');
                        if (! $subjectId || ! $levelId) {
                            return [];
                        }

                        return CurriculumResource::query()
                            ->whereHas('sequence.domain', fn ($q) => $q->where('subject_id', $subjectId)->where('level_id', $levelId))
                            ->with('sequence.domain')
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn (CurriculumResource $r): array => [
                                $r->id => $r->label(),
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if ($state) {
                            $resource = CurriculumResource::with('sequence')->find($state);
                            if ($resource) {
                                $set('title', $resource->name);
                                $set('phase', $resource->sequence?->name ?? '');
                            }
                        }
                    })
                    ->helperText(__('يمكنك اختيار درس من المنهاج لملء العنوان والمقطع تلقائياً، أو كتابة عنوان مخصص أدناه.')),

                TextInput::make('title')
                    ->label(__('عنوان الدرس / المذكرة'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('phase')
                    ->label(__('المقطع التعلمي'))
                    ->maxLength(255),

                Select::make('preferred_strategy')
                    ->label(__('استراتيجية التعلم النشط المفضلة'))
                    ->options(fn (): array => ActiveLearningStrategy::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'name')->all())
                    ->searchable()
                    ->placeholder(__('اختيار تلقائي من الذكاء الاصطناعي وفق طبيعة الدرس')),

                Textarea::make('custom_instructions')
                    ->label(__('توجيهات أو إضافات خاصة بالأستاذ (اختياري)'))
                    ->rows(2),
            ])
            ->action(function (array $data, AiLessonPlannerService $planner): void {
                $subject = Subject::find($data['subject_id']);
                $level = Level::find($data['level_id']);

                if (! $subject || ! $level) {
                    return;
                }

                try {
                    $plan = $planner->generatePedagogicalMemo(
                        topic: $data['title'],
                        subject: $subject->name,
                        level: $level->display_name(),
                        phase: filled($data['phase'] ?? null) ? $data['phase'] : null,
                        locale: app()->getLocale(),
                        preferredStrategy: filled($data['preferred_strategy'] ?? null) ? (string) $data['preferred_strategy'] : null,
                        customInstructions: filled($data['custom_instructions'] ?? null) ? (string) $data['custom_instructions'] : null,
                    );

                    $memo = PedagogicalMemo::create([
                        'user_id' => Auth::id(),
                        'subject_id' => $data['subject_id'],
                        'level_id' => $data['level_id'],
                        'title' => $data['title'],
                        'phase' => $data['phase'] ?? null,
                        'competence' => $plan['competence'],
                        'starting_situation' => $plan['starting_situation'],
                        'activities' => $plan['activities'],
                        'resources' => $plan['resources'],
                        'evaluation' => $plan['evaluation'],
                        'notes' => $plan['notes'],
                        'is_template' => false,
                        'is_archived' => false,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('تم توليد وحفظ المذكرة البيداغوجية بنجاح'))
                        ->body(__('تم إنشاء المذكرة: «:title» وحفظها في بنك المذكرات.', ['title' => $memo->title]))
                        ->actions([
                            NotificationAction::make('print')
                                ->label(__('معاينة وطباعة المذكرة'))
                                ->icon('heroicon-o-printer')
                                ->color('primary')
                                ->url(static::getPrintUrl($memo))
                                ->openUrlInNewTab(),
                            NotificationAction::make('edit')
                                ->label(__('تعديل المذكرة'))
                                ->icon('heroicon-o-pencil-square')
                                ->color('gray')
                                ->url(static::getUrl('edit', ['record' => $memo->id])),
                        ])
                        ->persistent()
                        ->send();
                } catch (AiLessonPlannerException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('تعذر توليد المذكرة بالذكاء الاصطناعي'))
                        ->body($e->getMessage())
                        ->send();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->danger()
                        ->title(__('حدث خطأ غير متوقع'))
                        ->body(__('تعذر إتمام عملية التوليد. حاول مرة أخرى لاحقاً.'))
                        ->send();
                }
            });
    }

    /**
     * Form action to populate the pedagogical memo fields with AI on the create/edit form.
     */
    public static function generateAiContentFormAction(): FormAction
    {
        return FormAction::make('generateAiContent')
            ->label(__('توليد بالذكاء الاصطناعي'))
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->tooltip(__('توليد الكفاءة والوضعية الانطلاقية والأنشطة والتقويم بالذكاء الاصطناعي.'))
            ->modalHeading(__('توليد المحتوى البيداغوجي بالذكاء الاصطناعي'))
            ->modalDescription(__('سيقوم المساعد الذكي بتحليل عنوان المذكرة والمادة والمستوى لتوليد الكفاءة المستهدفة، الوضعية الانطلاقية، سير الأنشطة، الموارد، والتقويم وتعبئتها في النموذج.'))
            ->modalSubmitActionLabel(__('توليد المحتوى'))
            ->form([
                Select::make('preferred_strategy')
                    ->label(__('استراتيجية التعلم النشط المفضلة'))
                    ->options(fn (): array => ActiveLearningStrategy::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'name')->all())
                    ->searchable()
                    ->placeholder(__('اختيار تلقائي من الذكاء الاصطناعي وفق طبيعة الدرس')),

                Textarea::make('custom_instructions')
                    ->label(__('توجيهات أو إضافات خاصة بالأستاذ (اختياري)'))
                    ->placeholder(__('مثال: التركيز على العمل الفوجي، إدماج الوسائل الرقمية، مراعاة الفروق الفردية...'))
                    ->rows(2),
            ])
            ->action(function (array $data, Get $get, Set $set, AiLessonPlannerService $planner): void {
                $title = trim((string) $get('title'));
                $subjectId = $get('subject_id');
                $levelId = $get('level_id');
                $phase = $get('phase');

                if ($title === '' || blank($subjectId) || blank($levelId)) {
                    Notification::make()
                        ->warning()
                        ->title(__('بيانات ناقصة'))
                        ->body(__('يرجى إدخال عنوان المذكرة وتحديد المادة والمستوى أولاً قبل التوليد.'))
                        ->send();

                    return;
                }

                $subject = Subject::find($subjectId);
                $level = Level::find($levelId);

                try {
                    $plan = $planner->generatePedagogicalMemo(
                        topic: $title,
                        subject: $subject?->name ?? '',
                        level: $level?->display_name() ?? '',
                        phase: filled($phase) ? (string) $phase : null,
                        locale: app()->getLocale(),
                        preferredStrategy: filled($data['preferred_strategy'] ?? null) ? (string) $data['preferred_strategy'] : null,
                        customInstructions: filled($data['custom_instructions'] ?? null) ? (string) $data['custom_instructions'] : null,
                    );

                    $set('competence', $plan['competence']);
                    $set('starting_situation', $plan['starting_situation']);
                    $set('activities', $plan['activities']);
                    $set('resources', $plan['resources']);
                    $set('evaluation', $plan['evaluation']);
                    $set('notes', $plan['notes']);

                    Notification::make()
                        ->success()
                        ->title(__('تم توليد المحتوى البيداغوجي بنجاح'))
                        ->body(__('تمت تعبئة عناصر المذكرة في النموذج. يمكنك مراجعتها وتعديلها قبل الحفظ.'))
                        ->send();
                } catch (AiLessonPlannerException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('تعذر توليد المحتوى بالذكاء الاصطناعي'))
                        ->body($e->getMessage())
                        ->send();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->danger()
                        ->title(__('حدث خطأ غير متوقع'))
                        ->body(__('تعذر إتمام عملية التوليد. حاول مرة أخرى لاحقاً.'))
                        ->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedagogicalMemos::route('/'),
            'create' => Pages\CreatePedagogicalMemo::route('/create'),
            'edit' => Pages\EditPedagogicalMemo::route('/{record}/edit'),
        ];
    }
}
