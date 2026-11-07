<?php

namespace Database\Factories;

use App\Models\EducationalInstitution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationalInstitution>
 */
class EducationalInstitutionFactory extends Factory
{
    protected $model = EducationalInstitution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'متوسطة '.fake()->company(),
            'code' => fake()->unique()->numerify('##-####-####'),
            'stage' => 'middle',
            'directorate_name' => 'مديرية التربية لولاية '.fake()->city(),
            'wilaya' => fake()->city(),
            'municipality' => fake()->city(),
            'principal_name' => fake()->name(),
            'counselor_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'capacity' => fake()->numberBetween(350, 1100),
            'regime' => 'half_board',
            'status' => 'active',
            'is_current' => false,
            'academic_year' => '2026-2027',
            'notes' => fake()->sentence(),
        ];
    }
}
