<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BehaviorObservationResource\Pages;
use App\Models\BehaviorObservation;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\StudentIncidentReportService;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BehaviorObservationResource extends Resource
{
    protected static ?string $model = BehaviorObservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('تقارير السلوك وتسيير القسم');
    }

    public static function getModelLabel(): string
    {
        return __('تقرير سلوكي / متابعة');
    }

    public static function getPluralModelLabel(): string
    {
        return __('تقارير السلوك وتسيير القسم');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('المتابعة البيداغوجية والإرشاد');
    }

    public static function form(Form $form): Form
    {
        $reportService = app(StudentIncidentReportService::class);

        return $form->schema([
            Section::make(__('بيانات التقرير والتلميذ المعني'))
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('student_id')
                            ->label(__('التلميذ المعني بالتقرير'))
                            ->options(function () {
                                $user = auth()->user();

                                return Student::query()
                                    ->with(['group.classroom'])
                                    ->when($user?->isTeacher() && ! $user->isAdmin(), function ($query) use ($user) {
                                        $query->whereHas('group.classroom.schedules', fn ($q) => $q->where('user_id', $user->id));
                                    })
                                    ->orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $s) => [
                                        $s->id => "{$s->full_name} (".($s->group?->classroom?->name ?? '—').')',
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('category')
                            ->label(__('طبيعة وفئة التقرير'))
                            ->options($reportService->getCategories())
                            ->default('disciplinary')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('template_facts', []);
                                $set('template_actions', []);
                                $set('template_recommendations', []);
                            }),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('schedule_id')
                            ->label(__('الحصة التعليمية (اختياري)'))
                            ->options(fn (): array => Schedule::query()
                                ->when(auth()->user()?->isTeacher(), fn ($query) => $query->forTeacher(auth()->id()))
                                ->with(['user', 'subject', 'classroom', 'group.classroom'])
                                ->get()
                                ->mapWithKeys(fn (Schedule $schedule): array => [$schedule->id => $schedule->label()])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->rules(fn (Get $get): array => [
                                function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (blank($value) || blank($get('student_id'))) {
                                        return;
                                    }

                                    $student = Student::query()->with('group')->find($get('student_id'));
                                    $schedule = Schedule::query()->find($value);

                                    if (
                                        ! $student
                                        || ! $schedule
                                        || (auth()->user()?->isTeacher() && $schedule->user_id !== auth()->id())
                                        || (
                                            $schedule->group_id !== $student->group_id
                                            && ! (
                                                $schedule->group_id === null
                                                && $schedule->classroom_id === $student->group?->classroom_id
                                            )
                                        )
                                    ) {
                                        $fail(__('الحصة المحددة لا تخص قسم التلميذ.'));
                                    }
                                },
                            ]),

                        DatePicker::make('date')
                            ->label(__('تاريخ الواقعة / التقرير'))
                            ->default(today())
                            ->required(),
                    ]),
                ]),

            Section::make(__('مُولّد نماذج التقارير الجاهزة بنقرة زر واحدة'))
                ->description(__('حدد الخيارات المناسبة من النماذج الجاهزة أدناه، ثم اضغط على زر الدمج ليتم تجميعها وصياغتها تلقائياً في تقرير إداري وبيداغوجي رصين.'))
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->schema([
                    CheckboxList::make('template_facts')
                        ->label(__('1. الوقائع والسلوكيات المرصودة (حدد ما ينطبق)'))
                        ->options(fn (Get $get, StudentIncidentReportService $service): array => $service->getObservedFactsOptions($get('category') ?: 'disciplinary'))
                        ->maxItems(3)
                        ->helperText(__('حد أقصى: 3 خيارات — تتغير الخيارات تلقائياً بحسب فئة التقرير لمنع التعارض.'))
                        ->columns(2),

                    CheckboxList::make('template_actions')
                        ->label(__('2. الإجراءات التربوية الأولية المتخذة من طرفك كأستاذ'))
                        ->options(fn (Get $get, StudentIncidentReportService $service): array => $service->getPedagogicalActionsOptions($get('category') ?: 'disciplinary'))
                        ->maxItems(2)
                        ->helperText(__('حد أقصى: خياران'))
                        ->columns(2),

                    CheckboxList::make('template_recommendations')
                        ->label(__('3. المطلب والمقترحات المرفوعة لإدارة المؤسسة'))
                        ->options(fn (Get $get, StudentIncidentReportService $service): array => $service->getRecommendationsOptions($get('category') ?: 'disciplinary'))
                        ->maxItems(2)
                        ->helperText(__('حد أقصى: خياران'))
                        ->columns(2),
                ])
                ->headerActions([
                    FormAction::make('generateReportText')
                        ->label(__('⚡ دمج الخيارات وتوليد التقرير الشامل بنقرة واحدة'))
                        ->icon('heroicon-o-bolt')
                        ->color('primary')
                        ->action(function (Get $get, Set $set, StudentIncidentReportService $service) {
                            $studentId = $get('student_id');
                            $student = $studentId ? Student::with('group.classroom')->find($studentId) : null;
                            $teacher = auth()->user();
                            $category = $get('category') ?: 'disciplinary';
                            $facts = $get('template_facts') ?: [];
                            $actions = $get('template_actions') ?: [];
                            $recommendations = $get('template_recommendations') ?: [];
                            $date = $get('date') ? Carbon::parse($get('date'))->format('Y/m/d') : today()->format('Y/m/d');

                            $schedule = $get('schedule_id') ? Schedule::with('subject')->find($get('schedule_id')) : null;
                            $subjectName = $schedule?->subject?->name ?? $teacher?->primarySubject?->name;

                            if (empty($facts) && empty($actions) && empty($recommendations)) {
                                Notification::make()
                                    ->warning()
                                    ->title(__('يرجى تحديد خيار واحد على الأقل من النماذج الجاهزة قبل التوليد.'))
                                    ->send();

                                return;
                            }

                            $assembled = $service->assembleReportText(
                                student: $student,
                                teacher: $teacher,
                                category: $category,
                                facts: $facts,
                                actions: $actions,
                                recommendations: $recommendations,
                                incidentDate: $date,
                                subjectName: $subjectName,
                            );

                            $set('notes', $assembled);

                            Notification::make()
                                ->success()
                                ->title(__('تم دمج وتوليد نص التقرير الشامل بنجاح!'))
                                ->body(__('يمكنك الآن مراجعة النص في الأسفل أو إجراء أي تعديلات إضافية.'))
                                ->send();
                        }),
                ]),

            Section::make(__('نص التقرير النهائي والمتابعة الإدارية'))
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Textarea::make('notes')
                        ->label(__('نص التقرير النهائي (قابل للتعديل والتحرير الكامل)'))
                        ->helperText(__('يتم ملء هذا الحقل تلقائياً عند الضغط على زر التوليد أعلاه، ويمكنك كتابته أو تعديله بحرية.'))
                        ->placeholder(__('انقر على زر "دمج الخيارات وتوليد التقرير الشامل" أعلاه أو اكتب نص التقرير هنا...'))
                        ->rows(9)
                        ->required(),

                    Grid::make(2)->schema([
                        Toggle::make('is_followed_up')
                            ->label(__('تمت المتابعة الإدارية للتقرير'))
                            ->default(false),

                        Textarea::make('follow_up_notes')
                            ->label(__('ملاحظات وقرار المتابعة الإدارية / البيداغوجية'))
                            ->placeholder(__('قرار مستشار التربية أو الإدارة بخصوص الحالة...'))
                            ->rows(2),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label(__('التلميذ'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('student.group.classroom.name')
                    ->label(__('القسم'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('category')
                    ->label(__('طبيعة التقرير'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'positive' => __('تميز وسلوك إيجابي'),
                        'negative' => __('إهمال بيداغوجي'),
                        'disciplinary' => __('مخالفة انضباطية'),
                        default => __('ملاحظة ومتابعة'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'positive' => 'success',
                        'negative' => 'warning',
                        'disciplinary' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('recorder.name')
                    ->label(__('الأستاذ(ة) المحيل(ة)'))
                    ->toggleable(),

                TextColumn::make('date')
                    ->label(__('التاريخ'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('is_followed_up')
                    ->label(__('المتابعة'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('تمت المعالجة') : __('قيد المتابعة'))
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label(__('طبيعة التقرير'))
                    ->options([
                        'disciplinary' => __('مخالفة انضباطية'),
                        'negative' => __('إهمال بيداغوجي'),
                        'observation' => __('ملاحظة ومتابعة'),
                        'positive' => __('تميز وسلوك إيجابي'),
                    ]),
                SelectFilter::make('is_followed_up')
                    ->label(__('حالة المتابعة'))
                    ->options([
                        '1' => __('تمت المعالجة'),
                        '0' => __('قيد المتابعة'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('printReport')
                    ->label(__('طباعة التقرير (A4)'))
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (BehaviorObservation $record) => route('print.student-behavior-report', [
                        'locale' => app()->getLocale(),
                        'behaviorObservation' => $record,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadPdf')
                    ->label(__('تحميل PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (BehaviorObservation $record) => route('print.student-behavior-report.pdf', [
                        'locale' => app()->getLocale(),
                        'behaviorObservation' => $record,
                    ])),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBehaviorObservations::route('/'),
            'create' => Pages\CreateBehaviorObservation::route('/create'),
            'edit' => Pages\EditBehaviorObservation::route('/{record}/edit'),
        ];
    }
}
