<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalPlanReceipt extends Model
{
    use HasFactory;

    /**
     * School year pedagogical months in order (September to May).
     */
    public const MONTHS = [
        'september' => 'سبتمبر',
        'october' => 'أكتوبر',
        'november' => 'نوفمبر',
        'december' => 'ديسمبر',
        'january' => 'جانفي',
        'february' => 'فيفري',
        'march' => 'مارس',
        'april' => 'أفريل',
        'may' => 'ماي',
    ];

    protected $fillable = [
        'user_id',
        'academic_year',
        'annual_plan_submitted',
        'annual_plan_date',
        'september_submitted',
        'september_date',
        'october_submitted',
        'october_date',
        'november_submitted',
        'november_date',
        'december_submitted',
        'december_date',
        'january_submitted',
        'january_date',
        'february_submitted',
        'february_date',
        'march_submitted',
        'march_date',
        'april_submitted',
        'april_date',
        'may_submitted',
        'may_date',
        'notes',
        'director_visa',
        'director_visa_at',
        'censor_visa',
        'censor_visa_at',
    ];

    protected function casts(): array
    {
        return [
            'annual_plan_submitted' => 'boolean',
            'annual_plan_date' => 'date',
            'september_submitted' => 'boolean',
            'september_date' => 'date',
            'october_submitted' => 'boolean',
            'october_date' => 'date',
            'november_submitted' => 'boolean',
            'november_date' => 'date',
            'december_submitted' => 'boolean',
            'december_date' => 'date',
            'january_submitted' => 'boolean',
            'january_date' => 'date',
            'february_submitted' => 'boolean',
            'february_date' => 'date',
            'march_submitted' => 'boolean',
            'march_date' => 'date',
            'april_submitted' => 'boolean',
            'april_date' => 'date',
            'may_submitted' => 'boolean',
            'may_date' => 'date',
            'director_visa' => 'boolean',
            'director_visa_at' => 'datetime',
            'censor_visa' => 'boolean',
            'censor_visa_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if a specific month plan has been submitted.
     */
    public function isMonthSubmitted(string $month): bool
    {
        $attribute = strtolower($month).'_submitted';

        return (bool) ($this->{$attribute} ?? false);
    }

    /**
     * Get the submission date for a specific month plan.
     */
    public function getMonthDate(string $month): ?string
    {
        $attribute = strtolower($month).'_date';
        $date = $this->{$attribute};

        return $date ? $date->format('Y-m-d') : null;
    }

    /**
     * Count submitted monthly plans.
     */
    public function getSubmittedMonthsCount(): int
    {
        $count = 0;
        foreach (array_keys(self::MONTHS) as $monthKey) {
            if ($this->isMonthSubmitted($monthKey)) {
                $count++;
            }
        }

        return $count;
    }
}
