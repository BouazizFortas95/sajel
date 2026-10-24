<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SchoolSupervisionService
{
    /**
     * @return array{
     *     date: Carbon,
     *     metrics: array<string, int>,
     *     complianceSummary: array{total_scheduled_sessions: int, completed_sessions: int, overdue_unlogged_sessions: int, percentage: int},
     *     sessions: array<int, array<string, mixed>>,
     *     overdueSessions: array<int, array<string, mixed>>,
     *     absences: Collection<int, Attendance>,
     *     attendanceAlerts: Collection<int, Attendance>,
     *     repeatAbsentees: array<int, array{student: Student, absence_count: int}>,
     *     observations: Collection<int, BehaviorObservation>,
     *     pendingBehaviorCases: Collection<int, BehaviorObservation>,
     *     week: array{due: int, completed: int, overdue: int, compliance: int}
     * }
     */
    public function overview(
        string $date,
        ?int $classroomId = null,
        ?int $levelId = null,
    ): array {
        $timezone = config('app.timezone', 'Africa/Algiers');
        $selectedDate = Carbon::parse($date, $timezone)->startOfDay();
        $today = now($timezone)->startOfDay();
        $now = now($timezone);
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);
        $days = collect(range(0, 4))
            ->map(fn (int $offset): string => DayOfWeek::cases()[$weekStart->copy()->addDays($offset)->dayOfWeek]->value)
            ->unique()
            ->values()
            ->all();

        $schedules = Schedule::query()
            ->with([
                'user',
                'subject',
                'classroom.level',
                'group.classroom.level',
                'dailyLogs' => fn ($query) => $query
                    ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]),
            ])
            ->whereIn('day_of_week', $days)
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $this->whereClassroom($query, $classroomId),
            )
            ->when(
                $levelId,
                fn (Builder $query): Builder => $this->whereLevel($query, $levelId),
            )
            ->orderBy('start_time')
            ->get();

        $selectedDay = DayOfWeek::cases()[$selectedDate->dayOfWeek]->value;

        if (SchoolHoliday::isHoliday($selectedDate)) {
            $holiday = \App\Models\SchoolHoliday::whereDate('start_date', '<=', $selectedDate)
                ->whereDate('end_date', '>=', $selectedDate)
                ->first();
            return [
                'date' => $selectedDate,
                'metrics' => [
                    'daily_compliance_percentage' => 0,
                    'scheduled_sessions_count' => 0,
                    'completed_sessions_count' => 0,
                    'due_sessions_count' => 0,
                    'overdue_sessions_count' => 0,
                    'attendance_alerts_count' => 0,
                    'open_behavior_count' => 0,
                ],
                'complianceSummary' => [
                    'total_scheduled_sessions' => 0,
                    'completed_sessions' => 0,
                    'overdue_unlogged_sessions' => 0,
                    'percentage' => 0,
                ],
                'holiday' => true,
                'holidayName' => $holiday?->title ?? '—',
                'sessions' => [],
                'overdueSessions' => [],
                'absences' => collect(),
                'attendanceAlerts' => collect(),
                'repeatAbsentees' => [],
                'observations' => collect(),
                'pendingBehaviorCases' => collect(),
                'week' => ['due' => 0, 'completed' => 0, 'overdue' => 0, 'compliance' => 0],
            ];
        }

        $dailySessions = $schedules
            ->filter(fn (Schedule $schedule): bool => $schedule->day_of_week?->value === $selectedDay)
            ->map(fn (Schedule $schedule): array => $this->sessionRow($schedule, $selectedDate, $now, $today))
            ->values()
            ->all();

        $activeSessions = collect($dailySessions)
            ->filter(fn (array $session): bool => $session['status'] !== DailyLogStatus::Cancelled->value);
        $dueSessions = $activeSessions
            ->filter(fn (array $session): bool => $session['due'] && $session['status'] !== DailyLogStatus::Cancelled->value);
        $completedSessions = $activeSessions
            ->filter(fn (array $session): bool => $session['status'] === DailyLogStatus::Completed->value)
            ->count();
        $overdueSessions = $dueSessions
            ->filter(fn (array $session): bool => in_array($session['status'], ['missing', DailyLogStatus::Planned->value], true));

        $weekly = $this->weeklyMetrics($schedules, $weekStart, $today, $now);
        $absences = $this->absencesForDate($selectedDate, $classroomId, $levelId);
        $repeatAbsentees = $this->repeatAbsentees($selectedDate, $classroomId, $levelId);
        $openBehaviorCount = $this->behaviorQuery($selectedDate, $classroomId, $levelId)->count();
        $observations = $this->behaviorQuery($selectedDate, $classroomId, $levelId)
            ->with(['student.group.classroom.level', 'recorder', 'schedule.user', 'schedule.subject'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
        $scheduledCount = $activeSessions->count();
        $overdueRows = $overdueSessions->values()->all();

        return [
            'date' => $selectedDate,
            'metrics' => [
                'daily_compliance_percentage' => $scheduledCount === 0
                    ? 0
                    : (int) round($completedSessions / $scheduledCount * 100),
                'scheduled_sessions_count' => $scheduledCount,
                'completed_sessions_count' => $completedSessions,
                'due_sessions_count' => $dueSessions->count(),
                'overdue_sessions_count' => count($overdueRows),
                'attendance_alerts_count' => $absences->count(),
                'open_behavior_count' => $openBehaviorCount,
            ],
            'complianceSummary' => [
                'total_scheduled_sessions' => $scheduledCount,
                'completed_sessions' => $completedSessions,
                'overdue_unlogged_sessions' => count($overdueRows),
                'percentage' => $scheduledCount === 0
                    ? 0
                    : (int) round($completedSessions / $scheduledCount * 100),
            ],
            'sessions' => $dailySessions,
            'overdueSessions' => $overdueRows,
            'absences' => $absences,
            'attendanceAlerts' => $absences,
            'repeatAbsentees' => $repeatAbsentees,
            'observations' => $observations,
            'pendingBehaviorCases' => $observations,
            'week' => $weekly,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRow(Schedule $schedule, Carbon $date, Carbon $now, Carbon $today): array
    {
        $log = $schedule->dailyLogs->first(
            fn (DailyLog $dailyLog): bool => $dailyLog->date->isSameDay($date),
        );
        $end = $schedule->end_time;
        $due = $date->lt($today)
            || ($date->isSameDay($today) && $end !== null && $date->copy()->setTime($end->hour, $end->minute)->lte($now));

        return [
            'schedule' => $schedule,
            'log' => $log,
            'date' => $date->copy(),
            'due' => $due,
            'overdue' => $due && (! $log || $log->status === DailyLogStatus::Planned),
            'status' => $log?->status?->value ?? 'missing',
        ];
    }

    /**
     * @param  Collection<int, Schedule>  $schedules
     * @return array{due: int, completed: int, overdue: int, compliance: int}
     */
    private function weeklyMetrics(
        Collection $schedules,
        Carbon $weekStart,
        Carbon $today,
        Carbon $now,
    ): array {
        $due = 0;
        $completed = 0;
        $overdue = 0;

        foreach (range(0, 6) as $offset) {
            $date = $weekStart->copy()->addDays($offset);
            if (SchoolHoliday::isHoliday($date)) {
                continue;
            }
            $day = DayOfWeek::cases()[$date->dayOfWeek]->value;

            foreach ($schedules->filter(fn (Schedule $schedule): bool => $schedule->day_of_week?->value === $day) as $schedule) {
                $session = $this->sessionRow($schedule, $date, $now, $today);

                if (! $session['due'] || $session['status'] === DailyLogStatus::Cancelled->value) {
                    continue;
                }

                $due++;

                if ($session['status'] === DailyLogStatus::Completed->value) {
                    $completed++;
                } elseif ($session['overdue']) {
                    $overdue++;
                }
            }
        }

        return [
            'due' => $due,
            'completed' => $completed,
            'overdue' => $overdue,
            'compliance' => $due === 0 ? 0 : (int) round($completed / $due * 100),
        ];
    }

    /**
     * @return Collection<int, Attendance>
     */
    private function absencesForDate(Carbon $date, ?int $classroomId, ?int $levelId): Collection
    {
        return Attendance::query()
            ->with([
                'student.group.classroom.level',
                'dailyLog.schedule.subject',
                'dailyLog.schedule.user',
            ])
            ->whereIn('status', [AttendanceStatus::Absent->value, AttendanceStatus::Late->value])
            ->whereHas('dailyLog', fn (Builder $query): Builder => $query->whereDate('date', $date))
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $this->whereStudentClassroom($query, $classroomId),
            )
            ->when(
                $levelId,
                fn (Builder $query): Builder => $this->whereStudentLevel($query, $levelId),
            )
            ->orderBy('status')
            ->get();
    }

    /**
     * @return array<int, array{student: Student, absence_count: int}>
     */
    private function repeatAbsentees(Carbon $date, ?int $classroomId, ?int $levelId): array
    {
        $absenceCounts = Attendance::query()
            ->select('attendances.student_id')
            ->selectRaw('COUNT(DISTINCT daily_logs.date) as absence_count')
            ->join('daily_logs', 'daily_logs.id', '=', 'attendances.daily_log_id')
            ->where('attendances.status', AttendanceStatus::Absent->value)
            ->whereDate('daily_logs.date', '>=', $date->copy()->subDays(29)->toDateString())
            ->whereDate('daily_logs.date', '<=', $date->toDateString())
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $this->whereStudentClassroom($query, $classroomId),
            )
            ->when(
                $levelId,
                fn (Builder $query): Builder => $this->whereStudentLevel($query, $levelId),
            )
            ->groupBy('attendances.student_id')
            ->havingRaw('COUNT(DISTINCT daily_logs.date) >= 3')
            ->orderByDesc('absence_count')
            ->limit(100)
            ->get();

        $students = Student::query()
            ->with(['group.classroom.level'])
            ->whereKey($absenceCounts->pluck('student_id'))
            ->get()
            ->keyBy('id');

        return $absenceCounts
            ->map(fn (Attendance $attendance): ?array => $students->has($attendance->student_id)
                ? [
                    'student' => $students->get($attendance->student_id),
                    'absence_count' => (int) $attendance->absence_count,
                ]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    private function behaviorQuery(Carbon $date, ?int $classroomId, ?int $levelId): Builder
    {
        return BehaviorObservation::query()
            ->whereDate('date', '>=', $date->copy()->subDays(29)->toDateString())
            ->whereDate('date', '<=', $date->toDateString())
            ->where('is_followed_up', false)
            ->whereIn('category', ['negative', 'disciplinary', 'observation'])
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $query->whereHas(
                    'student.group',
                    fn (Builder $query): Builder => $query->where('classroom_id', $classroomId),
                ),
            )
            ->when(
                $levelId,
                fn (Builder $query): Builder => $query->whereHas(
                    'student.group.classroom',
                    fn (Builder $query): Builder => $query->where('level_id', $levelId),
                ),
            );
    }

    private function whereClassroom(Builder $query, int $classroomId): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->where('classroom_id', $classroomId)
            ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroomId)));
    }

    private function whereLevel(Builder $query, int $levelId): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->whereHas('classroom', fn (Builder $query): Builder => $query->where('level_id', $levelId))
            ->orWhereHas('group.classroom', fn (Builder $query): Builder => $query->where('level_id', $levelId)));
    }

    private function whereStudentClassroom(Builder $query, int $classroomId): Builder
    {
        return $query->whereHas(
            'student.group',
            fn (Builder $query): Builder => $query->where('classroom_id', $classroomId),
        );
    }

    private function whereStudentLevel(Builder $query, int $levelId): Builder
    {
        return $query->whereHas(
            'student.group.classroom',
            fn (Builder $query): Builder => $query->where('level_id', $levelId),
        );
    }
}
