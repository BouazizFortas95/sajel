<?php

namespace App\Models;

use App\Enums\SafetyDrillScenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyEvacuationDrill extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'drill_code',
        'drill_date',
        'drill_time',
        'simulation_scenario',
        'evacuation_time_seconds',
        'total_persons_evacuated',
        'students_evacuated_count',
        'staff_evacuated_count',
        'assembly_zone_used',
        'civil_protection_partner',
        'civil_protection_representatives',
        'evaluation_rating',
        'bottlenecks_and_remarks',
        'corrective_measures',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'drill_date' => 'date',
            'simulation_scenario' => SafetyDrillScenario::class,
            'evacuation_time_seconds' => 'integer',
            'total_persons_evacuated' => 'integer',
            'students_evacuated_count' => 'integer',
            'staff_evacuated_count' => 'integer',
        ];
    }

    public function formattedDuration(): string
    {
        $minutes = intdiv($this->evacuation_time_seconds, 60);
        $seconds = $this->evacuation_time_seconds % 60;

        return sprintf('%d د %02d ث', $minutes, $seconds);
    }
}
