<?php

namespace App\Filament\Pages;

use App\Enums\DisciplinarySanction;
use App\Enums\InfractionDegree;
use App\Models\DisciplinaryRecord;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\DisciplinaryCouncilService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class DisciplinaryCouncilHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.disciplinary-council-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'hearings'; // 'hearings', 'sanctions', 'analytics'

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    #[Url(as: 'degree')]
    public ?string $selectedDegree = null;

    #[Url(as: 'status')]
    public ?string $selectedStatus = null;

    #[Url(as: 'q')]
    public string $searchQuery = '';

    public ?int $deliberatingRecordId = null;

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->canManageDiscipline() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->canManageDiscipline() ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('مجلس التأديب والمتابعة السلوكية');
    }

    public function getTitle(): string
    {
        return __('منظومة مجلس التأديب ومتابعة العقوبات والتقويم السلوكي');
    }

    public function getHeaderActions(): array
    {
        $actions = [];

        if (Auth::user()?->isAdmin()) {
            $actions[] = Action::make('scheduleSession')
                ->label(__('جدولة جلسة تأديب جديدة'))
                ->icon('heroicon-o-calendar-days')
                ->color('danger')
                ->modalHeading(__('جدولة جلسة مجلس التأديب وإحالة تلميذ'))
                ->modalSubmitActionLabel(__('تأكيد الجدولة وإصدار الاستدعاء'))
                ->form([
                    Grid::make(2)->schema([
                        Select::make('student_id')
                            ->label(__('التلميذ المحال'))
                            ->options(function () {
                                return Student::orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $s): array => [
                                        $s->id => "{$s->full_name} (".($s->group?->classroom?->name ?? '—').')',
                                    ]);
                            })
                            ->searchable()
                            ->required(),

                        Select::make('degree')
                            ->label(__('درجة المخالفة التنظيمية'))
                            ->options(collect(InfractionDegree::cases())->mapWithKeys(fn ($d) => [$d->value => $d->getLabel()]))
                            ->default(InfractionDegree::SecondDegree->value)
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        DateTimePicker::make('hearing_date')
                            ->label(__('تاريخ وتوقيت انعقاد الجلسة'))
                            ->default(now()->addDays(3)->setTime(14, 0))
                            ->required(),

                        DatePicker::make('incident_date')
                            ->label(__('تاريخ وقوع الحادثة / المخالفة'))
                            ->default(now()->toDateString())
                            ->required(),
                    ]),

                    Textarea::make('incident_description')
                        ->label(__('تفاصيل ووقائع المخالفة المنسوبة للتلميذ'))
                        ->placeholder(__('وصف دقيق للأفعال، الوقائع، والشهود الحاضرين...'))
                        ->rows(3)
                        ->required(),

                    Grid::make(2)->schema([
                        TextInput::make('session_number')
                            ->label(__('رقم المحضر / الجلسة'))
                            ->placeholder(__('مثال: CD-2026-01 (يترك فارغاً للتوليد التلقائي)')),

                        Toggle::make('guardian_summoned')
                            ->label(__('تم إرسال استدعاء الوليّ رسمياً'))
                            ->default(false),
                    ]),
                ])
                ->action(function (array $data, DisciplinaryCouncilService $service) {
                    $record = $service->scheduleSession(
                        student: Student::findOrFail($data['student_id']),
                        hearingDate: $data['hearing_date'],
                        degree: InfractionDegree::from($data['degree']),
                        incidentDescription: $data['incident_description'],
                        incidentDate: $data['incident_date'] ?? null,
                        reportedBy: Auth::user(),
                        sessionNumber: ! empty($data['session_number']) ? $data['session_number'] : null,
                        guardianSummoned: (bool) ($data['guardian_summoned'] ?? false),
                        academicYear: $this->academicYear,
                    );

                    Notification::make()
                        ->title(__('تمت جدولة جلسة مجلس التأديب'))
                        ->body(__('تم فتح الملف التأديبي رقم: :num وتجهيز الاستدعاءات الرسمية.', ['num' => $record->session_number]))
                        ->success()
                        ->send();
                });

            $actions[] = Action::make('printLedger')
                ->label(__('سجل مجالس التأديب السنوي'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => route('print.disciplinary.ledger', [
                    'locale' => app()->getLocale(),
                    'academic_year' => $this->academicYear,
                ]))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function openDeliberationModal(int $recordId): void
    {
        $this->deliberatingRecordId = $recordId;
        $this->mountAction('recordDeliberation');
    }

    public function recordDeliberationAction(): Action
    {
        return Action::make('recordDeliberation')
            ->label(__('تسجيل المداولة وإصدار القرار'))
            ->modalHeading(__('المداولة السرية وإصدار مقرر مجلس التأديب'))
            ->modalSubmitActionLabel(__('المصادقة على القرار وتبليغ الإشعار'))
            ->color('danger')
            ->form([
                Select::make('record_id')
                    ->label(__('الملف التأديبي'))
                    ->options(function () {
                        return DisciplinaryRecord::where('academic_year', $this->academicYear)
                            ->with('student')
                            ->get()
                            ->mapWithKeys(fn ($r) => [$r->id => "#{$r->id} - {$r->student?->full_name} ({$r->status->getLabel()})"]);
                    })
                    ->default(fn () => $this->deliberatingRecordId)
                    ->required(),

                Select::make('sanction')
                    ->label(__('العقوبة المقررة قانوناً'))
                    ->options(collect(DisciplinarySanction::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()]))
                    ->required()
                    ->live(),

                Grid::make(3)->schema([
                    TextInput::make('exclusion_days')
                        ->label(__('مدة الإقصاء المؤقت (بالأيام 1-8)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(8)
                        ->visible(fn ($get) => in_array($get('sanction'), [DisciplinarySanction::TemporaryExclusion->value])),

                    DatePicker::make('exclusion_start_date')
                        ->label(__('تاريخ بداية الإقصاء'))
                        ->visible(fn ($get) => in_array($get('sanction'), [DisciplinarySanction::TemporaryExclusion->value])),

                    DatePicker::make('exclusion_end_date')
                        ->label(__('تاريخ نهاية الإقصاء'))
                        ->visible(fn ($get) => in_array($get('sanction'), [DisciplinarySanction::TemporaryExclusion->value])),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('restitution_amount')
                        ->label(__('مبلغ التعويض عن الإتلاف المادي (د.ج)'))
                        ->numeric()
                        ->prefix('DZD')
                        ->placeholder('0.00'),

                    DatePicker::make('deliberated_at')
                        ->label(__('تاريخ المداولة الرسمية'))
                        ->default(now()->toDateString())
                        ->required(),
                ]),

                Textarea::make('defense_summary')
                    ->label(__('ملخص أقوال ودفاع التلميذ أو ممثله'))
                    ->placeholder(__('تسجيل ما صرح به التلميذ، أوجه الندم، أو الدفوع المقدمة...'))
                    ->rows(2),

                Textarea::make('decision_notes')
                    ->label(__('حيثيات القرار وتوصيات المجلس التربوية'))
                    ->placeholder(__('توجيهات المرافقة السلوكية، التكليف بالمهام المدرسية، أو شروط الاستئناف...'))
                    ->rows(2),
            ])
            ->action(function (array $data, DisciplinaryCouncilService $service) {
                $record = DisciplinaryRecord::findOrFail($data['record_id']);

                $service->recordDeliberation(
                    record: $record,
                    sanction: DisciplinarySanction::from($data['sanction']),
                    exclusionDays: ! empty($data['exclusion_days']) ? (int) $data['exclusion_days'] : null,
                    exclusionStartDate: $data['exclusion_start_date'] ?? null,
                    exclusionEndDate: $data['exclusion_end_date'] ?? null,
                    restitutionAmount: ! empty($data['restitution_amount']) ? (float) $data['restitution_amount'] : null,
                    defenseSummary: $data['defense_summary'] ?? null,
                    decisionNotes: $data['decision_notes'] ?? null,
                    president: Auth::user(),
                    deliberatedAt: $data['deliberated_at'] ?? null,
                );

                $this->deliberatingRecordId = null;

                Notification::make()
                    ->title(__('تم اعتماد قرار مجلس التأديب'))
                    ->body(__('تم حفظ المحضر وتجهيز الإشعار الرسمي بقرار العقوبة وحق الطعن.'))
                    ->success()
                    ->send();
            });
    }

    public function toggleSummoned(int $recordId, DisciplinaryCouncilService $service): void
    {
        $record = DisciplinaryRecord::findOrFail($recordId);
        $record->guardian_summoned = ! $record->guardian_summoned;
        $record->guardian_summoned_at = $record->guardian_summoned ? now() : null;
        $record->save();

        Notification::make()
            ->title($record->guardian_summoned ? __('تم تأكيد استدعاء الوليّ') : __('تم إلغاء تأشيرة الاستدعاء'))
            ->success()
            ->send();
    }

    public function toggleNotified(int $recordId, DisciplinaryCouncilService $service): void
    {
        $record = DisciplinaryRecord::findOrFail($recordId);
        $record->guardian_notified = ! $record->guardian_notified;
        $record->guardian_notified_at = $record->guardian_notified ? now() : null;
        $record->save();

        Notification::make()
            ->title($record->guardian_notified ? __('تم تأكيد إشعار الوليّ بالقرار') : __('تم إلغاء تأشيرة الإشعار'))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, DisciplinaryRecord>
     */
    public function getRecordsProperty(): Collection
    {
        $service = app(DisciplinaryCouncilService::class);
        $year = $service->resolveAcademicYear($this->academicYear);
        $clean = str_replace('/', '-', $year);

        $query = DisciplinaryRecord::query()
            ->where(function ($q) use ($year, $clean) {
                $q->where('academic_year', $year)
                    ->orWhere('academic_year', $clean);
            })
            ->with(['student.group.classroom', 'reportedBy', 'president'])
            ->latest('hearing_date');

        if (! empty($this->selectedDegree)) {
            $query->where('degree', $this->selectedDegree);
        }

        if (! empty($this->selectedStatus)) {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->searchQuery)) {
            $term = '%'.$this->searchQuery.'%';
            $query->whereHas('student', function ($sq) use ($term) {
                $sq->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('registration_number', 'like', $term)
                    ->orWhere('national_id', 'like', $term);
            });
        }

        return $query->get();
    }

    public function getKpisProperty(): array
    {
        return app(DisciplinaryCouncilService::class)->getDisciplinaryKpis($this->academicYear);
    }
}
