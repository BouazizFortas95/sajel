<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\PedagogicalRemediation;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class ClassCouncilService
{
    /**
     * Compute and prepare the full class council deliberation data.
     *
     * @return array<string, mixed>
     */
    public function viewData(Classroom $classroom, ?int $groupId = null): array
    {
        $classroom->loadMissing(['level.educationPhase', 'groups']);

        $students = Student::query()
            ->with('group')
            ->whereHas('group', fn (Builder $q) => $q->where('classroom_id', $classroom->id))
            ->when($groupId, fn (Builder $q) => $q->where('group_id', $groupId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $subjects = $this->getSubjectsForClassroom($classroom);

        $studentDeliberations = $students->map(function (Student $student) use ($subjects) {
            return $this->computeStudentDeliberation($student, $subjects);
        });

        $kpis = $this->computeClassroomKpis($studentDeliberations);

        $honorDistribution = $this->computeHonorDistribution($studentDeliberations);

        return [
            'classroom' => $classroom,
            'group' => $groupId ? Group::find($groupId) : null,
            'studentDeliberations' => $studentDeliberations,
            'subjects' => $subjects,
            'kpis' => $kpis,
            'honorDistribution' => $honorDistribution,
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
            'autoPrint' => true,
            'inlineCss' => false,
        ];
    }

    /**
     * Get all subjects that have assessments/grades for students in the classroom.
     */
    private function getSubjectsForClassroom(Classroom $classroom): Collection
    {
        return Subject::query()
            ->whereHas('schedules', fn (Builder $q) => $q->where('classroom_id', $classroom->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Compute full deliberation data for a single student.
     */
    private function computeStudentDeliberation(Student $student, Collection $subjects): array
    {
        $perSubject = [];
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($subjects as $subject) {
            $avg = $this->computeSubjectAverage($student, $subject);

            if ($avg !== null) {
                $perSubject[] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'average' => round($avg, 2),
                ];
                $weightedSum += $avg;
                $totalWeight++;
            }
        }

        $generalAverage = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;

        $attendanceCounts = $this->computeAttendanceCounts($student);
        $behaviorCounts = $this->computeBehaviorCounts($student);
        $remediationCount = PedagogicalRemediation::where('student_id', $student->id)->count();

        $recommendation = $this->computeRecommendation($generalAverage, $behaviorCounts['negative']);

        return [
            'student' => $student,
            'full_name' => $student->full_name,
            'registration_number' => $student->registration_number,
            'group' => $student->group?->name,
            'perSubjectAverages' => $perSubject,
            'generalAverage' => $generalAverage,
            'attendanceCounts' => $attendanceCounts,
            'behaviorCounts' => $behaviorCounts,
            'remediationCount' => $remediationCount,
            'recommendation' => $recommendation,
            'passed' => $generalAverage !== null && $generalAverage >= 10.0,
        ];
    }

    /**
     * Compute a student's average for a specific subject (/20 scale).
     */
    private function computeSubjectAverage(Student $student, Subject $subject): ?float
    {
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->whereHas('assessment', fn (Builder $q) => $q
                ->whereHas('schedule', fn (Builder $s) => $s->where('subject_id', $subject->id)))
            ->with('assessment.schedule')
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $totalMax = 0;

        foreach ($grades as $grade) {
            if ($grade->score !== null && $grade->assessment?->max_score) {
                $totalScore += (float) $grade->score;
                $totalMax += (float) $grade->assessment->max_score;
            }
        }

        if ($totalMax === 0) {
            return null;
        }

        return round(($totalScore / $totalMax) * 20, 2);
    }

    /**
     * Compute attendance tallies for a student.
     */
    private function computeAttendanceCounts(Student $student): array
    {
        $counts = Attendance::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'present' => $counts[AttendanceStatus::Present->value] ?? 0,
            'absent' => $counts[AttendanceStatus::Absent->value] ?? 0,
            'late' => $counts[AttendanceStatus::Late->value] ?? 0,
            'excused' => $counts[AttendanceStatus::Excused->value] ?? 0,
        ];
    }

    /**
     * Compute behavior observation counts (positive vs negative/disciplinary).
     */
    private function computeBehaviorCounts(Student $student): array
    {
        $positiveCategories = ['participation', 'helpfulness', 'leadership', 'improvement', 'other_positive'];
        $negativeCategories = ['disruption', 'disrespect', 'violence', 'cheating', 'absence_without_leave', 'other_negative'];

        $obs = BehaviorObservation::where('student_id', $student->id)
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $positive = 0;
        $negative = 0;

        foreach ($obs as $cat => $count) {
            if (in_array($cat, $positiveCategories, true)) {
                $positive += (int) $count;
            } elseif (in_array($cat, $negativeCategories, true)) {
                $negative += (int) $count;
            }
        }

        return [
            'positive' => $positive,
            'negative' => $negative,
        ];
    }

    /**
     * Compute the automatic pedagogical recommendation based on general average
     * and disciplinary alerts.
     */
    private function computeRecommendation(?float $generalAverage, int $negativeBehaviorCount): string
    {
        if ($generalAverage === null) {
            return __('tbd');
        }

        if ($generalAverage >= 16.0 && $negativeBehaviorCount === 0) {
            return __('class_council.recommendation.excellence');
        }

        if ($generalAverage >= 14.0) {
            return __('class_council.recommendation.encouragement');
        }

        if ($generalAverage >= 12.0) {
            return __('class_council.recommendation.honor_roll');
        }

        if ($generalAverage >= 10.0) {
            return __('class_council.recommendation.acceptable');
        }

        return __('class_council.recommendation.warning');
    }

    /**
     * Compute classroom-level KPIs.
     */
    private function computeClassroomKpis(\Illuminate\Support\Collection $studentDeliberations): array
    {
        $averages = $studentDeliberations
            ->pluck('generalAverage')
            ->filter()
            ->values();

        $count = $averages->count();
        $passed = $studentDeliberations->where('passed', true)->count();
        $honorRoll = $studentDeliberations->filter(fn ($s) => $s['generalAverage'] !== null && $s['generalAverage'] >= 14.0)->count();

        $totalAbsences = $studentDeliberations->sum(fn ($s) => $s['attendanceCounts']['absent']);
        $totalLates = $studentDeliberations->sum(fn ($s) => $s['attendanceCounts']['late']);

        return [
            'total_students' => $count,
            'class_general_average' => $count > 0 ? round($averages->avg(), 2) : null,
            'pass_rate' => $count > 0 ? round(($passed / $count) * 100, 1) : 0.0,
            'highest_average' => $count > 0 ? $averages->max() : null,
            'lowest_average' => $count > 0 ? $averages->min() : null,
            'total_absences' => $totalAbsences,
            'total_lates' => $totalLates,
            'honor_roll_count' => $honorRoll,
        ];
    }

    /**
     * Compute honor roll distribution for summary badges.
     */
    private function computeHonorDistribution(\Illuminate\Support\Collection $studentDeliberations): array
    {
        $dist = [
            'excellence' => 0,
            'encouragement' => 0,
            'honor_roll' => 0,
            'acceptable' => 0,
            'warning' => 0,
        ];

        foreach ($studentDeliberations as $s) {
            $rec = $s['recommendation'];
            if (str_contains($rec, 'امتياز') || str_contains($rec, 'Excellence') || str_contains($rec, 'Félicitations')) {
                $dist['excellence']++;
            } elseif (str_contains($rec, 'تشجيع') || str_contains($rec, 'Encouragement')) {
                $dist['encouragement']++;
            } elseif (str_contains($rec, 'شرف') || str_contains($rec, 'Honneur')) {
                $dist['honor_roll']++;
            } elseif (str_contains($rec, 'مقبول') || str_contains($rec, 'Acceptable')) {
                $dist['acceptable']++;
            } else {
                $dist['warning']++;
            }
        }

        return $dist;
    }

    /**
     * Render the class council report as HTML.
     */
    public function renderHtml(Classroom $classroom, ?int $groupId = null): string
    {
        return view('print.class-council', $this->viewData($classroom, $groupId, autoPrint: true))->render();
    }

    /**
     * Generate the class council report as PDF using Browsershot.
     */
    public function generatePdf(Classroom $classroom, ?int $groupId = null): string
    {
        $html = $this->renderHtml($classroom, $groupId);
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
            ->landscape()
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle()
            ->timeout(180);

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
}
