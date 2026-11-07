<?php

namespace App\Models;

use App\Enums\SafetyInterventionTeamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyInterventionTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'safety_plan_id',
        'team_type',
        'member_name',
        'institution_role',
        'user_id',
        'assigned_zone',
        'phone_number',
        'is_certified_first_aider',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'team_type' => SafetyInterventionTeamType::class,
            'is_certified_first_aider' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SafetyEmergencyPlan::class, 'safety_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
