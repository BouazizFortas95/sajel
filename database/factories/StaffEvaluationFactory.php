<?php

namespace Database\Factories;

use App\Enums\StaffEvaluationAppreciation;
use App\Models\StaffEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffEvaluationFactory extends Factory
{
    protected $model = StaffEvaluation::class;

    public function definition(): array
    {
        $p = 4.5;
        $pf = 4.0;
        $c = 4.5;
        $i = 4.0;
        $admin = $p + $pf + $c + $i;

        return [
            'user_id' => User::factory(),
            'academic_year' => '2025/2026',
            'evaluation_year' => 2026,
            'punctuality_score' => $p,
            'performance_score' => $pf,
            'conduct_score' => $c,
            'initiative_score' => $i,
            'administrative_score' => $admin,
            'pedagogical_score' => 16.50,
            'last_inspection_date' => '2025-11-20',
            'appreciation' => StaffEvaluationAppreciation::VeryGood,
            'evaluator_id' => null,
            'evaluator_notes' => 'أستاذ منضبط ومواظب ذو مردود تربوي ممتاز.',
            'evaluated_at' => '2026-06-15',
            'staff_signature_status' => 'acknowledged',
            'staff_contestation_notes' => null,
        ];
    }
}

