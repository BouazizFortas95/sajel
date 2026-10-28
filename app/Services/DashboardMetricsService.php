<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Server-side, role-scoped data for the dashboard tabs.
 *
 * Every method starts from schedules or daily logs owned by the current user
 * (teachers) or from the whole school (administrators). Filters supplied by
 * the browser never widen that base scope; they only narrow it.
 *
 * Periods use the application's school timezone (Africa/Algiers by default):
 * - today: the current calendar day;
 * - week: Sunday through Saturday of the current week;
 * - month: the current calendar month.
 *
 * "Missing" is not a daily-log status. It describes a scheduled session whose
 * end time has passed without a log. "Not recorded" attendance describes a
 * due session whose log has no attendance rows. Neither is counted as absent.
 */
class DashboardMetricsService
{
    public function __construct(
        private PendingSessionService $pendingSessions,
    ) {}

    public function timezone(): string
    {
        return config('app.timezone', 'Africa/Algiers');
    }

    public function now(): Carbon
    {
        return now($this->timezone());
    }

    public function today(): Carbon
    {
        return $this->now()->startOfDay();
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public function periodBounds(string $period): array
    {
        $today = $this->today();

        return match ($period) {
            'week' => [
                'start' => $today->copy()->startOfWeek(Carbon::SUNDAY),
                'end' => $today->copy()->endOfWeek(Carbon::SATURDAY),
            ],
            'month' => [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfMonth(),
            ],
            default => [
                'start' => $today->copy(),
                'end' => $today->copy(),
            ],
        };
    }

    /**
     * Filter options are limited to schedules the user can actually see.
     * Administrators see every scheduled classroom and subject; teachers see
     * only their own timetable.
     *
     * @return array{
     *     classrooms: array<int, string>,
     *     subjects: array<int, string>
     * }
     */
    public function filterOptions(User $user): array
    {
        $base = Schedule::query()
            ->when($user->isTeacher(), fn (Builder $query): Builder => $query->forTeacher($user));

        $subjectIds = (clone $base)
            ->whereNotNull('subject_id')
            ->distinct()
            ->pluck('subject_id');

        $directClassroomIds = (clone $base)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id');

        $groupClassroomIds = (clone $base)
            ->whereNotNull('group_id')
            ->join('groups', 'groups.id', '=', 'schedules.group_id')
            ->distinct()
            ->pluck('groups.classroom_id');

        $classroomIds = $directClassroomIds
            ->merge($groupClassroomIds)
            ->filter()
            ->unique()
            ->values();

        $classrooms = $classroomIds->isEmpty()
            ? []
            : Classroom::query()
                ->with('level')
                ->whereKey($classroomIds->all())
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Classroom $classroom): array => [
                    $classroom->id => $classroom->display_name(),
                ])
                ->all();

        $subjects = $subjectIds->isEmpty()
            ? []
            : Subject::query()
                ->whereKey($subjectIds->all())
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

