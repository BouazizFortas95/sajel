<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Subject;
use App\Models\Textbook;
use Illuminate\Database\Eloquent\Factories\Factory;

class TextbookFactory extends Factory
{
    protected $model = Textbook::class;

    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'subject_id' => Subject::factory(),
            'title' => fake()->randomElement([
                'كتاب اللغة العربية وآدابها',
                'كتاب الرياضيات للتعليم المتوسط',
                'كتاب العلوم الفيزيائية والتكنولوجية',
                'كتاب علوم الطبيعة والحياة',
                'كتاب التاريخ والجغرافيا',
                'كتاب اللغة الفرنسية',
                'كتاب اللغة الإنجليزية',
                'كتاب التربية الإسلامية',
                'كتاب التربية المدنية',
                'كتاب الإعلام الآلي',
            ]),
            'isbn' => '978-9961-0-'.fake()->numerify('####-#'),
            'unit_price' => fake()->randomFloat(2, 180.00, 320.00),
            'total_received' => fake()->numberBetween(60, 200),
            'storage_location' => 'مخزن الكتب المدرسية — الجناح ب',
            'is_active' => true,
        ];
    }
}

