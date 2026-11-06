<?php

namespace App\Models;

use App\Enums\AdvancementRhythm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerAdvancement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'current_step' => 'integer',
        'next_step' => 'integer',
        'current_step_date' => 'date',
        'eligible_at_minimum' => 'date',
        'eligible_at_medium' => 'date',
        'eligible_at_maximum' => 'date',
        'effective_date' => 'date',
        'financial_impact_date' => 'date',
        'advancement_rhythm' => AdvancementRhythm::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => __('مشروع مقترح'),
            'submitted' => __('مُحال على اللجنة المتساوية الأعضاء (CAP)'),
            'approved_by_cap' => __('مُصادق عليه من اللجنة المتساوية الأعضاء'),
            'rejected' => __('مرفوض أو مؤجل'),
            'archived' => __('مؤرشف'),
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted' => 'warning',
            'approved_by_cap' => 'success',
            'rejected' => 'danger',
            'archived' => 'info',
            default => 'primary',
        };
    }

    /**
     * Check if user is eligible under a specific rhythm as of a given date
     */
    public function isEligibleUnder(AdvancementRhythm $rhythm, ?Carbon $date = null): bool
    {
        $checkDate = $date ?? now();
        $targetDate = match ($rhythm) {
            AdvancementRhythm::Minimum => $this->eligible_at_minimum,
            AdvancementRhythm::Medium => $this->eligible_at_medium,
            AdvancementRhythm::Maximum => $this->eligible_at_maximum,
        };

        return $targetDate !== null && $checkDate->greaterThanOrEqualTo($targetDate);
    }

    /**
     * Seniority in current step formatted in years and months
     */
    public function getSeniorityInCurrentStep(?Carbon $asOf = null): array
    {
        if (! $this->current_step_date) {
            return ['years' => 0, 'months' => 0, 'formatted' => '0 سنة'];
        }

        $asOf = $asOf ?? now();
        $diff = $this->current_step_date->diff($asOf);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'formatted' => "{$diff->y} سنة و {$diff->m} شهر",
        ];
    }
}
