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
        'regime',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
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

    /**
     * The student's full name, used by Filament tables and reports.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
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
}
