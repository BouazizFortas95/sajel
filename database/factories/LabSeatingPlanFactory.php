<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabSeatingPlan>
 */
class LabSeatingPlanFactory extends Factory
{
    protected $model = LabSeatingPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => Lab::factory(),
            'group_id' => Group::factory(),
            'name' => 'خطة جلوس '.fake()->randomElement(['الصباح', 'المساء', 'أ', 'ب']).' '.fake()->numberBetween(1, 10),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
