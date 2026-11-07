<?php

namespace App\Models;

use App\Enums\OfficialExamDutyRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialExamSupervisor extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'duty_role' => OfficialExamDutyRole::class,
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(OfficialExamCenter::class, 'center_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(OfficialExamRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
