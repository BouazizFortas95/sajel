<?php

namespace App\Filament\Resources;

use App\Enums\DailyLogStatus;
use App\Enums\LessonPhaseType;
use App\Filament\Resources\DailyLogResource\Pages;
use App\Models\DailyLog;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        return __('التشغيل اليومي');
    }

    protected static ?int $navigationSort = 70;

    /**
     * Teachers see only their logs; admins see everything
     * (record-level abilities are enforced by DailyLogPolicy).
     */
    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = parent::getEloquentQuery();

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
                                                    ->with(['subject', 'classroom.level', 'group', 'user']);

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
                                                if (blank($get('title')) && filled($state)) {
                                                    $schedule = Schedule::query()->with('subject')->find($state);

                                                    if ($schedule) {
                                                        $set('title', __('درس: ').($schedule->subject?->name ?? ''));
                                                    }
                                                }
                                            })
                                            ->rule(fn (Get $get, ?DailyLog $record): Rule => Rule::unique(DailyLog::class, 'schedule_id')
                                                ->where(fn (EloquentBuilder $query) => $query->whereDate('date', $get('date')))
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
                                            ->columnSpanFull(),

                                        Select::make('status')
                                            ->label(__('الحالة'))
                                            ->options(DailyLogStatus::options())
                                            ->default(DailyLogStatus::Planned->value)
                                            ->disabledOn('edit')
                                            ->required(),
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
                                    ->schema([
                                        RichEditor::make('actual_content')
                                            ->label(__('المحتوى المنجز فعلياً'))
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get): bool => $get('status') === DailyLogStatus::Completed->value)
                                            ->required(fn (Get $get): bool => $get('status') === DailyLogStatus::Completed->value)
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
            ]);
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
                        ->itemLabel(fn (array $state): ?string => $state['phase_name'] ?? null),
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

                            TextInput::make('strategy')
                                ->label(__('استراتيجية التدريس'))
                                ->placeholder(__('حوار، عمل في أفواج، عرض توضيحي...'))
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
            ->visible(fn (DailyLog $record): bool => $record->isPlanned())
            ->action(function (DailyLog $record): void {
                $record->markAsCompleted(static::generateActualContent($record));

                Notification::make()
                    ->title(__('تم تأكيد الإنجاز'))
                    ->body(__('أصبح هذا السجل هو المحتوى الرسمي لهذه الحصة.'))
                    ->success()
                    ->send();
            });
    }

    protected static function generateActualContent(DailyLog $record): string
    {
        $html = ['<h2>'.e($record->title).'</h2>'];

        foreach ($record->content_data ?? [] as $block) {
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

                    $html[] = '<h4>'.e($name).'</h4>';

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

        return implode("\n", $html);
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
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

                Tables\Columns\TextColumn::make('schedule.classroom.name')
                    ->label(__('القسم'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (DailyLogStatus $state): string => $state->label())
                    ->color(fn (DailyLogStatus $state): string => $state->color())
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('الحالة'))
                    ->options(DailyLogStatus::options()),

                Tables\Filters\Filter::make('today')
                    ->label(__('سجلات اليوم'))
                    ->query(fn (EloquentBuilder $query) => $query->forDate(today())),
            ])
            ->actions([
                static::confirmCompletionTableAction(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
