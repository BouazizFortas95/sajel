<?php

namespace App\Filament\Pages;

use App\Enums\StudentAwardType;
use App\Models\Classroom;
use App\Models\Group;
use App\Services\StudentAwardService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class StudentAwardsHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 84;

    protected static string $view = 'filament.pages.student-awards-hub';

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'trimester')]
    public int $trimester = 1;

    #[Url(as: 'award')]
    public ?string $awardFilter = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    public function mount(): void
    {
        if ($this->classroomId === null) {
            $classroom = $this->classroomOptions->first();
            $this->classroomId = $classroom?->id;
        }

        if (! in_array($this->trimester, [0, 1, 2, 3], true)) {
            $this->trimester = 1;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public static function getNavigationLabel(): string
    {
        return __('لوحة الشرف والشهادات التقديرية');
    }

    public function getTitle(): string
    {
        return __('لوحة الشرف والشهادات التقديرية');
    }

    /**
     * Accessible classrooms: Admins see all, teachers see classrooms where they have schedules.
     *
     * @return Collection<int, Classroom>
     */
    public function getClassroomOptionsProperty(): Collection
    {
        $user = Auth::user();
        $query = Classroom::query()
            ->with(['level.educationPhase'])
            ->orderBy('name');

        if ($user && $user->isTeacher()) {
            $query->where(function (Builder $q) use ($user): void {
                $q->whereHas('groups.schedules', fn (Builder $sq) => $sq->where('user_id', $user->id))
                    ->orWhereHas('schedules', fn (Builder $sq) => $sq->where('user_id', $user->id));
            });
        }

        return $query->get();
    }

    public function getClassroomProperty(): ?Classroom
    {
        if (! $this->classroomId) {
            return null;
        }

        $classroom = Classroom::query()
            ->with(['level.educationPhase', 'groups'])
            ->find($this->classroomId);

        if (! $classroom) {
            return null;
        }

        $user = Auth::user();
        if ($user && $user->isTeacher()) {
            $hasSchedule = $classroom->groups()
                ->whereHas('schedules', fn (Builder $q) => $q->where('user_id', $user->id))
                ->exists()
                || $classroom->schedules()
                    ->where('user_id', $user->id)
                    ->exists();

            if (! $hasSchedule) {
                return null;
            }
        }

        return $classroom;
    }

    /**
     * Groups inside current classroom.
     *
     * @return Collection<int, Group>
     */
    public function getGroupOptionsProperty(): Collection
    {
        if (! $this->classroom) {
            return collect();
        }

        return $this->classroom->groups()->orderBy('name')->get();
    }

    /**
     * Compute full awards deliberation data for the active view.
     *
     * @return array<string, mixed>
     */
    public function getAwardsDataProperty(): array
    {
        if (! $this->classroom) {
            return [
                'students' => collect(),
                'allRankedStudents' => collect(),
                'kpis' => [
                    'total_students' => 0,
                    'graded_students' => 0,
                    'total_honored' => 0,
                    'excellence_count' => 0,
                    'encouragement_count' => 0,
                    'honor_roll_count' => 0,
                    'good_conduct_count' => 0,
                    'highest_average' => null,
                    'lowest_average' => null,
                    'class_general_average' => null,
                ],
                'period' => app(StudentAwardService::class)->getTrimesterPeriod($this->trimester),
            ];
        }

        $awardEnum = filled($this->awardFilter) ? StudentAwardType::tryFrom($this->awardFilter) : null;

        return app(StudentAwardService::class)->computeClassroomAwards(
            $this->classroom,
            $this->trimester,
            $this->groupId,
            $awardEnum
        );
    }
}
