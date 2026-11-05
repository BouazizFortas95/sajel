<?php

namespace App\Models;

use App\Enums\FamilyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCounselingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'family_status',
        'siblings_count',
        'birth_order',
        'father_job',
        'mother_job',
        'social_grant',
        'blood_type',
        'chronic_conditions',
        'has_physical_disability',
        'pe_exemption',
        'health_notes',
        'primary_school_name',
        'primary_graduation_year',
        'repeated_years_count',
        'repeated_levels',
        'learning_difficulties',
        'behavior_tendencies',
        'interests_and_talents',
        'career_aspirations',
        'counselor_general_notes',
        'is_confidential',
    ];

    protected $casts = [
        'family_status' => FamilyStatus::class,
        'social_grant' => 'boolean',
        'has_physical_disability' => 'boolean',
        'pe_exemption' => 'boolean',
        'is_confidential' => 'boolean',
        'chronic_conditions' => 'array',
        'repeated_levels' => 'array',
        'learning_difficulties' => 'array',
        'behavior_tendencies' => 'array',
        'interests_and_talents' => 'array',
        'siblings_count' => 'integer',
        'birth_order' => 'integer',
        'repeated_years_count' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'student_id', 'student_id');
    }

    public function scopeWithSocialGrant(Builder $query): Builder
    {
        return $query->where('social_grant', true);
    }

    public function scopeWithPeExemption(Builder $query): Builder
    {
        return $query->where('pe_exemption', true);
    }

    public function scopeWithChronicConditions(Builder $query): Builder
    {
        return $query->whereNotNull('chronic_conditions')
            ->where('chronic_conditions', '!=', '[]')
            ->where('chronic_conditions', '!=', '');
    }

    public function scopeWithLearningDifficulties(Builder $query): Builder
    {
        return $query->whereNotNull('learning_difficulties')
            ->where('learning_difficulties', '!=', '[]')
            ->where('learning_difficulties', '!=', '');
    }

    public function hasRiskFactors(): bool
    {
        return ($this->family_status !== null && $this->family_status !== FamilyStatus::BothParents)
            || ! empty($this->chronic_conditions)
            || $this->has_physical_disability
            || $this->repeated_years_count > 0
            || ! empty($this->learning_difficulties);
    }

    public function chronicConditionsList(): string
    {
        return ! empty($this->chronic_conditions) ? implode('، ', (array) $this->chronic_conditions) : __('لا توجد أمراض مزمنة مصرح بها');
    }

    public function learningDifficultiesList(): string
    {
        return ! empty($this->learning_difficulties) ? implode('، ', (array) $this->learning_difficulties) : __('لا توجد صعوبات تعلم مسجلة');
    }

    public function behaviorTendenciesList(): string
    {
        return ! empty($this->behavior_tendencies) ? implode('، ', (array) $this->behavior_tendencies) : __('سلوك معتاد ومتزن');
    }

    public function interestsList(): string
    {
        return ! empty($this->interests_and_talents) ? implode('، ', (array) $this->interests_and_talents) : __('لم تحدد بعد');
    }
}
