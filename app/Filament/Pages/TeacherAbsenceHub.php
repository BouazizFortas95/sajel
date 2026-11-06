<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Enums\TeacherAbsenceReason;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Services\TeacherAbsenceService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class TeacherAbsenceHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.teacher-absence-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'radar';

    #[Url(as: 'year')]
    public int $year = 2026;

    #[Url(as: 'month')]
    public int $month = 10;

    #[Url(as: 'teacher')]
    public ?int $selectedTeacherId = null;

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    // Modal state for assigning substitute
    public bool $isAssignModalOpen = false;

    public ?int $assigningScheduleId = null;

    public ?string $assigningDate = null;

    public ?int $selectedSubstituteId = null;

    public ?string $substituteInstructions = null;

    // Modal state for Work Resumption (محضر استئناف العمل)
    public bool $isResumptionModalOpen = false;

    public ?int $resumingAbsenceId = null;

    public ?string $resumptionDate = null;

    public ?string $resumptionPvNumber = null;

    public ?string $resumptionNotes = null;

    // Modal state for Salary Deduction (إشعار الخصم من الراتب)
    public bool $isDeductionModalOpen = false;

    public ?int $deductingAbsenceId = null;

    public ?int $deductionDays = null;

    public ?string $deductionRefNumber = null;

    public ?string $deductionNotes = null;

    public function mount(): void
    {
        $this->year = $this->year ?: (int) now()->year;
        $this->month = $this->month ?: (int) now()->month;

        $user = Auth::user();
        if ($user instanceof User && $user->isTeacher()) {
            $this->selectedTeacherId = $user->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('غيابات الأساتذة والاستخلاف');
    }

    public function getTitle(): string
    {
        return __('رادار غيابات الأساتذة وإدارة الاستخلاف البيداغوجي');
    }

    public function getHeaderActions(): array
    {
        $actions = [];

        if (Auth::user()?->isAdmin()) {
            $actions[] = Action::make('recordAbsence')
                ->label(__('تسجيل غياب أستاذ جديد'))
                ->icon('heroicon-o-plus-circle')
                ->color('amber')
                ->modalHeading(__('تسجيل غياب بيداغوجي لأستاذ'))
                ->modalSubmitActionLabel(__('حفظ الغياب واستخراج الحصص'))
                ->form([
                    Grid::make(2)->schema([
                        Select::make('teacher_id')
                            ->label(__('الأستاذ(ة) المتغيب(ة)'))
                            ->options(fn () => User::where('role', Role::Teacher)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('reason')
                            ->label(__('طبيعة / سبب الغياب'))
                            ->options(TeacherAbsenceReason::options())
                            ->default(TeacherAbsenceReason::SickLeave->value)
                            ->required(),

                        DatePicker::make('starts_at')
                            ->label(__('تاريخ بداية الغياب'))
                            ->default(today())
                            ->required(),

                        DatePicker::make('ends_at')
                            ->label(__('تاريخ نهاية الغياب'))
                            ->default(today())
                            ->required(),

                        TextInput::make('reference_number')
                            ->label(__('رقم المرجع / الشهادة الطبية'))
                            ->placeholder('CERT-2026/012')
                            ->columnSpan(2),

                        Textarea::make('notes')
                            ->label(__('الملاحظات والتوجيهات الإدارية'))
                            ->rows(3)
                            ->columnSpan(2),
                    ]),
                ])
                ->action(function (array $data, TeacherAbsenceService $service): void {
                    $absence = $service->recordAbsence($data, Auth::user());

                    Notification::make()
                        ->title(__('تم تسجيل الغياب بنجاح'))
                        ->body(__('تم حصر الحصص المتأثرة وفتح خيارات الاستخلاف الفوري.'))
                        ->success()
                        ->send();

                    $this->activeTab = 'sessions';
                });

            $actions[] = Action::make('printLedger')
                ->label(__('طباعة الكشف الشهري'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('print.teacher-absence.monthly-ledger', [
                    'locale' => app()->getLocale(),
                    'year' => $this->year,
                    'month' => $this->month,
                ]))
                ->openUrlInNewTab();

            $actions[] = Action::make('pdfLedger')
                ->label(__('تحميل الكشف (PDF)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (): string => route('print.teacher-absence.monthly-ledger.pdf', [
                    'locale' => app()->getLocale(),
                    'year' => $this->year,
                    'month' => $this->month,
                ]))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function openAssignModal(int $scheduleId, string $date): void
    {
        $this->assigningScheduleId = $scheduleId;
        $this->assigningDate = $date;
        $this->selectedSubstituteId = null;
        $this->substituteInstructions = null;
        $this->isAssignModalOpen = true;
    }

    public function closeAssignModal(): void
    {
        $this->isAssignModalOpen = false;
        $this->assigningScheduleId = null;
        $this->assigningDate = null;
        $this->selectedSubstituteId = null;
        $this->substituteInstructions = null;
    }

    public function confirmAssignSubstitute(TeacherAbsenceService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        if (! $this->assigningScheduleId || ! $this->assigningDate || ! $this->selectedSubstituteId) {
            Notification::make()
                ->title(__('يرجى اختيار أستاذ مستخلف'))
                ->danger()
                ->send();

            return;
        }

        $schedule = Schedule::find($this->assigningScheduleId);
        $substitute = User::find($this->selectedSubstituteId);

        if (! $schedule || ! $substitute) {
            $this->closeAssignModal();

            return;
        }

        $service->assignSubstitute(
            $schedule,
            $this->assigningDate,
            $substitute,
            $this->substituteInstructions
        );

        Notification::make()
            ->title(__('تم تكليف الأستاذ المستخلف بنجاح'))
            ->body(__('تم تسجيل الحصة وربطها بالأستاذ :name.', ['name' => $substitute->name]))
            ->success()
            ->send();

        $this->closeAssignModal();
    }

    public function unassignSubstitute(int $dailyLogId, TeacherAbsenceService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $log = DailyLog::find($dailyLogId);
        if ($log) {
            $service->unassignSubstitute($log);

            Notification::make()
                ->title(__('تم إلغاء تكليف الاستخلاف'))
                ->info()
                ->send();
        }
    }

    public function openResumptionModal(int $absenceId): void
    {
        $absence = TeacherAbsence::find($absenceId);
        if (! $absence) {
            return;
        }

        $this->resumingAbsenceId = $absence->id;
        $this->resumptionDate = now()->format('Y-m-d\TH:i');
        $this->resumptionPvNumber = $absence->resumptionPvFormatted();
        $this->resumptionNotes = $absence->resumption_notes;
        $this->isResumptionModalOpen = true;
    }

    public function closeResumptionModal(): void
    {
        $this->isResumptionModalOpen = false;
        $this->resumingAbsenceId = null;
        $this->resumptionDate = null;
        $this->resumptionPvNumber = null;
        $this->resumptionNotes = null;
    }

    public function confirmResumption(TeacherAbsenceService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $absence = TeacherAbsence::find($this->resumingAbsenceId);
        if (! $absence) {
            $this->closeResumptionModal();

            return;
        }

        $service->recordWorkResumption($absence, [
            'resumed_at' => $this->resumptionDate,
            'resumption_pv_number' => $this->resumptionPvNumber,
            'resumption_notes' => $this->resumptionNotes,
        ]);

        Notification::make()
            ->title(__('تم تسجيل استئناف العمل بنجاح'))
            ->body(__('تم توثيق المحضر ويمكن الآن معاينته وطباعته أو تحميله بصيغة PDF.'))
            ->success()
            ->send();

        $this->closeResumptionModal();
    }

    public function openDeductionModal(int $absenceId): void
    {
        $absence = TeacherAbsence::find($absenceId);
        if (! $absence) {
            return;
        }

        $this->deductingAbsenceId = $absence->id;
        $this->deductionDays = $absence->calculateDeductionDays();
        $this->deductionRefNumber = $absence->deductionNoticeFormatted();
        $this->deductionNotes = $absence->notes;
        $this->isDeductionModalOpen = true;
    }

    public function closeDeductionModal(): void
    {
        $this->isDeductionModalOpen = false;
        $this->deductingAbsenceId = null;
        $this->deductionDays = null;
        $this->deductionRefNumber = null;
        $this->deductionNotes = null;
    }

    public function confirmDeduction(TeacherAbsenceService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $absence = TeacherAbsence::find($this->deductingAbsenceId);
        if (! $absence) {
            $this->closeDeductionModal();

            return;
        }

        $service->issueSalaryDeductionNotice($absence, [
            'deduction_days' => $this->deductionDays,
            'deduction_ref_number' => $this->deductionRefNumber,
        ]);

        Notification::make()
            ->title(__('تم إصدار إشعار الخصم من الراتب بنجاح'))
            ->body(__('تم حصر أيام الخصم وتوثيق الإشعار الرسمي للتبليغ.'))
            ->warning()
            ->send();

        $this->closeDeductionModal();
    }

    public function toggleRequisition(int $absenceId, TeacherAbsenceService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $absence = TeacherAbsence::find($absenceId);
        if ($absence) {
            $service->issueRequisitionMandate($absence, []);

            Notification::make()
                ->title(__('تم اعتماد التسخير الإداري الرسمي'))
                ->success()
                ->send();
        }
    }

    /**
     * Get available candidate substitutes for the modal dialog.
     */
    public function getCandidateSubstitutesProperty(TeacherAbsenceService $service): Collection
    {
        if (! $this->assigningScheduleId || ! $this->assigningDate) {
            return collect();
        }

        $schedule = Schedule::find($this->assigningScheduleId);
        if (! $schedule) {
            return collect();
        }

        return $service->findAvailableSubstitutes($schedule, $this->assigningDate);
    }

    public function getActiveScheduleProperty(): ?Schedule
    {
        return $this->assigningScheduleId ? Schedule::find($this->assigningScheduleId) : null;
    }

    /**
     * Render radar view data.
     *
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(TeacherAbsenceService::class);
        $user = Auth::user();

        $absencesQuery = TeacherAbsence::query()
            ->with(['teacher.primarySubject', 'recordedBy'])
            ->orderBy('starts_at', 'desc');

        if ($user instanceof User && $user->isTeacher()) {
            // Teacher sees own absences and absences where they act as substitute
            $absencesQuery->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhereHas('teacher.schedules.dailyLogs', function ($sub) use ($user) {
                        $sub->where('substitute_teacher_id', $user->id);
                    });
            });
        } elseif ($this->selectedTeacherId) {
            $absencesQuery->where('teacher_id', $this->selectedTeacherId);
        }

        if ($this->statusFilter !== 'all') {
            $absencesQuery->where('status', $this->statusFilter);
        }

        $absences = $absencesQuery->get();

        // Affected sessions calculation across filtered absences
        $affectedSessions = collect();
        foreach ($absences as $absence) {
            $sessions = $service->getAffectedSessions($absence);
            foreach ($sessions as $session) {
                $session['parent_absence'] = $absence;
                $affectedSessions->push($session);
            }
        }

        $kpis = $service->getRadarKpis($this->year, $this->month);

        $teacherOptions = User::where('role', Role::Teacher)->orderBy('name')->pluck('name', 'id');

        return [
            'absences' => $absences,
            'affectedSessions' => $affectedSessions,
            'kpis' => $kpis,
            'teacherOptions' => $teacherOptions,
            'isAdmin' => $user?->isAdmin() ?? false,
        ];
    }
}
