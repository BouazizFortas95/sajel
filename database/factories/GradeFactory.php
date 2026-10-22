<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => null,
            'remarks' => null,
        ];
    }

    public function graded(?float $score = null): static
    {
        return $this->state(fn (): array => [
            'score' => $score ?? fake()->randomFloat(2, 0, 20),
        ]);
    }
}
