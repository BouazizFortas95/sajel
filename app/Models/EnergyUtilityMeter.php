<?php

namespace App\Models;

use App\Enums\UtilityMeterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnergyUtilityMeter extends Model
{
    use HasFactory;

    protected $table = 'energy_utility_meters';

    protected $fillable = [
        'meter_code',
        'meter_type',
        'utility_provider',
        'meter_serial_number',
        'meter_location',
        'subscribed_power_or_flow',
        'tariff_category',
        'unit_of_measure',
        'is_active',
        'installation_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'meter_type' => UtilityMeterType::class,
            'is_active' => 'boolean',
            'installation_date' => 'date',
        ];
    }

    public function consumptionRecords(): HasMany
    {
        return $this->hasMany(UtilityConsumptionRecord::class, 'energy_utility_meter_id');
    }

    public function latestRecord(): ?UtilityConsumptionRecord
    {
        return $this->consumptionRecords()->latest('reading_date_end')->first();
    }
}