        return [
            'classrooms' => $classrooms,
            'subjects' => $subjects,
        ];
    }

    public function classroomIsVisible(User $user, int $classroomId): bool
    {
        return array_key_exists($classroomId, $this->filterOptions($user)['classrooms']);
    }

    public function subjectIsVisible(User $user, int $subjectId): bool
    {
        return array_key_exists($subjectId, $this->filterOptions($user)['subjects']);
    }

    /**
     * Default tab: today's operational snapshot for the user's scope.
     *
     * @return array<string, mixed>
     */
    public function overview(
        User $user,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        $today = $this->today();
        $holiday = $this->holidayOn($today);

        $statusCounts = $this->dailyLogQuery($user, $today, $today, $classroomId, $subjectId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $sessionsToday = $holiday
            ? 0
            : $this->scheduleQuery($user, $classroomId, $subjectId)
                ->onDay(DayOfWeek::cases()[$today->dayOfWeek])
                ->count();

        $pending = $this->pendingSessions->forUser($user, $classroomId, $subjectId);

        return [
            'holiday' => $holiday,
            'sessions_today' => $sessionsToday,
            'logs_today' => (int) array_sum($statusCounts),
            'completed_today' => (int) ($statusCounts[DailyLogStatus::Completed->value] ?? 0),
            'planned_today' => (int) ($statusCounts[DailyLogStatus::Planned->value] ?? 0),
            'cancelled_today' => (int) ($statusCounts[DailyLogStatus::Cancelled->value] ?? 0),
            'weekly_sessions' => $this->scheduleQuery($user, $classroomId, $subjectId)->count(),
            'subjects_count' => $this->scheduleQuery($user, $classroomId, $subjectId)
                ->distinct()
                ->count('subject_id'),
            'pending_count' => $pending->count(),
            'today_pending_count' => $pending
                ->filter(fn (array $task): bool => $task['date']->isSameDay($today))
                ->count(),
            'attention' => $pending->take(3)->values(),
            'attendance' => $this->dueSessionSummary($user, $today, $today, $classroomId, $subjectId),
            'next_lesson' => $holiday
                ? null
                : $this->nextLesson($user, $classroomId, $subjectId),
            'latest_log' => $this->latestLogForToday($user, $classroomId, $subjectId),
            'today' => $today,
        ];
    }

    /**
     * Weekly timetable summary. Teachers see their own recurring sessions
     * grouped by school day; administrators see aggregate per-day counts for
     * the selected classroom/subject.
     *
     * @return array{
     *     days: list<array{day: DayOfWeek, sessions: int, teachers: int}>,
     *     total_sessions: int,
     *     next_lesson: Schedule|null,
     *     holiday: bool
     * }
     */
    public function schedule(User $user, ?int $classroomId = null, ?int $subjectId = null): array
    {
        $base = $this->scheduleQuery($user, $classroomId, $subjectId);
        $counts = (clone $base)
            ->selectRaw('day_of_week, COUNT(*) as sessions, COUNT(DISTINCT user_id) as teachers')
            ->groupBy('day_of_week')
            ->get()
            ->keyBy(fn ($row): string => $row->day_of_week instanceof DayOfWeek
                ? $row->day_of_week->value
                : (string) $row->day_of_week);

        $days = [];

        foreach (DayOfWeek::cases() as $day) {
            $row = $counts->get($day->value);
            $days[] = [
                'day' => $day,
                'sessions' => (int) ($row->sessions ?? 0),
                'teachers' => (int) ($row->teachers ?? 0),
            ];
        }

        return [
            'days' => $days,
            'total_sessions' => array_sum(array_column($days, 'sessions')),
            'subjects_count' => (clone $base)->distinct()->count('subject_id'),
            'next_lesson' => $this->nextLesson($user, $classroomId, $subjectId),
            'today_sessions' => $user->isTeacher()
                ? $this->scheduleQuery($user, $classroomId, $subjectId)
                    ->with(['subject', 'classroom.level', 'group.classroom.level'])
                    ->onDay(DayOfWeek::cases()[$this->today()->dayOfWeek])
                    ->orderBy('start_time')
                    ->get()
                : collect(),
            'holiday' => $this->holidayOn($this->today()),
        ];
    }

    /**
     * Attendance aggregates for the selected period.
     *
     * - due_sessions: non-cancelled scheduled occurrences whose end time has
     *   passed in the period, after excluding school holidays;
     * - recorded_sessions: due sessions whose log is completed and has at
     *   least one attendance row;
     * - not_recorded_sessions: due_sessions minus recorded_sessions. This is
     *   neither "present" nor "absent";
     * - status counts cover attendance rows in the period from completed logs
     *   whose session has already ended (recorded data only);
     * - repeat_absentees counts distinct students with >= 3 distinct absence
     *   dates inside the selected period.
     *
     * @return array<string, mixed>
     */
    public function attendance(
        User $user,
        string $period,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        ['start' => $start, 'end' => $end] = $this->periodBounds($period);
        $due = $this->dueSessionSummary($user, $start, $end, $classroomId, $subjectId);

        $statusCounts = $this->attendanceQuery($user, $start, $end, $classroomId, $subjectId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $rows = [];

        foreach (AttendanceStatus::cases() as $status) {
            $rows[$status->value] = (int) ($statusCounts[$status->value] ?? 0);
        }

        return [
            ...$due,
            'status_counts' => $rows,
            'attendance_rows' => array_sum($rows),
            'absence_entries' => $rows[AttendanceStatus::Absent->value],
            'repeat_absentees' => $this->repeatAbsentees($user, $start, $end, $classroomId, $subjectId),
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Lesson/register counts for the selected period plus the week-to-date
     * missing/planned queue that the reminder widget uses.
     *
     * @return array<string, mixed>
     */
    public function lessons(
        User $user,
        string $period,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        ['start' => $start, 'end' => $end] = $this->periodBounds($period);

        $statusCounts = $this->dailyLogQuery($user, $start, $end, $classroomId, $subjectId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $pending = $this->pendingSessions->forUser($user, $classroomId, $subjectId);

        return [
            'planned' => (int) ($statusCounts[DailyLogStatus::Planned->value] ?? 0),
            'completed' => (int) ($statusCounts[DailyLogStatus::Completed->value] ?? 0),
            'cancelled' => (int) ($statusCounts[DailyLogStatus::Cancelled->value] ?? 0),
            'total' => (int) array_sum($statusCounts),
            'pending_count' => $pending->count(),
            'missing_count' => $pending->where('state', 'missing')->count(),
            'awaiting_completion_count' => $pending->where('state', 'planned')->count(),
            'pending' => $pending,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Assessment and grade-entry summary.
     *
     * Grade entry is deliberately reported as entered rows / existing grade
     * rows, plus the number of assessments whose gradebook has not been
     * materialized. No average is shown because assessments can use different
     * maximum scores.
     *
     * @return array<string, mixed>
     */
    public function assessments(
        User $user,
        string $period,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        ['start' => $start, 'end' => $end] = $this->periodBounds($period);
        $today = $this->today();
        $pastEnd = $end->lt($today) ? $end : $today;

        $base = $this->assessmentQuery($user, $classroomId, $subjectId);

        $upcoming = (clone $base)
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group.classroom'])
            ->whereDate('date', '>', $today->toDateString())
            ->whereDate('date', '<=', $today->copy()->addDays(14)->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $recentCount = (clone $base)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $pastEnd->toDateString())
            ->count();

        $assessmentIds = (clone $base)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $pastEnd->toDateString())
            ->pluck('id');

        $gradeRows = 0;
        $scoredRows = 0;
        $startedAssessments = 0;
        $assessmentsWithMissingScores = 0;

        if ($assessmentIds->isNotEmpty()) {
            $aggregate = DB::table('grades')
                ->whereIn('assessment_id', $assessmentIds)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN score IS NOT NULL THEN 1 ELSE 0 END) as scored, COUNT(DISTINCT assessment_id) as started')
                ->first();

            $gradeRows = (int) ($aggregate->total ?? 0);
            $scoredRows = (int) ($aggregate->scored ?? 0);
            $startedAssessments = (int) ($aggregate->started ?? 0);

            $assessmentsWithMissingScores = DB::table('grades')
                ->whereIn('assessment_id', $assessmentIds)
                ->select('assessment_id')
                ->groupBy('assessment_id')
                ->havingRaw('SUM(CASE WHEN score IS NULL THEN 1 ELSE 0 END) > 0')
                ->get()
                ->count();
        }

        return [
            'upcoming' => $upcoming,
            'upcoming_count' => (clone $base)
                ->whereDate('date', '>', $today->toDateString())
                ->whereDate('date', '<=', $today->copy()->addDays(14)->toDateString())
                ->count(),
            'recent_count' => $recentCount,
            'not_started_count' => max(0, $recentCount - $startedAssessments),
            'grade_rows' => $gradeRows,
            'scored_rows' => $scoredRows,
            'assessments_with_missing_scores' => $assessmentsWithMissingScores,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Curriculum progress is delegated to CurriculumProgressService so the
     * dashboard cannot drift from the detailed progress screen.
     *
     * @return array{
     *     rows: Collection<int, array<string, mixed>>,
     *     total_resources: int,
     *     completed_resources: int,
     *     percentage: int|null,
     *     row_count: int,
     *     as_of: Carbon
     * }
     */
    public function curriculum(
        User $user,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        $rows = app(CurriculumProgressService::class)
            ->overview($user)
            ->when(
                $classroomId,
                fn (Collection $rows): Collection => $rows->where(
                    fn (array $row): bool => $row['classroom']->id === $classroomId,
                ),
            )
            ->when(
                $subjectId,
                fn (Collection $rows): Collection => $rows->where(
                    fn (array $row): bool => $row['subject']->id === $subjectId,
                ),
            )
            ->sortBy(fn (array $row): string => sprintf(
                '%03d|%s|%s',
                $row['percentage'],
                $row['classroom']->name,
                $row['subject']->name,
            ))
            ->values();

        $totalResources = (int) $rows->sum('total_resources');
        $completedResources = (int) $rows->sum('completed_resources');

        return [
            'rows' => $rows->take(6)->values(),
            'total_resources' => $totalResources,
            'completed_resources' => $completedResources,
            'percentage' => $totalResources > 0
                ? (int) round($completedResources / $totalResources * 100)
                : null,
            'row_count' => $rows->count(),
            'as_of' => $this->now(),
        ];
    }

    /**
     * Aggregate student-support counts. No student names cross this boundary.
     * Teachers only see concerns attached to their own schedules or recorded
     * by them; administrators see school-wide aggregates.
     *
     * @return array{
     *     behavior_total: int,
     *     positive_count: int,
     *     open_concerns: int,
     *     remediation_total: int,
     *     open_remediations: int,
     *     start: Carbon,
     *     end: Carbon
     * }
     */
    public function support(
        User $user,
        string $period,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): array {
        ['start' => $start, 'end' => $end] = $this->periodBounds($period);

        $behavior = $this->behaviorQuery($user, $classroomId, $subjectId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN category = 'positive' THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN is_followed_up = 0 AND category IN ('negative', 'disciplinary', 'observation') THEN 1 ELSE 0 END) as open_concerns")
            ->first();

        $remediation = $this->remediationQuery($user, $classroomId, $subjectId)
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status IN ('planned', 'ongoing') THEN 1 ELSE 0 END) as open")
            ->first();

        return [
            'behavior_total' => (int) ($behavior->total ?? 0),
            'positive_count' => (int) ($behavior->positive ?? 0),
            'open_concerns' => (int) ($behavior->open_concerns ?? 0),
            'remediation_total' => (int) ($remediation->total ?? 0),
            'open_remediations' => (int) ($remediation->open ?? 0),
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Administrative context only. The component hides this tab from
     * teachers, and this guard protects direct service calls as well.
     *
     * @return array{
     *     teachers: int,
     *     students: int,
     *     classrooms: int,
     *     subjects: int,
     *     holidays: Collection<int, SchoolHoliday>,
     *     today: Carbon
     * }
     */
    public function institutional(User $user): array
    {
        abort_unless($user->isAdmin(), 403);

        return [
            'teachers' => User::query()->where('role', 'teacher')->count(),
            'students' => Student::query()->count(),
            'classrooms' => Classroom::query()->count(),
            'subjects' => Subject::query()->count(),
            'holidays' => SchoolHoliday::query()
                ->whereDate('end_date', '>=', $this->today()->toDateString())
                ->orderBy('start_date')
                ->limit(5)
                ->get(),
            'today' => $this->today(),
        ];
    }

    public function latestLogForToday(
        User $user,
        ?int $classroomId = null,
        ?int $subjectId = null,
    ): ?DailyLog {
        $today = $this->today();

        return $this->dailyLogQuery($user, $today, $today, $classroomId, $subjectId)
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group.classroom'])
            ->latest('updated_at')
            ->first();
    }

    /**
     * @return Builder<Schedule>
     */
    private function scheduleQuery(
        User $user,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $query = Schedule::query();

        if ($user->isTeacher()) {
            $query->forTeacher($user);
        }

        return $this->applyScheduleFilters($query, $classroomId, $subjectId);
    }

    /**
     * @return Builder<DailyLog>
     */
    private function dailyLogQuery(
        User $user,
        Carbon $start,
        Carbon $end,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $query = DailyLog::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString());

        if ($user->isTeacher()) {
            $query->forTeacher($user);
        }

        if ($classroomId !== null || $subjectId !== null) {
            $query->whereHas(
                'schedule',
                fn (Builder $query): Builder => $this->applyScheduleFilters($query, $classroomId, $subjectId),
            );
        }

        return $query;
    }

    /**
     * @return Builder<Attendance>
     */
    private function attendanceQuery(
        User $user,
        Carbon $start,
        Carbon $end,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $lastDate = $end->copy()->startOfDay()->min($this->today());

        return Attendance::query()
            ->whereHas('dailyLog', function (Builder $query) use ($user, $start, $lastDate, $classroomId, $subjectId): void {
                $query->where('status', DailyLogStatus::Completed->value)
                    ->whereDate('date', '>=', $start->toDateString())
                    ->whereDate('date', '<=', $lastDate->toDateString());

                $this->whereSessionHasEnded($query, $lastDate);

                if ($user->isTeacher()) {
                    $query->forTeacher($user);
                }

                if ($classroomId !== null || $subjectId !== null) {
                    $query->whereHas(
                        'schedule',
                        fn (Builder $query): Builder => $this->applyScheduleFilters($query, $classroomId, $subjectId),
                    );
                }
            });
    }

    /**
     * Restrict a daily-log query to sessions that have already ended. Dates
     * before today have ended; today's session is included only after its
     * timetable end time, so pre-filled future registers do not inflate
     * "recorded" attendance aggregates.
     */
    private function whereSessionHasEnded(Builder $query, Carbon $lastDate): void
    {
        $today = $this->today();

        if (! $lastDate->isSameDay($today)) {
            return;
        }

        $now = $this->now()->format('H:i:s');

        $query->where(function (Builder $query) use ($today, $now): void {
            $query->whereDate('date', '<', $today->toDateString())
                ->orWhere(function (Builder $query) use ($today, $now): void {
                    $query->whereDate('date', $today->toDateString())
                        ->whereHas(
                            'schedule',
                            fn (Builder $query): Builder => $query->where('end_time', '<=', $now),
                        );
                });
        });
    }

    /**
     * @return Builder<Assessment>
     */
    private function assessmentQuery(
        User $user,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $query = Assessment::query();

        if ($user->isTeacher()) {
            $query->whereHas('schedule', fn (Builder $query): Builder => $query->forTeacher($user));
        }

        if ($classroomId !== null || $subjectId !== null) {
            $query->whereHas(
                'schedule',
                fn (Builder $query): Builder => $this->applyScheduleFilters($query, $classroomId, $subjectId),
            );
        }

        return $query;
    }

    /**
     * @return Builder<BehaviorObservation>
     */
    private function behaviorQuery(
        User $user,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $query = BehaviorObservation::query();

        if ($user->isTeacher()) {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereHas('schedule', fn (Builder $query): Builder => $query->forTeacher($user))
                    ->orWhere('recorded_by_id', $user->id);
            });
        }

        if ($classroomId !== null || $subjectId !== null) {
            $query->whereHas(
                'schedule',
                fn (Builder $query): Builder => $this->applyScheduleFilters($query, $classroomId, $subjectId),
            );
        }

        return $query;
    }

    /**
     * @return Builder<PedagogicalRemediation>
     */
    private function remediationQuery(
        User $user,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        $query = PedagogicalRemediation::query();

        if ($user->isTeacher()) {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereHas('schedule', fn (Builder $query): Builder => $query->forTeacher($user))
                    ->orWhere('recorded_by_id', $user->id);
            });
        }

        if ($classroomId !== null || $subjectId !== null) {
            $query->whereHas(
                'schedule',
                fn (Builder $query): Builder => $this->applyScheduleFilters($query, $classroomId, $subjectId),
            );
        }

        return $query;
    }

    private function applyScheduleFilters(
        Builder $query,
        ?int $classroomId,
        ?int $subjectId,
    ): Builder {
        return $query
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('classroom_id', $classroomId)
                        ->orWhereHas(
                            'group',
                            fn (Builder $query): Builder => $query->where('classroom_id', $classroomId),
                        ),
                ),
            )
            ->when($subjectId, fn (Builder $query): Builder => $query->where('subject_id', $subjectId));
    }

    /**
     * Count due, recorded, and not-recorded sessions. Due sessions are
     * non-cancelled timetable occurrences whose lesson end time has passed.
     * Holidays are excluded and no future session is considered due.
     *
     * @return array{
     *     due_sessions: int,
     *     recorded_sessions: int,
     *     not_recorded_sessions: int,
     *     recorded_percentage: int|null
     * }
     */
    private function dueSessionSummary(
        User $user,
        Carbon $start,
        Carbon $end,
        ?int $classroomId,
        ?int $subjectId,
    ): array {
        $today = $this->today();
        $now = $this->now();
        $lastDate = $end->copy()->startOfDay()->min($today);

        if ($start->copy()->startOfDay()->gt($lastDate)) {
            return $this->emptyDueSummary();
        }

        $holidayDates = array_flip(SchoolHoliday::holidayDatesBetween($start, $lastDate));
        $datesByDay = [];

        for ($date = $start->copy()->startOfDay(); $date->lte($lastDate); $date->addDay()) {
            if (isset($holidayDates[$date->toDateString()])) {
                continue;
            }

            $datesByDay[DayOfWeek::cases()[$date->dayOfWeek]->value][] = $date->copy();
        }

        if ($datesByDay === []) {
            return $this->emptyDueSummary();
        }

        $schedules = $this->scheduleQuery($user, $classroomId, $subjectId)
            ->with([
                'dailyLogs' => fn ($query) => $query
                    ->whereDate('date', '>=', $start->toDateString())
                    ->whereDate('date', '<=', $lastDate->toDateString())
                    ->withCount('attendances'),
            ])
            ->whereIn('day_of_week', array_keys($datesByDay))
            ->get();

        $due = 0;
        $recorded = 0;

        foreach ($schedules as $schedule) {
            $day = $schedule->day_of_week;
            $endTime = $schedule->end_time;

            if (! $day || ! $endTime) {
                continue;
            }

            $logsByDate = $schedule->dailyLogs->keyBy(
                fn (DailyLog $log): string => $log->date->toDateString(),
            );

            foreach ($datesByDay[$day->value] ?? [] as $date) {
                $endsAt = $date->copy()->setTime($endTime->hour, $endTime->minute);

                if ($date->isSameDay($today) && $endsAt->gt($now)) {
                    continue;
                }

                $log = $logsByDate->get($date->toDateString());

                if ($log?->status === DailyLogStatus::Cancelled) {
                    continue;
                }

                $due++;

                if (
                    $log?->status === DailyLogStatus::Completed
                    && (int) ($log->attendances_count ?? 0) > 0
                ) {
                    $recorded++;
                }
            }
        }

        return [
            'due_sessions' => $due,
            'recorded_sessions' => $recorded,
            'not_recorded_sessions' => max(0, $due - $recorded),
            'recorded_percentage' => $due > 0
                ? (int) round($recorded / $due * 100)
                : null,
        ];
    }

    /**
     * @return array{
     *     due_sessions: int,
     *     recorded_sessions: int,
     *     not_recorded_sessions: int,
     *     recorded_percentage: int|null
     * }
     */
    private function emptyDueSummary(): array
    {
        return [
            'due_sessions' => 0,
            'recorded_sessions' => 0,
            'not_recorded_sessions' => 0,
            'recorded_percentage' => null,
        ];
    }

    private function nextLesson(
        User $user,
        ?int $classroomId,
        ?int $subjectId,
    ): ?Schedule {
        $today = $this->today();

        if ($this->holidayOn($today)) {
            return null;
        }

        return $this->scheduleQuery($user, $classroomId, $subjectId)
            ->with(['subject', 'classroom.level', 'group.classroom.level'])
            ->onDay(DayOfWeek::cases()[$today->dayOfWeek])
            ->where('end_time', '>', $this->now()->format('H:i'))
            ->orderBy('start_time')
            ->first();
    }

    private function holidayOn(Carbon $date): ?SchoolHoliday
    {
        return SchoolHoliday::query()
            ->coversDate($date)
            ->orderBy('start_date')
            ->first();
    }

    private function repeatAbsentees(
        User $user,
        Carbon $start,
        Carbon $end,
        ?int $classroomId,
        ?int $subjectId,
    ): int {
        $lastDate = $end->copy()->startOfDay()->min($this->today());

        $query = DB::table('attendances')
            ->join('daily_logs', 'daily_logs.id', '=', 'attendances.daily_log_id')
            ->join('schedules', 'schedules.id', '=', 'daily_logs.schedule_id')
            ->leftJoin('groups', 'groups.id', '=', 'schedules.group_id')
            ->where('attendances.status', AttendanceStatus::Absent->value)
            ->where('daily_logs.status', DailyLogStatus::Completed->value)
            ->whereDate('daily_logs.date', '>=', $start->toDateString())
            ->whereDate('daily_logs.date', '<=', $lastDate->toDateString())
            ->when(
                $user->isTeacher(),
                fn ($query) => $query->where(
                    fn ($query) => $query
                        ->where('schedules.user_id', $user->id)
                        ->orWhere('daily_logs.substitute_teacher_id', $user->id),
                ),
            )
            ->when(
                $classroomId,
                fn ($query) => $query->where(
                    fn ($query) => $query
                        ->where('schedules.classroom_id', $classroomId)
                        ->orWhere('groups.classroom_id', $classroomId),
                ),
            )
            ->when($subjectId, fn ($query) => $query->where('schedules.subject_id', $subjectId))
            ->groupBy('attendances.student_id')
            ->havingRaw('COUNT(DISTINCT daily_logs.date) >= 3');

        if ($lastDate->isSameDay($this->today())) {
            $today = $this->today()->toDateString();
            $now = $this->now()->format('H:i:s');

            $query->where(function ($query) use ($today, $now): void {
                $query->whereDate('daily_logs.date', '<', $today)
                    ->orWhere(
                        fn ($query) => $query
                            ->whereDate('daily_logs.date', $today)
                            ->where('schedules.end_time', '<=', $now),
                    );
            });
        }

        return DB::query()->fromSub($query, 'repeat_absentees')->count();
    }
}
