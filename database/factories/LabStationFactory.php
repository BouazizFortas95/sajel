<?php

namespace Database\Factories;

use App\Enums\LabStatus;
use App\Models\Lab;
use App\Models\LabStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabStation>
 */
class LabStationFactory extends Factory
{
    protected $model = LabStation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => Lab::factory(),
            'station_number' => fake()->unique()->numberBetween(1, 1000),
            'device_type' => fake()->randomElement(['computer', 'tablet', 'device']),
            'status' => fake()->randomElement(LabStatus::cases())->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
