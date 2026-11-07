<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyEmergencyPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'plan_code',
        'title',
        'crisis_cell_leader',
        'evacuation_coordinator',
        'first_aid_leader',
        'technical_safety_officer',
        'assembly_points',
        'emergency_contacts',
        'hazardous_zones',
        'status',
        'approval_date',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assembly_points' => 'array',
            'emergency_contacts' => 'array',
            'hazardous_zones' => 'array',
            'approval_date' => 'date',
        ];
    }

    public function interventionTeams(): HasMany
    {
        return $this->hasMany(SafetyInterventionTeam::class, 'safety_plan_id');
    }
}
