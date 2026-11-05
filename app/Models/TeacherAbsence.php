<?php

namespace App\Models;

use App\Enums\TeacherAbsenceReason;
use App\Enums\TeacherAbsenceStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAbsence extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'reason' => TeacherAbsenceReason::class,
        'status' => TeacherAbsenceStatus::class,
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function durationInDays(): int
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return 1;
        }

        return (int) $this->starts_at->diffInDays($this->ends_at) + 1;
    }

    public function durationLabel(): string
    {
        $days = $this->durationInDays();

        return $days === 1
            ? __('يوم واحد')
            : ($days === 2 ? __('يومان') : __(':count أيام', ['count' => $days]));
    }

    public function isCovered(): bool
    {
        return $this->status === TeacherAbsenceStatus::Covered;
    }

    public function scopeForTeacher(Builder $query, User|int $teacher): Builder
    {
        $id = $teacher instanceof User ? $teacher->id : $teacher;

        return $query->where('teacher_id', $id);
    }

    public function scopeActiveOnDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $dateStr = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        return $query->whereDate('starts_at', '<=', $dateStr)
            ->whereDate('ends_at', '>=', $dateStr);
    }

    public function scopeOverlapping(Builder $query, CarbonInterface|string $start, CarbonInterface|string $end): Builder
    {
        $startStr = $start instanceof CarbonInterface ? $start->toDateString() : (string) $start;
        $endStr = $end instanceof CarbonInterface ? $end->toDateString() : (string) $end;

        return $query->whereDate('starts_at', '<=', $endStr)
            ->whereDate('ends_at', '>=', $startStr);
    }

    public function scopePendingOrUncovered(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TeacherAbsenceStatus::Pending,
            TeacherAbsenceStatus::Uncovered,
            TeacherAbsenceStatus::PartiallyCovered,
        ]);
    }
}
