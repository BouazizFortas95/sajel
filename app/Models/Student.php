<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'first_name_latin',
        'last_name_latin',
        'gender',
        'registration_number',
        'matricule_number',
        'national_id_number',
        'date_of_birth',
        'place_of_birth',
        'group_id',
        'enrollment_date',
        'departure_date',
        'is_active',
        'is_repeater',
        'regime',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
        'address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
            'departure_date' => 'date',
            'is_active' => 'boolean',
            'is_repeater' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(StudentTextbookLoan::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class)->orderByDesc('transfer_date');
    }

    public function latestTransfer(): HasOne
    {
        return $this->hasOne(StudentTransfer::class)->latestOfMany('transfer_date');
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class)->orderByDesc('session_date');
    }

    public function orientations(): HasMany
    {
        return $this->hasMany(StudentOrientation::class);
    }

    public function currentOrientation(): HasOne
    {
        return $this->hasOne(StudentOrientation::class)
            ->where('academic_year', SchoolSetting::currentAcademicYear());
    }

    public function counselingProfile(): HasOne
    {
        return $this->hasOne(StudentCounselingProfile::class);
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class)->orderByDesc('session_date');
    }

    public function latestCounselingSession(): HasOne
    {
        return $this->hasOne(CounselingSession::class)->latestOfMany('session_date');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(StudentMedicalRecord::class);
    }

    public function currentMedicalRecord(): HasOne
    {
        $year = SchoolSetting::currentAcademicYear();

        return $this->hasOne(StudentMedicalRecord::class)->ofMany(
            ['academic_year' => 'max'],
            fn ($query) => $query->where('academic_year', $year)
        );
    }

    public function isEpsDispensed(?string $year = null): bool
    {
        $year = $year ?? SchoolSetting::currentAcademicYear();
        $record = $this->medicalRecords()->where('academic_year', $year)->first();

        return $record ? $record->isExemptFromEps() : false;
    }

    /**
     * The student's full name, used by Filament tables and reports.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /**
     * The student's full name in Latin / French script.
     */
    protected function fullNameLatin(): Attribute
    {
        return Attribute::get(
            fn (): ?string => ($this->first_name_latin || $this->last_name_latin)
                ? trim("{$this->last_name_latin} {$this->first_name_latin}")
                : null,
        );
    }

    public function isRepeater(): bool
    {
        return (bool) $this->is_repeater;
    }

    /*
     * -------------------------------------------------------------
     * Aliases & Accessors for Cross-System and Ministry Compatibility
     * -------------------------------------------------------------
     */

    public function getNationalIdAttribute(): ?string
    {
        return $this->national_id_number;
    }

    public function setNationalIdAttribute($value): void
    {
        $this->attributes['national_id_number'] = $value;
    }

    public function getBirthDateAttribute()
    {
        return $this->date_of_birth;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return $this->place_of_birth;
    }

    public function isMale(): bool
    {
        return in_array(strtolower((string) $this->gender), ['male', 'm', 'ذكر', '1'], true);
    }

    public function isFemale(): bool
    {
        return in_array(strtolower((string) $this->gender), ['female', 'f', 'أنثى', '2'], true);
    }

    public function regimeLabel(): string
    {
        $regime = strtolower((string) $this->regime);

        if (in_array($regime, ['half_board', 'نصف داخلي', 'نصف-داخلي', 'demi-pensionnaire', 'demi_pensionnaire', 'demi'])) {
            return __('نصف داخلي');
        }

        if (in_array($regime, ['boarder', 'داخلي', 'interne'])) {
            return __('داخلي');
        }

        return __('خارجي');
    }

    public function isHalfBoard(): bool
    {
        $regime = strtolower((string) $this->regime);

        return in_array($regime, ['half_board', 'نصف داخلي', 'نصف-داخلي', 'demi-pensionnaire', 'demi_pensionnaire', 'demi'], true);
    }

    public function isBoarder(): bool
    {
        $regime = strtolower((string) $this->regime);

        return in_array($regime, ['boarder', 'داخلي', 'interne'], true);
    }

    /*
     * -------------------------------------------------------------
     * Query Scopes
     * -------------------------------------------------------------
     */

    public function scopeForSchedule(Builder $query, Schedule $schedule): Builder
    {
        if (filled($schedule->group_id)) {
            return $query->where('group_id', $schedule->group_id);
        }

        if (filled($schedule->classroom_id)) {
            return $query->whereHas(
                'group',
                fn (Builder $query): Builder => $query->where('classroom_id', $schedule->classroom_id),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeWithNin(Builder $query): Builder
    {
        return $query->whereNotNull('national_id_number')->where('national_id_number', '!=', '');
    }

    public function scopeWithoutNin(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('national_id_number')->orWhere('national_id_number', '');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function solidarityBeneficiaries(): HasMany
    {
        return $this->hasMany(StudentSolidarityBeneficiary::class);
    }

    public function solidarityAidDistributions(): HasMany
    {
        return $this->hasMany(SchoolSolidarityAidDistribution::class);
    }

    public function dormitoryAllocations(): HasMany
    {
        return $this->hasMany(StudentDormitoryAllocation::class);
    }

    public function activeDormitoryAllocation(): ?StudentDormitoryAllocation
    {
        return $this->dormitoryAllocations()->where('status', 'active')->latest('id')->first();
    }

    public function boardingExitPasses(): HasMany
    {
        return $this->hasMany(BoardingExitPass::class);
    }

    public function busSubscriptions(): HasMany
    {
        return $this->hasMany(StudentBusSubscription::class);
    }
}
