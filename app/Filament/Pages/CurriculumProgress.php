<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\CurriculumProgressService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class CurriculumProgress extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.curriculum-progress';

    #[Url(as: 'subject')]
    public ?int $subjectId = null;

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

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
    }

    public function clearFilters(): void
    {
        $this->subjectId = null;
        $this->classroomId = null;
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
