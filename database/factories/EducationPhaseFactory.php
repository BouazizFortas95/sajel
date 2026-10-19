<?php

namespace Database\Factories;

use App\Models\EducationPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationPhase>
 */
class EducationPhaseFactory extends Factory
{
    protected $model = EducationPhase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'المرحلة الابتدائية',
                'المرحلة المتوسطة',
                'المرحلة الثانوية',
            ]),
            'code' => 'phase-'.fake()->unique()->numberBetween(1, 99999),
            'settings' => [
                'weekly_periods' => 6,
            ],
        ];
    }
}
