<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolArchiveService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class SchoolArchiveHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.school-archive-hub';

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'subject')]
    public ?int $subjectId = null;

    #[Url(as: 'trimester')]
    public ?int $trimester = null;

    #[Url(as: 'teacher')]
    public ?int $teacherId = null;

    public function mount(): void
    {
        if ($this->classroomId === null) {
            $classroom = $this->classroomOptions->first();
            $this->classroomId = $classroom?->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || ($user?->isFinanceOfficer() ?? false);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('أرشيف المؤسسة ودفاتر النصوص');
    }

    public function getTitle(): string
    {
        return __('أرشيف المؤسسة الرقمي ودفاتر النصوص السنوية');
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
     * Subjects taught in this classroom.
     *
     * @return Collection<int, Subject>
     */
    public function getSubjectOptionsProperty(): Collection
    {
        if (! $this->classroom) {
            return collect();
        }

        return Subject::query()
            ->whereHas('schedules', function (Builder $q): void {
                $q->where('classroom_id', $this->classroom->id)
                    ->orWhereHas('group', fn (Builder $gq): Builder => $gq->where('classroom_id', $this->classroom->id));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Teachers who teach in this classroom.
     *
     * @return Collection<int, User>
     */
    public function getTeacherOptionsProperty(): Collection
    {
        if (! $this->classroom) {
            return collect();
        }

        return User::query()
            ->whereHas('schedules', function (Builder $q): void {
                $q->where('classroom_id', $this->classroom->id)
                    ->orWhereHas('group', fn (Builder $gq): Builder => $gq->where('classroom_id', $this->classroom->id));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Compute full archive book data for the active view.
     *
     * @return array<string, mixed>
     */
    public function getArchiveBookDataProperty(): array
    {
        if (! $this->classroom) {
            return [
                'logs' => collect(),
                'curriculumIndex' => ['domains' => [], 'taught_resources_count' => 0, 'completion_rate' => null],
                'visas' => collect(),
                'stats' => [
                    'total_sessions' => 0,
                    'total_hours' => 0,
                    'homework_count' => 0,
                    'observations_count' => 0,
                    'visas_count' => 0,
                    'presence_rate' => 100.0,
                    'distinct_subjects' => collect(),
                    'distinct_teachers' => collect(),
                ],
                'period' => app(SchoolArchiveService::class)->getTrimesterPeriod($this->trimester),
            ];
        }

        $user = Auth::user();
        $effectiveTeacherId = ($user && $user->isTeacher()) ? $user->id : $this->teacherId;

        return app(SchoolArchiveService::class)->assembleAnnualRegisterBook(
            $this->classroom,
            $this->subjectId,
            $this->trimester,
            $effectiveTeacherId
        );
    }
}
