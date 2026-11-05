<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\Browsershot\Browsershot;

class ExamConflictService
{
    /**
     * Maximum allowed major assessments (Midterms / Exams) per day for a single classroom
     * according to Algerian Middle School regulations.
     */
    public const MAX_MAJOR_ASSESSMENTS_PER_DAY = 2;

    /**
     * Check if a proposed assessment collides with existing assessments.
     *
     * @return array<int, array{type: string, severity: string, message: string}>
     */
    public function checkConflicts(
        Schedule|int $schedule,
        string|CarbonInterface $date,
        AssessmentType|string $type,
        ?int $ignoreAssessmentId = null
    ): array {
        $schedule = $schedule instanceof Schedule
            ? $schedule
            : Schedule::with(['classroom', 'group.classroom', 'subject', 'user'])->find($schedule);

        if (! $schedule) {
            return [];
        }

        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $assessmentType = $type instanceof AssessmentType ? $type : AssessmentType::tryFrom($type);

        $classroom = $schedule->resolvedClassroom();
        $classroomId = $classroom?->id;

        $conflicts = [];

        // Only Midterm and Exam types are subject to the strict 2-per-day ministerial limit
        $isMajor = in_array($assessmentType, [AssessmentType::Midterm, AssessmentType::Exam], true);

        if ($classroomId) {
            // 1. Fetch other assessments for the same classroom on this date
            $existingSameDay = $this->getClassroomDayAssessments($classroomId, $dateString, $ignoreAssessmentId);

            if ($isMajor) {
                $existingMajorCount = $existingSameDay->filter(function (Assessment $a) {
                    return in_array($a->type, [AssessmentType::Midterm, AssessmentType::Exam], true);
                })->count();

                if ($existingMajorCount >= self::MAX_MAJOR_ASSESSMENTS_PER_DAY) {
                    $conflicts[] = [
                        'type' => 'daily_limit',
                        'severity' => 'danger',
                        'message' => __('تنبيه وزاري: يتجاوز القسم الحد الأقصى المسموح به (:max فروض في اليوم الواحد). مبرمج حالياً :count فرض/امتحان في :date.', [
                            'max' => self::MAX_MAJOR_ASSESSMENTS_PER_DAY,
                            'count' => $existingMajorCount,
                            'date' => $dateString,
                        ]),
                    ];
                }
            }

            // 2. Check for Time Overlap Collision for the same classroom/group
            foreach ($existingSameDay as $existing) {
                if ($this->hasTimeOverlap($schedule, $existing->schedule)) {
                    // Check if they target the same students (same classroom or overlapping group)
                    if ($this->targetsSameStudents($schedule, $existing->schedule)) {
                        $conflicts[] = [
                            'type' => 'time_overlap',
                            'severity' => 'danger',
                            'message' => __('تضارب زمني: يوجد تقييم آخر (:title - :subject) مبرمج في نفس الفترة الزمنية (:start - :end).', [
                                'title' => $existing->title,
                                'subject' => $existing->schedule?->subject?->name ?? '',
                                'start' => $existing->schedule?->start_time?->format('H:i') ?? '',
                                'end' => $existing->schedule?->end_time?->format('H:i') ?? '',
                            ]),
                        ];
                    }
                }
            }
        }

        // 3. Check for Teacher Double-Booking Collision
        if ($schedule->user_id) {
            $teacherCollisions = $this->getTeacherCollidingAssessments(
                $schedule->user_id,
                $dateString,
                $schedule,
                $ignoreAssessmentId
            );

            foreach ($teacherCollisions as $collision) {
                $conflicts[] = [
                    'type' => 'teacher_collision',
                    'severity' => 'warning',
                    'message' => __('تضارب إشراف الأستاذ: الأستاذ المشرف لديه تقييم متزامن آخر (:title في قسم :section).', [
                        'title' => $collision->title,
                        'section' => $collision->schedule?->sectionName() ?? '',
                    ]),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Get all assessments for a classroom on a specific day.
     *
     * @return Collection<int, Assessment>
     */
    public function getClassroomDayAssessments(int $classroomId, string $date, ?int $ignoreAssessmentId = null): Collection
    {
        return Assessment::query()
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group', 'schedule.user'])
            ->whereDate('date', $date)
            ->when($ignoreAssessmentId, fn ($q) => $q->where('id', '!=', $ignoreAssessmentId))
            ->whereHas('schedule', function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                    ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroomId));
            })
            ->get();
    }

    /**
     * Check whether two schedules have overlapping time slots.
     */
    public function hasTimeOverlap(?Schedule $s1, ?Schedule $s2): bool
    {
        if (! $s1 || ! $s2 || ! $s1->start_time || ! $s1->end_time || ! $s2->start_time || ! $s2->end_time) {
            return false;
        }

        $s1Start = $s1->start_time->format('H:i');
        $s1End = $s1->end_time->format('H:i');
        $s2Start = $s2->start_time->format('H:i');
        $s2End = $s2->end_time->format('H:i');

        return ($s1Start < $s2End) && ($s1End > $s2Start);
    }

    /**
     * Check if two schedules target overlapping students.
     */
    public function targetsSameStudents(Schedule $s1, Schedule $s2): bool
    {
        // If neither has group_id, both apply to the whole classroom -> collision
        if (! $s1->group_id || ! $s2->group_id) {
            return true;
        }

        // If both specify a group, collision occurs only if they are the exact same group
        return $s1->group_id === $s2->group_id;
    }

    /**
     * Find any other assessment supervised by the same teacher overlapping in time on the given date.
     *
     * @return Collection<int, Assessment>
     */
    public function getTeacherCollidingAssessments(
        int $teacherId,
        string $date,
        Schedule $currentSchedule,
        ?int $ignoreAssessmentId = null
    ): Collection {
        $otherAssessments = Assessment::query()
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group'])
            ->whereDate('date', $date)
            ->when($ignoreAssessmentId, fn ($q) => $q->where('id', '!=', $ignoreAssessmentId))
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId)->where('id', '!=', $currentSchedule->id))
            ->get();

