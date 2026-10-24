<?php

namespace Database\Factories;

use App\Models\SchoolHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolHolidayFactory extends Factory
{
    protected $model = SchoolHoliday::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'عطلة الخريف',
                'عيد اندلاع الثورة التحريرية',
                'عطلة الشتاء',
                'عيد الفطر',
                'عيد الأضحى',
                'عطلة ربيعية',
                'استثناء من الدراسة',
            ]),
            'start_date' => $this->faker->dateTimeBetween('-2 years', '+1 year')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('+1 day', '+10 days')->format('Y-m-d'),
            'type' => $this->faker->randomElement(['national', 'religious', 'seasonal', 'exceptional']),
            'notes' => $this->faker->optional(0.5)->sentence(),
        ];
    }
}