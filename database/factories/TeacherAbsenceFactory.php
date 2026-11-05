<?php

namespace Database\Factories;

use App\Enums\TeacherAbsenceReason;
use App\Enums\TeacherAbsenceStatus;
use App\Models\TeacherAbsence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherAbsenceFactory extends Factory
{
    protected $model = TeacherAbsence::class;

    public function definition(): array
    {
        $startDate = now()->addDays(fake()->numberBetween(-15, 10))->startOfDay();
        $days = fake()->numberBetween(1, 4);
        $endDate = $startDate->copy()->addDays($days - 1);

        return [
            'teacher_id' => User::factory()->teacher(),
            'starts_at' => $startDate->toDateString(),
            'ends_at' => $endDate->toDateString(),
            'reason' => fake()->randomElement(TeacherAbsenceReason::cases()),
            'status' => TeacherAbsenceStatus::Pending,
            'reference_number' => 'CERT-'.fake()->numerify('2026/####'),
            'notes' => fake()->randomElement([
                'شهادة طبية مسلمة من طبيب الصحة العمومية.',
                'استدعاء رسمي للمشاركة في ملتقى تكويني بيداغوجي.',
                'إذن مسبق بالغياب لظرف عائلي طارئ.',
                'مهمة تربوية رسمية بمقر مديرية التربية.',
            ]),
            'recorded_by_id' => User::factory()->admin(),
        ];
    }

    public function sick(): static
    {
        return $this->state(fn (): array => [
            'reason' => TeacherAbsenceReason::SickLeave,
            'reference_number' => 'MED-'.fake()->numerify('2026/###'),
            'notes' => 'عطلة مرضية مبررة بشهادة طبية.',
        ]);
    }

    public function covered(): static
    {
        return $this->state(fn (): array => [
            'status' => TeacherAbsenceStatus::Covered,
        ]);
    }
}

