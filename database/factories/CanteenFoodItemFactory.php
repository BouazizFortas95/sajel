<?php

namespace Database\Factories;

use App\Enums\FoodItemCategory;
use App\Models\CanteenFoodItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanteenFoodItem>
 */
class CanteenFoodItemFactory extends Factory
{
    protected $model = CanteenFoodItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['أرز بسمتي رفيع', 'عدس بني ممتاز', 'معكرونة ممتازة', 'زيت نباتي للمائدة', 'لحم بقري طازج', 'دجاج طازج مجزر', 'جبن أبيض طري', 'تفاح محلي طازج']),
            'category' => fake()->randomElement(FoodItemCategory::cases()),
            'unit' => fake()->randomElement(['كلغ', 'لتر', 'علبة', 'قطعة']),
            'unit_price' => fake()->randomFloat(2, 50, 1500),
            'current_stock' => fake()->randomFloat(2, 20, 500),
            'safety_threshold' => 15.00,
            'is_active' => true,
        ];
    }
}
