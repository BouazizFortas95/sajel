<?php

namespace App\Models;

use App\Enums\StaffEvaluationAppreciation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEvaluation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'evaluation_year' => 'integer',
        'administrative_score' => 'decimal:2',
        'punctuality_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'conduct_score' => 'decimal:2',
        'initiative_score' => 'decimal:2',
        'pedagogical_score' => 'decimal:2',
        'last_inspection_date' => 'date',
        'evaluated_at' => 'date',
        'appreciation' => StaffEvaluationAppreciation::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Compute composite administrative score (sum of 4 components /5 = /20)
     */
    public function computeAdministrativeScore(): float
    {
        return round(
            (float) $this->punctuality_score +
            (float) $this->performance_score +
            (float) $this->conduct_score +
            (float) $this->initiative_score,
            2
        );
    }

    /**
     * Composite score for promotions:
     * For teaching staff with an inspection pedagogical mark: (Admin + Pedagogical) / 2
     * Otherwise: Admin score
     */
    public function getCompositeScoreAttribute(): float
    {
        if ($this->pedagogical_score !== null && $this->pedagogical_score > 0) {
            return round(((float) $this->administrative_score + (float) $this->pedagogical_score) / 2, 2);
        }

        return (float) $this->administrative_score;
    }

    public function getSignatureStatusLabelAttribute(): string
    {
        return match ($this->staff_signature_status) {
            'acknowledged' => __('اطّلع ووقع'),
            'contested' => __('قدّم طعناً / تحفظات'),
            default => __('قيد التبليغ والتوقيع'),
        };
    }
}

