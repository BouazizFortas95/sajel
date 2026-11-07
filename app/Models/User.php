<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'primary_subject_id',
        'secondary_subject_id',
        'weekly_hours_limit',
        'registration_number',
        'national_id_number',
        'birth_date',
        'birth_place',
        'gender',
        'phone',
        'recruitment_date',
        'grade_appointment_date',
        'current_step',
        'current_step_date',
        'statutory_grade',
        'diploma',
    ];

    /**
     * Keep the application-level default in sync with the database default.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'weekly_hours_limit' => 22,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'weekly_hours_limit' => 'integer',
            'birth_date' => 'date',
            'recruitment_date' => 'date',
            'grade_appointment_date' => 'date',
            'current_step' => 'integer',
            'current_step_date' => 'date',
        ];
    }

    public function primarySubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'primary_subject_id');
    }

    public function secondarySubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'secondary_subject_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(StaffEvaluation::class)->orderByDesc('academic_year');
    }

    public function latestEvaluation(): HasOne
    {
        return $this->hasOne(StaffEvaluation::class)->latestOfMany();
    }

    public function careerAdvancements(): HasMany
    {
        return $this->hasMany(CareerAdvancement::class)->orderByDesc('created_at');
    }

    public function latestAdvancement(): HasOne
    {
        return $this->hasOne(CareerAdvancement::class)->latestOfMany();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StaffMovement::class)->orderByDesc('effective_date');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(StaffInstallation::class)->orderByDesc('installation_date');
    }

    public function outgoingHandovers(): HasMany
    {
        return $this->hasMany(AdministrativeHandover::class, 'outgoing_user_id')->orderByDesc('handover_date');
    }

    public function incomingHandovers(): HasMany
    {
        return $this->hasMany(AdministrativeHandover::class, 'incoming_user_id')->orderByDesc('handover_date');
    }

    public function assignedMails(): HasMany
    {
        return $this->hasMany(OfficialMail::class, 'assigned_to')->orderByDesc('mail_date');
    }

    /**
     * Compute General Seniority (الأقدمية العامة في الوظيفة العمومية)
     */
    public function getGeneralSeniority(?Carbon $asOf = null): array
    {
        if (! $this->recruitment_date) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'formatted' => '0 سنة'];
        }

        $asOf = $asOf ?? now();
        $diff = $this->recruitment_date->diff($asOf);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'formatted' => "{$diff->y} سنة و {$diff->m} شهر",
        ];
    }

    /**
     * Compute Grade Seniority (الأقدمية في الرتبة الحالية)
     */
    public function getGradeSeniority(?Carbon $asOf = null): array
    {
        $date = $this->grade_appointment_date ?? $this->recruitment_date;
        if (! $date) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'formatted' => '0 سنة'];
        }

        $asOf = $asOf ?? now();
        $diff = $date->diff($asOf);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'formatted' => "{$diff->y} سنة و {$diff->m} شهر",
        ];
    }

    /**
     * Compute Step Seniority (الأقدمية في الدرجة الحالية)
     */
    public function getStepSeniority(?Carbon $asOf = null): array
    {
        $date = $this->current_step_date ?? $this->recruitment_date;
        if (! $date) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'formatted' => '0 سنة'];
        }

        $asOf = $asOf ?? now();
        $diff = $date->diff($asOf);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'formatted' => "{$diff->y} سنة و {$diff->m} شهر",
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [
            Role::Admin,
            Role::Developer,
            Role::Principal,
            Role::Censor,
        ], true);
    }

    public function isDeveloper(): bool
    {
        return $this->role === Role::Developer;
    }

    public function isPrincipal(): bool
    {
        return in_array($this->role, [Role::Principal, Role::Admin], true);
    }

    public function isCensor(): bool
    {
        return $this->role === Role::Censor;
    }

    public function isLeadership(): bool
    {
        return in_array($this->role, [
            Role::Developer,
            Role::Principal,
            Role::Censor,
            Role::Admin,
        ], true);
    }

    public function isGeneralSupervisor(): bool
    {
        return $this->role === Role::GeneralSupervisor;
    }

    public function isEducationSupervisor(): bool
    {
        return $this->role === Role::EducationSupervisor;
    }

    public function isCounselor(): bool
    {
        return $this->role === Role::Counselor;
    }

    public function isFinanceOfficer(): bool
    {
        return $this->role === Role::FinanceOfficer;
    }

    public function isEducationStaff(): bool
    {
        return in_array($this->role, [
            Role::GeneralSupervisor,
            Role::EducationSupervisor,
            Role::Counselor,
            Role::FinanceOfficer,
        ], true);
    }

    public function isHeadTeacher(): bool
    {
        return $this->role === Role::HeadTeacher;
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, [Role::Teacher, Role::HeadTeacher], true);
    }

    public function isTeachingStaff(): bool
    {
        return in_array($this->role, [Role::Teacher, Role::HeadTeacher], true);
    }

    /**
     * Specialized Role-Based Capability Checks
     */
    public function canManageUsers(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal();
    }

    public function canManageSchoolSettings(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal();
    }

    public function canValidateOfficialReports(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal();
    }

    public function canManageDiscipline(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor() || $this->isEducationSupervisor();
    }

    public function canManageCounseling(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCounselor();
    }

    public function canManageTextbooks(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isFinanceOfficer();
    }

    public function canManageTeacherAbsences(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor();
    }

    public function canSuperviseSchoolLife(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor();
    }

    public function canManagePedagogicalCouncils(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor() || $this->isTeacher();
    }

    public function canManageCanteen(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isGeneralSupervisor() || $this->isFinanceOfficer();
    }

    public function canManageRakmana(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor() || $this->isAdmin();
    }

    public function canManageStaffCareer(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isAdmin();
    }

    public function canManageAdministrativeArchive(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isFinanceOfficer() || $this->isAdmin();
    }

    public function canManageClassCouncils(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor() || $this->isCounselor() || $this->isTeacher() || $this->isAdmin();
    }

    public function canManageSchoolInventory(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isFinanceOfficer() || $this->isAdmin();
    }

    public function canManageSchoolBudget(): bool
    {
        return in_array($this->role, [
            Role::FinanceOfficer,
            Role::Principal,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function canManageOfficialExams(): bool
    {
        return $this->isDeveloper() || $this->isPrincipal() || $this->isCensor() || $this->isGeneralSupervisor() || $this->isAdmin();
    }

    public function canManageSolidarity(): bool
    {
        return in_array($this->role, [
            Role::Principal,
            Role::FinanceOfficer,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function canManageBoarding(): bool
    {
        return in_array($this->role, [
            Role::Principal,
            Role::GeneralSupervisor,
            Role::FinanceOfficer,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function canManageSchoolSafety(): bool
    {
        return in_array($this->role, [
            Role::Principal,
            Role::GeneralSupervisor,
            Role::FinanceOfficer,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function canManageSchoolGovernance(): bool
    {
        return in_array($this->role, [
            Role::Principal,
            Role::GeneralSupervisor,
            Role::FinanceOfficer,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function canManageSchoolMaintenance(): bool
    {
        return in_array($this->role, [
            Role::Principal,
            Role::FinanceOfficer,
            Role::GeneralSupervisor,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ], true);
    }

    public function reportedMaintenanceWorkOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'reported_by_user_id');
    }

    public function boardSessionsAsPresident(): HasMany
    {
        return $this->hasMany(SchoolBoardSession::class, 'president_user_id');
    }

    public function boardSessionsAsRapporteur(): HasMany
    {
        return $this->hasMany(SchoolBoardSession::class, 'rapporteur_user_id');
    }

    public function boardMemberships(): HasMany
    {
        return $this->hasMany(SchoolBoardMember::class);
    }

    public function safetyIncidents(): HasMany
    {
        return $this->hasMany(SchoolSafetyIncident::class)->orderByDesc('incident_date');
    }

    public function safetyInterventionAssignments(): HasMany
    {
        return $this->hasMany(SafetyInterventionTeam::class);
    }

    public function classCouncilSessionsAsPresident(): HasMany
    {
        return $this->hasMany(ClassCouncilSession::class, 'president_id');
    }

    public function classCouncilSessionsAsMainTeacher(): HasMany
    {
        return $this->hasMany(ClassCouncilSession::class, 'main_teacher_id');
    }

    public function subjectCoordinationProtocols(): HasMany
    {
        return $this->hasMany(SubjectCoordinationProtocol::class, 'coordinator_id');
    }

    public function curriculumPacingAudits(): HasMany
    {
        return $this->hasMany(CurriculumPacingAudit::class, 'teacher_id');
    }

    /**
     * Both panel roles (admin, teacher) may access the Sajel panel;
     * fine-grained abilities are enforced by the model policies.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role !== null;
    }
}
