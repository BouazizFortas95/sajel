<?php

namespace App\Models;

use App\Enums\CounselingSessionReason;
use App\Enums\CounselingSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'counselor_id',
        'academic_year',
        'session_date',
        'reason_category',
        'interviewee_type',
        'observations',
        'action_plan',
        'follow_up_date',
        'status',
        'is_confidential',
    ];

    protected $casts = [
        'session_date' => 'date',
        'follow_up_date' => 'date',
        'reason_category' => CounselingSessionReason::class,
        'status' => CounselingSessionStatus::class,
        'is_confidential' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function scopeForAcademicYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CounselingSessionStatus::Open);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', CounselingSessionStatus::InProgress);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', CounselingSessionStatus::Resolved);
    }

    public function scopePendingFollowUp(Builder $query): Builder
    {
        return $query->whereNotNull('follow_up_date')
            ->where('status', '!=', CounselingSessionStatus::Resolved);
    }

    public function intervieweeLabel(): string
    {
        return match ($this->interviewee_type) {
            'student_only' => __('التلميذ بمفرده'),
            'guardian_only' => __('ولي الأمر بمفرده'),
            'student_and_guardian' => __('التلميذ بحضور الولي'),
            default => __('التلميذ'),
        };
    }
}
