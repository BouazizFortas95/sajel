<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->randomElement([
                'أمين',
                'ياسمين',
                'محمد',
                'ليلى',
                'عبد الرحمن',
                'سارة',
                'إلياس',
                'نور',
            ]),
            'last_name' => fake()->randomElement([
                'بن علي',
                'مرزوقي',
                'بوعلام',
                'حمداني',
                'زروقي',
                'شريف',
                'بلقاسم',
                'عمراني',
            ]),
            'registration_number' => fake()->unique()->numerify('2026-####'),
            'date_of_birth' => fake()->dateTimeBetween('-16 years', '-11 years')->format('Y-m-d'),
            'group_id' => Group::factory(),
        ];
    }
}
