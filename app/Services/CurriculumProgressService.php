<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\Level;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Derives curriculum progress from completed daily logs.
 *
 * Progress is intentionally calculated on demand instead of being stored in a
 * mutable counter: completing, editing, or reopening a log immediately changes
 * the percentage, and deleting a curriculum resource cannot leave stale
 * progress behind.
 */
class CurriculumProgressService
{
    /**
     * One summary row per classroom + subject pair that has at least one
     * scheduled lesson. Teachers only receive pairs they actually teach.
     *
     * @return Collection<int, array{
     *     classroom: Classroom,
     *     subject: Subject,
     *     level: Level|null,
     *     total_resources: int,
     *     completed_resources: int,
     *     percentage: int,
     *     total_sequences: int,
     *     completed_sequences: int
     * }>
     */
    public function overview(?User $user = null): Collection
    {
        $teacherId = $user?->isTeacher() ? $user->id : null;

        $pairs = DB::table('schedules')
            ->leftJoin('groups', 'groups.id', '=', 'schedules.group_id')
            ->selectRaw('COALESCE(schedules.classroom_id, groups.classroom_id) as classroom_id, schedules.subject_id')
            ->whereRaw('COALESCE(schedules.classroom_id, groups.classroom_id) IS NOT NULL')
            ->when($teacherId, fn ($query) => $query->where('schedules.user_id', $teacherId))
            ->distinct()
            ->get();

        if ($pairs->isEmpty()) {
            return collect();
        }

        $classrooms = Classroom::query()
            ->with('level')
            ->whereIn('id', $pairs->pluck('classroom_id')->unique())
            ->get()
            ->keyBy('id');

        $subjects = Subject::query()
            ->whereIn('id', $pairs->pluck('subject_id')->unique())
            ->get()
            ->keyBy('id');

        $domains = CurriculumDomain::query()
            ->whereIn('subject_id', $subjects->keys())
            ->whereIn('level_id', $classrooms->pluck('level_id')->unique())
            ->with(['sequences.resources' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (CurriculumDomain $domain): string => $domain->subject_id.':'.$domain->level_id);

        $completedRows = DB::table('daily_logs')
            ->join('schedules', 'schedules.id', '=', 'daily_logs.schedule_id')
            ->leftJoin('groups', 'groups.id', '=', 'schedules.group_id')
            ->where('daily_logs.status', DailyLogStatus::Completed->value)
            ->whereNotNull('daily_logs.curriculum_resource_id')
            ->selectRaw('COALESCE(schedules.classroom_id, groups.classroom_id) as classroom_id, schedules.subject_id, daily_logs.curriculum_resource_id')
            ->distinct()
            ->get();

        $completedByPair = $completedRows
            ->groupBy(fn ($row): string => $row->classroom_id.':'.$row->subject_id)
            ->map(fn (Collection $rows): Collection => $rows
                ->pluck('curriculum_resource_id')
                ->map(fn ($id): int => (int) $id)
                ->unique());

        return $pairs
            ->map(function ($pair) use ($classrooms, $subjects, $domains, $completedByPair): ?array {
                $classroom = $classrooms->get($pair->classroom_id);
                $subject = $subjects->get($pair->subject_id);

                if (! $classroom || ! $subject) {
                    return null;
                }

                $pairDomains = $domains->get(
                    $subject->id.':'.$classroom->level_id,
                    collect(),
                );

                $resources = $pairDomains->flatMap(
                    fn (CurriculumDomain $domain): Collection => $domain->sequences
                        ->flatMap(fn (CurriculumSequence $sequence): Collection => $sequence->resources),
                );

                $completedIds = $completedByPair->get(
                    $classroom->id.':'.$subject->id,
                    collect(),
                );

                $completedResources = $resources
                    ->filter(fn (CurriculumResource $resource): bool => $completedIds->contains($resource->id))
                    ->count();

                $totalSequences = $pairDomains->sum(
                    fn (CurriculumDomain $domain): int => $domain->sequences->count(),
                );

                $completedSequences = $pairDomains->sum(
                    fn (CurriculumDomain $domain): int => $domain->sequences
                        ->filter(fn (CurriculumSequence $sequence): bool => $sequence->resources->isNotEmpty()
                            && $sequence->resources->every(
                                fn (CurriculumResource $resource): bool => $completedIds->contains($resource->id),
                            ))
                        ->count(),
                );

                return [
                    'classroom' => $classroom,
                    'subject' => $subject,
                    'level' => $classroom->level,
                    'total_resources' => $resources->count(),
                    'completed_resources' => $completedResources,
                    'percentage' => $this->percentage($completedResources, $resources->count()),
                    'total_sequences' => $totalSequences,
                    'completed_sequences' => $completedSequences,
                ];
            })
            ->filter()
            ->sortBy(fn (array $row): string => sprintf(
                '%s|%s',
                $row['classroom']->name,
                $row['subject']->name,
            ))
            ->values();
    }

    /**
     * Detailed progress for one classroom + subject, grouped by domain and
     * sequence. A sequence is complete only when every one of its knowledge
     * resources has at least one completed daily log.
     *
     * @return array{
     *     classroom: Classroom,
     *     subject: Subject,
     *     level: Level|null,
     *     domains: list<array{
     *         domain: CurriculumDomain,
     *         total_resources: int,
     *         completed_resources: int,
     *         percentage: int,
     *         status: string,
     *         sequences: list<array{
     *             sequence: CurriculumSequence,
     *             total_resources: int,
     *             completed_resources: int,
     *             percentage: int,
     *             status: string,
     *             resources: list<array{resource: CurriculumResource, completed: bool}>
     *         }>
     *     }>,
     *     total_resources: int,
     *     completed_resources: int,
     *     percentage: int,
     *     total_sequences: int,
     *     completed_sequences: int
     * }
     */
    public function detail(Classroom $classroom, Subject $subject): array
    {
        $classroom->loadMissing('level');

        $domains = CurriculumDomain::query()
            ->where('subject_id', $subject->id)
            ->where('level_id', $classroom->level_id)
            ->with(['sequences.resources' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $completedIds = DailyLog::query()
            ->where('status', DailyLogStatus::Completed->value)
            ->whereNotNull('curriculum_resource_id')
            ->whereHas('schedule', function ($query) use ($classroom, $subject): void {
                $query->where('subject_id', $subject->id)
                    ->where(function ($query) use ($classroom): void {
                        $query->where('classroom_id', $classroom->id)
                            ->orWhereHas('group', fn ($query) => $query->where('classroom_id', $classroom->id));
                    });
            })
            ->pluck('curriculum_resource_id')
            ->map(fn ($id): int => (int) $id)
            ->unique();

        $domainRows = [];
        $totalResources = 0;
        $completedResources = 0;
        $totalSequences = 0;
        $completedSequences = 0;

        foreach ($domains as $domain) {
            $domainTotal = 0;
            $domainCompleted = 0;
            $sequenceRows = [];

            foreach ($domain->sequences as $sequence) {
                $sequenceTotal = $sequence->resources->count();
                $sequenceCompleted = $sequence->resources
                    ->filter(fn (CurriculumResource $resource): bool => $completedIds->contains($resource->id))
                    ->count();

                $domainTotal += $sequenceTotal;
                $domainCompleted += $sequenceCompleted;
                $totalSequences++;

                if ($sequenceTotal > 0 && $sequenceCompleted === $sequenceTotal) {
                    $completedSequences++;
                }

                $sequenceRows[] = [
                    'sequence' => $sequence,
                    'total_resources' => $sequenceTotal,
                    'completed_resources' => $sequenceCompleted,
                    'percentage' => $this->percentage($sequenceCompleted, $sequenceTotal),
                    'status' => $this->status($sequenceCompleted, $sequenceTotal),
                    'resources' => $sequence->resources
                        ->map(fn (CurriculumResource $resource): array => [
                            'resource' => $resource,
                            'completed' => $completedIds->contains($resource->id),
                        ])
                        ->all(),
                ];
            }

            $totalResources += $domainTotal;
            $completedResources += $domainCompleted;

            $domainRows[] = [
                'domain' => $domain,
                'total_resources' => $domainTotal,
                'completed_resources' => $domainCompleted,
                'percentage' => $this->percentage($domainCompleted, $domainTotal),
                'status' => $this->status($domainCompleted, $domainTotal),
                'sequences' => $sequenceRows,
            ];
        }

        return [
            'classroom' => $classroom,
            'subject' => $subject,
            'level' => $classroom->level,
            'domains' => $domainRows,
            'total_resources' => $totalResources,
            'completed_resources' => $completedResources,
            'percentage' => $this->percentage($completedResources, $totalResources),
            'total_sequences' => $totalSequences,
            'completed_sequences' => $completedSequences,
        ];
    }

    private function percentage(int $completed, int $total): int
    {
        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    private function status(int $completed, int $total): string
    {
        if ($total === 0 || $completed === 0) {
            return 'not_started';
        }

        return $completed >= $total ? 'completed' : 'in_progress';
    }
}
