<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\CareerAdvancement;
use App\Models\SchoolSetting;
use App\Models\StaffEvaluation;
use App\Models\StaffMovement;
use App\Models\User;
use App\Services\StaffCareerService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class StaffCareerHub extends Page
{
    protected static string $view = 'filament.pages.staff-career-hub';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->canManageStaffCareer();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('المسار المهني والتنقيط السنوي');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة تسيير المسار المهني، التنقيط السنوي، والترقية في الدرجات');
    }

    public string $activeTab = 'evaluations';

    public string $selectedAcademicYear = '2025/2026';

    public int $selectedYear = 2026;

    public string $searchQuery = '';

    // Evaluation Modal State
    public bool $isEvaluationModalOpen = false;

    public ?int $editingEvaluationId = null;

    public ?int $evaluationUserId = null;

    public float $punctualityScore = 4.5;

    public float $performanceScore = 4.0;

    public float $conductScore = 4.5;

    public float $initiativeScore = 4.0;

    public ?float $pedagogicalScore = null;

    public ?string $lastInspectionDate = null;

    public string $evaluationAppreciation = 'very_good';

    public string $evaluatorNotes = '';

    public string $staffSignatureStatus = 'pending';

    public string $staffContestationNotes = '';

    // Advancement Approval Modal State
    public bool $isAdvancementModalOpen = false;

    public ?int $editingAdvancementId = null;

    public string $advancementEffectiveDate = '';

    public string $advancementCommissionRef = '';

    public string $advancementNotes = '';

    // Movement Modal State
    public bool $isMovementModalOpen = false;

    public ?int $movementUserId = null;

    public string $movementType = 'transfer';

    public string $movementStatus = 'approved';

    public string $movementEffectiveDate = '';

    public ?string $movementEndDate = null;

    public string $movementDecisionNumber = '';

    public ?string $movementDecisionDate = null;

    public string $movementSource = '';

    public string $movementTarget = '';

    public string $movementReason = '';

    // Career Profile Edit Modal State
    public bool $isProfileModalOpen = false;

    public ?int $profileUserId = null;

    public string $profileRegistrationNumber = '';

    public string $profileNIN = '';

    public ?string $profileBirthDate = null;

    public string $profileBirthPlace = '';

    public string $profileGender = 'ذكر';

    public string $profilePhone = '';

    public ?string $profileRecruitmentDate = null;

    public ?string $profileGradeDate = null;

    public int $profileCurrentStep = 0;

    public ?string $profileStepDate = null;

    public string $profileStatutoryGrade = '';

    public string $profileDiploma = '';

    public function mount(StaffCareerService $service): void
    {
        $school = SchoolSetting::current();
        if ($school && $school->academic_year) {
            $this->selectedAcademicYear = $school->academic_year;
            $this->selectedYear = (int) substr($school->academic_year, -4);
        } else {
            $this->selectedYear = (int) now()->year;
        }

        $this->advancementEffectiveDate = now()->toDateString();
        $this->movementEffectiveDate = now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['evaluations', 'advancements', 'movements', 'profiles'], true)
            ? $tab
            : 'evaluations';
    }

    // --- EVALUATION ACTIONS ---

    public function openNewEvaluationModal(): void
    {
        $this->resetEvaluationForm();
        $firstUser = User::whereIn('role', [
            Role::Teacher,
            Role::HeadTeacher,
            Role::GeneralSupervisor,
            Role::EducationSupervisor,
            Role::Counselor,
            Role::FinanceOfficer,
            Role::Censor,
        ])->first();

        $this->evaluationUserId = $firstUser?->id;
        $this->isEvaluationModalOpen = true;
    }

    public function openEditEvaluationModal(int $evaluationId): void
    {
        $eval = StaffEvaluation::findOrFail($evaluationId);
        $this->editingEvaluationId = $eval->id;
        $this->evaluationUserId = $eval->user_id;
        $this->selectedAcademicYear = $eval->academic_year;
        $this->punctualityScore = (float) $eval->punctuality_score;
        $this->performanceScore = (float) $eval->performance_score;
        $this->conductScore = (float) $eval->conduct_score;
        $this->initiativeScore = (float) $eval->initiative_score;
        $this->pedagogicalScore = $eval->pedagogical_score !== null ? (float) $eval->pedagogical_score : null;
        $this->lastInspectionDate = $eval->last_inspection_date?->format('Y-m-d');
        $this->evaluationAppreciation = $eval->appreciation?->value ?? 'good';
        $this->evaluatorNotes = $eval->evaluator_notes ?? '';
        $this->staffSignatureStatus = $eval->staff_signature_status;
        $this->staffContestationNotes = $eval->staff_contestation_notes ?? '';
        $this->isEvaluationModalOpen = true;
    }

    public function saveEvaluation(StaffCareerService $service): void
    {
        $this->validate([
            'evaluationUserId' => 'required|exists:users,id',
            'punctualityScore' => 'required|numeric|min:0|max:5',
            'performanceScore' => 'required|numeric|min:0|max:5',
            'conductScore' => 'required|numeric|min:0|max:5',
            'initiativeScore' => 'required|numeric|min:0|max:5',
            'pedagogicalScore' => 'nullable|numeric|min:0|max:20',
        ]);

        $evaluator = Auth::user();

        $service->saveEvaluation([
            'user_id' => $this->evaluationUserId,
            'academic_year' => $this->selectedAcademicYear,
            'evaluation_year' => (int) substr($this->selectedAcademicYear, -4),
            'punctuality_score' => $this->punctualityScore,
            'performance_score' => $this->performanceScore,
            'conduct_score' => $this->conductScore,
            'initiative_score' => $this->initiativeScore,
            'pedagogical_score' => $this->pedagogicalScore,
            'last_inspection_date' => $this->lastInspectionDate,
            'appreciation' => $this->evaluationAppreciation,
            'evaluator_notes' => $this->evaluatorNotes,
            'staff_signature_status' => $this->staffSignatureStatus,
            'staff_contestation_notes' => $this->staffContestationNotes,
        ], $evaluator);

        $this->isEvaluationModalOpen = false;
        $this->resetEvaluationForm();

        Notification::make()
            ->title(__('تم حفظ بطاقة التنقيط والتقييم السنوي بنجاح'))
            ->success()
            ->send();
    }

    public function resetEvaluationForm(): void
    {
        $this->editingEvaluationId = null;
        $this->evaluationUserId = null;
        $this->punctualityScore = 4.5;
        $this->performanceScore = 4.0;
        $this->conductScore = 4.5;
        $this->initiativeScore = 4.0;
        $this->pedagogicalScore = null;
        $this->lastInspectionDate = null;
        $this->evaluationAppreciation = 'very_good';
        $this->evaluatorNotes = '';
        $this->staffSignatureStatus = 'pending';
        $this->staffContestationNotes = '';
    }

    // --- ADVANCEMENT ACTIONS ---

    public function syncAdvancementProjections(StaffCareerService $service): void
    {
        $count = $service->syncAdvancementProjections($this->selectedYear);

        Notification::make()
            ->title(__('تم تحديث وضبط جدول الترقية في الدرجات بنجاح'))
            ->body(__('تم إدراج / تدقيق :count مقترح ترقية برسم سنة :year', ['count' => $count, 'year' => $this->selectedYear]))
            ->success()
            ->send();
    }

    public function openApproveAdvancementModal(int $advancementId): void
    {
        $adv = CareerAdvancement::findOrFail($advancementId);
        $this->editingAdvancementId = $adv->id;
        $this->advancementEffectiveDate = $adv->effective_date?->format('Y-m-d') ?? now()->toDateString();
        $this->advancementCommissionRef = $adv->commission_reference ?? 'محضر اللجنة المتساوية الأعضاء رقم 01/'.$this->selectedYear;
        $this->advancementNotes = $adv->notes ?? '';
        $this->isAdvancementModalOpen = true;
    }

    public function approveAdvancement(StaffCareerService $service): void
    {
        $this->validate([
            'advancementEffectiveDate' => 'required|date',
        ]);

        $adv = CareerAdvancement::findOrFail($this->editingAdvancementId);
        $service->promoteStaff(
            $adv,
            Carbon::parse($this->advancementEffectiveDate),
            $this->advancementCommissionRef,
            $this->advancementNotes
        );

        $this->isAdvancementModalOpen = false;
        $this->editingAdvancementId = null;

        Notification::make()
            ->title(__('تمت المصادقة وترقية الموظف إلى الدرجة الجديدة'))
            ->body(__('تمت ترقية :name إلى الدرجة :step بنجاح وتحديث تاريخ السريان والأثر المالي.', [
                'name' => $adv->user?->name,
                'step' => $adv->next_step,
            ]))
            ->success()
            ->send();
    }

    // --- MOVEMENT ACTIONS ---

    public function openNewMovementModal(): void
    {
        $this->resetMovementForm();
        $firstUser = User::first();
        $this->movementUserId = $firstUser?->id;
        $this->movementEffectiveDate = now()->toDateString();
        $this->isMovementModalOpen = true;
    }

    public function saveMovement(StaffCareerService $service): void
    {
        $this->validate([
            'movementUserId' => 'required|exists:users,id',
            'movementType' => 'required|string',
            'movementEffectiveDate' => 'required|date',
        ]);

        $service->recordMovement([
            'user_id' => $this->movementUserId,
            'movement_type' => $this->movementType,
            'status' => $this->movementStatus,
            'effective_date' => $this->movementEffectiveDate,
            'end_date' => $this->movementEndDate,
            'decision_number' => $this->movementDecisionNumber,
            'decision_date' => $this->movementDecisionDate,
            'source_institution' => $this->movementSource,
            'target_institution' => $this->movementTarget,
            'reason' => $this->movementReason,
        ]);

        $this->isMovementModalOpen = false;
        $this->resetMovementForm();

        Notification::make()
            ->title(__('تم تسجيل حركة الموظف بنجاح'))
            ->success()
            ->send();
    }

    public function resetMovementForm(): void
    {
        $this->movementUserId = null;
        $this->movementType = 'transfer';
        $this->movementStatus = 'approved';
        $this->movementEffectiveDate = now()->toDateString();
        $this->movementEndDate = null;
        $this->movementDecisionNumber = '';
        $this->movementDecisionDate = null;
        $this->movementSource = '';
        $this->movementTarget = '';
        $this->movementReason = '';
    }

    // --- PROFILE ACTIONS ---

    public function openEditProfileModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->profileUserId = $user->id;
        $this->profileRegistrationNumber = $user->registration_number ?? '';
        $this->profileNIN = $user->national_id_number ?? '';
        $this->profileBirthDate = $user->birth_date?->format('Y-m-d');
        $this->profileBirthPlace = $user->birth_place ?? '';
        $this->profileGender = $user->gender ?? 'ذكر';
        $this->profilePhone = $user->phone ?? '';
        $this->profileRecruitmentDate = $user->recruitment_date?->format('Y-m-d');
        $this->profileGradeDate = $user->grade_appointment_date?->format('Y-m-d');
        $this->profileCurrentStep = (int) ($user->current_step ?? 0);
        $this->profileStepDate = $user->current_step_date?->format('Y-m-d');
        $this->profileStatutoryGrade = $user->statutory_grade ?? ($user->role?->label() ?? '');
        $this->profileDiploma = $user->diploma ?? '';

        $this->isProfileModalOpen = true;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profileUserId' => 'required|exists:users,id',
            'profileCurrentStep' => 'required|integer|min:0|max:12',
        ]);

        $user = User::findOrFail($this->profileUserId);
        $user->update([
            'registration_number' => $this->profileRegistrationNumber ?: null,
            'national_id_number' => $this->profileNIN ?: null,
            'birth_date' => $this->profileBirthDate ?: null,
            'birth_place' => $this->profileBirthPlace ?: null,
            'gender' => $this->profileGender ?: null,
            'phone' => $this->profilePhone ?: null,
            'recruitment_date' => $this->profileRecruitmentDate ?: null,
            'grade_appointment_date' => $this->profileGradeDate ?: null,
            'current_step' => $this->profileCurrentStep,
            'current_step_date' => $this->profileStepDate ?: null,
            'statutory_grade' => $this->profileStatutoryGrade ?: null,
            'diploma' => $this->profileDiploma ?: null,
        ]);

        $this->isProfileModalOpen = false;

        Notification::make()
            ->title(__('تم تحديث البطاقة المهنية للموظف بنجاح'))
            ->success()
            ->send();
    }

    // --- COMPUTED DATA FOR VIEW ---

    public function getStaffUsersProperty()
    {
        return User::orderBy('name')->get();
    }

    public function getEvaluationsListProperty()
    {
        return StaffEvaluation::with(['user', 'evaluator'])
            ->where('academic_year', $this->selectedAcademicYear)
            ->when($this->searchQuery, function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('registration_number', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->orderByDesc('administrative_score')
            ->get();
    }

    public function getAdvancementsListProperty()
    {
        return CareerAdvancement::with(['user.latestEvaluation'])
            ->where(function ($q) {
                $q->whereYear('eligible_at_minimum', $this->selectedYear)
                    ->orWhereYear('eligible_at_medium', $this->selectedYear)
                    ->orWhereYear('eligible_at_maximum', $this->selectedYear)
                    ->orWhereYear('effective_date', $this->selectedYear);
            })
            ->when($this->searchQuery, function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->get()
            ->sortBy(fn ($a) => $a->user->name ?? '')
            ->values();
    }

    public function getMovementsListProperty()
    {
        return StaffMovement::with('user')
            ->when($this->searchQuery, function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->orderByDesc('effective_date')
            ->get();
    }

    public function getProfilesListProperty()
    {
        return User::query()
            ->when($this->searchQuery, function ($q) {
                $q->where('name', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('registration_number', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('national_id_number', 'like', '%'.$this->searchQuery.'%');
            })
            ->orderBy('name')
            ->get();
    }
}
