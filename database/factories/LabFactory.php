<?php

namespace Database\Factories;

use App\Models\Lab;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lab>
 */
class LabFactory extends Factory
{
    protected $model = Lab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'name' => 'مختبر '.fake()->randomElement(['الحاسوب', 'العلوم', 'اللغات', 'الفيزياء']).' '.fake()->numberBetween(1, 5),
            'academic_year' => '2025-2026',
            'capacity' => fake()->numberBetween(10, 40),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
