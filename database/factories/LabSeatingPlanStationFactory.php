<?php

namespace Database\Factories;

use App\Models\LabSeatingPlan;
use App\Models\LabStation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabSeatingPlanStation>
 */
class LabSeatingPlanStationFactory extends Factory
{
    protected $model = LabSeatingPlanStation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seating_plan_id' => LabSeatingPlan::factory(),
            'lab_station_id' => LabStation::factory(),
            'student_id' => Student::factory(),
            'position_x' => fake()->numberBetween(0, 10),
            'position_y' => fake()->numberBetween(0, 5),
        ];
    }
}
