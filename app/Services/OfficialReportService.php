<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the official weekly timetable, consolidated attendance summary, and
 * grade transcript, then renders any of them to an A4 PDF when requested.
 *
 * Every report is derived from the schedules visible to the viewing teacher,
 * so a direct URL cannot expose another teacher's classes. Admins keep the
 * unrestricted school-wide view.
 */
class OfficialReportService
{
    /**
     * @return array<string, mixed>
     */
    public function timetableViewData(
        User $viewer,
        ?int $teacherId = null,
        ?int $classroomId = null,
        bool $autoPrint = true,
        bool $inlineCss = false,
    ): array {
        $teacher = $teacherId !== null
            ? User::query()->findOrFail($teacherId)
            : null;

        if ($viewer->isTeacher()) {
            abort_unless($teacher === null || $teacher->id === $viewer->id, 403);
            $teacher = $viewer;
        }

        $classroom = $classroomId !== null
            ? Classroom::query()->with('level.educationPhase')->findOrFail($classroomId)
            : null;

        if ($viewer->isTeacher() && $classroom !== null) {
            abort_unless($this->teacherTeachesClassroom($viewer, $classroom->id), 403);
        }

        $schedules = Schedule::query()
            ->with([
                'user',
                'subject',
                'classroom.level.educationPhase',
                'group.classroom.level.educationPhase',
            ])
            ->when($teacher !== null, fn (Builder $query): Builder => $query->where('user_id', $teacher->id))
            ->when($classroom !== null, function (Builder $query) use ($classroom): void {
                $query->where(function (Builder $query) use ($classroom): void {
                    $query->where('classroom_id', $classroom->id)
                        ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroom->id));
                });
            })
            ->when($viewer->isTeacher(), fn (Builder $query): Builder => $query->where('user_id', $viewer->id))
            ->get()
            ->sortBy(fn (Schedule $schedule): string => sprintf(
                '%02d|%s|%s|%s',
                $this->dayIndex($schedule->day_of_week),
                $schedule->start_time?->format('H:i') ?? '',
                $schedule->end_time?->format('H:i') ?? '',
                $schedule->subject?->name ?? '',
            ))
            ->values();

        $activeDays = $schedules
            ->pluck('day_of_week')
            ->filter()
            ->map(fn (DayOfWeek $day): string => $day->value)
            ->unique();

        $days = collect(DayOfWeek::cases())
            ->filter(fn (DayOfWeek $day): bool => in_array($day->value, [
                DayOfWeek::Sunday->value,
                DayOfWeek::Monday->value,
                DayOfWeek::Tuesday->value,
                DayOfWeek::Wednesday->value,
                DayOfWeek::Thursday->value,
            ], true) || $activeDays->contains($day->value))
            ->values();

        $timeSlots = $schedules
            ->map(function (Schedule $schedule): ?array {
                $start = $schedule->start_time?->format('H:i');
                $end = $schedule->end_time?->format('H:i');

                if ($start === null || $end === null) {
                    return null;
                }

                return [
                    'key' => "{$start}-{$end}",
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->filter()
            ->unique('key')
            ->sortBy('start')
            ->values();

        $grid = [];

        foreach ($schedules as $schedule) {
            $day = $schedule->day_of_week?->value;
            $start = $schedule->start_time?->format('H:i');
            $end = $schedule->end_time?->format('H:i');

            if ($day === null || $start === null || $end === null) {
                continue;
            }

            $grid[$day]["{$start}-{$end}"][] = $schedule;
        }

        $weekDates = $days->mapWithKeys(
            fn (DayOfWeek $day): array => [$day->value => $day->dateInCurrentWeek()],
        );

        $academicYear = $classroom?->academic_year;

        if (blank($academicYear)) {
            $academicYear = $schedules
                ->map(fn (Schedule $schedule): ?string => $schedule->classroom?->academic_year
                    ?? $schedule->group?->classroom?->academic_year)
                ->filter()
                ->first();
        }

        return [
            'teacher' => $teacher,
            'classroom' => $classroom,
            'schedules' => $schedules,
            'days' => $days,
            'timeSlots' => $timeSlots,
            'grid' => $grid,
            'weekDates' => $weekDates,
            'academicYear' => $academicYear ?: $this->currentAcademicYear(),
            'scheduleCount' => $schedules->count(),
            'subjects' => $schedules->map(fn (Schedule $schedule): ?Subject => $schedule->subject)->filter()->unique('id')->sortBy('name')->values(),
            'teachers' => $schedules->map(fn (Schedule $schedule): ?User => $schedule->user)->filter()->unique('id')->sortBy('name')->values(),
            'autoPrint' => $autoPrint,
            'inlineCss' => $inlineCss,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceViewData(
        User $viewer,
        Classroom $classroom,
        ?Group $group,
        ?Subject $subject,
        string $from,
        string $to,
        bool $autoPrint = true,
        bool $inlineCss = false,
    ): array {
        $this->authorizeClassroom($viewer, $classroom);
        $this->assertGroupBelongsToClassroom($group, $classroom);

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $logs = DailyLog::query()
            ->with([
                'schedule.user',
                'schedule.subject',
                'schedule.group',
                'attendances.student.group',
            ])
            ->whereIn('schedule_id', $this->scheduleIdsForReport($viewer, $classroom, $group, $subject))
            ->where('status', DailyLogStatus::Completed->value)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $attendanceRows = $logs->flatMap(fn (DailyLog $log): Collection => $log->attendances);
        $attendanceByStudent = $attendanceRows->groupBy('student_id');

        $students = $this->roster($classroom, $group)->keyBy('id');

        foreach ($attendanceRows as $attendance) {
            if ($attendance->student !== null && ! $students->has($attendance->student->id)) {
                $students->put($attendance->student->id, $attendance->student);
            }
        }

        $students = $students
            ->sortBy(fn (Student $student): string => $student->full_name)
            ->values();

        $studentRows = $students->map(function (Student $student) use ($attendanceByStudent): array {
            $counts = $this->statusCounts($attendanceByStudent->get($student->id, collect()));

            return [
                'student' => $student,
                'present' => $counts[AttendanceStatus::Present->value],
                'absent' => $counts[AttendanceStatus::Absent->value],
                'late' => $counts[AttendanceStatus::Late->value],
                'excused' => $counts[AttendanceStatus::Excused->value],
                'total' => array_sum($counts),
                'attendance_rate' => $this->attendanceRate($counts),
                'absence_rate' => $this->absenceRate($counts),
            ];
        })->values();

        $sessions = $logs->map(function (DailyLog $log): array {
            $counts = $this->statusCounts($log->attendances);

            return [
                'log' => $log,
                'date' => $log->date,
                'subject' => $log->schedule?->subject,
                'teacher' => $log->schedule?->user,
                'group' => $log->schedule?->group,
                'counts' => $counts,
                'total' => array_sum($counts),
            ];
        })->values();

        $summaryCounts = $this->statusCounts($attendanceRows);
        $summary = [
            'sessions' => $logs->count(),
            'students' => $students->count(),
            'present' => $summaryCounts[AttendanceStatus::Present->value],
            'absent' => $summaryCounts[AttendanceStatus::Absent->value],
            'late' => $summaryCounts[AttendanceStatus::Late->value],
            'excused' => $summaryCounts[AttendanceStatus::Excused->value],
            'total' => array_sum($summaryCounts),
            'attendance_rate' => $this->attendanceRate($summaryCounts),
            'absence_rate' => $this->absenceRate($summaryCounts),
        ];

        $absences = $logs
            ->flatMap(function (DailyLog $log): Collection {
                return $log->attendances
                    ->filter(fn (Attendance $attendance): bool => $attendance->status !== AttendanceStatus::Present)
                    ->map(fn (Attendance $attendance): array => [
                        'date' => $log->date,
                        'student' => $attendance->student,
                        'status' => $attendance->status,
                        'subject' => $log->schedule?->subject,
                        'group' => $log->schedule?->group,
                        'remarks' => $attendance->remarks,
                    ]);
            })
            ->sortBy(fn (array $absence): string => ($absence['date']?->format('Y-m-d') ?? '').'|'.($absence['student']?->full_name ?? ''))
            ->values();

        $academicYear = $classroom->academic_year;

        if (blank($academicYear)) {
            $academicYear = $logs
                ->map(fn (DailyLog $log): ?string => $log->schedule?->classroom?->academic_year
                    ?? $log->schedule?->group?->classroom?->academic_year)
                ->filter()
                ->first();
        }

        return [
            'classroom' => $classroom,
            'group' => $group,
            'subject' => $subject,
            'from' => $start,
            'to' => $end,
            'sessions' => $sessions,
            'students' => $studentRows,
            'absences' => $absences,
            'summary' => $summary,
            'teachers' => $logs->map(fn (DailyLog $log): ?User => $log->schedule?->user)->filter()->unique('id')->sortBy('name')->values(),
            'academicYear' => $academicYear ?: $this->currentAcademicYear(),
            'autoPrint' => $autoPrint,
            'inlineCss' => $inlineCss,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transcriptViewData(
        User $viewer,
        Classroom $classroom,
        ?Group $group,
        Subject $subject,
        string $from,
        string $to,
        bool $autoPrint = true,
        bool $inlineCss = false,
    ): array {
        $this->authorizeClassroom($viewer, $classroom);
        $this->assertGroupBelongsToClassroom($group, $classroom);

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $assessments = Assessment::query()
            ->with(['schedule.user', 'schedule.group'])
            ->whereHas('schedule', function (Builder $query) use ($viewer, $classroom, $group, $subject): void {
                $query->where('subject_id', $subject->id)
                    ->where(function (Builder $query) use ($classroom): void {
                        $query->where('classroom_id', $classroom->id)
                            ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroom->id));
                    });

                if ($group !== null) {
                    $query->where(function (Builder $query) use ($group, $classroom): void {
                        $query->where('group_id', $group->id)
                            ->orWhere(function (Builder $query) use ($classroom): void {
                                $query->whereNull('group_id')->where('classroom_id', $classroom->id);
                            });
                    });
                }

                if ($viewer->isTeacher()) {
                    $query->where('user_id', $viewer->id);
                }
            })
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $students = $this->roster($classroom, $group);

        $grades = Grade::query()
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy(fn (Grade $grade): string => $grade->assessment_id.':'.$grade->student_id);

        $rows = $students->map(function (Student $student) use ($assessments, $grades): array {
            $scores = [];
            $normalized = [];

            foreach ($assessments as $assessment) {
                $grade = $grades->get($assessment->id.':'.$student->id);
                $score = $grade?->score !== null ? (float) $grade->score : null;
                $scores[$assessment->id] = $score;

                if ($score !== null) {
                    $maxScore = (float) $assessment->max_score;
                    $normalized[] = $maxScore > 0 ? ($score / $maxScore) * 20 : 0.0;
                }
            }

            return [
                'student' => $student,
                'scores' => $scores,
                'average' => $normalized === [] ? null : round(array_sum($normalized) / count($normalized), 2),
                'rank' => null,
                'graded' => count($normalized),
            ];
        });

        [$rows, $summary] = $this->rankTranscriptRows($rows, $assessments);

        $academicYear = $classroom->academic_year;

        if (blank($academicYear)) {
            $academicYear = $assessments
                ->map(fn (Assessment $assessment): ?string => $assessment->schedule?->classroom?->academic_year
                    ?? $assessment->schedule?->group?->classroom?->academic_year)
                ->filter()
                ->first();
        }

        return [
            'classroom' => $classroom,
            'group' => $group,
            'subject' => $subject,
            'assessments' => $assessments->map(fn (Assessment $assessment): array => [
                'assessment' => $assessment,
                'max_score' => (float) $assessment->max_score,
                'group' => $assessment->schedule?->group,
            ])->values(),
            'students' => $rows,
            'summary' => $summary,
            'teachers' => $assessments->map(fn (Assessment $assessment): ?User => $assessment->schedule?->user)->filter()->unique('id')->sortBy('name')->values(),
            'from' => $start,
            'to' => $end,
            'academicYear' => $academicYear ?: $this->currentAcademicYear(),
            'autoPrint' => $autoPrint,
            'inlineCss' => $inlineCss,
        ];
    }

    /**
     * Render a report view to a PDF and stream it as a download.
     *
     * @param  array<string, mixed>  $data
     */
    public function pdfResponse(
        string $view,
        array $data,
        string $filename,
        bool $landscape = false,
    ): StreamedResponse {
        $css = $this->compiledCss();

        $data['autoPrint'] = false;
        $data['inlineCss'] = $css !== null;

        $html = view($view, $data)->render();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        $pdf = $this->browsershot($html, $landscape)->pdf();

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return Collection<int, int|string>
     */
    private function scheduleIdsForReport(
        User $viewer,
        Classroom $classroom,
        ?Group $group,
        ?Subject $subject,
    ): Collection {
        return Schedule::query()
            ->when($viewer->isTeacher(), fn (Builder $query): Builder => $query->where('user_id', $viewer->id))
            ->where(function (Builder $query) use ($classroom): void {
                $query->where('classroom_id', $classroom->id)
                    ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroom->id));
            })
            ->when($group !== null, function (Builder $query) use ($group, $classroom): void {
                $query->where(function (Builder $query) use ($group, $classroom): void {
                    $query->where('group_id', $group->id)
                        ->orWhere(function (Builder $query) use ($classroom): void {
                            $query->whereNull('group_id')->where('classroom_id', $classroom->id);
                        });
                });
            })
            ->when($subject !== null, fn (Builder $query): Builder => $query->where('subject_id', $subject->id))
            ->pluck('id');
    }

    /**
     * @return Collection<int, Student>
     */
    private function roster(Classroom $classroom, ?Group $group): Collection
    {
        return Student::query()
            ->with('group')
            ->whereHas('group', function (Builder $query) use ($classroom, $group): void {
                $query->where('classroom_id', $classroom->id);

                if ($group !== null) {
                    $query->where('id', $group->id);
                }
            })
            ->get()
            ->sortBy(fn (Student $student): string => $student->full_name)
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, Assessment>  $assessments
     * @return array{0: Collection<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    private function rankTranscriptRows(Collection $rows, Collection $assessments): array
    {
        $ranked = $rows
            ->filter(fn (array $row): bool => $row['average'] !== null)
            ->sortByDesc('average')
            ->values();

        $unranked = $rows
            ->filter(fn (array $row): bool => $row['average'] === null)
            ->sortBy(fn (array $row): string => $row['student']->full_name)
            ->values();

        $lastAverage = null;
        $lastRank = 0;

        foreach ($ranked as $index => $row) {
            $average = (float) $row['average'];

            if ($lastAverage === null || abs($average - $lastAverage) >= 0.005) {
                $lastRank = $index + 1;
                $lastAverage = $average;
            }

            $ranked[$index] = [...$row, 'rank' => $lastRank];
        }

        $ranked = $ranked->values();
        $averages = $ranked->pluck('average');
        $totalCells = $rows->count() * $assessments->count();
        $gradedCells = $rows->sum('graded');

        return [
            $ranked->concat($unranked)->values(),
            [
                'students' => $rows->count(),
                'graded_students' => $ranked->count(),
                'assessments' => $assessments->count(),
                'average' => $averages->isNotEmpty() ? round((float) $averages->avg(), 2) : null,
                'highest' => $averages->isNotEmpty() ? (float) $averages->max() : null,
                'lowest' => $averages->isNotEmpty() ? (float) $averages->min() : null,
                'completion' => $totalCells > 0 ? (int) round(($gradedCells / $totalCells) * 100) : 0,
            ],
        ];
    }

    private function authorizeClassroom(User $viewer, Classroom $classroom): void
    {
        if ($viewer->isAdmin()) {
            return;
        }

        abort_unless($this->teacherTeachesClassroom($viewer, $classroom->id), 403);
    }

    private function assertGroupBelongsToClassroom(?Group $group, Classroom $classroom): void
    {
        if ($group !== null) {
            abort_unless($group->classroom_id === $classroom->id, 404);
        }
    }

    private function teacherTeachesClassroom(User $teacher, int $classroomId): bool
    {
        return Schedule::query()
            ->forTeacher($teacher->id)
            ->where(function (Builder $query) use ($classroomId): void {
                $query->where('classroom_id', $classroomId)
                    ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroomId));
            })
            ->exists();
    }

    /**
     * @return array<string, int>
     */
    private function emptyStatusCounts(): array
    {
        return [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Absent->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::Excused->value => 0,
        ];
    }

    /**
     * @param  Collection<int, Attendance>  $attendances
     * @return array<string, int>
     */
    private function statusCounts(Collection $attendances): array
    {
        $counts = $this->emptyStatusCounts();

        foreach ($attendances as $attendance) {
            $status = $attendance->status?->value ?? AttendanceStatus::Present->value;

            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function attendanceRate(array $counts): float
    {
        $total = array_sum($counts);

        if ($total === 0) {
            return 0.0;
        }

        return round((($counts[AttendanceStatus::Present->value] + $counts[AttendanceStatus::Late->value]) / $total) * 100, 1);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function absenceRate(array $counts): float
    {
        $total = array_sum($counts);

        if ($total === 0) {
            return 0.0;
        }

        return round(($counts[AttendanceStatus::Absent->value] / $total) * 100, 1);
    }

    private function dayIndex(?DayOfWeek $day): int
    {
        if ($day === null) {
            return 99;
        }

        $index = array_search($day, DayOfWeek::cases(), true);

        return $index === false ? 99 : $index;
    }

    private function browsershot(string $html, bool $landscape): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(8, 8, 8, 8)
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle()
            ->timeout(120);

        if ($landscape) {
            $browsershot->landscape();
        }

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        foreach ([
            'node_binary' => 'setNodeBinary',
            'npm_binary' => 'setNpmBinary',
            'chrome_path' => 'setChromePath',
        ] as $configKey => $method) {
            $value = config("services.browsershot.{$configKey}");

            if (filled($value)) {
                $browsershot->{$method}($value);
            }
        }

        return $browsershot;
    }

    private function inlineCss(string $html, string $css): string
    {
        $baseUrl = e(rtrim((string) config('app.url'), '/').'/');

        return str_replace(
            '</head>',
            '<base href="'.$baseUrl.'"><style>'.str_replace('</style', '<\/style', $css).'</style></head>',
            $html,
        );
    }

    private function compiledCss(): ?string
    {
        try {
            return Vite::content('resources/css/app.css');
        } catch (ViteException) {
            return null;
        }
    }

    private function currentAcademicYear(): string
    {
        $startYear = now()->month >= 9 ? now()->year : now()->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
