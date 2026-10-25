<?php

namespace App\Livewire;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickAttendance extends Component
{
    public ?int $selectedScheduleId = null;

    public ?string $selectedDate = null;

    public array $attendanceStatuses = [];

    public array $schedules = [];

    public array $students = [];

    public bool $rosterLoaded = false;

    public array $rosterSnapshot = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->selectedDate = today()->toDateString();

        // Auto-select the current session (schedule happening now) if any.
        $this->loadSchedules();

        if ($this->schedules) {
            $current = $this->findCurrentSchedule();

            if ($current) {
                $this->selectedScheduleId = $current->id;
            } else {
                $this->selectedScheduleId = $this->schedules[0]['id'];
            }

            $this->loadRoster();
        }
    }

    #[On('schedule-selected')]
    public function selectSchedule(int $scheduleId): void
    {
        $this->selectedScheduleId = $scheduleId;
        $this->attendanceStatuses = [];
        $this->loadRoster();
    }

    public function loadSchedules(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $this->schedules = [];

            return;
        }

        $today = today();
        $dayOfWeek = DayOfWeek::cases()[$today->dayOfWeek]->value;

        $this->schedules = $this->accessibleSchedules($user)
            ->with(['subject', 'classroom.level', 'group.classroom'])
            ->onDay(DayOfWeek::tryFrom($dayOfWeek))
            ->orderBy('start_time')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'label' => $schedule->label(),
                'subject' => $schedule->subject?->name ?? '—',
                'classroom' => $schedule->sectionName() ?? '—',
                'group' => $schedule->group?->name ?? __('القسم كاملاً'),
                'start' => $schedule->start_time?->format('H:i') ?? '—',
                'end' => $schedule->end_time?->format('H:i') ?? '—',
                'isCurrent' => $this->isCurrentSchedule($schedule),
            ])
            ->toArray();
    }

    public function loadRoster(): void
    {
        $this->students = [];
        $this->rosterLoaded = false;
        $this->rosterSnapshot = [];

        if (blank($this->selectedScheduleId)) {
            return;
        }

        $schedule = $this->accessibleSchedules()
            ->with(['subject', 'classroom.level', 'group.classroom'])
            ->find($this->selectedScheduleId);

        if (! $schedule) {
            abort(403);
        }

        $this->students = Student::query()
            ->with('group')
            ->forSchedule($schedule)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'registration_number' => $student->registration_number ?? '—',
                'group' => $student->group?->name ?? '—',
                'status' => $this->attendanceStatuses[$student->id] ?? AttendanceStatus::Present->value,
                'remarks' => '',
            ])
            ->toArray();

        $this->rosterLoaded = true;

        // Pre-fill existing attendance records if a log already exists.
        $this->syncExistingAttendance();
        $this->refreshRosterSnapshot($schedule);
        $this->dispatch('attendance-roster-updated', snapshot: $this->rosterSnapshot);
    }

    public function syncExistingAttendance(): void
    {
        if (blank($this->selectedScheduleId) || blank($this->selectedDate)) {
            return;
        }

        $schedule = $this->accessibleSchedules()->find($this->selectedScheduleId);

        if (! $schedule) {
            return;
        }

        $dailyLog = DailyLog::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('date', $this->selectedDate)
            ->first();

        if (! $dailyLog) {
            return;
        }

        $existing = $dailyLog->attendances
            ->keyBy('student_id')
            ->map(fn (Attendance $attendance): array => [
                'status' => $attendance->status->value,
                'remarks' => $attendance->remarks ?? '',
            ])
            ->toArray();

        foreach ($this->students as &$student) {
            if (isset($existing[$student['id']])) {
                $student['status'] = $existing[$student['id']]['status'];
                $student['remarks'] = $existing[$student['id']]['remarks'];
            }
        }

        unset($student);

        $this->attendanceStatuses = array_merge(
            $this->attendanceStatuses,
            collect($existing)
                ->filter(fn (array $attendance): bool => $attendance['status'] !== AttendanceStatus::Present->value)
                ->map(fn (array $attendance): string => $attendance['status'])
                ->all(),
        );
    }

    public function markAttendance(int $studentId, string $status): void
    {
        $this->attendanceStatuses[$studentId] = $status;

        Notification::make()
            ->title(__('تم تحديث الحالة'))
            ->body(__('تم تسجيل الحالة للطالب بنجاح.'))
            ->success()
            ->send();
    }

    public function markAllPresent(): void
    {
        foreach ($this->students as &$student) {
            $this->attendanceStatuses[$student['id']] = AttendanceStatus::Present->value;
            $student['status'] = AttendanceStatus::Present->value;
        }
        unset($student);

        Notification::make()
            ->title(__('تم تحديد الجميع كحاضرين'))
            ->success()
            ->send();
    }

    public function saveAttendance(): void
    {
        $this->validate([
            'selectedScheduleId' => 'required|exists:schedules,id',
            'selectedDate' => 'required|date',
        ]);

        $schedule = $this->accessibleSchedules()->find($this->selectedScheduleId);

        if (! $schedule) {
            Notification::make()
                ->danger()
                ->title(__('الحصة غير متاحة'))
                ->send();

            return;
        }

        // Find or create the daily log for this schedule and date.
        $dailyLog = DailyLog::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('date', $this->selectedDate)
            ->first();

        if (! $dailyLog) {
            $dailyLog = DailyLog::create([
                'schedule_id' => $schedule->id,
                'date' => $this->selectedDate,
                'title' => __('درس: :subject', [
                    'subject' => $schedule->subject?->name ?? '—',
                ]),
                'content_data' => [],
                'status' => DailyLogStatus::Planned->value,
            ]);
        }

        // Mark the log as completed so attendance is official.
        $dailyLog->markAsCompleted();

        // Upsert attendance records for every student in the roster.
        $now = now();
        $rows = [];

        foreach ($this->students as $student) {
            $status = $this->attendanceStatuses[$student['id']] ?? AttendanceStatus::Present->value;

            $rows[] = [
                'daily_log_id' => $dailyLog->id,
                'student_id' => $student['id'],
                'status' => $status,
                'remarks' => $student['remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            foreach (array_chunk($rows, 100) as $chunk) {
                Attendance::insertOrIgnore($chunk);
            }
        }

        $this->syncExistingAttendance();

        Notification::make()
            ->title(__('تم حفظ الحضور بنجاح'))
            ->body(__('تم تسجيل حضور تلاميذ الحصة بنجاح.'))
            ->success()
            ->send();
    }

    /**
     * Persist offline changes atomically after rechecking schedule ownership
     * and student roster membership on the server.
     *
     * @param  array<int, array{schedule_id: int|string, date: string, student_id: int|string, status: string, remarks?: ?string}>  $entries
     * @return array{synced: int}
     */
    public function syncOfflineAttendance(array $entries): array
    {
        $validated = Validator::make(['entries' => $entries], [
            'entries' => ['required', 'array', 'min:1', 'max:200'],
            'entries.*' => ['required', 'array:schedule_id,date,student_id,status,remarks'],
            'entries.*.schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'entries.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'entries.*.remarks' => ['nullable', 'string', 'max:1000'],
        ])->validate()['entries'];

        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $entriesByStudent = [];

        foreach ($validated as $entry) {
            $schedule = $this->accessibleSchedules($user)
                ->with('subject')
                ->find($entry['schedule_id']);

            abort_unless($schedule, 403);

            $student = Student::query()
                ->forSchedule($schedule)
                ->whereKey($entry['student_id'])
                ->exists();

            if (! $student) {
                abort(403, __('التلميذ غير موجود في قائمة هذه الحصة.'));
            }

            $key = implode(':', [$schedule->id, $entry['date'], $entry['student_id']]);
            $entriesByStudent[$key] = [$schedule, $entry];
        }

        DB::transaction(function () use ($entriesByStudent): void {
            $attendanceRows = [];
            $dailyLogs = [];
            $now = now();

            foreach ($entriesByStudent as [$schedule, $entry]) {
                $sessionKey = $schedule->id.':'.$entry['date'];

                if (! isset($dailyLogs[$sessionKey])) {
                    $dailyLog = DailyLog::query()
                        ->where('schedule_id', $schedule->id)
                        ->whereDate('date', $entry['date'])
                        ->first();

                    if (! $dailyLog) {
                        $dailyLog = DailyLog::create([
                            'schedule_id' => $schedule->id,
                            'date' => $entry['date'],
                            'title' => __('درس: :subject', [
                                'subject' => $schedule->subject?->name ?? '—',
                            ]),
                            'content_data' => [],
                            'status' => DailyLogStatus::Planned->value,
                        ]);
                    }

                    $dailyLog->ensureAttendanceRecords();
                    $dailyLogs[$sessionKey] = $dailyLog;
                }

                $attendanceRows[] = [
                    'daily_log_id' => $dailyLogs[$sessionKey]->id,
                    'student_id' => $entry['student_id'],
                    'status' => $entry['status'],
                    'remarks' => $entry['remarks'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Attendance::query()->upsert(
                $attendanceRows,
                ['daily_log_id', 'student_id'],
                ['status', 'remarks', 'updated_at'],
            );
        });

        $syncedCount = count($entriesByStudent);

        Notification::make()
            ->title(__('اكتملت المزامنة'))
            ->body(__('تمت مزامنة :count تعديلات حضور.', ['count' => $syncedCount]))
            ->success()
            ->send();

        return ['synced' => $syncedCount];
    }

    public function getCurrentScheduleProperty(): ?array
    {
        foreach ($this->schedules as $schedule) {
            if ($schedule['isCurrent']) {
                return $schedule;
            }
        }

        return null;
    }

    private function findCurrentSchedule(): ?Schedule
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $now = now();
        $dayOfWeek = DayOfWeek::cases()[$now->dayOfWeek]->value;
        $currentTime = $now->format('H:i');

        return $this->accessibleSchedules($user)
            ->onDay(DayOfWeek::tryFrom($dayOfWeek))
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->first();
    }

    private function isCurrentSchedule(Schedule $schedule): bool
    {
        $now = now();

        if ($schedule->day_of_week->value !== DayOfWeek::cases()[$now->dayOfWeek]->value) {
            return false;
        }

        $start = $schedule->start_time?->format('H:i') ?? '00:00';
        $end = $schedule->end_time?->format('H:i') ?? '23:59';
        $current = $now->format('H:i');

        return $current >= $start && $current < $end;
    }

    private function accessibleSchedules(?User $user = null): Builder
    {
        $user ??= auth()->user();

        return Schedule::query()
            ->when(
                $user instanceof User && ! $user->isAdmin(),
                fn ($query) => $query->forTeacher($user->id),
            );
    }

    private function refreshRosterSnapshot(Schedule $schedule): void
    {
        $dailyLog = DailyLog::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('date', $this->selectedDate)
            ->first();

        $this->rosterSnapshot = [
            'schedule_id' => $schedule->id,
            'date' => $this->selectedDate,
            'daily_log_id' => $dailyLog?->id,
            'classroom_label' => $schedule->sectionName() ?? '—',
            'subject_label' => $schedule->subject?->name ?? '—',
            'schedule_label' => $schedule->label(),
            'students' => array_map(
                fn (array $student): array => [
                    'student_id' => $student['id'],
                    'full_name' => $student['full_name'],
                    'registration_number' => $student['registration_number'],
                    'group' => $student['group'],
                    'status' => $student['status'],
                    'remarks' => $student['remarks'] ?? '',
                ],
                $this->students,
            ),
        ];
    }

    public function render()
    {
        $statusOptions = collect(AttendanceStatus::cases())
            ->mapWithKeys(fn (AttendanceStatus $status): array => [
                $status->value => $status->label(),
            ])
            ->toArray();

        return view('livewire.quick-attendance', [
            'schedules' => $this->schedules,
            'students' => $this->students,
            'selectedScheduleId' => $this->selectedScheduleId,
            'selectedDate' => $this->selectedDate,
            'attendanceStatuses' => $this->attendanceStatuses,
            'rosterLoaded' => $this->rosterLoaded,
            'statusOptions' => $statusOptions,
            'currentSchedule' => $this->currentSchedule,
            'rosterSnapshot' => $this->rosterSnapshot,
        ]);
    }
}
