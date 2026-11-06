<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\StudentAwardType;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Concerns\ConfiguresBrowsershot;
use App\Support\BrowsershotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class StudentAwardService
{
    use ConfiguresBrowsershot;

    /**
     * Resolve date boundaries for Algerian academic trimesters.
     *
     * @return array{from: ?string, to: ?string, name: string}
     */
    public function getTrimesterPeriod(?int $trimester = 1, ?int $year = null): array
    {
        if ($trimester === null || $trimester === 0) {
            return [
                'from' => null,
                'to' => null,
                'name' => __('الموسم الدراسي كاملاً'),
            ];
        }

        $year = $year ?? (int) now()->format('Y');
        $currentMonth = (int) now()->format('m');
        $academicStartYear = $currentMonth >= 9 ? $year : $year - 1;

        return match ($trimester) {
            2 => [
                'from' => sprintf('%d-01-01', $academicStartYear + 1),
                'to' => sprintf('%d-03-31', $academicStartYear + 1),
                'name' => __('الفصل الثاني'),
            ],
            3 => [
                'from' => sprintf('%d-04-01', $academicStartYear + 1),
                'to' => sprintf('%d-06-30', $academicStartYear + 1),
                'name' => __('الفصل الثالث'),
            ],
            default => [
                'from' => sprintf('%d-09-01', $academicStartYear),
                'to' => sprintf('%d-12-31', $academicStartYear),
                'name' => __('الفصل الأول'),
            ],
        };
    }

    /**
     * Compute full classroom awards, merit allocations, rankings and statistics.
     *
     * @return array<string, mixed>
     */
    public function computeClassroomAwards(
        Classroom $classroom,
        ?int $trimester = 1,
        ?int $groupId = null,
        ?StudentAwardType $awardTypeFilter = null,
    ): array {
        $classroom->loadMissing(['level.educationPhase', 'groups']);

        $period = $this->getTrimesterPeriod($trimester);
        $groupIds = $classroom->groups->pluck('id');

        $students = Student::query()
            ->with('group')
            ->whereIn('group_id', $groupIds)
            ->when($groupId, fn (Builder $query): Builder => $query->where('group_id', $groupId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $studentIds = $students->pluck('id');

        $gradesByStudent = $this->getGradesByStudent($studentIds, $period['from'], $period['to']);
        $subjects = $this->getSubjectsForClassroom($classroom, $gradesByStudent);

        $studentRows = $students->map(function (Student $student) use ($subjects, $gradesByStudent, $period): array {
            return $this->computeStudentMetrics(
                $student,
                $subjects,
                $gradesByStudent->get($student->id, collect()),
                $period['from'],
                $period['to']
            );
        });

        // Sort students by general average descending, with tie-breakers
        $rankedRows = $studentRows->sort(function (array $a, array $b): int {
            $avgA = $a['general_average'] ?? -1.0;
            $avgB = $b['general_average'] ?? -1.0;

            if ($avgA !== $avgB) {
                return $avgB <=> $avgA;
            }

            // Tie breaker 1: positive behavior count
            if ($a['positive_behavior_count'] !== $b['positive_behavior_count']) {
                return $b['positive_behavior_count'] <=> $a['positive_behavior_count'];
            }

            // Tie breaker 2: fewer absences
            if ($a['attendance_counts']['absent'] !== $b['attendance_counts']['absent']) {
                return $a['attendance_counts']['absent'] <=> $b['attendance_counts']['absent'];
            }

            return strcmp($a['student']->full_name, $b['student']->full_name);
        })->values();

        // Assign ranks to graded students
        $rank = 1;
        $rankedRows = $rankedRows->map(function (array $row) use (&$rank): array {
            if ($row['general_average'] !== null) {
                $row['rank'] = $rank++;
                $row['rank_label'] = $this->formatArabicRank($row['rank']);
            } else {
                $row['rank'] = null;
                $row['rank_label'] = null;
            }

            return $row;
        });

        // Compute KPIs
        $kpis = $this->computeClassroomKpis($rankedRows);

        // Filter if requested
        $filteredRows = $rankedRows;
        if ($awardTypeFilter !== null) {
            $filteredRows = $rankedRows->filter(function (array $row) use ($awardTypeFilter): bool {
                return in_array($awardTypeFilter, $row['eligible_awards'], true)
                    || $row['primary_award'] === $awardTypeFilter;
            })->values();
        }

        return [
            'classroom' => $classroom,
            'group' => $groupId ? Group::find($groupId) : null,
            'trimester' => $trimester,
            'period' => $period,
            'subjects' => $subjects,
            'students' => $filteredRows,
            'allRankedStudents' => $rankedRows,
            'kpis' => $kpis,
            'schoolSetting' => SchoolSetting::current(),
            'academicYear' => $classroom->academic_year ?? SchoolSetting::current()?->academic_year ?? '2026/2027',
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Compute full metrics, averages, behavior, attendance and awards for a student.
     *
     * @param  Collection<int, Subject>  $subjects
     * @param  Collection<int, Grade>  $grades
     * @return array<string, mixed>
     */
    public function computeStudentMetrics(
        Student $student,
        Collection $subjects,
        Collection $grades,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $subjectAverages = [];
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($subjects as $subject) {
            $subjectGrades = $grades->filter(
                fn (Grade $grade): bool => $grade->assessment?->schedule?->subject_id === $subject->id
            );

            $avg = $this->computeSubjectAverage($subjectGrades);

            if ($avg !== null) {
                $subjectAverages[] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'color' => $subject->color,
                    'average' => round($avg, 2),
                ];
                $weightedSum += $avg;
                $totalWeight++;
            }
        }

        $generalAverage = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;

        $attendanceCounts = $this->computeAttendanceCounts($student, $fromDate, $toDate);
        $behaviorCounts = $this->computeBehaviorCounts($student, $fromDate, $toDate);

        $eligibleAwards = $this->determineEligibleAwards(
            $generalAverage,
            $behaviorCounts['positive'],
            $behaviorCounts['negative'],
            $attendanceCounts['absent'],
            $attendanceCounts['present'] + $attendanceCounts['late']
        );

        $primaryAward = ! empty($eligibleAwards) ? $eligibleAwards[0] : null;

        return [
            'student' => $student,
            'full_name' => $student->full_name,
            'registration_number' => $student->registration_number,
            'date_of_birth' => $student->date_of_birth,
            'group' => $student->group?->name,
            'subject_averages' => $subjectAverages,
            'general_average' => $generalAverage,
            'attendance_counts' => $attendanceCounts,
            'positive_behavior_count' => $behaviorCounts['positive'],
            'negative_behavior_count' => $behaviorCounts['negative'],
            'eligible_awards' => $eligibleAwards,
            'primary_award' => $primaryAward,
            'has_awards' => ! empty($eligibleAwards),
        ];
    }

    /**
     * Determine eligible awards based on academic performance and conduct.
     *
     * @return list<StudentAwardType>
     */
    public function determineEligibleAwards(
        ?float $generalAverage,
        int $positiveBehaviorCount,
        int $negativeBehaviorCount,
        int $unexcusedAbsenceCount,
        int $totalPresentCount
    ): array {
        $awards = [];

        if ($generalAverage !== null) {
            // Excellence: General average >= 16.0 AND 0 negative behavior observations
            if ($generalAverage >= 16.0 && $negativeBehaviorCount === 0) {
                $awards[] = StudentAwardType::Excellence;
            }

            // Encouragement: General average >= 14.0 and < 16.0 (or >= 16.0 with minor remark)
            if ($generalAverage >= 14.0 && $generalAverage < 16.0) {
                $awards[] = StudentAwardType::Encouragement;
            } elseif ($generalAverage >= 16.0 && $negativeBehaviorCount > 0) {
                $awards[] = StudentAwardType::Encouragement;
            }

            // Honor Roll: General average >= 12.0 and < 14.0
            if ($generalAverage >= 12.0 && $generalAverage < 14.0) {
                $awards[] = StudentAwardType::HonorRoll;
            }
        }

        // Good Conduct: 0 unexcused absences, 0 negative observations, and active presence
        if ($unexcusedAbsenceCount === 0 && $negativeBehaviorCount === 0 && ($positiveBehaviorCount > 0 || ($generalAverage !== null && $generalAverage >= 10.0))) {
            $awards[] = StudentAwardType::GoodConduct;
        }

        return $awards;
    }

    /**
     * Compute average on a /20 scale from grades.
     *
     * @param  Collection<int, Grade>  $grades
     */
    private function computeSubjectAverage(Collection $grades): ?float
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
     * Load grades for given students optionally filtered by assessment date range.
     *
     * @param  Collection<int, int|string>  $studentIds
     * @return Collection<int, Collection<int, Grade>>
     */
    private function getGradesByStudent(Collection $studentIds, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return Grade::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('assessment', function (Builder $query) use ($fromDate, $toDate): void {
                if ($fromDate) {
                    $query->whereDate('date', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->whereDate('date', '<=', $toDate);
                }
            })
            ->with(['assessment.schedule.subject'])
            ->get()
            ->filter(fn (Grade $grade): bool => $grade->assessment?->schedule?->subject !== null)
            ->groupBy('student_id');
    }

    /**
     * Get subjects taught in the classroom.
     *
     * @param  Collection<int, Collection<int, Grade>>  $gradesByStudent
     * @return EloquentCollection<int, Subject>
     */
    private function getSubjectsForClassroom(Classroom $classroom, Collection $gradesByStudent): EloquentCollection
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
            return new EloquentCollection;
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Compute attendance tallies for student.
     *
     * @return array{present: int, absent: int, late: int, excused: int}
     */
    private function computeAttendanceCounts(Student $student, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Attendance::query()
            ->where('student_id', $student->id);

        if ($fromDate || $toDate) {
            $query->whereHas('dailyLog', function (Builder $q) use ($fromDate, $toDate): void {
                if ($fromDate) {
                    $q->whereDate('date', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->whereDate('date', '<=', $toDate);
                }
            });
        }

        $counts = $query
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
     * Compute behavior observation counts.
     *
     * @return array{positive: int, negative: int}
     */
    private function computeBehaviorCounts(Student $student, ?string $fromDate = null, ?string $toDate = null): array
    {
        $positiveCategories = ['positive', 'participation', 'helpfulness', 'leadership', 'improvement', 'other_positive'];
        $negativeCategories = ['negative', 'disciplinary', 'disruption', 'disrespect', 'violence', 'cheating', 'absence_without_leave', 'other_negative'];

        $query = BehaviorObservation::where('student_id', $student->id);

        if ($fromDate) {
            $query->whereDate('date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('date', '<=', $toDate);
        }

        $obs = $query
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
     * Compute classroom-level summary KPIs.
     *
     * @param  Collection<int, array<string, mixed>>  $students
     * @return array<string, int|float|null>
     */
    private function computeClassroomKpis(Collection $students): array
    {
        $totalStudents = $students->count();
        $gradedStudents = $students->filter(fn ($s): bool => $s['general_average'] !== null)->values();
        $gradedCount = $gradedStudents->count();

        $averages = $gradedStudents->pluck('general_average');

        $honoredStudents = $students->filter(fn ($s): bool => $s['has_awards'])->count();

        $excellenceCount = $students->filter(fn ($s): bool => in_array(StudentAwardType::Excellence, $s['eligible_awards'], true))->count();
        $encouragementCount = $students->filter(fn ($s): bool => in_array(StudentAwardType::Encouragement, $s['eligible_awards'], true))->count();
        $honorRollCount = $students->filter(fn ($s): bool => in_array(StudentAwardType::HonorRoll, $s['eligible_awards'], true))->count();
        $goodConductCount = $students->filter(fn ($s): bool => in_array(StudentAwardType::GoodConduct, $s['eligible_awards'], true))->count();

        return [
            'total_students' => $totalStudents,
            'graded_students' => $gradedCount,
            'total_honored' => $honoredStudents,
            'excellence_count' => $excellenceCount,
            'encouragement_count' => $encouragementCount,
            'honor_roll_count' => $honorRollCount,
            'good_conduct_count' => $goodConductCount,
            'highest_average' => $gradedCount > 0 ? (float) $averages->max() : null,
            'lowest_average' => $gradedCount > 0 ? (float) $averages->min() : null,
            'class_general_average' => $gradedCount > 0 ? round((float) $averages->avg(), 2) : null,
        ];
    }

    /**
     * Format Arabic rank string (e.g. الأولى، الثانية...).
     */
    public function formatArabicRank(int $rank): string
    {
        return match ($rank) {
            1 => __('المرتبة الأولى'),
            2 => __('المرتبة الثانية'),
            3 => __('المرتبة الثالثة'),
            4 => __('المرتبة الرابعة'),
            5 => __('المرتبة الخامسة'),
            6 => __('المرتبة السادسة'),
            7 => __('المرتبة السابعة'),
            8 => __('المرتبة الثامنة'),
            9 => __('المرتبة التاسعة'),
            10 => __('المرتبة العاشرة'),
            default => __('المرتبة :rank', ['rank' => $rank]),
        };
    }

    /**
     * Prepare full data payload for rendering single student certificate.
     *
     * @return array<string, mixed>
     */
    public function prepareSingleCertificateData(
        Student $student,
        StudentAwardType|string $awardType,
        ?int $trimester = 1
    ): array {
        $student->loadMissing(['group.classroom.level.educationPhase']);
        $classroom = $student->group?->classroom;

        if (is_string($awardType)) {
            $awardType = StudentAwardType::from($awardType);
        }

        $period = $this->getTrimesterPeriod($trimester);
        $schoolSetting = SchoolSetting::current();
        $academicYear = $classroom?->academic_year ?? $schoolSetting?->academic_year ?? '2026/2027';

        // Calculate student metrics within classroom context if classroom exists
        $rank = null;
        $rankLabel = null;
        $generalAverage = null;

        if ($classroom) {
            $classroomAwards = $this->computeClassroomAwards($classroom, $trimester);
            $studentRecord = collect($classroomAwards['students'])->firstWhere('student.id', $student->id);

            if ($studentRecord) {
                $rank = $studentRecord['rank'] ?? null;
                $rankLabel = $studentRecord['rank_label'] ?? null;
                $generalAverage = $studentRecord['general_average'] ?? null;
            }
        }

        $citation = $this->getCitationText($awardType, $period['name'], $generalAverage, $rankLabel);

        return [
            'student' => $student,
            'classroom' => $classroom,
            'awardType' => $awardType,
            'trimester' => $trimester,
            'period' => $period,
            'rank' => $rank,
            'rankLabel' => $rankLabel,
            'generalAverage' => $generalAverage,
            'citation' => $citation,
            'schoolSetting' => $schoolSetting,
            'academicYear' => $academicYear,
            'issueDate' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Generate official ministerial commendation text for certificate.
     */
    public function getCitationText(
        StudentAwardType $awardType,
        string $periodName,
        ?float $average = null,
        ?string $rankLabel = null
    ): string {
        $formattedAvg = $average !== null ? number_format($average, 2) : null;

        return match ($awardType) {
            StudentAwardType::Excellence => sprintf(
                __('تقديراً لنتائجه الدراسية الباهرة وتفوقه النموذجي خلال %s، وحصوله على معدل ممتاز (%s / 20)%s، متمنين له دوام التألق والريادة.'),
                $periodName,
                $formattedAvg ?? '—',
                $rankLabel ? ' بـ '.$rankLabel : ''
            ),
            StudentAwardType::Encouragement => sprintf(
                __('تشجيعاً لجهوده الملموسة وسعيه الدؤوب في تحصيل العلم والمعرفة خلال %s، وحصوله على معدل مشرف (%s / 20)، وتحفيزاً له على مواصلة مسار التميز.'),
                $periodName,
                $formattedAvg ?? '—'
            ),
            StudentAwardType::HonorRoll => sprintf(
                __('اعترافاً بجديته واجتهاده المتواصل في مسيرته التربوية خلال %s، واستحقاقه عن جدارة مكانة مستحقة في لوحة شرف المؤسسة بمعدل (%s / 20).'),
                $periodName,
                $formattedAvg ?? '—'
            ),
            StudentAwardType::GoodConduct => sprintf(
                __('تنويهاً بأخلاقه الفاضلة وسلوكه المثالي وانضباطه العالي واحترامه للنظام الداخلي للمؤسسة طيلة %s، ليكون قدوة حسنة لزملائه.'),
                $periodName
            ),
        };
    }

    /**
     * Render single certificate HTML.
     */
    public function renderSingleCertificateHtml(Student $student, StudentAwardType|string $awardType, ?int $trimester = 1): string
    {
        $data = $this->prepareSingleCertificateData($student, $awardType, $trimester);
        $data['certificates'] = [$data];

        return view('print.student-award-certificate', $data)->render();
    }

    /**
     * Generate single certificate PDF stream using Browsershot.
     */
    public function generateSingleCertificatePdf(Student $student, StudentAwardType|string $awardType, ?int $trimester = 1): string
    {
        $html = $this->renderSingleCertificateHtml($student, $awardType, $trimester);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    /**
     * Render batch classroom certificates HTML.
     */
    public function renderClassroomCertificatesHtml(
        Classroom $classroom,
        ?int $trimester = 1,
        ?int $groupId = null,
        ?StudentAwardType $awardTypeFilter = null
    ): string {
        $classroomData = $this->computeClassroomAwards($classroom, $trimester, $groupId, $awardTypeFilter);

        $certificates = [];
        foreach ($classroomData['students'] as $row) {
            $student = $row['student'];
            // Determine award to print: filtered award or primary award
            $targetAward = $awardTypeFilter ?? $row['primary_award'];

            if ($targetAward !== null) {
                $certificates[] = $this->prepareSingleCertificateData($student, $targetAward, $trimester);
            }
        }

        return view('print.student-award-certificate', [
            'classroom' => $classroom,
            'certificates' => $certificates,
            'schoolSetting' => $classroomData['schoolSetting'],
            'academicYear' => $classroomData['academicYear'],
            'period' => $classroomData['period'],
        ])->render();
    }

    /**
     * Generate batch classroom certificates PDF using Browsershot.
     */
    public function generateClassroomCertificatesPdf(
        Classroom $classroom,
        ?int $trimester = 1,
        ?int $groupId = null,
        ?StudentAwardType $awardTypeFilter = null
    ): string {
        $html = $this->renderClassroomCertificatesHtml($classroom, $trimester, $groupId, $awardTypeFilter);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    /**
     * Render Classroom Bulletin Board Honor Roll Sheet HTML.
     */
    public function renderHonorRollSheetHtml(
        Classroom $classroom,
        ?int $trimester = 1,
        ?int $groupId = null
    ): string {
        $data = $this->computeClassroomAwards($classroom, $trimester, $groupId);

        return view('print.classroom-honor-roll', $data)->render();
    }

    /**
     * Generate Classroom Bulletin Board Honor Roll Sheet PDF using Browsershot.
     */
    public function generateHonorRollSheetPdf(
        Classroom $classroom,
        ?int $trimester = 1,
        ?int $groupId = null
    ): string {
        $html = $this->renderHonorRollSheetHtml($classroom, $trimester, $groupId);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    protected function browsershot(string $html): Browsershot
    {
        return BrowsershotFactory::html($html)
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->timeout(180);
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
