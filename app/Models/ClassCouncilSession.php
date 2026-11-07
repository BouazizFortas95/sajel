<?php

namespace App\Models;

use App\Enums\ClassCouncilSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassCouncilSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'trimester' => 'integer',
        'session_date' => 'datetime',
        'class_average' => 'float',
        'highest_average' => 'float',
        'lowest_average' => 'float',
        'success_rate' => 'float',
        'total_students' => 'integer',
        'status' => ClassCouncilSessionStatus::class,
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    public function mainTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_teacher_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ClassCouncilDecision::class, 'class_council_session_id')
            ->orderBy('rank');
    }

    public function isCertified(): bool
    {
        return $this->status === ClassCouncilSessionStatus::Certified;
    }

    public function honorsCount(): int
    {
        return $this->decisions()->where('honors', '!=', 'none')->count();
    }

    public function sanctionsCount(): int
    {
        return $this->decisions()->where('sanctions', '!=', 'none')->count();
    }
}
