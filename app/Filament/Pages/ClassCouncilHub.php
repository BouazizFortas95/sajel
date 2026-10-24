<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Services\ClassCouncilService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ClassCouncilHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 26;

    protected static string $view = 'filament.pages.class-council-hub';

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    public ?int $groupId = null;

    public function mount(): void
    {
        if ($this->classroomId === null) {
            $classroom = $this->getClassroomOptions()->first();
            $this->classroomId = $classroom?->id;
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('مجلس القسم');
    }

    public function getClassroomOptionsProperty()
    {
        $user = Auth::user();
        $query = Classroom::query()
            ->with('level.educationPhase')
            ->orderBy('name');

        if ($user && $user->isTeacher()) {
            $query->whereHas('group.schedules', fn (Builder $q) => $q->where('user_id', $user->id));
        }

        return $query->get();
    }

    public function getClassroomProperty(): ?Classroom
    {
        if (! $this->classroomId) {
            return null;
        }

        $classroom = Classroom::find($this->classroomId);
        if (! $classroom) {
            return null;
        }

        $user = Auth::user();
        if ($user && $user->isTeacher()) {
            $hasSchedule = $classroom->group->schedules()
                ->where('user_id', $user->id)
                ->exists();

            if (! $hasSchedule) {
                $classroom = null;
            }
        }

        return $classroom;
    }

    public function getReportDataProperty(): array
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return [];
        }

        $service = app(ClassCouncilService::class);

        return $service->viewData($classroom, $this->groupId);
    }

    public function getClassroomPrintUrl(): ?string
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return null;
        }

        return route('print.class-council', ['locale' => app()->getLocale(), 'classroom' => $classroom]);
    }

    public function getClassroomPdfUrl(): ?string
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return null;
        }

        return route('print.class-council.pdf', ['locale' => app()->getLocale(), 'classroom' => $classroom]);
    }
}
