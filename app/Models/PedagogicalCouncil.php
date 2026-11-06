<?php

namespace App\Models;

use App\Enums\PedagogicalCouncilStatus;
use App\Enums\PedagogicalCouncilType;
use Database\Factories\PedagogicalCouncilFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedagogicalCouncil extends Model
{
    /** @use HasFactory<PedagogicalCouncilFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'subject_id',
        'council_type',
        'session_term',
        'title',
        'held_at',
        'location',
        'coordinator_id',
        'agenda',
        'curriculum_pacing_notes',
        'unified_assessments_agreement',
        'recommendations',
        'pv_reference',
        'status',
    ];

    protected $casts = [
        'held_at' => 'datetime',
        'session_term' => 'integer',
        'council_type' => PedagogicalCouncilType::class,
        'status' => PedagogicalCouncilStatus::class,
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PedagogicalCouncilAttendance::class);
    }

    public function presentTeachers(): HasMany
    {
        return $this->hasMany(PedagogicalCouncilAttendance::class)->where('is_present', true);
    }

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }

    public function isCompleted(): bool
    {
        return $this->status === PedagogicalCouncilStatus::Completed;
    }

    public function generatePvReference(): string
    {
        $subjectCode = $this->subject ? mb_substr(strtoupper($this->subject->name), 0, 3, 'UTF-8') : 'GEN';
        $yearCode = substr(str_replace(['/', '-'], '', $this->academic_year ?? date('Y')), -4);

        return sprintf('PV-CE-%s-%s-%03d', $subjectCode, $yearCode, $this->id ?: rand(1, 999));
    }
}

