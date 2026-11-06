<?php

namespace Database\Factories;

use App\Models\CanteenWeeklyMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanteenWeeklyMenu>
 */
class CanteenWeeklyMenuFactory extends Factory
{
    protected $model = CanteenWeeklyMenu::class;

    public function definition(): array
    {
        return [
            'academic_year' => '2026/2027',
            'week_number' => fake()->unique()->numberBetween(1, 40),
            'week_start_date' => fake()->date(),
            'sunday_menu' => 'شوربة خضار + لحم عجل مفروم + بطاطا كوشة + سلطة + تفاح + خبز',
            'monday_menu' => 'عدس بني بالخضار + بيض مسلوق + سلطة جزر مبشور + برتقال + خبز',
            'tuesday_menu' => 'كسكسي بالخضار ومرق الدجاج + سلطة خضراء بالخيار + ياغورت معقم + خبز',
            'wednesday_menu' => 'لوبيا بيضاء بالصلصة ولحم البقر + سلطة شمندر + فواكه موسمية + خبز',
            'thursday_menu' => 'أرز مفور بالخضار وصلصة التونة + جبن أبيض + موزة + خبز',
            'nutrition_calories_approx' => 850,
            'is_approved_by_doctor' => true,
            'approved_by_principal' => true,
        ];
    }
}
