<?php

namespace Database\Factories;

use App\Enums\AdvancementRhythm;
use App\Models\CareerAdvancement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareerAdvancementFactory extends Factory
{
    protected $model = CareerAdvancement::class;

    public function definition(): array
    {
        $stepDate = Carbon::parse('2023-01-01');

        return [
            'user_id' => User::factory(),
            'current_step' => 3,
            'next_step' => 4,
            'current_step_date' => $stepDate,
            'eligible_at_minimum' => $stepDate->copy()->addMonths(30),
            'eligible_at_medium' => $stepDate->copy()->addMonths(36),
            'eligible_at_maximum' => $stepDate->copy()->addMonths(42),
            'advancement_rhythm' => AdvancementRhythm::Medium,
            'effective_date' => $stepDate->copy()->addMonths(36),
            'financial_impact_date' => $stepDate->copy()->addMonths(36),
            'status' => 'draft',
            'commission_reference' => null,
            'notes' => 'مستوفٍ للشروط القانونية للترقية في الدرجة.',
        ];
    }
}
