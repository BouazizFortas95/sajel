<?php

namespace Database\Factories;

use App\Models\PedagogicalRemediation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedagogicalRemediation>
 */
class PedagogicalRemediationFactory extends Factory
{
    protected $model = PedagogicalRemediation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'schedule_id' => null,
            'reason' => fake()->sentence(4),
            'activities' => fake()->sentence(8),
            'session_date' => now()->toDateString(),
            'attended' => false,
            'follow_up' => null,
            'status' => 'planned',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'attended' => true,
        ]);
    }
}