        return $otherAssessments->filter(fn (Assessment $a) => $this->hasTimeOverlap($currentSchedule, $a->schedule));
    }

    /**
     * Get all assessments for a classroom in a date range.
     *
     * @return Collection<int, Assessment>
     */
    public function getClassroomAssessments(
        int $classroomId,
        ?string $from = null,
        ?string $to = null,
        ?string $type = null
    ): Collection {
        return Assessment::query()
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group', 'schedule.user'])
            ->whereHas('schedule', function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                    ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroomId));
            })
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->when($type && $type !== 'all', fn ($q) => $q->where('type', $type))
            ->orderBy('date')
            ->orderBy(
                Schedule::select('start_time')
                    ->whereColumn('schedules.id', 'assessments.schedule_id')
                    ->limit(1)
            )
            ->get();
    }

    /**
     * Detect all conflicts in a classroom's assessment schedule within a date range.
     * Returns an array of [assessment_id => list of conflicts].
     *
     * @return array<int, array<int, array{type: string, severity: string, message: string}>>
     */
    public function detectClassroomConflicts(int $classroomId, ?string $from = null, ?string $to = null): array
    {
        $assessments = $this->getClassroomAssessments($classroomId, $from, $to);
        $conflictsByAssessment = [];

        foreach ($assessments as $assessment) {
            if (! $assessment->schedule) {
                continue;
            }

            $dateString = $assessment->date?->toDateString() ?? '';
            if (blank($dateString)) {
                continue;
            }

            $clashes = $this->checkConflicts(
                $assessment->schedule,
                $dateString,
                $assessment->type,
                $assessment->id
            );

            if (! empty($clashes)) {
                $conflictsByAssessment[$assessment->id] = $clashes;
            }
        }

        return $conflictsByAssessment;
    }

    /**
     * Resolve date boundaries for Algerian academic trimesters.
     *
     * @return array{from: string, to: string, name: string}
     */
    public function getTrimesterPeriod(int $trimester = 1, ?int $year = null): array
    {
        $year = $year ?? (int) now()->format('Y');

        // Algerian School Year spans September (year) to June (year+1)
        // If current month is Jan-Aug, base year is previous year
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
     * Generate printable PDF for classroom exam timetable using Browsershot.
     */
    public function generatePdf(Classroom $classroom, int $trimester = 1): string
    {
        $period = $this->getTrimesterPeriod($trimester);
        $assessments = $this->getClassroomAssessments($classroom->id, $period['from'], $period['to']);
        $conflicts = $this->detectClassroomConflicts($classroom->id, $period['from'], $period['to']);

        $view = view('print.exam-timetable', [
            'classroom' => $classroom,
            'assessments' => $assessments,
            'trimester' => $trimester,
            'period' => $period,
            'conflicts' => $conflicts,
            'schoolSetting' => SchoolSetting::current(),
            'academicYear' => $classroom->academic_year ?? SchoolSetting::current()?->academic_year ?? '2026/2027',
            'inlineCss' => true,
        ]);

        return Browsershot::html((string) $view)
            ->format('A4')
            ->portrait()
            ->setOption('args', ['--no-sandbox'])
            ->pdf();
    }
}
