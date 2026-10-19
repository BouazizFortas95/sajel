<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'الرياضيات',
                'اللغة العربية',
                'اللغة الفرنسية',
                'اللغة الإنجليزية',
                'التربية الإسلامية',
                'العلوم الفيزيائية والتكنولوجيا',
                'علوم الطبيعة والحياة',
                'التاريخ والجغرافيا',
            ]),
            'color' => fake()->hexColor(),
        ];
    }
}
