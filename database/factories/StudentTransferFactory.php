<?php

namespace Database\Factories;

use App\Enums\TransferReason;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentTransfer>
 */
class StudentTransferFactory extends Factory
{
    protected $model = StudentTransfer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(TransferType::cases());
        $schools = [
            'متوسطة الشهيد زبانة',
            'متوسطة الإخوة بوحجة',
            'متوسطة ابن خلدون',
            'متوسطة عبد الحميد بن باديس',
            'متوسطة 1 نوفمبر 1954',
            'متوسطة مفدي زكرياء',
        ];

        return [
            'student_id' => Student::factory(),
            'transfer_type' => $type,
            'academic_year' => '2026/2027',
            'transfer_date' => fake()->dateTimeBetween('2026-09-15', '2026-10-30')->format('Y-m-d'),
            'reference_number' => fake()->numerify('ر.ت/###/2026'),
            'reference_date' => fake()->dateTimeBetween('2026-09-01', '2026-09-14')->format('Y-m-d'),
            'other_school_name' => fake()->randomElement($schools),
            'other_directorate_name' => 'مديرية التربية لولاية الجزائر وسط',
            'reason' => fake()->randomElement(TransferReason::cases()),
            'status' => TransferStatus::Approved,
            'school_file_forwarded' => fake()->boolean(),
            'school_file_forwarded_at' => null,
            'clearance_attached' => fake()->boolean(),
            'notes' => null,
            'approved_by_id' => User::factory()->admin(),
        ];
    }

    public function inward(): static
    {
        return $this->state(fn (): array => [
            'transfer_type' => TransferType::Inward,
            'other_school_name' => 'متوسطة الإخوة عمراني — قسنطينة',
            'other_directorate_name' => 'مديرية التربية لولاية قسنطينة',
        ]);
    }

    public function outward(): static
    {
        return $this->state(fn (): array => [
            'transfer_type' => TransferType::Outward,
            'other_school_name' => 'متوسطة الأمير عبد القادر — وهران',
            'other_directorate_name' => 'مديرية التربية لولاية وهران',
            'clearance_attached' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => TransferStatus::Pending,
            'approved_by_id' => null,
        ]);
    }
}

