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
            ->whereHas('schedule', fn (Builder $q) => $q->where('classroom_id', $group?->classroom_id)->orWhere('group_id', $group?->id))
            ->where('status', DailyLogStatus::Completed->value)
            ->whereNotNull('homework')
            ->orWhereNotNull('actual_content')
            ->orderByDesc('date')
            ->limit(10)
            ->with('schedule.subject')
            ->get();

        $attendanceCounts = $student->attendances()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();
        $attendanceTotal = array_sum($attendanceCounts);
        $attendanceRate = $attendanceTotal > 0 ? round(($attendanceCounts['present'] ?? 0) / $attendanceTotal * 100, 1) : 0;

        $gradesBySubject = $student->grades()->with('assessment.schedule.subject')->get()->groupBy(fn ($g) => $g->assessment?->schedule?->subject?->name ?? '—');

        $recentAbsences = $student->attendances()->whereIn('status', ['absent', 'late'])->orderByDesc('created_at')->limit(5)->get();

        $observations = BehaviorObservation::where('student_id', $student->id)->with('schedule.subject')->orderByDesc('date')->limit(10)->get();
        $remediations = PedagogicalRemediation::where('student_id', $student->id)->with('schedule.subject')->orderByDesc('session_date')->limit(5)->get();

        return [
            'student' => $student,
            'classroom' => $group?->classroom,
            'group' => $group,
            'recentLogs' => $recentLogs,
            'attendanceCounts' => $attendanceCounts,
            'attendanceRate' => $attendanceRate,
            'gradesBySubject' => $gradesBySubject,
            'recentAbsences' => $recentAbsences,
            'observations' => $observations,
            'remediations' => $remediations,
            'portalUrl' => '',
        ];
    }
}
