<?php

namespace App\Filament\Resources;

use App\Enums\InfractionDegree;
use App\Filament\Pages\DisciplinaryCouncilHub;
use App\Filament\Resources\BehaviorObservationResource\Pages;
use App\Models\BehaviorObservation;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\DisciplinaryCouncilService;
use App\Services\StudentIncidentReportService;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('بيانات التلميذ والحصة الدراسية'))
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('student_id')
                            ->label(__('التلميذ(ة)'))
                            ->options(function () {
                                $user = auth()->user();
                                $query = Student::query()->with('group.classroom');

                                if ($user && $user->isTeacher() && ! $user->isAdmin()) {
                                    $classrooms = Schedule::query()
                                        ->where('user_id', $user->id)
                                        ->whereNotNull('classroom_id')
                                        ->pluck('classroom_id');

                                    $query->whereHas('group', fn ($q) => $q->whereIn('classroom_id', $classrooms));
                                }

                                return $query->orderBy('first_name')->get()->mapWithKeys(fn (Student $s): array => [
                                    $s->id => sprintf('%s (%s)', $s->full_name, $s->group?->classroom?->name ?? '—'),
                                ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('category')
                            ->label(__('فئة وطبيعة التقرير'))
                            ->options(fn (StudentIncidentReportService $service): array => $service->getCategories())
                            ->default('disciplinary')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('template_facts', []);
                                $set('template_actions', []);
                                $set('template_recommendations', []);
                            })
                            ->required(),
                    ]),

                    Grid::make(3)->schema([
                        DatePicker::make('date')
                            ->label(__('تاريخ الواقعة / الحصة'))
                            ->default(today())
                            ->required(),

                        Select::make('schedule_id')
                            ->label(__('الحصة الدراسية المرتبطة'))
                            ->options(function (Get $get) {
                                $user = auth()->user();
                                $query = Schedule::query()->with(['subject', 'classroom', 'group']);

                                if ($user && $user->isTeacher() && ! $user->isAdmin()) {
                                    $query->where('user_id', $user->id);
                                }

                                return $query->get()->mapWithKeys(fn (Schedule $sch): array => [
                                    $sch->id => sprintf(
                                        '%s — %s (%s)',
                                        $sch->subject?->name ?? '—',
                                        $sch->classroom?->name ?? $sch->group?->name ?? '—',
                                        $sch->day_of_week?->label() ?? '—'
                                    ),
                                ]);
                            })
                            ->searchable()
                            ->placeholder(__('اختياري — ربط بحصة من جدول التوقيت')),

                        Select::make('recorded_by_id')
                            ->label(__('الأستاذ(ة) المحيل(ة)'))
                            ->relationship('recorder', 'name')
                            ->default(auth()->id())
                            ->disabled(fn () => ! auth()->user()?->isAdmin())
                            ->dehydrated(),
                    ]),
                ]),

            Section::make(__('نماذج التقارير المعيارية السريعة (توليد بنقرة زر)'))
                ->description(__('حدد البنود التي تنطبق على الحالة من كل قائمة، ثم اضغط على زر التوليد في الأعلى لدمجها تلقائياً في تقرير إداري شامل.'))
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
                    Placeholder::make('disciplinary_record_info')
                        ->label(__('حالة الإحالة إلى مجلس التأديب'))
                        ->content(function (?BehaviorObservation $record): ?string {
                            if (! $record?->disciplinaryRecord) {
                                return null;
                            }
                            $disc = $record->disciplinaryRecord;

                            return sprintf(
                                '⚖️ تم فتح ملف بمجلس التأديب (جلسة رقم: %s بتاريخ: %s) — الحالة: %s',
                                $disc->session_number ?? '—',
                                $disc->session_date ? $disc->session_date->format('Y/m/d H:i') : '—',
                                $disc->status?->label() ?? 'مجدولة'
                            );
                        })
                        ->visible(fn (?BehaviorObservation $record): bool => $record?->hasDisciplinaryRecord() ?? false),

                    Placeholder::make('guardian_summons_info')
                        ->label(__('حالة استدعاء ولي الأمر'))
                        ->content(function (?BehaviorObservation $record): ?string {
                            if (! $record?->hasGuardianSummons()) {
                                return null;
                            }

                            return sprintf(
                                '✉️ تم إصدار استدعاء رسمي لولي الأمر بتاريخ %s لمقابلة الإدارة يوم: %s',
                                $record->guardian_summons_sent_at?->format('Y/m/d') ?? '—',
                                $record->guardian_summons_appointment_at?->format('Y/m/d H:i') ?? '—'
                            );
                        })
                        ->visible(fn (?BehaviorObservation $record): bool => $record?->hasGuardianSummons() ?? false),

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

            Section::make(__('متابعة استدعاء واستقبال ولي الأمر'))
                ->icon('heroicon-o-user-group')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('appointment_info')
                            ->label(__('موعد الاستقبال المحدد'))
                            ->content(fn (?BehaviorObservation $record): string => $record?->guardian_summons_appointment_at ? $record->guardian_summons_appointment_at->format('Y/m/d H:i') : '—'),

                        Toggle::make('is_guardian_receipt_returned')
                            ->label(__('تم استرجاع وصل الاستدعاء موقعاً')),

                        Select::make('guardian_attendance_status')
                            ->label(__('حالة حضور ولي الأمر'))
                            ->options([
                                'pending' => __('⏳ قيد الانتظار'),
                                'attended' => __('✅ حضر الولي'),
                                'absent' => __('❌ لم يحضر'),
                                'excused' => __('⚠️ معذور / تأجيل'),
                            ])
                            ->default('pending'),
                    ]),

                    Textarea::make('guardian_attendance_notes')
                        ->label(__('مخرجات المقابلة والتعهدات المشتركة'))
                        ->placeholder(__('تدوين التزامات وتعهدات ولي الأمر وما تم الاتفاق عليه خلال المقابلة...'))
                        ->rows(2),
                ])
                ->visible(fn (?BehaviorObservation $record): bool => $record?->administrative_action === 'guardian_summons')
                ->collapsible(),
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

                SelectColumn::make('administrative_action')
                    ->label(__('الإجراء الإداري'))
                    ->options([
                        'pending' => __('⏳ قيد المتابعة'),
                        'internal_followup' => __('✅ معالجة داخلية بيداغوجية'),
                        'guardian_summons' => __('✉️ استدعاء ولي الأمر'),
                        'disciplinary_council' => __('⚖️ إحالة لمجلس التأديب'),
                    ])
                    ->selectablePlaceholder(false)
                    ->rules(['required', 'in:pending,internal_followup,guardian_summons,disciplinary_council'])
                    ->disabled(fn (BehaviorObservation $record): bool => auth()->user() && auth()->user()->isTeacher() && ! auth()->user()->isAdmin() && $record->recorded_by_id !== auth()->id())
                    ->updateStateUsing(function (BehaviorObservation $record, string $state, DisciplinaryCouncilService $councilService): string {
                        $result = $record->applyAdministrativeAction($state, auth()->user(), $councilService);

                        $notification = Notification::make()
                            ->title($result['title'])
                            ->body($result['body']);

                        if ($state === 'disciplinary_council') {
                            $notification->danger();
                        } elseif ($state === 'guardian_summons') {
                            $notification->warning();
                        } elseif ($state === 'internal_followup') {
                            $notification->success();
                        } else {
                            $notification->info();
                        }

                        $actions = [];
                        if (! empty($result['print_url'])) {
                            $actions[] = NotificationAction::make('print')
                                ->label($state === 'disciplinary_council' ? __('طباعة استدعاء المجلس (A4)') : __('طباعة الاستدعاء (A4)'))
                                ->url($result['print_url'])
                                ->openUrlInNewTab();
                        }
                        if (! empty($result['pdf_url'])) {
                            $actions[] = NotificationAction::make('pdf')
                                ->label(__('تحميل PDF'))
                                ->url($result['pdf_url']);
                        }
                        if ($state === 'disciplinary_council') {
                            $actions[] = NotificationAction::make('hub')
                                ->label(__('مركز مجلس التأديب'))
                                ->url(DisciplinaryCouncilHub::getUrl());
                        }

                        if (! empty($actions)) {
                            $notification->actions($actions)->persistent();
                        }

                        $notification->send();

                        return $state;
                    })
                    ->width('215px'),

                ToggleColumn::make('is_guardian_receipt_returned')
                    ->label(__('وصل الاستلام'))
                    ->disabled(fn (BehaviorObservation $record): bool => $record->administrative_action !== 'guardian_summons')
                    ->updateStateUsing(function (BehaviorObservation $record, bool $state): bool {
                        $record->markReceiptReturned($state);

                        Notification::make()
                            ->title($state ? __('تم استرجاع الوصل موقعاً') : __('تم إلغاء استرجاع الوصل'))
                            ->body($state
                                ? __('تم تسجيل استرجاع وصل استدعاء ولي الأمر موقعاً.')
                                : __('تم إعادة ضبط حالة وصل الاستدعاء إلى قيد الانتظار.'))
                            ->color($state ? 'success' : 'gray')
                            ->send();

                        return $state;
                    })
                    ->tooltip(fn (BehaviorObservation $record): string => $record->administrative_action !== 'guardian_summons'
                        ? __('خاص باستدعاء ولي الأمر')
                        : ($record->is_guardian_receipt_returned ? __('تم استرجاع الوصل موقعاً من الولي') : __('في انتظار استرجاع الوصل'))),

                SelectColumn::make('guardian_attendance_status')
                    ->label(__('حضور المقابلة'))
                    ->options([
                        'pending' => __('⏳ قيد الانتظار'),
                        'attended' => __('✅ حضر الولي'),
                        'absent' => __('❌ لم يحضر'),
                        'excused' => __('⚠️ معذور / تأجيل'),
                    ])
                    ->selectablePlaceholder(false)
                    ->disabled(fn (BehaviorObservation $record): bool => $record->administrative_action !== 'guardian_summons')
                    ->updateStateUsing(function (BehaviorObservation $record, string $state): string {
                        $record->updateGuardianAttendance($state);

                        $notification = Notification::make();

                        if ($state === 'attended') {
                            $notification->title(__('✅ تم تسجيل حضور ولي الأمر'))
                                ->body(__('تمت مقابلة ولي الأمر واعتماد المتابعة البيداغوجية بنجاح.'))
                                ->success();
                        } elseif ($state === 'absent') {
                            $notification->title(__('❌ غياب ولي الأمر عن المقابلة'))
                                ->body(__('تم تسجيل تخلف ولي الأمر عن الحضور في الموعد المحدد.'))
                                ->danger();
                        } elseif ($state === 'excused') {
                            $notification->title(__('⚠️ تم تسجيل عذر ولي الأمر'))
                                ->body(__('تم تدوين عذر الولي وطلب إعادة جدولة المقابلة.'))
                                ->warning();
                        } else {
                            $notification->title(__('⏳ الموعد قيد الانتظار'))
                                ->info();
                        }

                        $notification->send();

                        return $state;
                    })
                    ->width('170px'),
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
                Filter::make('disciplinary_referred')
                    ->label(__('المحالين لمجلس التأديب'))
                    ->query(fn (Builder $query) => $query->whereNotNull('disciplinary_record_id')),
                Filter::make('guardian_summoned')
                    ->label(__('استدعاءات الأولياء الصادرة'))
                    ->query(fn (Builder $query) => $query->whereNotNull('guardian_summons_sent_at')),
                SelectFilter::make('guardian_attendance_status')
                    ->label(__('حضور ولي الأمر'))
                    ->options([
                        'attended' => __('حضر الولي'),
                        'absent' => __('لم يحضر (غائب)'),
                        'excused' => __('معذور / تأجيل'),
                        'pending' => __('قيد الانتظار'),
                    ]),
                SelectFilter::make('is_guardian_receipt_returned')
                    ->label(__('وصل الاستدعاء'))
                    ->options([
                        '1' => __('تم استرجاع الوصل'),
                        '0' => __('في الانتظار (لم يسترجع)'),
                    ]),
            ])
            ->actions([
                // Quick Action 1: Refer to Disciplinary Council
                Tables\Actions\Action::make('referToDisciplinary')
                    ->label(__('إحالة لمجلس التأديب'))
                    ->icon('heroicon-o-scale')
                    ->color('danger')
                    ->visible(fn (BehaviorObservation $record): bool => ! $record->hasDisciplinaryRecord() && in_array($record->category, ['disciplinary', 'negative', 'observation']))
                    ->modalHeading(fn (BehaviorObservation $record): string => __('⚖️ إحالة التلميذ(ة): :name إلى مجلس التأديب', ['name' => $record->student?->full_name ?? '—']))
                    ->modalDescription(__('سيتم فتح ملف رسمي ضمن سجلات مجلس التأديب وتحديد موعد الجلسة مع إمكانية إصدار استدعاء الولي فوراً.'))
                    ->modalSubmitActionLabel(__('تأكيد الإحالة وجدولة جلسة المجلس'))
                    ->modalWidth('2xl')
                    ->form([
                        Select::make('infraction_degree')
                            ->label(__('درجة المخالفة المنسوبة'))
                            ->options([
                                InfractionDegree::FirstDegree->value => InfractionDegree::FirstDegree->label(),
                                InfractionDegree::SecondDegree->value => InfractionDegree::SecondDegree->label(),
                                InfractionDegree::ThirdDegree->value => InfractionDegree::ThirdDegree->label(),
                            ])
                            ->default(InfractionDegree::SecondDegree->value)
                            ->required(),

                        DateTimePicker::make('session_date')
                            ->label(__('تاريخ وموعد انعقاد جلسة المجلس'))
                            ->default(now()->addDays(3)->setTime(10, 0))
                            ->required(),

                        Textarea::make('incident_description')
                            ->label(__('موضوع الإحالة والوقائع المنسوبة للتلميذ'))
                            ->default(fn (BehaviorObservation $record): string => $record->notes)
                            ->rows(4)
                            ->required(),

                        Toggle::make('guardian_summoned')
                            ->label(__('إصدار استدعاء رسمي فوري لولي الأمر لحضور الجلسة'))
                            ->default(true),
                    ])
                    ->action(function (BehaviorObservation $record, array $data, DisciplinaryCouncilService $councilService): void {
                        $student = $record->student;
                        if (! $student) {
                            return;
                        }

                        $discRecord = $councilService->scheduleSession(
                            student: $student,
                            data: [
                                'session_date' => $data['session_date'],
                                'infraction_degree' => $data['infraction_degree'],
                                'incident_description' => $data['incident_description'],
                                'incident_date' => $record->date?->toDateString() ?? now()->toDateString(),
                                'reported_by' => $record->recorder ?? auth()->user(),
                                'guardian_summoned' => $data['guardian_summoned'] ?? true,
                            ]
                        );

                        $followUpNotice = __('تمت الإحالة إلى مجلس التأديب (جلسة رقم :session بتاريخ :date).', [
                            'session' => $discRecord->session_number,
                            'date' => $discRecord->session_date ? $discRecord->session_date->format('Y/m/d H:i') : '—',
                        ]);

                        $record->update([
                            'disciplinary_record_id' => $discRecord->id,
                            'is_followed_up' => true,
                            'follow_up_notes' => trim(($record->follow_up_notes ? $record->follow_up_notes."\n" : '').$followUpNotice),
                        ]);

                        $summonsPrintUrl = route('print.disciplinary.summons', [
                            'locale' => app()->getLocale(),
                            'record' => $discRecord->id,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('تمت إحالة التلميذ(ة) بنجاح إلى مجلس التأديب'))
                            ->body(__('تم فتح ملف تأديبي برقم :session وتحديد موعد الجلسة.', ['session' => $discRecord->session_number]))
                            ->actions([
                                NotificationAction::make('printSummons')
                                    ->label(__('طباعة استدعاء المجلس (A4)'))
                                    ->url($summonsPrintUrl)
                                    ->openUrlInNewTab(),
                                NotificationAction::make('openHub')
                                    ->label(__('مركز مجلس التأديب'))
                                    ->url(DisciplinaryCouncilHub::getUrl()),
                            ])
                            ->persistent()
                            ->send();
                    }),

                // Quick Action 2: Guardian Summons (استدعاء ولي الأمر لمقابلة الإدارة والأستاذ)
                Tables\Actions\Action::make('guardianSummons')
                    ->label(__('استدعاء ولي الأمر'))
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (BehaviorObservation $record): bool => in_array($record->category, ['disciplinary', 'negative', 'observation']))
                    ->modalHeading(fn (BehaviorObservation $record): string => __('✉️ إصدار استدعاء رسمي لولي أمر التلميذ(ة): :name', ['name' => $record->student?->full_name ?? '—']))
                    ->modalDescription(__('تحديد موعد المقابلة ومكان الاستقبال لتوليد وثيقة استدعاء رسمية ومطبوعة لولي الأمر.'))
                    ->modalSubmitActionLabel(__('تأكيد الموعد وإصدار الاستدعاء'))
                    ->modalWidth('2xl')
                    ->form([
                        DateTimePicker::make('guardian_summons_appointment_at')
                            ->label(__('الموعد والتوقيت المحدد للمقابلة بالمؤسسة'))
                            ->default(fn (BehaviorObservation $record): Carbon => $record->guardian_summons_appointment_at ?? now()->addDay()->setTime(10, 0))
                            ->required(),

                        Textarea::make('guardian_summons_notes')
                            ->label(__('موضوع الاستدعاء والملاحظات الموجهة لولي الأمر'))
                            ->default(fn (BehaviorObservation $record): string => $record->guardian_summons_notes ?: $record->notes)
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (BehaviorObservation $record, array $data): void {
                        $record->update([
                            'guardian_summons_sent_at' => now(),
                            'guardian_summons_appointment_at' => $data['guardian_summons_appointment_at'],
                            'guardian_summons_notes' => $data['guardian_summons_notes'],
                            'is_followed_up' => true,
                        ]);

                        $summonsPrintUrl = route('print.student-behavior-report.guardian-summons', [
                            'locale' => app()->getLocale(),
                            'behaviorObservation' => $record,
                        ]);

                        $summonsPdfUrl = route('print.student-behavior-report.guardian-summons.pdf', [
                            'locale' => app()->getLocale(),
                            'behaviorObservation' => $record,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('تم تسجيل وإصدار استدعاء ولي الأمر بنجاح'))
                            ->body(__('تم تحديد موعد المقابلة يوم :date وحفظ الاستدعاء الرسمي.', [
                                'date' => Carbon::parse($data['guardian_summons_appointment_at'])->format('Y/m/d H:i'),
                            ]))
                            ->actions([
                                NotificationAction::make('printGuardianSummons')
                                    ->label(__('طباعة الاستدعاء (A4)'))
                                    ->url($summonsPrintUrl)
                                    ->openUrlInNewTab(),
                                NotificationAction::make('downloadPdf')
                                    ->label(__('تحميل PDF'))
                                    ->url($summonsPdfUrl),
                            ])
                            ->persistent()
                            ->send();
                    }),

                // Record Interview Outcome & Receipt (مخرجات مقابلة الولي والتعهد)
                Tables\Actions\Action::make('recordInterviewOutcome')
                    ->label(__('مخرجات المقابلة والتعهد'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn (BehaviorObservation $record): bool => $record->administrative_action === 'guardian_summons' || $record->hasGuardianSummons())
                    ->modalHeading(fn (BehaviorObservation $record): string => __('تسجيل مخرجات مقابلة ولي أمر التلميذ(ة): :name', ['name' => $record->student?->full_name ?? '—']))
                    ->modalDescription(__('توثيق استلام الوصل المقتطع، حضور ولي الأمر في الموعد المحدد، وما تم الاتفاق عليه من تعهدات وإجراءات.'))
                    ->modalWidth('xl')
                    ->form([
                        Toggle::make('is_guardian_receipt_returned')
                            ->label(__('تم استرجاع وصل الاستدعاء موقعاً ومقتطعاً من ولي الأمر'))
                            ->default(fn (BehaviorObservation $record): bool => (bool) $record->is_guardian_receipt_returned),

                        Select::make('guardian_attendance_status')
                            ->label(__('حالة حضور ولي الأمر للمقابلة'))
                            ->options([
                                'attended' => __('✅ حضر الولي وأجريت المقابلة'),
                                'absent' => __('❌ لم يحضر (تخلف عن الحضور)'),
                                'excused' => __('⚠️ معذور / طلب تأجيل الموعد'),
                                'pending' => __('⏳ قيد الانتظار'),
                            ])
                            ->default(fn (BehaviorObservation $record): string => $record->guardian_attendance_status ?: 'attended')
                            ->required(),

                        Textarea::make('guardian_attendance_notes')
                            ->label(__('مخرجات المقابلة والتعهدات المتفق عليها'))
                            ->placeholder(__('تدوين ما تم الاتفاق عليه مع ولي الأمر، تعهداته بالانضباط، أو الإجراءات البيداغوجية المتخذة...'))
                            ->default(fn (BehaviorObservation $record): ?string => $record->guardian_attendance_notes)
                            ->rows(4),
                    ])
                    ->action(function (BehaviorObservation $record, array $data): void {
                        $record->markReceiptReturned((bool) ($data['is_guardian_receipt_returned'] ?? false));
                        $record->updateGuardianAttendance(
                            $data['guardian_attendance_status'] ?? 'pending',
                            $data['guardian_attendance_notes'] ?? null
                        );

                        $notification = Notification::make()
                            ->title(__('تم حفظ مخرجات المقابلة وحالة الاستدعاء بنجاح'))
                            ->success();

                        if ($record->guardian_attendance_status === 'attended') {
                            $notification->actions([
                                NotificationAction::make('printMinutes')
                                    ->label(__('🖨️ طباعة محضر المقابلة (A4)'))
                                    ->url(route('print.student-behavior-report.interview-minutes', [
                                        'locale' => app()->getLocale(),
                                        'behaviorObservation' => $record,
                                    ]))
                                    ->openUrlInNewTab(),
                            ])->persistent();
                        }

                        $notification->send();
                    }),

                // Print Interview Minutes (محضر المقابلة والتعهد)
                Tables\Actions\Action::make('printInterviewMinutes')
                    ->label(__('محضر المقابلة (A4)'))
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (BehaviorObservation $record): bool => $record->hasGuardianSummons() && $record->guardian_attendance_status === 'attended')
                    ->url(fn (BehaviorObservation $record): string => route('print.student-behavior-report.interview-minutes', [
                        'locale' => app()->getLocale(),
                        'behaviorObservation' => $record,
                    ]))
                    ->openUrlInNewTab(),

                // Print Disciplinary Summons if already referred
                Tables\Actions\Action::make('printDisciplinarySummons')
                    ->label(__('استدعاء مجلس التأديب (A4)'))
                    ->icon('heroicon-o-scale')
                    ->color('danger')
                    ->visible(fn (BehaviorObservation $record): bool => $record->hasDisciplinaryRecord())
                    ->url(fn (BehaviorObservation $record): string => route('print.disciplinary.summons', [
                        'locale' => app()->getLocale(),
                        'record' => $record->disciplinary_record_id,
                    ]))
                    ->openUrlInNewTab(),

                // Print Guardian Summons if issued
                Tables\Actions\Action::make('printGuardianSummons')
                    ->label(__('استدعاء الولي (A4)'))
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->visible(fn (BehaviorObservation $record): bool => $record->hasGuardianSummons())
                    ->url(fn (BehaviorObservation $record): string => route('print.student-behavior-report.guardian-summons', [
                        'locale' => app()->getLocale(),
                        'behaviorObservation' => $record,
                    ]))
                    ->openUrlInNewTab(),

                // Print Behavior Report
                Tables\Actions\Action::make('printReport')
                    ->label(__('طباعة التقرير (A4)'))
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (BehaviorObservation $record): string => route('print.student-behavior-report', [
                        'locale' => app()->getLocale(),
                        'behaviorObservation' => $record,
                    ]))
                    ->openUrlInNewTab(),

                // Download Behavior Report PDF
                Tables\Actions\Action::make('downloadPdf')
                    ->label(__('تحميل PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (BehaviorObservation $record): string => route('print.student-behavior-report.pdf', [
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
