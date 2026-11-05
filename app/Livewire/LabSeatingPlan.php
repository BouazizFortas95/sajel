<?php

namespace App\Livewire;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan as LabPlan;
use App\Models\LabSeatingPlanStation;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\NetStationService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class LabSeatingPlan extends Component
{
    #[Url(as: 'schedule')]
    public ?int $scheduleId = null;

    #[Url(as: 'lab')]
    public ?int $labId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'date')]
    public string $attendanceDate = '';

    public ?int $dailyLogId = null;

    /**
     * Map of student_id => attendance_status ('present'|'absent'|'late'|'excused')
     *
     * @var array<int, string>
     */
    public array $attendances = [];

    public bool $showExperimentModal = false;

    public ?int $existingPlanId = null;

    public string $planName = '';

    public string $planNotes = '';

    public array $stations = [];

    public array $students = [];

    public ?int $selectedStationId = null;

    public bool $showSaveModal = false;

    /**
     * Mode: 'control' (NetStation live PC monitoring) or 'seating' (seating arrangement).
     */
    public string $viewMode = 'control';

    public bool $showSafetyModal = false;

    public bool $showBroadcastModal = false;

    public string $broadcastMessageText = '';

    public bool $showLockModal = false;

    public string $customLockMessage = '';

    public bool $globalInternetEnabled = true;

    public function mount(?int $lab = null, ?int $group = null, ?int $schedule = null): void
    {
        if (blank($this->attendanceDate)) {
            $this->attendanceDate = today()->toDateString();
        }

        if ($schedule) {
            $this->scheduleId = $schedule;
        }

        if ($lab) {
            $this->labId = $lab;
        }

        if ($group) {
            $this->groupId = $group;
        }

        if ($this->scheduleId) {
            $this->loadFromSchedule();
        }

        $this->refreshState();
        $this->loadAttendances();
    }

    public function updatedScheduleId(): void
    {
        if ($this->scheduleId) {
            $this->loadFromSchedule();
        }
        $this->refreshState();
        $this->loadAttendances();
    }

    public function updatedAttendanceDate(): void
    {
        if ($this->scheduleId) {
            $this->resolveDailyLog();
        }
        $this->loadAttendances();
    }

    public function updatedLabId(): void
    {
        $this->selectedStationId = null;
        $this->refreshState();
        $this->loadAttendances();
    }

    public function updatedGroupId(): void
    {
        $this->selectedStationId = null;
        $this->refreshState();
        $this->loadAttendances();
    }

    public function loadFromSchedule(): void
    {
        if (! $this->scheduleId) {
            return;
        }

        $schedule = Schedule::query()
            ->with(['subject', 'classroom.groups', 'classroom.level', 'group'])
            ->find($this->scheduleId);

        if (! $schedule) {
            return;
        }

        // Set group from schedule
        if ($schedule->group_id) {
            $this->groupId = $schedule->group_id;
        } elseif ($schedule->classroom && $schedule->classroom->groups->isNotEmpty()) {
            $this->groupId = $schedule->classroom->groups->first()->id;
        }

        // Auto-select lab matching subject if lab is not yet selected
        if (! $this->labId) {
            $subjectName = $schedule->subject?->name ?? '';
            $matchingLab = null;

            if (str_contains($subjectName, 'فيزياء') || str_contains($subjectName, 'فيزيائ') || str_contains($subjectName, 'كيمياء')) {
                $matchingLab = Lab::all()->first(fn (Lab $l) => $l->isPhysics());
            } elseif (str_contains($subjectName, 'طبيعة') || str_contains($subjectName, 'علوم') || str_contains($subjectName, 'أحياء')) {
                $matchingLab = Lab::all()->first(fn (Lab $l) => $l->isNaturalScience());
            } elseif (str_contains($subjectName, 'إعلام') || str_contains($subjectName, 'حاسوب') || str_contains($subjectName, 'تقنية')) {
                $matchingLab = Lab::all()->first(fn (Lab $l) => $l->isInformatics());
            }

            if (! $matchingLab) {
                $matchingLab = Lab::first();
            }

            if ($matchingLab) {
                $this->labId = $matchingLab->id;
            }
        }

        // Resolve daily log
        $this->resolveDailyLog($schedule);
    }

    public function resolveDailyLog(?Schedule $schedule = null): void
    {
        if (! $schedule && $this->scheduleId) {
            $schedule = Schedule::find($this->scheduleId);
        }

        if (! $schedule) {
            $this->dailyLogId = null;

            return;
        }

        $date = ! blank($this->attendanceDate) ? $this->attendanceDate : today()->toDateString();

        $log = DailyLog::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('date', $date)
            ->first();

        if (! $log) {
            $user = auth()->user();
            if ($user && ($user->isTeacher() || $user->isAdmin())) {
                $log = DailyLog::create([
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'title' => __('حصة مخبرية: :subject', ['subject' => $schedule->subject?->name ?? '']),
                    'status' => DailyLogStatus::Planned->value,
                    'content_data' => [],
                ]);
            }
        }

        $this->dailyLogId = $log?->id;
    }

    public function loadAttendances(): void
    {
        $this->attendances = [];

        if (! $this->dailyLogId) {
            foreach ($this->students as $student) {
                $this->attendances[$student['id']] = AttendanceStatus::Present->value;
            }

            return;
        }

        $records = Attendance::query()
            ->where('daily_log_id', $this->dailyLogId)
            ->get()
            ->keyBy('student_id');

        foreach ($this->students as $student) {
            $sId = $student['id'];
            if ($att = $records->get($sId)) {
                $this->attendances[$sId] = $att->status instanceof AttendanceStatus ? $att->status->value : (string) $att->status;
            } else {
                $this->attendances[$sId] = AttendanceStatus::Present->value;
            }
        }
    }

    public function toggleAttendance(int $studentId): void
    {
        $current = $this->attendances[$studentId] ?? AttendanceStatus::Present->value;

        $next = match ($current) {
            AttendanceStatus::Present->value => AttendanceStatus::Absent->value,
            AttendanceStatus::Absent->value => AttendanceStatus::Late->value,
            AttendanceStatus::Late->value => AttendanceStatus::Excused->value,
            default => AttendanceStatus::Present->value,
        };

        $this->attendances[$studentId] = $next;
        $this->saveStudentAttendance($studentId, $next);

        $studentName = collect($this->students)->firstWhere('id', $studentId)['full_name'] ?? '';
        $statusLabel = AttendanceStatus::from($next)->label();

        Notification::make()
            ->title("{$studentName}: {$statusLabel}")
            ->color(AttendanceStatus::from($next)->color())
            ->duration(2000)
            ->send();
    }

    public function setStudentAttendance(int $studentId, string $status): void
    {
        $this->attendances[$studentId] = $status;
        $this->saveStudentAttendance($studentId, $status);
    }

    public function markAllPresent(): void
    {
        foreach ($this->students as $student) {
            $this->attendances[$student['id']] = AttendanceStatus::Present->value;
            $this->saveStudentAttendance($student['id'], AttendanceStatus::Present->value);
        }

        Notification::make()
            ->title(__('تم تسجيل جميع التلاميذ حاضرين'))
            ->success()
            ->send();
    }

    protected function saveStudentAttendance(int $studentId, string $status): void
    {
        if (! $this->dailyLogId && $this->scheduleId) {
            $this->resolveDailyLog();
        }

        if ($this->dailyLogId) {
            Attendance::updateOrCreate(
                [
                    'daily_log_id' => $this->dailyLogId,
                    'student_id' => $studentId,
                ],
                [
                    'status' => $status,
                ]
            );
        }
    }

    public function getPresentCountProperty(): int
    {
        return collect($this->attendances)->filter(fn ($st) => $st === AttendanceStatus::Present->value)->count();
    }

    public function getAbsentCountProperty(): int
    {
        return collect($this->attendances)->filter(fn ($st) => $st === AttendanceStatus::Absent->value)->count();
    }

    public function getLateCountProperty(): int
    {
        return collect($this->attendances)->filter(fn ($st) => $st === AttendanceStatus::Late->value)->count();
    }

    public function getExcusedCountProperty(): int
    {
        return collect($this->attendances)->filter(fn ($st) => $st === AttendanceStatus::Excused->value)->count();
    }

    public function setViewMode(string $mode): void
    {
        $selectedLab = $this->labId ? Lab::find($this->labId) : null;
        if ($selectedLab && ! $selectedLab->isInformatics()) {
            $this->viewMode = 'seating';

            return;
        }

        $this->viewMode = in_array($mode, ['control', 'seating'], true) ? $mode : 'control';
    }

    public function refreshState(): void
    {
        $this->stations = [];
        $this->students = [];
        $this->selectedStationId = null;

        // 1. Load stations if lab is selected
        if ($this->labId) {
            $lab = Lab::query()->with('workingStations')->find($this->labId);
            if ($lab) {
                if (! $lab->isInformatics()) {
                    $this->viewMode = 'seating';
                }

                foreach ($lab->workingStations as $station) {
                    $this->stations[] = [
                        'id' => $station->id,
                        'station_number' => $station->station_number,
                        'device_type' => $station->device_type,
                        'ip_address' => $station->ip_address ?? '192.168.1.'.(100 + $station->station_number),
                        'is_locked' => (bool) $station->is_locked,
                        'lock_message' => $station->lock_message,
                        'active_broadcast' => $station->active_broadcast,
                        'internet_enabled' => (bool) $station->internet_enabled,
                        'is_online' => $station->isOnline(),
                        'is_computer' => $station->isComputer(),
                        'student_id' => null,
                        'student_name' => null,
                        'position_x' => 0,
                        'position_y' => 0,
                    ];
                }
            }
        }

        // 2. Load students if group is selected
        if ($this->groupId) {
            $group = Group::query()->with('students')->find($this->groupId);
            if ($group) {
                foreach ($group->students as $student) {
                    $this->students[] = [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'registration_number' => $student->registration_number ?? '—',
                    ];
                }
            }
        }

        // 3. If both lab and group are selected, look for existing saved plan
        if ($this->labId && $this->groupId) {
            $existingPlan = LabPlan::query()
                ->with(['stations.student'])
                ->where('lab_id', $this->labId)
                ->where('group_id', $this->groupId)
                ->latest()
                ->first();

            if ($existingPlan) {
                $this->existingPlanId = $existingPlan->id;
                $this->planName = $existingPlan->name;
                $this->planNotes = $existingPlan->notes ?? '';

                $planStations = $existingPlan->stations->keyBy('lab_station_id');
                foreach ($this->stations as &$st) {
                    if ($ps = $planStations->get($st['id'])) {
                        $st['student_id'] = $ps->student_id;
                        $st['student_name'] = $ps->student?->full_name;
                        $st['position_x'] = $ps->position_x;
                        $st['position_y'] = $ps->position_y;
                    }
                }
                unset($st);
            } else {
                $this->existingPlanId = null;
                $lab = Lab::find($this->labId);
                $group = Group::find($this->groupId);
                if ($lab && $group) {
                    $this->planName = __('خطة جلوس :lab — :group', [
                        'lab' => $lab->name,
                        'group' => $group->display_name(),
                    ]);
                }
            }
        }
    }

    public function selectStation(int $stationId): void
    {
        $this->selectedStationId = $this->selectedStationId === $stationId ? null : $stationId;
    }

    public function assignStudent(int $studentId): void
    {
        if (! $this->selectedStationId) {
            Notification::make()
                ->title(__('يرجى اختيار محطة أولاً بالنقر عليها، ثم النقر على التلميذ لتعيينه'))
                ->warning()
                ->send();

            return;
        }

        // Remove student from any other station
        foreach ($this->stations as &$station) {
            if ($station['student_id'] === $studentId) {
                $station['student_id'] = null;
                $station['student_name'] = null;
            }
        }
        unset($station);

        // Assign to selected station
        foreach ($this->stations as &$station) {
            if ($station['id'] === $this->selectedStationId) {
                $student = collect($this->students)->firstWhere('id', $studentId);
                $station['student_id'] = $studentId;
                $station['student_name'] = $student['full_name'] ?? null;
                break;
            }
        }
        unset($station);

        $this->selectedStationId = null;
    }

    public function unassignStudent(int $stationId): void
    {
        foreach ($this->stations as &$station) {
            if ($station['id'] === $stationId) {
                $station['student_id'] = null;
                $station['student_name'] = null;
                break;
            }
        }
        unset($station);

        if ($this->selectedStationId === $stationId) {
            $this->selectedStationId = null;
        }
    }

    public function autoAssign(): void
    {
        if (empty($this->stations) || empty($this->students)) {
            return;
        }

        $assignedStudentIds = collect($this->stations)->pluck('student_id')->filter()->all();
        $unassignedStudents = collect($this->students)
            ->reject(fn ($s) => in_array($s['id'], $assignedStudentIds))
            ->values();

        $studentIdx = 0;
        foreach ($this->stations as &$station) {
            if (! $station['student_id'] && $studentIdx < $unassignedStudents->count()) {
                $student = $unassignedStudents[$studentIdx++];
                $station['student_id'] = $student['id'];
                $station['student_name'] = $student['full_name'];
            }
        }
        unset($station);

        Notification::make()
            ->title(__('تم توزيع التلاميذ على المحطات تلقائياً'))
            ->success()
            ->send();
    }

    public function clearAssignments(): void
    {
        foreach ($this->stations as &$station) {
            $station['student_id'] = null;
            $station['student_name'] = null;
        }
        unset($station);
        $this->selectedStationId = null;

        Notification::make()
            ->title(__('تم تفريغ جميع المحطات'))
            ->info()
            ->send();
    }

    public function resetStations(): void
    {
        $this->refreshState();

        Notification::make()
            ->title(__('تمت استعادة حالة الخطة'))
            ->info()
            ->send();
    }

    public function savePlan(): void
    {
        if (blank($this->planName) && $this->labId && $this->groupId) {
            $lab = Lab::find($this->labId);
            $group = Group::find($this->groupId);
            $this->planName = __('خطة جلوس :lab — :group', [
                'lab' => $lab?->name ?? '',
                'group' => $group?->display_name() ?? '',
            ]);
        }

        $this->validate([
            'labId' => 'required|exists:labs,id',
            'groupId' => 'required|exists:groups,id',
            'planName' => 'required|string|max:255',
        ]);

        if ($this->existingPlanId) {
            $plan = LabPlan::find($this->existingPlanId);
            if ($plan) {
                $plan->update([
                    'name' => $this->planName,
                    'notes' => $this->planNotes,
                ]);
                $plan->stations()->delete();
            } else {
                $plan = LabPlan::create([
                    'lab_id' => $this->labId,
                    'group_id' => $this->groupId,
                    'name' => $this->planName,
                    'notes' => $this->planNotes,
                ]);
                $this->existingPlanId = $plan->id;
            }
        } else {
            $plan = LabPlan::firstOrNew([
                'lab_id' => $this->labId,
                'name' => $this->planName,
            ]);
            $plan->group_id = $this->groupId;
            $plan->notes = $this->planNotes;
            $plan->save();

            $plan->stations()->delete();
            $this->existingPlanId = $plan->id;
        }

        foreach ($this->stations as $index => $station) {
            LabSeatingPlanStation::create([
                'seating_plan_id' => $plan->id,
                'lab_station_id' => $station['id'],
                'student_id' => $station['student_id'],
                'position_x' => $station['position_x'] ?? (int) ($index / 6),
                'position_y' => $station['position_y'] ?? (int) ($index % 6),
            ]);
        }

        $this->showSaveModal = false;

        Notification::make()
            ->title(__('تم حفظ خطة الجلوس بنجاح'))
            ->body(__('المحطات المشغولة: :count محطة', ['count' => $this->assignedCount]))
            ->success()
            ->send();
    }

    // =========================================================================
    // NetStation Leader PC Controls
    // =========================================================================

    public function lockAllStations(?string $customMsg = null): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->lockAll($this->labId, $customMsg);

        foreach ($this->stations as &$st) {
            $st['is_locked'] = true;
            $st['lock_message'] = $customMsg ?: __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.');
        }
        unset($st);

        $this->showLockModal = false;

        Notification::make()
            ->title(__('تم قفل جميع شاشات المخبر (:count محطة)', ['count' => $count]))
            ->warning()
            ->send();
    }

    public function unlockAllStations(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->unlockAll($this->labId);

        foreach ($this->stations as &$st) {
            $st['is_locked'] = false;
            $st['lock_message'] = null;
        }
        unset($st);

        Notification::make()
            ->title(__('تم إلغاء قفل جميع شاشات المخبر بنجاح'))
            ->success()
            ->send();
    }

    public function toggleStationLock(int $stationId): void
    {
        $lab = $this->labId ? Lab::find($this->labId) : null;
        if (! $lab?->isInformatics()) {
            return;
        }

        $stationIndex = collect($this->stations)->search(fn ($s) => $s['id'] === $stationId);
        if ($stationIndex === false) {
            return;
        }

        $isCurrentlyLocked = (bool) $this->stations[$stationIndex]['is_locked'];

        if ($isCurrentlyLocked) {
            app(NetStationService::class)->unlockStation($stationId);
            $this->stations[$stationIndex]['is_locked'] = false;
            $this->stations[$stationIndex]['lock_message'] = null;

            Notification::make()
                ->title(__('تم إلغاء قفل المحطة :num', ['num' => $this->stations[$stationIndex]['station_number']]))
                ->success()
                ->send();
        } else {
            app(NetStationService::class)->lockStation($stationId);
            $this->stations[$stationIndex]['is_locked'] = true;
            $this->stations[$stationIndex]['lock_message'] = __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.');

            Notification::make()
                ->title(__('تم قفل شاشة المحطة :num', ['num' => $this->stations[$stationIndex]['station_number']]))
                ->warning()
                ->send();
        }
    }

    public function toggleInternetAll(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $this->globalInternetEnabled = ! $this->globalInternetEnabled;
        app(NetStationService::class)->toggleInternet($this->labId, $this->globalInternetEnabled);

        foreach ($this->stations as &$st) {
            $st['internet_enabled'] = $this->globalInternetEnabled;
        }
        unset($st);

        Notification::make()
            ->title($this->globalInternetEnabled ? __('تم تمكين الإنترنت لجميع المحطات') : __('تم حجب الإنترنت عن جميع المحطات'))
            ->info()
            ->send();
    }

    public function sendBroadcastMessage(): void
    {
        if (! $this->labId || blank($this->broadcastMessageText)) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->broadcastMessage($this->labId, $this->broadcastMessageText);

        foreach ($this->stations as &$st) {
            $st['active_broadcast'] = $this->broadcastMessageText;
        }
        unset($st);

        $this->showBroadcastModal = false;
        $this->broadcastMessageText = '';

        Notification::make()
            ->title(__('تم إرسال البث الجماعي إلى :count محطة بنجاح', ['count' => $count]))
            ->success()
            ->send();
    }

    public function clearBroadcastMessage(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        app(NetStationService::class)->clearBroadcast($this->labId);

        foreach ($this->stations as &$st) {
            $st['active_broadcast'] = null;
        }
        unset($st);

        Notification::make()
            ->title(__('تم إيقاف ومسح البث الجماعي عن جميع الشاشات'))
            ->info()
            ->send();
    }

    public function getAssignedCountProperty(): int
    {
        return collect($this->stations)->filter(fn ($s) => ! empty($s['student_id']))->count();
    }

    public function getAvailableStationsCountProperty(): int
    {
        return count($this->stations);
    }

    public function getLockedCountProperty(): int
    {
        return collect($this->stations)->filter(fn ($s) => ! empty($s['is_locked']))->count();
    }

    public function render(): View
    {
        $user = auth()->user();

        $labs = Lab::query()->with('workingStations')->orderBy('name')->get();

        $groupsQuery = Group::query()->with(['students', 'classroom.level'])->orderBy('name');
        if ($user && $user->isTeacher()) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->whereHas('schedules', fn ($sq) => $sq->where('user_id', $user->id))
                    ->orWhereHas('classroom.schedules', fn ($sq) => $sq->where('user_id', $user->id));
            });
        }
        $groups = $groupsQuery->get();
        if ($groups->isEmpty()) {
            $groups = Group::query()->with(['students', 'classroom.level'])->orderBy('name')->get();
        }

        $schedulesQuery = Schedule::query()
            ->with(['subject', 'classroom.level', 'group'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($user && $user->isTeacher()) {
            $schedulesQuery->forTeacher($user->id);
        }
        $schedules = $schedulesQuery->get();

        $activeDailyLog = $this->dailyLogId
            ? DailyLog::query()->with(['schedule.subject', 'schedule.user', 'curriculumResource'])->find($this->dailyLogId)
            : null;

        return view('livewire.lab-seating-plan', [
            'labs' => $labs,
            'groups' => $groups,
            'schedules' => $schedules,
            'activeDailyLog' => $activeDailyLog,
            'activeSchedule' => $this->scheduleId ? Schedule::find($this->scheduleId) : null,
        ]);
    }
}
