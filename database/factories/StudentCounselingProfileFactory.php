<?php

namespace Database\Factories;

use App\Enums\FamilyStatus;
use App\Models\Student;
use App\Models\StudentCounselingProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentCounselingProfileFactory extends Factory
{
    protected $model = StudentCounselingProfile::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'family_status' => fake()->randomElement(FamilyStatus::cases()),
            'siblings_count' => fake()->numberBetween(0, 7),
            'birth_order' => fake()->numberBetween(1, 4),
            'father_job' => fake()->optional(0.8)->jobTitle(),
            'mother_job' => fake()->optional(0.5)->jobTitle(),
            'social_grant' => fake()->boolean(25),
            'blood_type' => fake()->randomElement(['O+', 'A+', 'B+', 'AB+', 'O-', 'A-']),
            'chronic_conditions' => fake()->optional(0.2)->passthrough(['حساسية تنفسية']),
            'has_physical_disability' => false,
            'pe_exemption' => fake()->boolean(10),
            'health_notes' => null,
            'primary_school_name' => 'مدرسة الشهداء الابتدائية',
            'primary_graduation_year' => '2022',
            'repeated_years_count' => fake()->randomElement([0, 0, 0, 1]),
            'repeated_levels' => null,
            'learning_difficulties' => fake()->optional(0.15)->passthrough(['صعوبات في الحساب']),
            'behavior_tendencies' => ['هادئ ومنتبه'],
            'interests_and_talents' => ['القراءة', 'الرسم والرياضة'],
            'career_aspirations' => 'مهندس إعلام آلي',
            'counselor_general_notes' => 'تلميذ مجتهد ومواظب، يحتاج لمتابعة دورية في مادة الرياضيات.',
            'is_confidential' => true,
        ];
    }
}
