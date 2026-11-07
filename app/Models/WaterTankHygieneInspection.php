<?php

namespace App\Models;

use App\Enums\WaterPotabilityStatus;
use App\Enums\WaterTankType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTankHygieneInspection extends Model
{
    use HasFactory;

    protected $table = 'water_tank_hygiene_inspections';

    protected $fillable = [
        'inspection_reference',
        'academic_year',
        'tank_name_code',
        'tank_type',
        'capacity_cubic_meters',
        'inspection_date',
        'operation_type',
        'chemical_agent_used',
        'executed_by',
        'residual_chlorine_ppm',
        'water_potable_clearance',
        'next_disinfection_due_date',
        'certification_ref',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'next_disinfection_due_date' => 'date',
            'tank_type' => WaterTankType::class,
            'water_potable_clearance' => WaterPotabilityStatus::class,
            'capacity_cubic_meters' => 'decimal:2',
            'residual_chlorine_ppm' => 'decimal:2',
        ];
    }

    public function isDisinfectionOverdue(): bool
    {
        return $this->next_disinfection_due_date && $this->next_disinfection_due_date->isPast();
    }

    public function isPotableAndValid(): bool
    {
        return $this->water_potable_clearance === WaterPotabilityStatus::CompliantPotable
            && ! $this->isDisinfectionOverdue();
    }
}
