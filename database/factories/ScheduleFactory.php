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
     * Valid one-hour lesson slots for the Algerian school week.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const LESSON_SLOTS = [
        ['08:00', '09:00'],
        ['09:00', '10:00'],
        ['10:00', '11:00'],
        ['11:00', '12:00'],
        ['14:00', '15:00'],
        ['15:00', '16:00'],
        ['16:00', '17:00'],
    ];

    /**
     * @var list<string>
     */
    public const SCHOOL_DAYS = [
        DayOfWeek::Sunday->value,
        DayOfWeek::Monday->value,
        DayOfWeek::Tuesday->value,
        DayOfWeek::Wednesday->value,
        DayOfWeek::Thursday->value,
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$startTime, $endTime] = fake()->randomElement(self::LESSON_SLOTS);

        return [
            'user_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'group_id' => null,
            'subject_id' => Subject::factory(),
            'day_of_week' => fake()->randomElement(self::SCHOOL_DAYS),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
