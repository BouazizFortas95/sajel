<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'group_id' => null,
            'subject_id' => Subject::factory(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases())->value,
            'start_time' => fake()->randomElement(['08:00', '09:00', '10:00', '11:00']),
            'end_time' => fake()->randomElement(['09:00', '10:00', '11:00', '12:00']),
        ];
    }
}
