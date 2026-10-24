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
        'student_id', 'schedule_id', 'category', 'notes', 'date', 'observed_by', 'is_followed_up',
        'follow_up_notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_followed_up' => 'boolean',
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
}
