<?php

namespace Database\Factories;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyLog>
 */
class DailyLogFactory extends Factory
{
    protected $model = DailyLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'date' => now()->toDateString(),
            'title' => 'درس: '.fake()->randomElement(['جمع الكسور', 'الأعداد النسبية', 'المعادلات']),
            'content_data' => [
                [
                    'type' => 'general_lesson',
                    'data' => [
                        'title' => fake()->sentence(4),
                        'objectives' => fake()->sentence(8),
                        'content' => fake()->paragraph(),
                    ],
                ],
            ],
            'actual_content' => null,
            'homework' => fake()->optional()->sentence(6),
            'observations' => null,
            'status' => DailyLogStatus::Planned->value,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attribute) => [
            'status' => DailyLogStatus::Completed->value,
            'actual_content' => fake()->paragraph(),
        ]);
    }
}
