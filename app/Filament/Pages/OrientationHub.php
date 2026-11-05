<?php

namespace App\Filament\Pages;

use App\Enums\OrientationAppealStatus;
use App\Models\Classroom;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentOrientation;
use App\Services\StudentOrientationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrientationHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 92;

    protected static string $view = 'filament.pages.orientation-hub';

    public string $activeTab = 'wishes'; // 'wishes', 'deliberations', 'appeals'

    public ?string $academicYear = null;

    public ?int $classroomId = null;

    public string $search = '';

    // Wishes Modal State
    public bool $isWishesModalOpen = false;

    public ?int $editingStudentId = null;

    public ?string $editFirstChoice = 'science_tech';

    public ?string $editSecondChoice = 'arts_humanities';

    public bool $editGuardianApproved = false;

    public ?string $editGuardianNotes = null;

    // Deliberation Modal State
    public bool $isDeliberationModalOpen = false;

    public ?int $deliberatingStudentId = null;

    public ?string $deliberatingDecision = null;

    public ?float $deliberatingBemScore = null;

    public ?string $deliberatingNotes = null;

    // Appeal Modal State
    public bool $isAppealModalOpen = false;

    public ?int $appealOrientationId = null;

    public string $appealStatus = 'submitted';

    public ?string $appealDecision = null;

    public ?string $appealNotes = null;

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        $this->academicYear = $setting?->academic_year ?? SchoolSetting::currentAcademicYear();
    }

    public static function canAccess(): bool
    {
        return Auth::check();
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
        return __('التوجيه والقبول إلى الثانوي (4AM)');
    }

    public function getTitle(): string
    {
        return __('منظومة التوجيه المدرسي والقبول إلى التعليم الثانوي (4AM)');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getClassroomsProperty(): Collection
    {
        return Classroom::query()
            ->whereHas('level', fn ($q) => $q->where('grade_number', 4))
            ->orderBy('name')
            ->get();
    }

    public function getCandidatesProperty(): Collection
    {
        $service = app(StudentOrientationService::class);

        $query = Student::query()
            ->active()
            ->whereHas('group.classroom.level', function (Builder $q) {
                $q->where('grade_number', 4);
            })
            ->with(['group.classroom', 'orientations' => function ($q) {
                $q->where('academic_year', $this->academicYear);
            }]);

        if ($this->classroomId) {
            $query->whereHas('group', function (Builder $q) {
                $q->where('classroom_id', $this->classroomId);
            });
        }

        if ($this->search) {
            $term = '%'.$this->search.'%';
            $query->where(function ($sq) use ($term) {
                $sq->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('registration_number', 'like', $term)
                    ->orWhere('matricule_number', 'like', $term);
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->get();

        return $students->map(function (Student $student) use ($service) {
            $orientation = $student->orientations->first() ?? $service->getOrCreateOrientation($student, $this->academicYear);

            return [
                'student' => $student,
                'orientation' => $orientation,
                'matricule' => $student->matricule_number ?? $student->registration_number ?? '—',
                'classroom_name' => $student->group?->classroom?->name ?? '—',
                'scientific_score' => $orientation->scientific_profile_score,
                'literary_score' => $orientation->literary_profile_score,
                'general_average' => $orientation->annual_general_average,
                'recommended' => $orientation->recommendedStream(),
                'gap' => $orientation->profileGap(),
                'first_choice' => $orientation->first_choice,
                'second_choice' => $orientation->second_choice,
                'guardian_approved' => $orientation->guardian_approved,
                'final_decision' => $orientation->final_decision,
                'bem_score' => $orientation->bem_score,
                'admission_average' => $orientation->admission_average,
                'is_admitted' => $orientation->is_admitted,
                'appeal_status' => $orientation->appeal_status,
                'effective_decision' => $orientation->effectiveDecision(),
            ];
        });
    }

    public function getKpisProperty(): array
    {
        return app(StudentOrientationService::class)->getOrientationKpis($this->academicYear);
    }

    // Modal Actions
    public function openWishesModal(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($student, $this->academicYear);

        $this->editingStudentId = $student->id;
        $this->editFirstChoice = $orientation->first_choice?->value ?? 'science_tech';
        $this->editSecondChoice = $orientation->second_choice?->value ?? 'arts_humanities';
        $this->editGuardianApproved = $orientation->guardian_approved;
        $this->editGuardianNotes = $orientation->guardian_notes;

        $this->isWishesModalOpen = true;
    }

    public function saveWishes(): void
    {
        if (! $this->editingStudentId) {
            return;
        }

        $student = Student::findOrFail($this->editingStudentId);
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($student, $this->academicYear);

        $service->updateWishes($orientation, [
            'first_choice' => $this->editFirstChoice,
            'second_choice' => $this->editSecondChoice,
            'guardian_approved' => $this->editGuardianApproved,
            'guardian_notes' => $this->editGuardianNotes,
        ]);

        $this->isWishesModalOpen = false;

        Notification::make()
            ->title(__('تم حفظ وتحديث رغبات التوجيه بنجاح'))
            ->success()
            ->send();
    }

    public function openDeliberationModal(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($student, $this->academicYear);

        $this->deliberatingStudentId = $student->id;
        $this->deliberatingDecision = $orientation->final_decision?->value ?? $orientation->first_choice?->value ?? 'science_tech';
        $this->deliberatingBemScore = $orientation->bem_score;
        $this->deliberatingNotes = $orientation->counselor_notes;

        $this->isDeliberationModalOpen = true;
    }

    public function saveDeliberation(): void
    {
        if (! $this->deliberatingStudentId) {
            return;
        }

        $student = Student::findOrFail($this->deliberatingStudentId);
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($student, $this->academicYear);

        $service->recordFinalDecision(
            $orientation,
            $this->deliberatingDecision,
            $this->deliberatingBemScore,
            $this->deliberatingNotes
        );

        $this->isDeliberationModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل قرار مجلس القبول والتوجيه بنجاح'))
            ->success()
            ->send();
    }

    public function openAppealModal(int $orientationId): void
    {
        $orientation = StudentOrientation::findOrFail($orientationId);

        $this->appealOrientationId = $orientation->id;
        $this->appealStatus = $orientation->appeal_status === OrientationAppealStatus::None
            ? 'submitted'
            : $orientation->appeal_status->value;
        $this->appealDecision = $orientation->appeal_decision?->value;
        $this->appealNotes = $orientation->appeal_notes;

        $this->isAppealModalOpen = true;
    }

    public function saveAppeal(): void
    {
        if (! $this->appealOrientationId) {
            return;
        }

        $orientation = StudentOrientation::findOrFail($this->appealOrientationId);
        $service = app(StudentOrientationService::class);

        $service->recordAppeal(
            $orientation,
            $this->appealStatus,
            $this->appealDecision,
            $this->appealNotes
        );

        $this->isAppealModalOpen = false;

        Notification::make()
            ->title(__('تم تحديث وضعية الطعن الولائي بنجاح'))
            ->success()
            ->send();
    }
}
