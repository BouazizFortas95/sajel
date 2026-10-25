<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\BehaviorObservation;
use App\Models\DailyLog;
use App\Models\PedagogicalRemediation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class ParentPortalService
{
    public function viewData(Student $student): array
    {
        $student->loadMissing('group.classroom.level.educationPhase', 'grades.assessment.schedule.subject', 'attendances', 'group.classroom');
        $group = $student->group;

        $recentLogs = DailyLog::query()
            ->whereHas('schedule', function (Builder $query) use ($group): void {
                if (! $group) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function (Builder $query) use ($group): void {
                    // Mirror Student::scopeForSchedule(): a group-scoped
                    // schedule belongs only to that group, even when it also
                    // carries the classroom id. Only classroom-wide schedules
                    // (no group) are shared by every group in the classroom.
                    $query->where('group_id', $group->id)
                        ->orWhere(function (Builder $query) use ($group): void {
                            $query->whereNull('group_id')
                                ->where('classroom_id', $group->classroom_id);
                        });
                });
            })
            ->where('status', DailyLogStatus::Completed->value)
            ->where(function (Builder $query): void {
                $query->whereNotNull('homework')->orWhereNotNull('actual_content');
            })
            ->orderByDesc('date')
            ->limit(10)
            ->with('schedule.subject')
            ->get();

        $rawAttendanceCounts = $student->attendances()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $attendanceCounts = [
            'present' => (int) ($rawAttendanceCounts['present'] ?? 0),
            'absent' => (int) ($rawAttendanceCounts['absent'] ?? 0),
            'late' => (int) ($rawAttendanceCounts['late'] ?? 0),
            'excused' => (int) ($rawAttendanceCounts['excused'] ?? 0),
        ];
        $attendanceTotal = array_sum($attendanceCounts);
        $attendanceRate = $attendanceTotal > 0
            ? round(($attendanceCounts['present'] / $attendanceTotal) * 100, 1)
            : 0;

        $gradesBySubject = $student->grades()
            ->with('assessment.schedule.subject')
            ->get()
            ->groupBy(fn ($grade) => $grade->assessment?->schedule?->subject?->name ?? '—');

        $subjectSummaries = $gradesBySubject
            ->map(function ($grades, string $subject): array {
                $totalScore = 0.0;
                $totalMax = 0.0;

                foreach ($grades as $grade) {
                    $maxScore = (float) ($grade->assessment?->max_score ?? 0);

                    if ($grade->score !== null && $maxScore > 0) {
                        $totalScore += (float) $grade->score;
                        $totalMax += $maxScore;
                    }
                }

                return [
                    'subject' => $subject,
                    'average' => $totalMax > 0 ? round(($totalScore / $totalMax) * 20, 2) : null,
                    'grades' => $grades,
                ];
            })
            ->values();

        $generalAverage = $subjectSummaries
            ->pluck('average')
            ->filter(fn ($average): bool => $average !== null)
            ->avg();
        $generalAverage = $generalAverage !== null ? round((float) $generalAverage, 2) : null;

        $recentAbsences = $student->attendances()
            ->whereIn('status', ['absent', 'late'])
            ->with('dailyLog.schedule.subject')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $observations = BehaviorObservation::where('student_id', $student->id)->with('schedule.subject')->orderByDesc('date')->limit(10)->get();
        $remediations = PedagogicalRemediation::where('student_id', $student->id)->with('schedule.subject')->orderByDesc('session_date')->limit(5)->get();

        return [
            'student' => $student,
            'classroom' => $group?->classroom,
            'group' => $group,
            'recentLogs' => $recentLogs,
            'attendanceCounts' => $attendanceCounts,
            'attendanceTotal' => $attendanceTotal,
            'attendanceRate' => $attendanceRate,
            'gradesBySubject' => $gradesBySubject,
            'subjectSummaries' => $subjectSummaries,
            'generalAverage' => $generalAverage,
            'recentAbsences' => $recentAbsences,
            'observations' => $observations,
            'remediations' => $remediations,
            'portalUrl' => '',
        ];
    }
}
