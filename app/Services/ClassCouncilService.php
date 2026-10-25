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
use Illuminate\Support\Collection as SupportCollection;
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

        $groupIds = $classroom->groups->pluck('id');

        $students = Student::query()
            ->with('group')
            ->whereIn('group_id', $groupIds)
            ->when($groupId, fn (Builder $query): Builder => $query->where('group_id', $groupId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $gradesByStudent = $this->gradesForStudents($students->pluck('id'));

        $subjects = $this->getSubjectsForClassroom($classroom, $gradesByStudent);

        $studentDeliberations = $students->map(function (Student $student) use ($subjects, $gradesByStudent): array {
            return $this->computeStudentDeliberation(
                $student,
                $subjects,
                $gradesByStudent->get($student->id, collect()),
            );
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
     * Load every grade for the classroom roster in one query.
     *
     * Querying by student_id (instead of by schedule/classroom) guarantees that
     * grades recorded on direct classroom schedules and on group-scoped
     * schedules are both included in the deliberation averages.
     *
     * @param  SupportCollection<int, int|string>  $studentIds
     * @return SupportCollection<int, SupportCollection<int, Grade>>
     */
    private function gradesForStudents(SupportCollection $studentIds): SupportCollection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return Grade::query()
            ->whereIn('student_id', $studentIds)
            ->with('assessment.schedule.subject')
            ->get()
            ->filter(fn (Grade $grade): bool => $grade->assessment?->schedule?->subject !== null)
            ->groupBy('student_id');
    }

    /**
     * Get every subject taught in the classroom, directly or through a group,
     * plus any subject that already has a recorded grade for these students.
     *
     * @param  SupportCollection<int, SupportCollection<int, Grade>>  $gradesByStudent
     * @return Collection<int, Subject>
     */
    private function getSubjectsForClassroom(Classroom $classroom, SupportCollection $gradesByStudent): Collection
    {
        $scheduledSubjectIds = Subject::query()
            ->whereHas('schedules', function (Builder $query) use ($classroom): void {
                $query->where('classroom_id', $classroom->id)
                    ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroom->id));
            })
            ->pluck('id');

        $gradedSubjectIds = $gradesByStudent
            ->flatten(1)
            ->map(fn (Grade $grade): ?int => $grade->assessment?->schedule?->subject_id)
            ->filter()
            ->unique();

        $subjectIds = $scheduledSubjectIds
            ->merge($gradedSubjectIds)
            ->unique()
            ->values();

        if ($subjectIds->isEmpty()) {
            return new Collection;
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Compute full deliberation data for a single student.
     *
     * @param  Collection<int, Subject>  $subjects
     * @param  SupportCollection<int, Grade>  $grades
     * @return array<string, mixed>
     */
    private function computeStudentDeliberation(Student $student, Collection $subjects, SupportCollection $grades): array
    {
        $perSubject = [];
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($subjects as $subject) {
            $subjectGrades = $grades->filter(
                fn (Grade $grade): bool => $grade->assessment?->schedule?->subject_id === $subject->id,
            );

            $avg = $this->computeSubjectAverage($subjectGrades);

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
            'recommendation' => $recommendation['label'],
            'recommendation_key' => $recommendation['key'],
            'recommendation_color' => $recommendation['color'],
            'passed' => $generalAverage !== null && $generalAverage >= 10.0,
        ];
    }

    /**
     * Compute a student's average for a specific subject (/20 scale).
     *
     * @param  SupportCollection<int, Grade>  $grades
     */
    private function computeSubjectAverage(SupportCollection $grades): ?float
    {
        $totalScore = 0.0;
        $totalMax = 0.0;

        foreach ($grades as $grade) {
            $maxScore = (float) ($grade->assessment?->max_score ?? 0);

            if ($grade->score !== null && $maxScore > 0) {
                $totalScore += (float) $grade->score;
                $totalMax += $maxScore;
            }
        }

        if ($totalMax === 0.0) {
            return null;
        }

        return round(($totalScore / $totalMax) * 20, 2);
    }

    /**
     * Compute attendance tallies for a student.
     *
     * @return array{present: int, absent: int, late: int, excused: int}
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
            'present' => (int) ($counts[AttendanceStatus::Present->value] ?? 0),
            'absent' => (int) ($counts[AttendanceStatus::Absent->value] ?? 0),
            'late' => (int) ($counts[AttendanceStatus::Late->value] ?? 0),
            'excused' => (int) ($counts[AttendanceStatus::Excused->value] ?? 0),
        ];
    }

    /**
     * Compute behavior observation counts (positive vs negative/disciplinary).
     *
     * @return array{positive: int, negative: int}
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

        foreach ($obs as $category => $count) {
            if (in_array($category, $positiveCategories, true)) {
                $positive += (int) $count;
            } elseif (in_array($category, $negativeCategories, true)) {
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
     *
     * @return array{key: string, label: string, color: string}
     */
    private function computeRecommendation(?float $generalAverage, int $negativeBehaviorCount): array
    {
        if ($generalAverage === null) {
            return [
                'key' => 'pending',
                'label' => __('class_council.recommendation.pending'),
                'color' => 'gray',
            ];
        }

        if ($generalAverage >= 16.0 && $negativeBehaviorCount === 0) {
            return [
                'key' => 'excellence',
                'label' => __('class_council.recommendation.excellence'),
                'color' => 'success',
            ];
        }

        if ($generalAverage >= 14.0) {
            return [
                'key' => 'encouragement',
                'label' => __('class_council.recommendation.encouragement'),
                'color' => 'info',
            ];
        }

        if ($generalAverage >= 12.0) {
            return [
                'key' => 'honor_roll',
                'label' => __('class_council.recommendation.honor_roll'),
                'color' => 'primary',
            ];
        }

        if ($generalAverage >= 10.0) {
            return [
                'key' => 'acceptable',
                'label' => __('class_council.recommendation.acceptable'),
                'color' => 'gray',
            ];
        }

        return [
            'key' => 'warning',
            'label' => __('class_council.recommendation.warning'),
            'color' => 'danger',
        ];
    }

    /**
     * Compute classroom-level KPIs.
     *
     * @param  SupportCollection<int, array<string, mixed>>  $studentDeliberations
     * @return array<string, int|float|null>
     */
    private function computeClassroomKpis(SupportCollection $studentDeliberations): array
    {
        $totalStudents = $studentDeliberations->count();

        $graded = $studentDeliberations
            ->filter(fn (array $student): bool => $student['generalAverage'] !== null)
            ->values();

        $averages = $graded->pluck('generalAverage');
        $gradedCount = $graded->count();
        $passed = $studentDeliberations->where('passed', true)->count();
        $honorRoll = $studentDeliberations
            ->filter(fn (array $student): bool => $student['generalAverage'] !== null && $student['generalAverage'] >= 14.0)
            ->count();

        $totalAbsences = $studentDeliberations->sum(fn (array $student): int => $student['attendanceCounts']['absent']);
        $totalLates = $studentDeliberations->sum(fn (array $student): int => $student['attendanceCounts']['late']);

        return [
            'total_students' => $totalStudents,
            'graded_students' => $gradedCount,
            'pending_students' => $totalStudents - $gradedCount,
            'class_general_average' => $gradedCount > 0 ? round((float) $averages->avg(), 2) : null,
            'pass_rate' => $gradedCount > 0 ? round(($passed / $gradedCount) * 100, 1) : 0.0,
            'highest_average' => $gradedCount > 0 ? (float) $averages->max() : null,
            'lowest_average' => $gradedCount > 0 ? (float) $averages->min() : null,
            'total_absences' => $totalAbsences,
            'total_lates' => $totalLates,
            'honor_roll_count' => $honorRoll,
        ];
    }

    /**
     * Compute honor roll distribution for summary badges.
     *
     * @param  SupportCollection<int, array<string, mixed>>  $studentDeliberations
     * @return array<string, int>
     */
    private function computeHonorDistribution(SupportCollection $studentDeliberations): array
    {
        $distribution = [
            'excellence' => 0,
            'encouragement' => 0,
            'honor_roll' => 0,
            'acceptable' => 0,
            'warning' => 0,
            'pending' => 0,
        ];

        foreach ($studentDeliberations as $student) {
            $key = $student['recommendation_key'] ?? 'pending';

            if (array_key_exists($key, $distribution)) {
                $distribution[$key]++;
            }
        }

        return $distribution;
    }

    /**
     * Render the class council report as HTML.
     */
    public function renderHtml(Classroom $classroom, ?int $groupId = null): string
    {
        return view('print.class-council', $this->viewData($classroom, $groupId))->render();
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
