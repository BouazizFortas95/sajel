<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Grade;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrintAssessmentController extends Controller
{
    public function __invoke(Request $request, string $locale, Assessment $assessment): View
    {
        // The route is available to any authenticated user, but the assessment
        // itself remains scoped to its owner (admins bypass the policy).
        Gate::authorize('view', $assessment);

        $assessment->loadMissing('schedule');

        // Make sure students added to the group/classroom after the assessment
        // was created also appear on the printed sheet.
        $assessment->ensureGradeRecords();

        $assessment->load([
            'schedule.user',
            'schedule.subject',
            'schedule.classroom.level.educationPhase',
            'schedule.group.classroom.level.educationPhase',
        ]);

        $schedule = $assessment->schedule;
        $classroom = $schedule?->classroom ?? $schedule?->group?->classroom;

        $grades = $schedule
            ? $assessment->grades()
                ->with('student.group')
                ->whereHas(
                    'student',
                    fn (Builder $query): Builder => $query->forSchedule($schedule),
                )
                ->get()
                ->sortBy(fn (Grade $grade): string => (string) $grade->student?->full_name)
                ->values()
            : collect();

        $scoredGrades = $grades->filter(
            fn (Grade $grade): bool => $grade->score !== null,
        );

        $scores = $scoredGrades->map(
            fn (Grade $grade): float => (float) $grade->score,
        );

        $totalStudents = $grades->count();
        $gradedCount = $scoredGrades->count();

        return view('print.assessment', [
            'assessment' => $assessment,
            'schedule' => $schedule,
            'classroom' => $classroom,
            'teacher' => $schedule?->user ?? $request->user(),
            'grades' => $grades,
            'totalStudents' => $totalStudents,
            'gradedCount' => $gradedCount,
            'completion' => $totalStudents > 0
                ? (int) round(($gradedCount / $totalStudents) * 100)
                : 0,
            'average' => $gradedCount > 0 ? round((float) $scores->avg(), 2) : null,
            'highest' => $gradedCount > 0 ? (float) $scores->max() : null,
            'lowest' => $gradedCount > 0 ? (float) $scores->min() : null,
            'maxScore' => (float) $assessment->max_score,
            'academicYear' => $classroom?->academic_year ?: $this->currentAcademicYear(),
        ]);
    }

    private function currentAcademicYear(): string
    {
        $startYear = now()->month >= 9 ? now()->year : now()->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
