<?php

namespace Database\Factories;

use App\Enums\StaffMovementType;
use App\Models\StaffMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffMovementFactory extends Factory
{
    protected $model = StaffMovement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'movement_type' => StaffMovementType::Transfer,
            'status' => 'approved',
            'effective_date' => '2026-09-01',
            'end_date' => null,
            'decision_number' => 'DEC/2026/894',
            'decision_date' => '2026-07-15',
            'source_institution' => 'متوسطة الشهيد زبانة',
            'target_institution' => 'متوسطة الأمير عبد القادر',
            'reason' => 'حركة التنقل السنوية بناءً على رغبة المعني.',
        ];
    }
}
