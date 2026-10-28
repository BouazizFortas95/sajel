<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\CurriculumProgressService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CurriculumProgress extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.curriculum-progress';

    #[Url(as: 'subject')]
    public ?int $subjectId = null;

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'progress')]
    public string $progressStatus = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'classroom';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function getNavigationLabel(): string
    {
        return __('تقدم المنهاج');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public function getTitle(): string
    {
        return __('تقدم المنهاج');
    }

    /**
     * Start teachers on their first scheduled class instead of an empty page.
     * Admins start with the full school overview.
     */
    public function mount(): void
    {
        $user = Auth::user();

        if (! $user?->isTeacher()) {
            return;
        }

        $schedule = Schedule::query()
            ->forTeacher($user->id)
            ->with(['group'])
            ->first();

        if (! $schedule) {
            return;
        }

        $this->subjectId = $schedule->subject_id;
        $this->classroomId = $schedule->classroom_id ?? $schedule->group?->classroom_id;
    }

    public function showDetail(int $subjectId, int $classroomId): void
    {
        $this->subjectId = $subjectId;
        $this->classroomId = $classroomId;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->subjectId = null;
        $this->classroomId = null;
        $this->resetTableTools();
    }

    /**
     * Keep the table on the first page whenever a tool changes the result set.
     */
    public function updated(string $property): void
    {
        if (in_array($property, [
            'subjectId',
            'classroomId',
            'search',
            'progressStatus',
            'sortBy',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function resetTableTools(): void
    {
        $this->search = '';
        $this->progressStatus = 'all';
        $this->sortBy = 'classroom';
        $this->perPage = 10;
        $this->resetPage();
    }

    /**
     * Export every row that matches the current tools, not just the visible
     * page. Rows are written with a UTF-8 BOM so Arabic text opens cleanly in
     * Excel.
     */
    public function exportCsv(): StreamedResponse
    {
        $rows = $this->filteredOverview;
        $filename = 'curriculum-progress-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                __('القسم'),
                __('المادة'),
                __('المستوى'),
                __('الحالة'),
                __('المقاطع المكتملة'),
                __('الموارد المنجزة'),
                __('نسبة التقدم'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['classroom']->display_name(),
                    $row['subject']->name,
                    $row['level']?->display_name() ?? '',
                    $this->rowStatusLabel($row),
                    $row['completed_sequences'].' / '.$row['total_sequences'],
                    $row['completed_resources'].' / '.$row['total_resources'],
                    $row['percentage'].'%',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getStatusFilterOptionsProperty(): array
    {
        return [
            'all' => __('كل الحالات'),
            'completed' => __('مكتمل'),
            'in_progress' => __('قيد الإنجاز'),
            'not_started' => __('لم يبدأ'),
            'at_risk' => __('في خطر'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSortOptionsProperty(): array
    {
        return [
            'classroom' => __('القسم أولاً'),
            'subject' => __('المادة أولاً'),
            'percentage_desc' => __('التقدم: الأعلى أولاً'),
            'percentage_asc' => __('التقدم: الأدنى أولاً'),
            'completed_desc' => __('الموارد: الأكثر إنجازاً'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPerPageOptionsProperty(): array
    {
        return [
            10 => '10',
            25 => '25',
            50 => '50',
            100 => '100',
        ];
    }

    /**
     * The scope is everything the current user may see, narrowed only by the
     * subject/classroom selectors. KPIs stay stable while search and status
     * filters change the table below them.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getScopedOverviewProperty(): Collection
    {
        return $this->overview
            ->when(
                filled($this->subjectId),
                fn (Collection $rows): Collection => $rows
                    ->filter(fn (array $row): bool => $row['subject']->id === (int) $this->subjectId),
            )
            ->when(
                filled($this->classroomId),
                fn (Collection $rows): Collection => $rows
                    ->filter(fn (array $row): bool => $row['classroom']->id === (int) $this->classroomId),
            )
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getFilteredOverviewProperty(): Collection
    {
        $rows = $this->scopedOverview;
        $search = Str::lower(trim($this->search));

        if ($search !== '') {
            $rows = $rows->filter(function (array $row) use ($search): bool {
                $haystack = implode(' ', array_filter([
                    $row['subject']->name,
                    $row['classroom']->name,
                    $row['classroom']->display_name(),
                    $row['level']?->name,
                ]));

                return Str::contains(Str::lower($haystack), $search);
            });
        }

        $status = $this->normalizedProgressStatus();

        if ($status !== 'all') {
            $rows = $rows->filter(fn (array $row): bool => $status === 'at_risk'
                ? $this->isAtRisk($row)
                : $this->rowStatus($row) === $status);
        }

        return $this->sortRows($rows)->values();
    }

    public function getOverviewPaginatorProperty(): LengthAwarePaginator
    {
        $rows = $this->filteredOverview;
        $perPage = $this->normalizedPerPage();
        $total = $rows->count();

        $page = max(1, (int) $this->getPage());
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return array{
     *     pairs: int,
     *     average: int,
     *     completed_pairs: int,
     *     at_risk: int,
     *     completed_resources: int,
     *     total_resources: int
     * }
     */
    public function getStatsProperty(): array
    {
        $rows = $this->scopedOverview;
        $total = $rows->count();

        return [
            'pairs' => $total,
            'average' => $total > 0 ? (int) round((float) $rows->avg('percentage')) : 0,
            'completed_pairs' => $rows->filter(
                fn (array $row): bool => $this->rowStatus($row) === 'completed',
            )->count(),
            'at_risk' => $rows->filter(fn (array $row): bool => $this->isAtRisk($row))->count(),
            'completed_resources' => (int) $rows->sum('completed_resources'),
            'total_resources' => (int) $rows->sum('total_resources'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatus(array $row): string
    {
        if ($row['total_resources'] === 0 || $row['completed_resources'] === 0) {
            return 'not_started';
        }

        return $row['completed_resources'] >= $row['total_resources']
            ? 'completed'
            : 'in_progress';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatusLabel(array $row): string
    {
        return match ($this->rowStatus($row)) {
            'completed' => __('مكتمل'),
            'in_progress' => __('قيد الإنجاز'),
            default => __('لم يبدأ'),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatusColor(array $row): string
    {
        return match ($this->rowStatus($row)) {
            'completed' => 'success',
            'in_progress' => 'warning',
            default => 'gray',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function isAtRisk(array $row): bool
    {
        return $row['total_resources'] > 0 && $row['percentage'] < 50;
    }

    private function sortRows(Collection $rows): Collection
    {
        return match ($this->normalizedSortBy()) {
            'subject' => $rows->sort(fn (array $a, array $b): int => [
                $a['subject']->name,
                $a['classroom']->name,
            ] <=> [
                $b['subject']->name,
                $b['classroom']->name,
            ]),
            'percentage_desc' => $rows->sort(fn (array $a, array $b): int => [
                $b['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $a['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            'percentage_asc' => $rows->sort(fn (array $a, array $b): int => [
                $a['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $b['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            'completed_desc' => $rows->sort(fn (array $a, array $b): int => [
                $b['completed_resources'],
                $b['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $a['completed_resources'],
                $a['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            default => $rows->sort(fn (array $a, array $b): int => [
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $b['classroom']->name,
                $b['subject']->name,
            ]),
        };
    }

    private function normalizedProgressStatus(): string
    {
        return array_key_exists($this->progressStatus, $this->statusFilterOptions)
            ? $this->progressStatus
            : 'all';
    }

    private function normalizedSortBy(): string
    {
        return array_key_exists($this->sortBy, $this->sortOptions)
            ? $this->sortBy
            : 'classroom';
    }

    private function normalizedPerPage(): int
    {
        return array_key_exists($this->perPage, $this->perPageOptions)
            ? $this->perPage
            : 10;
    }

    /**
     * @return array<int, string>
     */
    public function getSubjectOptionsProperty(): array
    {
        $query = Subject::query()->orderBy('name');

        $user = Auth::user();

        if ($user?->isTeacher()) {
            $query->whereIn(
                'id',
                Schedule::query()
                    ->forTeacher($user->id)
                    ->select('subject_id'),
            );
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public function getClassroomOptionsProperty(): array
    {
        $query = Classroom::query()->with('level')->orderBy('name');

        $user = Auth::user();

        if ($user?->isTeacher()) {
            $schedules = Schedule::query()
                ->forTeacher($user->id)
                ->with(['group'])
                ->get(['classroom_id', 'group_id']);

            $classroomIds = $schedules
                ->map(fn (Schedule $schedule): ?int => $schedule->classroom_id ?? $schedule->group?->classroom_id)
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('id', $classroomIds);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->id => $classroom->display_name(),
            ])
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getOverviewProperty(): Collection
    {
        return app(CurriculumProgressService::class)->overview(Auth::user());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetailProperty(): ?array
    {
        if (blank($this->subjectId) || blank($this->classroomId)) {
            return null;
        }

        $classroom = Classroom::query()->with('level')->find($this->classroomId);
        $subject = Subject::query()->find($this->subjectId);

        if (! $classroom || ! $subject) {
            return null;
        }

        $user = Auth::user();

        if ($user?->isTeacher() && ! $this->teacherCanAccess($user, $classroom->id, $subject->id)) {
            return null;
        }

        return app(CurriculumProgressService::class)->detail($classroom, $subject);
    }

    private function teacherCanAccess(User $teacher, int $classroomId, int $subjectId): bool
    {
        return Schedule::query()
            ->forTeacher($teacher->id)
            ->where('subject_id', $subjectId)
            ->where(function (Builder $query) use ($classroomId): void {
                $query->where('classroom_id', $classroomId)
                    ->orWhereHas('group', fn (Builder $query) => $query->where('classroom_id', $classroomId));
            })
            ->exists();
    }
}
