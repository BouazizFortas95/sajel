<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BehaviorObservation extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (BehaviorObservation $observation): void {
            $observation->recorded_by_id ??= Auth::id();
        });
    }

    protected $fillable = [
        'student_id',
        'schedule_id',
        'disciplinary_record_id',
        'recorded_by_id',
        'category',
        'notes',
        'date',
        'observed_by',
        'is_followed_up',
        'follow_up_notes',
        'guardian_summons_sent_at',
        'guardian_summons_appointment_at',
        'guardian_summons_notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_followed_up' => 'boolean',
            'guardian_summons_sent_at' => 'date',
            'guardian_summons_appointment_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function disciplinaryRecord(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryRecord::class);
    }

    public function hasDisciplinaryRecord(): bool
    {
        return ! is_null($this->disciplinary_record_id);
    }

    public function hasGuardianSummons(): bool
    {
        return ! is_null($this->guardian_summons_sent_at);
    }
}
