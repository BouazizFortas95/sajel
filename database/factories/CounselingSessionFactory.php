<?php

namespace Database\Factories;

use App\Enums\CounselingSessionReason;
use App\Enums\CounselingSessionStatus;
use App\Models\CounselingSession;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CounselingSessionFactory extends Factory
{
    protected $model = CounselingSession::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'counselor_id' => User::factory(),
            'academic_year' => SchoolSetting::currentAcademicYear(),
            'session_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'reason_category' => fake()->randomElement(CounselingSessionReason::cases()),
            'interviewee_type' => fake()->randomElement(['student_only', 'guardian_only', 'student_and_guardian']),
            'observations' => fake()->paragraph(),
            'action_plan' => fake()->sentence(),
            'follow_up_date' => fake()->optional(0.6)->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'status' => fake()->randomElement(CounselingSessionStatus::cases()),
            'is_confidential' => true,
        ];
    }
}
