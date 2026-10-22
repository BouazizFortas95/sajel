<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'title' => fake()->randomElement([
                'الفرض الأول',
                'الاختبار القصير',
                'الامتحان الفصلي',
                'التقويم المستمر',
            ]),
            'type' => AssessmentType::Quiz->value,
            'date' => fake()->dateTimeBetween('-2 months', '+1 month')->format('Y-m-d'),
            'max_score' => 20,
        ];
    }
}
