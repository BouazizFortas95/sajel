<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalSeminarAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedagogical_seminar_id',
        'user_id',
        'attended',
        'certificate_ref',
        'notes',
    ];

    protected $casts = [
        'attended' => 'boolean',
    ];

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(PedagogicalSeminar::class, 'pedagogical_seminar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
