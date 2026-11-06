<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Both panel roles (admin, teacher) may access the Sajel panel;
     * fine-grained abilities are enforced by the model policies.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role !== null;
    }
}
