<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\ChronicConditionType;
use App\Enums\DentalHygieneStatus;
use App\Enums\EpsDispensationStatus;
use App\Enums\HearingCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'screening_date',
        'doctor_name',
        'uds_name',
        'height_cm',
        'weight_kg',
        'blood_type',
        'visual_acuity_right',
        'visual_acuity_left',
        'wears_glasses',
        'hearing_condition',
        'dental_caries_count',
        'dental_hygiene',
        'has_chronic_illness',
        'chronic_condition_type',
        'chronic_illness_details',
        'emergency_protocol_notes',
        'emergency_medication_at_school',
        'vaccinations_up_to_date',
        'vaccination_notes',
        'eps_dispensation_status',
        'eps_dispensation_start_date',
        'eps_dispensation_end_date',
        'eps_dispensation_reason',
        'eps_medical_certificate_ref',
        'general_clinical_notes',
        'follow_up_required',
        'specialist_referral',
    ];

    protected function casts(): array
    {
        return [
            'screening_date' => 'date',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'blood_type' => BloodType::class,
            'wears_glasses' => 'boolean',
            'hearing_condition' => HearingCondition::class,
            'dental_caries_count' => 'integer',
            'dental_hygiene' => DentalHygieneStatus::class,
            'has_chronic_illness' => 'boolean',
            'chronic_condition_type' => ChronicConditionType::class,
            'emergency_medication_at_school' => 'boolean',
            'vaccinations_up_to_date' => 'boolean',
            'eps_dispensation_status' => EpsDispensationStatus::class,
            'eps_dispensation_start_date' => 'date',
            'eps_dispensation_end_date' => 'date',
            'follow_up_required' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function bmi(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg || $this->height_cm <= 0) {
            return null;
        }

        $heightInMeters = (float) $this->height_cm / 100;

        return round((float) $this->weight_kg / ($heightInMeters ** 2), 1);
    }

    public function getBmiAttribute(): ?float
    {
        return $this->bmi();
    }

    public function bmiCategory(): string
    {
        $bmi = $this->bmi();
        if ($bmi === null) {
            return __('غير محدد');
        }

        if ($bmi < 18.5) {
            return __('نقص وزن (Sous-poids)');
        }
        if ($bmi <= 24.9) {
            return __('وزن طبيعي ومثالي (Normal)');
        }
        if ($bmi <= 29.9) {
            return __('زيادة في الوزن (Surpoids)');
        }

        return __('سمنة مفرطة (Obésité)');
    }

    public function getBmiCategoryAttribute(): string
    {
        return $this->bmiCategory();
    }

    public function getBmiClassificationAttribute(): string
    {
        return $this->bmiCategory();
    }

    public function isExemptFromEps(): bool
    {
        return $this->eps_dispensation_status instanceof EpsDispensationStatus
            ? $this->eps_dispensation_status->isExempt()
            : false;
    }

    public function hasScreening(): bool
    {
        return ! is_null($this->screening_date);
    }

    public function scopeScreened(Builder $query): Builder
    {
        return $query->whereNotNull('screening_date');
    }

    public function scopeChronic(Builder $query): Builder
    {
        return $query->where('has_chronic_illness', true);
    }

    public function scopeEpsExempted(Builder $query): Builder
    {
        return $query->where('eps_dispensation_status', '!=', EpsDispensationStatus::Fit->value);
    }

    public function scopeFollowUpRequired(Builder $query): Builder
    {
        return $query->where('follow_up_required', true);
    }

    public function scopeForAcademicYear(Builder $query, string $academicYear): Builder
    {
        return $query->where('academic_year', $academicYear);
    }
}
