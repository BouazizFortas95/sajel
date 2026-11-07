<?php

namespace App\Models;

use App\Enums\FacilityCondition;
use App\Enums\FacilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBuildingFacility extends Model
{
    use HasFactory;

    protected $table = 'school_building_facilities';

    protected $fillable = [
        'facility_code',
        'facility_name',
        'facility_type',
        'building_block',
        'total_rooms_count',
        'condition_rating',
        'has_heating',
        'has_water_connection',
        'has_gas_connection',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'facility_type' => FacilityType::class,
            'condition_rating' => FacilityCondition::class,
            'total_rooms_count' => 'integer',
            'has_heating' => 'boolean',
            'has_water_connection' => 'boolean',
            'has_gas_connection' => 'boolean',
        ];
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'facility_id');
    }

    public function openWorkOrdersCount(): int
    {
        return $this->workOrders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    public function needsUrgentIntervention(): bool
    {
        return $this->condition_rating === FacilityCondition::NeedsRestoration
            || $this->condition_rating === FacilityCondition::Dilapidated;
    }
}

