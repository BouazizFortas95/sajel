<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'name' => fake()->randomElement(['أ', 'ب', 'ج', 'د']).' '.fake()->numberBetween(1, 3),
            'academic_year' => '2025-2026',
        ];
    }
}
