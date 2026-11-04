<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\Classroom;
use App\Models\CurriculumResource;
use App\Models\DailyLog;
use App\Models\InspectionReport;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;

class PedagogicalInspectionService
{
    /**
     * Compute curriculum velocity and parallel section pacing.
     *
     * @return array<string, mixed>
     */
    public function getCurriculumVelocity(int $subjectId, int $levelId): array
    {
        $subject = Subject::find($subjectId);
        $level = Level::with('educationPhase')->find($levelId);

        if (! $subject || ! $level) {
            return [
                'subject' => null,
                'level' => null,
                'total_resources' => 0,
                'max_completed' => 0,
                'classrooms' => [],
            ];
        }

        // Count total curriculum resources for this subject and level
        $totalResources = CurriculumResource::query()
            ->whereHas('curriculumSequence.curriculumDomain', function ($q) use ($subjectId, $levelId) {
                $q->where('subject_id', $subjectId)->where('level_id', $levelId);
            })
            ->count();

        $classrooms = Classroom::query()
            ->where('level_id', $levelId)
            ->orderBy('name')
            ->get();

        $classroomData = [];
        $maxCompleted = 0;

        foreach ($classrooms as $classroom) {
            // Find distinct completed resources for this classroom & subject
            $completedCount = DailyLog::query()
                ->where('status', DailyLogStatus::Completed)
                ->whereNotNull('curriculum_resource_id')
                ->whereHas('schedule', function ($q) use ($subjectId, $classroom) {
                    $q->where('subject_id', $subjectId)
                        ->where(function ($sq) use ($classroom) {
                            $sq->where('classroom_id', $classroom->id)
                                ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroom->id));
                        });
                })
                ->distinct('curriculum_resource_id')
                ->count('curriculum_resource_id');

            // Find the primary teacher assigned to this classroom for this subject
            $schedule = Schedule::query()
                ->where('subject_id', $subjectId)
                ->where(function ($q) use ($classroom) {
                    $q->where('classroom_id', $classroom->id)
                        ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroom->id));
                })
                ->with('user')
                ->first();

            $teacher = $schedule?->user;

            if ($completedCount > $maxCompleted) {
                $maxCompleted = $completedCount;
            }

            $percent = $totalResources > 0 ? round(($completedCount / $totalResources) * 100, 1) : 0;

            $classroomData[] = [
                'classroom' => $classroom,
                'teacher' => $teacher,
                'completed_resources' => $completedCount,
                'progress_percent' => $percent,
            ];
        }

        // Compute pacing status and resource gaps
        foreach ($classroomData as &$item) {
            $gap = $maxCompleted - $item['completed_resources'];
            $item['resource_gap'] = $gap;

            if ($maxCompleted === 0) {
                $item['pacing_status'] = 'on_track';
            } elseif ($gap >= 2) {
                $item['pacing_status'] = 'lagging';
            } elseif ($item['completed_resources'] === $maxCompleted && $maxCompleted > 0) {
                $item['pacing_status'] = 'ahead';
            } else {
                $item['pacing_status'] = 'on_track';
            }
        }
        unset($item);

        return [
            'subject' => $subject,
            'level' => $level,
            'total_resources' => $totalResources,
            'max_completed' => $maxCompleted,
            'classrooms' => $classroomData,
        ];
    }

    /**
     * Compute teacher pedagogical compliance scorecard.
     *
     * @return array<string, mixed>
     */
    public function getTeacherPedagogicalScorecard(User $teacher): array
    {
        $teacherId = $teacher->id;

        $completedLogsCount = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $visaCount = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereNotNull('reviewed_at')
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $visaRate = $completedLogsCount > 0 ? round(($visaCount / $completedLogsCount) * 100, 1) : 100.0;

        $memosCount = DB::table('pedagogical_memos')
            ->where('user_id', $teacherId)
            ->count();

        $assessmentsCount = DB::table('assessments')
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $inspections = InspectionReport::query()
            ->where('teacher_id', $teacherId)
            ->orderByDesc('visit_date')
            ->get();

        $avgScore = $inspections->count() > 0
            ? round((float) $inspections->avg('pedagogical_score'), 2)
            : null;

        $latestInspection = $inspections->first();

        return [
            'teacher' => $teacher,
            'completed_logs_count' => $completedLogsCount,
            'visa_count' => $visaCount,
            'visa_rate' => $visaRate,
            'memos_count' => $memosCount,
            'assessments_count' => $assessmentsCount,
            'inspections_count' => $inspections->count(),
            'average_score' => $avgScore,
            'latest_inspection' => $latestInspection,
            'inspections' => $inspections,
        ];
    }

    /**
     * Global inspection summary and KPIs.
     *
     * @return array<string, mixed>
     */
    public function getInspectionMetrics(): array
    {
        $totalInspections = InspectionReport::count();
        $inspectedTeachersCount = InspectionReport::distinct('teacher_id')->count('teacher_id');
        $totalTeachersCount = User::where('role', 'teacher')->count();

        $avgScore = $totalInspections > 0
            ? round((float) InspectionReport::avg('pedagogical_score'), 2)
            : null;

        $appreciations = [
            'excellent' => InspectionReport::where('pedagogical_appreciation', 'excellent')->count(),
            'very_good' => InspectionReport::where('pedagogical_appreciation', 'very_good')->count(),
            'good' => InspectionReport::where('pedagogical_appreciation', 'good')->count(),
            'acceptable' => InspectionReport::where('pedagogical_appreciation', 'acceptable')->count(),
            'needs_improvement' => InspectionReport::where('pedagogical_appreciation', 'needs_improvement')->count(),
        ];

        return [
            'total_inspections' => $totalInspections,
            'inspected_teachers_count' => $inspectedTeachersCount,
            'total_teachers_count' => $totalTeachersCount,
            'average_score' => $avgScore,
            'appreciations' => $appreciations,
        ];
    }

    public function renderHtml(InspectionReport $report): string
    {
        return view('print.inspection-report', [
            'report' => $report->load(['teacher', 'subject', 'classroom', 'schedule', 'dailyLog', 'curriculumResource']),
            'schoolSetting' => SchoolSetting::current(),
            'generatedAt' => now(),
            'autoPrint' => false,
        ])->render();
    }

    public function generatePdf(InspectionReport $report): string
    {
        $html = $this->renderHtml($report);

        return $this->browsershot($html)->pdf();
    }

    private function browsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle();

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        foreach ([
            'node_binary' => 'setNodeBinary',
            'npm_binary' => 'setNpmBinary',
            'chrome_path' => 'setChromePath',
        ] as $configKey => $method) {
            $value = config("services.browsershot.{$configKey}");
            if (! empty($value)) {
                $browsershot->{$method}($value);
            }
        }

        return $browsershot;
    }
}
