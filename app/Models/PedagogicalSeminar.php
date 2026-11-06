<?php

namespace App\Models;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use Database\Factories\PedagogicalSeminarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedagogicalSeminar extends Model
{
    /** @use HasFactory<PedagogicalSeminarFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'subject_id',
        'title',
        'inspector_name',
        'host_school',
        'scheduled_at',
        'seminar_type',
        'target_teachers_description',
        'animator_teacher_id',
        'objectives',
        'recommendations_summary',
        'official_call_number',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
        'seminar_type' => SeminarType::class,
        'status' => SeminarStatus::class,
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function animator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'animator_teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PedagogicalSeminarAttendance::class);
    }

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }
}
