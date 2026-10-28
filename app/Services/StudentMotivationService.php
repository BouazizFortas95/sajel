<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Group;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Zero-Click Pedagogical Motivation Engine (نبض التميز والتحفيز التربوي)
 *
 * Computes badges, pulse scores, classroom synergy, and momentum metrics
 * directly from Attendance, Grades, BehaviorObservations, DailyLogs,
 * Assessments, and PedagogicalRemediations — no manual clicking required.
 */
class StudentMotivationService
{
    /**
     * Compute badges for a student within a given scope.
     *
     * Scope is a single classroom when a teacher calls (via their schedules),
     * or school-wide for admin / portal context.
     */
    public function badgesForStudent(Student $student, ?Classroom $scopeClassroom = null): array
    {
        $badges = [];

        if ($this->attendanceStreak($student, $scopeClassroom)) {
            $badges['attendance_streak'] = [
                'label' => __('وسام الانضباط الذهبي 🔥'),
                'description' => __('حضور متواصل بدون غياب غير مبرر لمدة 5 أيام متتالية.'),
            ];
        }

        if ($this->comebackChampion($student, $scopeClassroom)) {
            $badges['comeback_champion'] = [
                'label' => __('وسام قفزة التحدي 🚀'),
                'description' => __('تحسن ملحوظ في الأداء أو إكمال جلسة معالجة إيجابية.'),
            ];
        }

        if ($this->academicExcellence($student, $scopeClassroom)) {
            $badges['academic_excellence'] = [
                'label' => __('وسام التفوق العلمي ⭐'),
                'description' => __('متوسط ≥ 16/20 أو علامة كاملة في أي تقييم.'),
            ];
        }

        if ($this->positiveRoleModel($student, $scopeClassroom)) {
            $badges['positive_role_model'] = [
                'label' => __('وسام القدوة والتميز السلوكي 🤝'),
                'description' => __('سلوك إيجابي متواصل بدون تنبيهات انضباطية.'),
            ];
        }

        return $badges;
    }

    /**
     * Compute a weighted Pulse Score for a student (0–100 scale conceptually).
     */
    public function pulseScore(Student $student, ?Classroom $scopeClassroom = null): int
    {
        $score = 0;

        // Attendance consistency (+5 per present session in last 14 days)
        $presentCount = $this->presentCountInWindow($student, $scopeClassroom, now()->subDays(13), now());
        $score += $presentCount * 5;

        // Attendance streak bonus (+15 for streak >= 5)
        $streak = $this->attendanceStreakCount($student, $scopeClassroom);
        if ($streak >= 5) {
            $score += 15;
        }

        // Positive behavior observations (+10 each in last 14 days)
        $positiveObservations = $this->positiveObservationsInWindow($student, $scopeClassroom, now()->subDays(13), now());
        $score += $positiveObservations * 10;

        // Remediation attendance commitment (+15 per attended session)
        $remediations = $this->attendedRemediations($student, $scopeClassroom);
        $score += $remediations * 15;

        // Academic growth momentum (+20 when grades improve between assessments)
        $improvedSubjectCount = $this->improvedAssessmentCount($student, $scopeClassroom);
        $score += $improvedSubjectCount * 20;

        // Cap to a practical high (soft cap at 150+ for exceptional)
        return min($score, 200);
    }

