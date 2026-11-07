<?php

namespace App\Models;

use App\Enums\BoilerFuelType;
use App\Enums\HeatingInspectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentralHeatingLog extends Model
{
    use HasFactory;

    protected $table = 'central_heating_logs';

    protected $fillable = [
        'log_reference',
        'academic_year',
        'boiler_code',
        'boiler_brand_model',
        'installation_year',
        'fuel_type',
        'inspection_date',
        'inspector_entity',
        'burner_operational_state',
        'safety_valve_tested',
        'pressure_bar',
        'water_temp_celsius',
        'co_detector_tested',
        'room_ventilation_ok',
        'chimneys_cleaned',
        'status',
        'observations_and_repairs',
        'certified_by_name',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'fuel_type' => BoilerFuelType::class,
            'status' => HeatingInspectionStatus::class,
            'safety_valve_tested' => 'boolean',
            'co_detector_tested' => 'boolean',
            'room_ventilation_ok' => 'boolean',
            'chimneys_cleaned' => 'boolean',
            'pressure_bar' => 'decimal:2',
            'water_temp_celsius' => 'integer',
            'installation_year' => 'integer',
        ];
    }

    public function isFullySafe(): bool
    {
        return $this->safety_valve_tested
            && $this->co_detector_tested
            && $this->room_ventilation_ok
            && $this->chimneys_cleaned
            && $this->status === HeatingInspectionStatus::CompliantApproved;
    }
}

