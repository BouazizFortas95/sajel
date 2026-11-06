<?php

namespace Database\Factories;

use App\Enums\CanteenSheetStatus;
use App\Models\CanteenDailySheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanteenDailySheet>
 */
class CanteenDailySheetFactory extends Factory
{
    protected $model = CanteenDailySheet::class;

    public function definition(): array
    {
        $halfBoard = fake()->numberBetween(80, 200);
        $boarders = 0;
        $staff = fake()->numberBetween(5, 12);
        $workers = fake()->numberBetween(2, 6);
        $total = $halfBoard + $boarders + $staff + $workers;
        $costPerMeal = 120.00;
        $totalCost = round($total * $costPerMeal, 2);

        return [
            'academic_year' => '2026/2027',
            'sheet_date' => fake()->unique()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'reference_number' => 'FJC-2026-'.fake()->unique()->numerify('####'),
            'half_board_students_present' => $halfBoard,
            'boarder_students_present' => $boarders,
            'staff_beneficiaries_count' => $staff,
            'service_workers_count' => $workers,
            'total_beneficiaries_count' => $total,
            'menu_description' => 'عدس بالخضار واللحم البقري + سلطة جزر مبشور + برتقال + خبز طازج',
            'total_cost' => $totalCost,
            'cost_per_meal' => $costPerMeal,
            'storekeeper_name' => 'أحمد بلخير',
            'kitchen_hygiene_status' => 'مطابق للشروط والنظافة ممتازة',
            'dietary_allergy_alerts' => null,
            'notes' => 'سير وجبة الغداء في ظروف عادية وهادئة ومحترمة لمعايير السلامة الغذائية.',
            'status' => CanteenSheetStatus::Draft,
            'certified_at' => null,
            'certified_by_id' => null,
        ];
    }

    public function certified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CanteenSheetStatus::Certified,
            'certified_at' => now(),
        ]);
    }
}
