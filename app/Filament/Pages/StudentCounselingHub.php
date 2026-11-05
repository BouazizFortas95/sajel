<?php

namespace App\Filament\Pages;

use App\Enums\CounselingSessionReason;
use App\Enums\CounselingSessionStatus;
use App\Enums\FamilyStatus;
use App\Models\Classroom;
use App\Models\CounselingSession;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\StudentCounselingService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StudentCounselingHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static string $view = 'filament.pages.student-counseling-hub';

    protected static ?int $navigationSort = 4;

    public string $activeTab = 'profiles';

    public ?int $classroomId = null;

    public string $search = '';

    public string $sessionReasonFilter = '';

    public string $sessionStatusFilter = '';

    // Profile Edit Modal State
    public bool $showProfileModal = false;

    public ?int $editingStudentId = null;

    public string $familyStatus = 'both_parents';

    public int $siblingsCount = 0;

    public int $birthOrder = 1;

    public ?string $fatherJob = null;

    public ?string $motherJob = null;

    public bool $socialGrant = false;

    public ?string $bloodType = null;

    public string $chronicConditionsText = '';

    public bool $hasPhysicalDisability = false;

    public bool $peExemption = false;

    public ?string $healthNotes = null;

    public ?string $primarySchoolName = null;

    public ?string $primaryGraduationYear = null;

    public int $repeatedYearsCount = 0;

    public string $learningDifficultiesText = '';

    public string $behaviorTendenciesText = '';

    public string $interestsAndTalentsText = '';

    public ?string $careerAspirations = null;

    public ?string $counselorGeneralNotes = null;

    // Session Modal State
    public bool $showSessionModal = false;

    public ?int $sessionStudentId = null;

    public string $sessionDate = '';

    public string $sessionReason = 'academic_difficulty';

    public string $sessionIntervieweeType = 'student_only';

    public string $sessionObservations = '';

    public ?string $sessionActionPlan = null;

    public ?string $sessionFollowUpDate = null;

    public string $sessionStatus = 'open';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('المتابعة البيداغوجية والإرشاد');
    }

    public static function getNavigationLabel(): string
    {
        return __('الملف النفسي والتربوي');
    }

    public function getTitle(): string
    {
        return __('الملف النفسي والتربوي ومتابعات مستشار التوجيه');
    }

    public function mount(): void
    {
        $this->sessionDate = Carbon::today()->format('Y-m-d');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getClassroomsProperty(): Collection
    {
        return Classroom::query()
            ->orderBy('name')
            ->get();
    }

    public function getAcademicYearProperty(): string
    {
        return SchoolSetting::currentAcademicYear();
    }

    public function getKpisProperty(): array
    {
        /** @var StudentCounselingService $service */
        $service = app(StudentCounselingService::class);

        return $service->computeCounselingKpis($this->academicYear);
    }

    public function getStudentsProperty(): Collection
    {
        $query = Student::query()
            ->with(['group.classroom', 'counselingProfile', 'latestCounselingSession'])
            ->active();

        if ($this->classroomId) {
            $query->whereHas('group', fn (Builder $q) => $q->where('classroom_id', $this->classroomId));
        }

        if (filled($this->search)) {
            $term = trim($this->search);
            $query->where(function (Builder $q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('registration_number', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    public function getSessionsProperty(): Collection
    {
        $query = CounselingSession::query()
            ->where('academic_year', $this->academicYear)
            ->with(['student.group.classroom', 'counselor']);

        if ($this->classroomId) {
            $query->whereHas('student.group', fn (Builder $q) => $q->where('classroom_id', $this->classroomId));
        }

        if (filled($this->search)) {
            $term = trim($this->search);
            $query->whereHas('student', function (Builder $q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('registration_number', 'like', "%{$term}%");
            });
        }

        if (filled($this->sessionReasonFilter)) {
            $query->where('reason_category', $this->sessionReasonFilter);
        }

        if (filled($this->sessionStatusFilter)) {
            $query->where('status', $this->sessionStatusFilter);
        }

        return $query->orderByDesc('session_date')->get();
    }

    public function openProfileModal(int $studentId): void
    {
        $student = Student::with('counselingProfile')->findOrFail($studentId);
        $this->editingStudentId = $studentId;

        /** @var StudentCounselingService $service */
        $service = app(StudentCounselingService::class);
        $profile = $service->getOrCreateProfile($student);

        $this->familyStatus = $profile->family_status->value;
        $this->siblingsCount = $profile->siblings_count;
        $this->birthOrder = $profile->birth_order;
        $this->fatherJob = $profile->father_job;
        $this->motherJob = $profile->mother_job;
        $this->socialGrant = $profile->social_grant;
        $this->bloodType = $profile->blood_type;
        $this->chronicConditionsText = ! empty($profile->chronic_conditions) ? implode(', ', (array) $profile->chronic_conditions) : '';
        $this->hasPhysicalDisability = $profile->has_physical_disability;
        $this->peExemption = $profile->pe_exemption;
        $this->healthNotes = $profile->health_notes;
        $this->primarySchoolName = $profile->primary_school_name;
        $this->primaryGraduationYear = $profile->primary_graduation_year;
        $this->repeatedYearsCount = $profile->repeated_years_count;
        $this->learningDifficultiesText = ! empty($profile->learning_difficulties) ? implode(', ', (array) $profile->learning_difficulties) : '';
        $this->behaviorTendenciesText = ! empty($profile->behavior_tendencies) ? implode(', ', (array) $profile->behavior_tendencies) : '';
        $this->interestsAndTalentsText = ! empty($profile->interests_and_talents) ? implode(', ', (array) $profile->interests_and_talents) : '';
        $this->careerAspirations = $profile->career_aspirations;
        $this->counselorGeneralNotes = $profile->counselor_general_notes;

        $this->showProfileModal = true;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'familyStatus' => 'required|string',
            'siblingsCount' => 'required|integer|min:0|max:20',
            'birthOrder' => 'required|integer|min:1|max:20',
            'repeatedYearsCount' => 'required|integer|min:0|max:10',
        ]);

        $student = Student::findOrFail($this->editingStudentId);

        /** @var StudentCounselingService $service */
        $service = app(StudentCounselingService::class);
        $service->updateProfile($student, [
            'family_status' => FamilyStatus::from($this->familyStatus),
            'siblings_count' => $this->siblingsCount,
            'birth_order' => $this->birthOrder,
            'father_job' => $this->fatherJob,
            'mother_job' => $this->motherJob,
            'social_grant' => $this->socialGrant,
            'blood_type' => $this->bloodType,
            'chronic_conditions' => $this->chronicConditionsText,
            'has_physical_disability' => $this->hasPhysicalDisability,
            'pe_exemption' => $this->peExemption,
            'health_notes' => $this->healthNotes,
            'primary_school_name' => $this->primarySchoolName,
            'primary_graduation_year' => $this->primaryGraduationYear,
            'repeated_years_count' => $this->repeatedYearsCount,
            'learning_difficulties' => $this->learningDifficultiesText,
            'behavior_tendencies' => $this->behaviorTendenciesText,
            'interests_and_talents' => $this->interestsAndTalentsText,
            'career_aspirations' => $this->careerAspirations,
            'counselor_general_notes' => $this->counselorGeneralNotes,
        ]);

        $this->showProfileModal = false;

        Notification::make()
            ->title(__('تم حفظ وتحديث الملف النفسي والتربوي بنجاح'))
            ->success()
            ->send();
    }

    public function openSessionModal(?int $studentId = null): void
    {
        $this->sessionStudentId = $studentId;
        $this->sessionDate = Carbon::today()->format('Y-m-d');
        $this->sessionReason = CounselingSessionReason::AcademicDifficulty->value;
        $this->sessionIntervieweeType = 'student_only';
        $this->sessionObservations = '';
        $this->sessionActionPlan = null;
        $this->sessionFollowUpDate = null;
        $this->sessionStatus = CounselingSessionStatus::Open->value;

        $this->showSessionModal = true;
    }

    public function saveSession(): void
    {
        $this->validate([
            'sessionStudentId' => 'required|exists:students,id',
            'sessionDate' => 'required|date',
            'sessionReason' => 'required|string',
            'sessionIntervieweeType' => 'required|string',
            'sessionObservations' => 'required|string|min:3',
        ]);

        $student = Student::findOrFail($this->sessionStudentId);
        $user = auth()->user();

        /** @var StudentCounselingService $service */
        $service = app(StudentCounselingService::class);
        $service->recordSession($student, $user, [
            'academic_year' => $this->academicYear,
            'session_date' => $this->sessionDate,
            'reason_category' => CounselingSessionReason::from($this->sessionReason),
            'interviewee_type' => $this->sessionIntervieweeType,
            'observations' => $this->sessionObservations,
            'action_plan' => $this->sessionActionPlan,
            'follow_up_date' => $this->sessionFollowUpDate,
            'status' => CounselingSessionStatus::from($this->sessionStatus),
            'is_confidential' => true,
        ]);

        $this->showSessionModal = false;

        Notification::make()
            ->title(__('تم تسجيل المقابلة الإرشادية بنجاح'))
            ->success()
            ->send();
    }

    public function toggleSessionStatus(int $sessionId, string $status): void
    {
        $session = CounselingSession::findOrFail($sessionId);

        /** @var StudentCounselingService $service */
        $service = app(StudentCounselingService::class);
        $service->updateSessionStatus($session, CounselingSessionStatus::from($status));

        Notification::make()
            ->title(__('تم تحديث حالة المقابلة الإرشادية'))
            ->success()
            ->send();
    }
}
