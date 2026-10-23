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

        $this->schedules = Schedule::query()
            ->with(['subject', 'classroom.level', 'group'])
            ->forTeacher($user->id)
            ->onDay(DayOfWeek::tryFrom($dayOfWeek))
            ->orderBy('start_time')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'label' => $schedule->label(),
                'subject' => $schedule->subject?->name ?? '—',
                'classroom' => $schedule->classroom?->name ?? '—',
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

        if (blank($this->selectedScheduleId)) {
            return;
        }

        $schedule = Schedule::query()
            ->with(['classroom.level', 'group'])
            ->find($this->selectedScheduleId);

        if (! $schedule) {
            return;
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
            ])
            ->toArray();

        $this->rosterLoaded = true;

        // Pre-fill existing attendance records if a log already exists.
        $this->syncExistingAttendance();
    }

    public function syncExistingAttendance(): void
    {
        if (blank($this->selectedScheduleId) || blank($this->selectedDate)) {
            return;
        }

        $schedule = Schedule::query()->find($this->selectedScheduleId);

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
            ->map(fn (Attendance $attendance): string => $attendance->status->value)
            ->toArray();

        foreach ($this->students as &$student) {
            if (isset($existing[$student['id']])) {
                $student['status'] = $existing[$student['id']];
            }
        }

        unset($student);

        $this->attendanceStatuses = array_merge(
            $this->attendanceStatuses,
            array_filter($existing, fn (string $status): bool => $status !== AttendanceStatus::Present->value),
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
        foreach ($this->students as $student) {
            $this->attendanceStatuses[$student['id']] = AttendanceStatus::Present->value;
        }

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

        $schedule = Schedule::query()->find($this->selectedScheduleId);

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
                'remarks' => null,
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

        return Schedule::query()
            ->forTeacher($user->id)
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
        ]);
    }
}