    /**
     * Find the classroom / group scope for a student based on the schedule
     * relationship (mirror of Student::scopeForSchedule logic).
     */
    public function scopeClassroom(Student $student): ?Classroom
    {
        $group = $student->group;

        if ($group) {
            return $group->classroom;
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Badge logic */
    /* ------------------------------------------------------------------ */

    private function attendanceStreak(Student $student, ?Classroom $scopeClassroom = null): bool
    {
        $last14 = $this->attendanceDays($student, $scopeClassroom)
            ->filter(fn (array $day): bool => $day['date']->gte(now()->subDays(14)->startOfDay()));

        $streak = 0;
        $maxStreak = 0;
        $prevDay = null;

        foreach ($last14 as $day) {
            if ($day['status'] === AttendanceStatus::Present) {
                $currentDay = $day['date'];

                if ($prevDay !== null && $prevDay->copy()->addDay()->isSameDay($currentDay)) {
                    $streak++;
                } else {
                    $streak = 1;
                }

                $prevDay = $currentDay;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 0;
                $prevDay = null;
            }
        }

        // A true streak requires consecutive present days (reset on any gap/non-present).
        $absentCount = $last14
            ->filter(fn (array $day): bool => $day['status'] === AttendanceStatus::Absent)
            ->count();

        return $maxStreak >= 5 && $absentCount === 0;
    }

    private function attendanceStreakCount(Student $student, ?Classroom $scopeClassroom = null): int
    {
        $last14 = $this->attendanceDays($student, $scopeClassroom)
            ->filter(fn (array $day): bool => $day['date']->gte(now()->subDays(14)->startOfDay()));

        $streak = 0;
        $currentStreak = 0;
        $prevDay = null;

        foreach ($last14 as $day) {
            if ($day['status'] === AttendanceStatus::Present) {
                $currentDay = $day['date'];

                if ($prevDay !== null && $prevDay->copy()->addDay()->isSameDay($currentDay)) {
                    $currentStreak++;
                } else {
                    $currentStreak = 1;
                }

                $prevDay = $currentDay;
                $streak = max($streak, $currentStreak);
            } else {
                $currentStreak = 0;
                $prevDay = null;
            }
        }

        return $streak;
    }

    /**
     * Collapse all attendance rows into one state per school day.
     *
     * Attendance is recorded per session, so a student can have several rows
     * on the same date. A day only counts as present when every session that
     * day was present; any absence/lateness/excuse breaks the streak.
     *
     * @return SupportCollection<int, array{date: Carbon, status: AttendanceStatus}>
     */
    private function attendanceDays(Student $student, ?Classroom $scopeClassroom = null): SupportCollection
    {
        return $this->scopedAttendances($student, $scopeClassroom)
            ->filter(fn (Attendance $attendance): bool => $attendance->dailyLog?->date !== null)
            ->groupBy(fn (Attendance $attendance): string => $attendance->dailyLog->date->toDateString())
            ->map(function ($dayAttendances): array {
                $nonPresent = $dayAttendances->first(
                    fn (Attendance $attendance): bool => $attendance->status !== AttendanceStatus::Present,
                );

                return [
                    'date' => $dayAttendances->first()->dailyLog->date->copy()->startOfDay(),
                    'status' => $nonPresent?->status ?? AttendanceStatus::Present,
                ];
            })
            ->sortBy(fn (array $day): int => $day['date']->timestamp)
            ->values();
    }

    private function comebackChampion(Student $student, ?Classroom $scopeClassroom = null): bool
    {
        // Grade improvement: any assessment pair where score increased by >= 2.5 points.
        $grades = Grade::whereHas('assessment.schedule', function ($q) use ($scopeClassroom): void {
            $q->when($scopeClassroom, function ($q) use ($scopeClassroom): void {
                $q->where('classroom_id', $scopeClassroom->id)
                    ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id));
            });
        })
            ->where('student_id', $student->id)
            ->with('assessment')
            ->get();

        $bySubject = $grades->groupBy(fn ($g) => $g->assessment?->subject_id ?? 0);

        foreach ($bySubject as $subjectGrades) {
            $sorted = $subjectGrades->sortBy(fn ($g) => $g->assessment?->date ?? now())->values();

            for ($i = 1; $i < $sorted->count(); $i++) {
                $prevScore = (float) ($sorted[$i - 1]->score ?? 0);
                $currentScore = (float) ($sorted[$i]->score ?? 0);
                $maxScore = (float) ($sorted[$i]->assessment?->max_score ?? 1);

                $prevNormalized = $maxScore > 0 ? ($prevScore / $maxScore) * 20 : 0;
                $currentNormalized = $maxScore > 0 ? ($currentScore / $maxScore) * 20 : 0;

                if ($currentNormalized - $prevNormalized >= 2.0) {
                    return true;
                }
            }
        }

        // Completed remediation session with positive outcome (attended + completed/ongoing).
        $remediations = PedagogicalRemediation::where('student_id', $student->id)
            ->when($scopeClassroom, function ($q) use ($scopeClassroom): void {
                $q->whereHas('schedule', fn ($s) => $s->where('classroom_id', $scopeClassroom->id)
                    ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id)));
            })
            ->where('attended', true)
            ->whereIn('status', ['completed', 'ongoing'])
            ->exists();

        return $remediations;
    }

    private function academicExcellence(Student $student, ?Classroom $scopeClassroom = null): bool
    {
        $grades = Grade::whereHas('assessment.schedule', function ($q) use ($scopeClassroom): void {
            $q->when($scopeClassroom, function ($q) use ($scopeClassroom): void {
                $q->where('classroom_id', $scopeClassroom->id)
                    ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id));
            });
        })
            ->where('student_id', $student->id)
            ->with('assessment')
            ->get();

        foreach ($grades as $grade) {
            $max = (float) ($grade->assessment?->max_score ?? 0);
            $score = (float) ($grade->score ?? 0);

            if ($max > 0) {
                $normalized = ($score / $max) * 20;

                if ($normalized >= 16 || $score == $max) {
                    return true;
                }
            }
        }

        return false;
    }

    private function positiveRoleModel(Student $student, ?Classroom $scopeClassroom = null): bool
    {
        $positiveCount = $this->positiveObservationsInWindow($student, $scopeClassroom);

        $negativeDisciplinary = BehaviorObservation::where('student_id', $student->id)
            ->whereIn('category', ['negative', 'disciplinary'])
            ->when($scopeClassroom, function ($q) use ($scopeClassroom): void {
                $q->whereHas('schedule', fn ($s) => $s->where('classroom_id', $scopeClassroom->id)
                    ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id)));
            })
            ->count();

        return $positiveCount > 0 && $negativeDisciplinary === 0;
    }

    /* ------------------------------------------------------------------ */
    /*  Helper queries */
    /* ------------------------------------------------------------------ */

    private function scopedAttendances(Student $student, ?Classroom $scopeClassroom = null): Collection
    {
        $query = $student->attendances()->with('dailyLog:id,date');

        if ($scopeClassroom) {
            $query->whereHas('dailyLog.schedule', function ($q) use ($scopeClassroom): void {
                $q->where('classroom_id', $scopeClassroom->id)
                    ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id));
            });
        }

        return $query->get();
    }

    private function presentCountInWindow(Student $student, ?Classroom $scopeClassroom, Carbon $from, Carbon $to): int
    {
        return $this->attendanceDays($student, $scopeClassroom)
            ->filter(fn (array $day): bool => $day['status'] === AttendanceStatus::Present
                && ! $day['date']->lt($from) && ! $day['date']->gt($to))
            ->count();
    }

    private function positiveObservationsInWindow(Student $student, ?Classroom $scopeClassroom, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = BehaviorObservation::where('student_id', $student->id)
            ->where('category', 'positive');

        if ($scopeClassroom) {
            $query->whereHas('schedule', fn ($q) => $q->where('classroom_id', $scopeClassroom->id)
                ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id)));
        }

        if ($from) {
            $query->whereDate('date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->whereDate('date', '<=', $to->toDateString());
        }

        return $query->count();
    }

    private function attendedRemediations(Student $student, ?Classroom $scopeClassroom = null): int
    {
        $query = PedagogicalRemediation::where('student_id', $student->id)
            ->where('attended', true);

        if ($scopeClassroom) {
            $query->whereHas('schedule', fn ($q) => $q->where('classroom_id', $scopeClassroom->id)
                ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $scopeClassroom->id)));
        }

        return $query->count();
    }

    private function improvedAssessmentCount(Student $student, ?Classroom $scopeClassroom = null): int
    {
        $grades = Grade::where('student_id', $student->id)
            ->with('assessment.schedule')
            ->get();

        $bySubject = $grades->groupBy(fn ($g) => $g->assessment?->subject_id ?? 0);
        $improvedCount = 0;

        foreach ($bySubject as $subjectGrades) {
            $sorted = $subjectGrades->sortBy(fn ($g) => $g->assessment?->date ?? now())->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prevScore = (float) ($sorted[$i - 1]->score ?? 0);
                $currentScore = (float) ($sorted[$i]->score ?? 0);
                $maxScore = (float) ($sorted[$i]->assessment?->max_score ?? 1);
                $prevNormalized = $maxScore > 0 ? ($prevScore / $maxScore) * 20 : 0;
                $currentNormalized = $maxScore > 0 ? ($currentScore / $maxScore) * 20 : 0;
                if ($currentNormalized - $prevNormalized >= 2.0) {
                    $improvedCount++;
                    break;
                }
            }
        }

        return $improvedCount;
    }

    /**
     * Classroom-level metrics.
     */
    public function classroomMetrics(Classroom $classroom): array
    {
        $studentIds = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->pluck('id');

        $synergyComponents = [
            'attendance_rate' => 0,
            'positive_behavior_ratio' => 0,
            'completed_lessons_rate' => 0,
        ];

        // Weekly attendance rate for students in this classroom
        $weeklyLogs = DailyLog::query()
            ->whereDate('date', '>=', now()->subDays(6)->toDateString())
            ->whereDate('date', '<=', now()->toDateString())
            ->where('status', DailyLogStatus::Completed)
            ->whereHas('schedule', fn (Builder $q) => $q->where('classroom_id', $classroom->id)
                ->orWhereHas('group', fn (Builder $g) => $g->where('classroom_id', $classroom->id)))
            ->count();

        $scheduledSessions = Schedule::query()
            ->where('classroom_id', $classroom->id)
            ->orWhereHas('group', fn (Builder $q) => $q->where('classroom_id', $classroom->id))
            ->count();

        $synergyComponents['completed_lessons_rate'] = $scheduledSessions > 0 ? ($weeklyLogs / max(1, $scheduledSessions)) * 100 : 0;

        // Positive behavior ratio
        $positiveObs = BehaviorObservation::whereIn('category', ['positive'])
            ->whereHas('schedule', fn ($q) => $q->where('classroom_id', $classroom->id)
                ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $classroom->id)))
            ->count();

        $negativeObs = BehaviorObservation::whereIn('category', ['negative', 'disciplinary'])
            ->whereHas('schedule', fn ($q) => $q->where('classroom_id', $classroom->id)
                ->orWhereHas('group', fn ($g) => $g->where('classroom_id', $classroom->id)))
            ->count();

        $synergyComponents['positive_behavior_ratio'] = ($positiveObs + $negativeObs) > 0 ? ($positiveObs / max(1, $positiveObs + $negativeObs)) * 100 : 0;

        // Attendance rate (present / total attendances in last 7 days)
        $attendanceQuery = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('dailyLog', fn (Builder $q) => $q
                ->whereDate('date', '>=', now()->subDays(6)->toDateString())
                ->whereDate('date', '<=', now()->toDateString())
                ->whereHas('schedule', fn (Builder $s) => $s->where('classroom_id', $classroom->id)
                    ->orWhereHas('group', fn (Builder $g) => $g->where('classroom_id', $classroom->id))));

        $totalAttendances = (clone $attendanceQuery)->count();
        $presentAttendances = (clone $attendanceQuery)
            ->where('status', AttendanceStatus::Present->value)
            ->count();

        $synergyComponents['attendance_rate'] = $totalAttendances > 0 ? ($presentAttendances / $totalAttendances) * 100 : 0;

        $synergy = (int) round(
            ($synergyComponents['attendance_rate'] * 0.35)
            + ($synergyComponents['positive_behavior_ratio'] * 0.25)
            + ($synergyComponents['completed_lessons_rate'] * 0.40)
        );

        $topMomentum = $this->topMomentumStudent($classroom);
        $topPulse = $this->topPulseStudent($classroom);

        return [
            'synergy_percentage' => min(100, max(0, $synergy)),
            'components' => $synergyComponents,
            'top_momentum_student' => $topMomentum,
            'top_pulse_student' => $topPulse,
        ];
    }

    public function topMomentumStudent(?Classroom $scopeClassroom = null): ?array
    {
        $students = Student::whereHas('group', fn ($q) => $q->when($scopeClassroom, fn ($g) => $g->where('classroom_id', $scopeClassroom->id)))->get();
        $best = ['student_name' => null, 'improvement' => 0];
        foreach ($students as $student) {
            $grades = Grade::where('student_id', $student->id)->with('assessment.schedule')->get();
            $bySubject = $grades->groupBy(fn ($g) => $g->assessment?->subject_id ?? 0);
            $maxImp = 0;
            foreach ($bySubject as $subjectGrades) {
                $sorted = $subjectGrades->sortBy(fn ($g) => $g->assessment?->date ?? now())->values();
                for ($i = 1; $i < $sorted->count(); $i++) {
                    $prevScore = (float) ($sorted[$i - 1]->score ?? 0);
                    $currentScore = (float) ($sorted[$i]->score ?? 0);
                    $maxScore = (float) ($sorted[$i]->assessment?->max_score ?? 1);
                    $prevNormalized = $maxScore > 0 ? ($prevScore / $maxScore) * 20 : 0;
                    $currentNormalized = $maxScore > 0 ? ($currentScore / $maxScore) * 20 : 0;
                    $delta = $currentNormalized - $prevNormalized;
                    if ($delta > $maxImp) {
                        $maxImp = $delta;
                    }
                }
            }
            if ($maxImp > $best['improvement']) {
                $best = ['student_name' => $student->full_name ?? $student->name ?? ($student->first_name.' '.$student->last_name), 'improvement' => round($maxImp, 2), 'student_id' => $student->id];
            }
        }

        return $best['student_name'] ? $best : null;
    }

    public function topPulseStudent(?Classroom $scopeClassroom = null): ?array
    {
        $students = Student::whereHas('group', fn ($q) => $q->when($scopeClassroom, fn ($g) => $g->where('classroom_id', $scopeClassroom->id)))->get();
        $best = ['student_name' => null, 'streak' => 0];
        foreach ($students as $student) {
            $streak = $this->attendanceStreakCount($student, $scopeClassroom);
            if ($streak > $best['streak']) {
                $best = ['student_name' => $student->full_name ?? $student->name ?? ($student->first_name.' '.$student->last_name), 'streak' => $streak, 'student_id' => $student->id];
            }
        }

        return $best['student_name'] ? $best : null;
    }

    public function topPositiveKudosStudent(?Classroom $scopeClassroom = null): ?array
    {
        $students = Student::whereHas('group', fn ($q) => $q->when($scopeClassroom, fn ($g) => $g->where('classroom_id', $scopeClassroom->id)))->get();
        $best = ['student_name' => null, 'positive_count' => 0, 'student_id' => null];
        foreach ($students as $student) {
            $count = $this->positiveObservationsInWindow($student, $scopeClassroom);
            if ($count > $best['positive_count']) {
                $best = ['student_name' => $student->full_name ?? $student->name ?? ($student->first_name.' '.$student->last_name), 'positive_count' => $count, 'student_id' => $student->id];
            }
        }

        return $best['student_name'] ? $best : null;
    }
}
