<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_log_id' => DailyLog::factory(),
            'student_id' => Student::factory(),
            'status' => AttendanceStatus::Present->value,
            'remarks' => null,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (): array => [
            'status' => AttendanceStatus::Absent->value,
        ]);
    }
}
