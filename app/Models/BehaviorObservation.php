<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'category', 'notes', 'date', 'observed_by', 'is_followed_up',
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
}
