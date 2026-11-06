<?php

namespace App\Models;

use App\Enums\InstitutionalReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarterlyInstitutionalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'trimester',
        'status',
        'general_success_rate',
        'evaluated_students_count',
        'passed_students_count',
        'excellence_count',
        'encouragement_count',
        'honor_count',
        'passing_count',
        'failing_count',
        'total_due_sessions',
        'completed_sessions',
        'teacher_absence_hours',
        'teacher_substitution_hours',
        'student_absence_records',
        'disciplinary_councils_count',
        'counseling_sessions_count',
        'level_breakdown',
        'subject_breakdown',
        'executive_summary',
        'pedagogical_recommendations',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'trimester' => 'integer',
        'status' => InstitutionalReportStatus::class,
        'general_success_rate' => 'decimal:2',
        'evaluated_students_count' => 'integer',
        'passed_students_count' => 'integer',
        'excellence_count' => 'integer',
        'encouragement_count' => 'integer',
        'honor_count' => 'integer',
        'passing_count' => 'integer',
        'failing_count' => 'integer',
        'total_due_sessions' => 'integer',
        'completed_sessions' => 'integer',
        'teacher_absence_hours' => 'integer',
        'teacher_substitution_hours' => 'integer',
        'student_absence_records' => 'integer',
        'disciplinary_councils_count' => 'integer',
        'counseling_sessions_count' => 'integer',
        'level_breakdown' => 'array',
        'subject_breakdown' => 'array',
        'validated_at' => 'datetime',
    ];

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopeForAcademicYear(Builder $query, ?string $year): Builder
    {
        return $query->when($year, fn ($q) => $q->where('academic_year', $year));
    }

    public function scopeForTrimester(Builder $query, ?int $trimester): Builder
    {
        return $query->when($trimester, fn ($q) => $q->where('trimester', $trimester));
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', InstitutionalReportStatus::Validated);
    }

    public function isValidated(): bool
    {
        return $this->status === InstitutionalReportStatus::Validated;
    }

    public function isDraft(): bool
    {
        return $this->status === InstitutionalReportStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === InstitutionalReportStatus::Archived;
    }

    public function trimesterName(): string
    {
        return match ($this->trimester) {
            1 => __('الفصل الأول'),
            2 => __('الفصل الثاني'),
            3 => __('الفصل الثالث'),
            default => __('الفصل :num', ['num' => $this->trimester]),
        };
    }

    public function honorRollTotal(): int
    {
        return $this->excellence_count + $this->encouragement_count + $this->honor_count;
    }

    public function honorRollRate(): float
    {
        if ($this->evaluated_students_count <= 0) {
            return 0.0;
        }

        return round(($this->honorRollTotal() / $this->evaluated_students_count) * 100, 2);
    }

    public function sessionCompletionRate(): float
    {
        if ($this->total_due_sessions <= 0) {
            return 0.0;
        }

        return round(($this->completed_sessions / $this->total_due_sessions) * 100, 2);
    }

    public function substitutionRate(): float
    {
        if ($this->teacher_absence_hours <= 0) {
            return 100.0;
        }

        return round(min(100.0, ($this->teacher_substitution_hours / $this->teacher_absence_hours) * 100), 2);
    }
}
