<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class StudentReportCardService
{
    /**
     * Prepare report data after policy authorization.
     *
     * @return array<string, mixed>
     */
    public function viewData(Student $student, User $viewer, bool $autoPrint = true): array
    {
        Gate::forUser($viewer)->authorize('view', $student);

        $student->loadMissing('group.classroom.level.educationPhase');

        $scheduleQuery = Schedule::query()
            ->when($viewer->isTeacher(), fn (Builder $query): Builder => $query->where('user_id', $viewer->id))
            ->where(function (Builder $query) use ($student): void {
                if (! $student->group) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where('group_id', $student->group_id)
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNull('group_id')
                        ->where('classroom_id', $student->group->classroom_id));
            });
        $scheduleIds = $scheduleQuery->pluck('id');

        $attendanceRows = Attendance::query()
            ->with(['dailyLog.schedule.subject', 'dailyLog.schedule.user'])
            ->where('student_id', $student->id)
            ->whereHas('dailyLog', fn (Builder $query): Builder => $query
                ->whereIn('schedule_id', $scheduleIds)
                ->where('status', '!=', DailyLogStatus::Cancelled->value))
            ->get();

        $attendanceCounts = collect(AttendanceStatus::cases())
            ->mapWithKeys(fn (AttendanceStatus $status): array => [$status->value => 0])
            ->all();

        foreach ($attendanceRows as $attendance) {
            $status = $attendance->status?->value ?? AttendanceStatus::Present->value;

            if (array_key_exists($status, $attendanceCounts)) {
                $attendanceCounts[$status]++;
            }
        }

        $attendanceTotal = array_sum($attendanceCounts);
        $attendanceRate = $attendanceTotal === 0
            ? 0.0
            : round((($attendanceCounts[AttendanceStatus::Present->value] + $attendanceCounts[AttendanceStatus::Late->value]) / $attendanceTotal) * 100, 1);

        $recentAttendanceAlerts = $attendanceRows
            ->filter(fn (Attendance $attendance): bool => in_array(
                $attendance->status?->value,
                [AttendanceStatus::Absent->value, AttendanceStatus::Late->value],
                true,
            ))
            ->sortByDesc(fn (Attendance $attendance): string => sprintf(
                '%s|%010d',
                $attendance->dailyLog?->date?->format('Y-m-d') ?? '',
                $attendance->id,
            ))
            ->take(10)
            ->values();

        $assessments = Assessment::query()
            ->with(['schedule.subject', 'schedule.user'])
            ->whereIn('schedule_id', $scheduleIds)
            ->whereHas('grades', fn (Builder $query): Builder => $query->where('student_id', $student->id))
            ->with(['grades' => fn ($query) => $query->where('student_id', $student->id)])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $gradesBySubject = $assessments
            ->groupBy(fn (Assessment $assessment): string => (string) ($assessment->schedule?->subject?->id ?? 'unknown'))
            ->map(function (Collection $subjectAssessments): array {
                $first = $subjectAssessments->first();
                $gradeRows = $subjectAssessments->map(fn (Assessment $assessment): array => [
                    'assessment' => $assessment,
                    'grade' => $assessment->grades->first(),
                    'teacher' => $assessment->schedule?->user,
                    'normalized_score' => $assessment->grades->first()?->score !== null && (float) $assessment->max_score > 0
                        ? ((float) $assessment->grades->first()->score / (float) $assessment->max_score) * 20
                        : null,
                ])->values();
                $scoredRows = $gradeRows->whereNotNull('normalized_score');

                return [
                    'subject' => $first?->schedule?->subject,
                    'assessments' => $gradeRows,
                    'average' => $scoredRows->isEmpty()
                        ? null
                        : round((float) $scoredRows->avg('normalized_score'), 2),
                ];
            })
            ->values();

        $observations = BehaviorObservation::query()
            ->with('schedule.subject')
            ->where('student_id', $student->id)
            ->when($viewer->isTeacher(), fn (Builder $query): Builder => $query
                ->where('recorded_by_id', $viewer->id)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('schedule_id')
                    ->orWhereIn('schedule_id', $scheduleIds)))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $remediations = PedagogicalRemediation::query()
            ->with('schedule.subject')
            ->where('student_id', $student->id)
            ->when($viewer->isTeacher(), fn (Builder $query): Builder => $query
                ->where('recorded_by_id', $viewer->id)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('schedule_id')
                    ->orWhereIn('schedule_id', $scheduleIds)))
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get();

        $classroom = $student->group?->classroom;

        return [
            'student' => $student,
            'studentIdentity' => [
                'full_name' => $student->full_name,
                'registration_number' => $student->registration_number,
                'date_of_birth' => $student->date_of_birth,
                'classroom' => $classroom?->name,
                'group' => $student->group?->name,
                'level' => $classroom?->level?->name,
                'academic_year' => $classroom?->academic_year ?: $this->currentAcademicYear(),
            ],
            'viewer' => $viewer,
            'attendanceCounts' => $attendanceCounts,
            'attendanceTotal' => $attendanceTotal,
            'attendanceRate' => $attendanceRate,
            'recentAttendanceAlerts' => $recentAttendanceAlerts,
            'gradesBySubject' => $gradesBySubject,
            'observations' => $observations,
            'remediations' => $remediations,
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
            'portalUrl' => URL::temporarySignedRoute('parent.student-portal', now()->addDays(90), ['locale' => app()->getLocale(), 'student' => $student]),
            'badges' => app(StudentMotivationService::class)->badgesForStudent($student),
            'autoPrint' => $autoPrint,
            'inlineCss' => false,
        ];
    }

    public function renderHtml(Student $student, User $viewer, bool $autoPrint = true): string
    {
        return view('print.student-report-card', $this->viewData($student, $viewer, $autoPrint))->render();
    }

    public function generatePdf(Student $student, User $viewer): string
    {
        $html = $this->renderHtml($student, $viewer, autoPrint: false);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    private function browsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle()
            ->timeout(120);

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
        $startYear = now(config('app.timezone', 'Africa/Algiers'))->month >= 9
            ? now(config('app.timezone', 'Africa/Algiers'))->year
            : now(config('app.timezone', 'Africa/Algiers'))->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
