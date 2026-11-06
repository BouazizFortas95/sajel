<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalCouncilAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedagogical_council_id',
        'user_id',
        'is_present',
        'role_in_council',
        'absence_justification',
        'notes',
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    public function council(): BelongsTo
    {
        return $this->belongsTo(PedagogicalCouncil::class, 'pedagogical_council_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
