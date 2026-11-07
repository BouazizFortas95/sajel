<?php

namespace App\Models;

use App\Enums\BoardingNightSessionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingNightSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'session_date' => 'date',
        'session_type' => BoardingNightSessionType::class,
        'boarders_present_count' => 'integer',
        'boarders_absent_count' => 'integer',
        'sickbay_count' => 'integer',
        'authorized_leave_count' => 'integer',
    ];

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
