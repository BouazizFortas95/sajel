<?php

namespace Database\Factories;

use App\Models\BehaviorObservation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BehaviorObservation>
 */
class BehaviorObservationFactory extends Factory
{
    protected $model = BehaviorObservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'schedule_id' => null,
            'category' => fake()->randomElement(['positive', 'negative', 'disciplinary', 'observation']),
            'notes' => fake()->sentence(),
            'date' => now()->toDateString(),
            'observed_by' => null,
            'is_followed_up' => false,
            'follow_up_notes' => null,
        ];
    }

    public function followedUp(): static
    {
        return $this->state(fn (): array => [
            'is_followed_up' => true,
        ]);
    }
}
