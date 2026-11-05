<?php

namespace App\Models;

use App\Enums\OrientationAppealStatus;
use App\Enums\OrientationStream;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentOrientation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'first_choice',
        'second_choice',
        'guardian_approved',
        'guardian_notes',
        'pre_orientation_choice',
        'pre_orientation_notes',
        'scientific_profile_score',
        'literary_profile_score',
        'annual_general_average',
        'bem_score',
        'admission_average',
        'is_admitted',
        'final_decision',
        'deliberated_at',
        'counselor_notes',
        'appeal_status',
        'appeal_decision',
        'appeal_notes',
    ];

    protected function casts(): array
    {
        return [
            'first_choice' => OrientationStream::class,
            'second_choice' => OrientationStream::class,
            'guardian_approved' => 'boolean',
            'pre_orientation_choice' => OrientationStream::class,
            'scientific_profile_score' => 'float',
            'literary_profile_score' => 'float',
            'annual_general_average' => 'float',
            'bem_score' => 'float',
            'admission_average' => 'float',
            'is_admitted' => 'boolean',
            'final_decision' => OrientationStream::class,
            'deliberated_at' => 'datetime',
            'appeal_status' => OrientationAppealStatus::class,
            'appeal_decision' => OrientationStream::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeForAcademicYear(Builder $query, ?string $year = null): Builder
    {
        $year = $year ?? SchoolSetting::currentAcademicYear();

        return $query->where('academic_year', $year);
    }

    public function scopeAdmitted(Builder $query): Builder
    {
        return $query->where('is_admitted', true);
    }

    public function scopeAppealed(Builder $query): Builder
    {
        return $query->where('appeal_status', '!=', OrientationAppealStatus::None);
    }

    public function scopeByStream(Builder $query, OrientationStream|string $stream): Builder
    {
        $val = $stream instanceof OrientationStream ? $stream->value : $stream;

        return $query->where('final_decision', $val);
    }

    public function recommendedStream(): ?OrientationStream
    {
        if ($this->scientific_profile_score === null && $this->literary_profile_score === null) {
            return null;
        }

        $sci = $this->scientific_profile_score ?? 0.0;
        $lit = $this->literary_profile_score ?? 0.0;

        return $sci >= $lit ? OrientationStream::ScienceTech : OrientationStream::ArtsHumanities;
    }

    public function profileGap(): ?float
    {
        if ($this->scientific_profile_score === null || $this->literary_profile_score === null) {
            return null;
        }

        return round(abs($this->scientific_profile_score - $this->literary_profile_score), 2);
    }

    public function effectiveDecision(): ?OrientationStream
    {
        if ($this->appeal_status === OrientationAppealStatus::Accepted && $this->appeal_decision) {
            return $this->appeal_decision;
        }

        return $this->final_decision;
    }

    public function hasSatisfiedWish(): bool
    {
        $eff = $this->effectiveDecision();

        return $eff !== null && $this->first_choice !== null && $eff === $this->first_choice;
    }
}
