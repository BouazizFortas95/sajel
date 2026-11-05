<?php

namespace App\Filament\Pages;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Services\ExamConflictService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ExamTimetableHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.exam-timetable-hub';

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'trimester')]
    public int $trimester = 1;

    #[Url(as: 'type')]
    public string $assessmentType = 'all';

    public function mount(): void
    {
        if ($this->classroomId === null) {
            $classroom = $this->classroomOptions->first();
            $this->classroomId = $classroom?->id;
        }

        if (! in_array($this->trimester, [1, 2, 3], true)) {
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
        return __('التقويمات والامتحانات والمجالس');
    }

    public static function getNavigationLabel(): string
    {
        return __('رزنامة الفروض والامتحانات');
    }

    public function getTitle(): string
    {
        return __('رزنامة الفروض والامتحانات');
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
     * @return array{from: string, to: string, name: string}
     */
    public function getTrimesterPeriodProperty(): array
    {
        return app(ExamConflictService::class)->getTrimesterPeriod($this->trimester);
    }

    /**
     * Get all assessments for this classroom in the selected trimester period.
     *
     * @return Collection<int, Assessment>
     */
    public function getAssessmentsProperty(): Collection
    {
        if (! $this->classroom) {
            return collect();
        }

        $period = $this->trimesterPeriod;

        return app(ExamConflictService::class)->getClassroomAssessments(
            $this->classroom->id,
            $period['from'],
            $period['to'],
            $this->assessmentType
        );
    }

    /**
     * Detect all conflicts in the current timetable view.
     *
     * @return array<int, array<int, array{type: string, severity: string, message: string}>>
     */
    public function getConflictsProperty(): array
    {
        if (! $this->classroom) {
            return [];
        }

        $period = $this->trimesterPeriod;

        return app(ExamConflictService::class)->detectClassroomConflicts(
            $this->classroom->id,
            $period['from'],
            $period['to']
        );
    }

    public function getConflictsCountProperty(): int
    {
        return count($this->conflicts);
    }

    public function getTotalAssessmentsCountProperty(): int
    {
        return $this->assessments->count();
    }

    public function getMajorAssessmentsCountProperty(): int
    {
        return $this->assessments->filter(function ($a) {
            return in_array($a->type, [AssessmentType::Midterm, AssessmentType::Exam], true);
        })->count();
    }

    public function getCoveredSubjectsProperty(): Collection
    {
        return $this->assessments
            ->map(fn ($a) => $a->schedule?->subject)
            ->filter()
            ->unique('id')
            ->values();
    }

    public function getPrintUrlProperty(): ?string
    {
        if (! $this->classroom) {
            return null;
        }

        return route('print.exam-timetable', [
            'locale' => app()->getLocale(),
            'classroom' => $this->classroom->id,
            'trimester' => $this->trimester,
        ]);
    }
}
