<?php

namespace Database\Factories;

use App\Models\EducationPhase;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'education_phase_id' => EducationPhase::factory(),
            'name' => 'السنة الأولى '.fake()->randomElement(['متوسطة', 'إعدادية']),
        ];
    }
}
