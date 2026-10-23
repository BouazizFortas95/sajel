<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalRemediation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'reason', 'activities', 'session_date', 'attended', 'follow_up', 'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'attended' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
