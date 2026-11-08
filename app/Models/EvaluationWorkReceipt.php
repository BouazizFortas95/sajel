<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationWorkReceipt extends Model
{
    use HasFactory;

    /**
     * Term indexes for deliverables.
     */
    public const TERMS = [1, 2, 3];

    protected $fillable = [
        'user_id',
        'academic_year',
        'diagnostic_test_submitted',
        'diagnostic_test_date',
        'homework_1_submitted',
        'homework_1_date',
        'homework_2_submitted',
        'homework_2_date',
        'homework_3_submitted',
        'homework_3_date',
        'quiz_1_submitted',
        'quiz_1_date',
        'quiz_2_submitted',
        'quiz_2_date',
        'quiz_3_submitted',
        'quiz_3_date',
        'exam_1_submitted',
        'exam_1_date',
        'exam_2_submitted',
        'exam_2_date',
        'exam_3_submitted',
        'exam_3_date',
        'notes',
        'director_visa',
        'director_visa_at',
        'censor_visa',
        'censor_visa_at',
    ];

    protected function casts(): array
    {
        return [
            'diagnostic_test_submitted' => 'boolean',
            'diagnostic_test_date' => 'date',
            'homework_1_submitted' => 'boolean',
            'homework_1_date' => 'date',
            'homework_2_submitted' => 'boolean',
            'homework_2_date' => 'date',
            'homework_3_submitted' => 'boolean',
            'homework_3_date' => 'date',
            'quiz_1_submitted' => 'boolean',
            'quiz_1_date' => 'date',
            'quiz_2_submitted' => 'boolean',
            'quiz_2_date' => 'date',
            'quiz_3_submitted' => 'boolean',
            'quiz_3_date' => 'date',
            'exam_1_submitted' => 'boolean',
            'exam_1_date' => 'date',
            'exam_2_submitted' => 'boolean',
            'exam_2_date' => 'date',
            'exam_3_submitted' => 'boolean',
            'exam_3_date' => 'date',
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
     * Check if a specific deliverable field is submitted.
     */
    public function isItemSubmitted(string $key): bool
    {
        $field = "{$key}_submitted";

        return (bool) ($this->{$field} ?? false);
    }

    /**
     * Get the date of a specific deliverable submission.
     */
    public function getItemDate(string $key): ?string
    {
        $field = "{$key}_date";
        $val = $this->{$field};

        return $val ? (is_string($val) ? $val : $val->format('Y-m-d')) : null;
    }
}

