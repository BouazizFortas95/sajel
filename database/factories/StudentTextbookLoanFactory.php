<?php

namespace Database\Factories;

use App\Enums\TextbookDistributionType;
use App\Enums\TextbookReturnCondition;
use App\Models\Student;
use App\Models\StudentTextbookLoan;
use App\Models\Textbook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentTextbookLoanFactory extends Factory
{
    protected $model = StudentTextbookLoan::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'textbook_id' => Textbook::factory(),
            'academic_year' => '2026/2027',
            'distribution_type' => fake()->randomElement(TextbookDistributionType::cases()),
            'distributed_at' => '2026-09-20',
            'returned_at' => null,
            'return_condition' => null,
            'clearance_granted' => false,
            'notes' => null,
            'distributed_by_id' => User::factory()->admin(),
        ];
    }

    public function returned(TextbookReturnCondition $condition = TextbookReturnCondition::Good): static
    {
        return $this->state(fn (): array => [
            'returned_at' => now()->toDateString(),
            'return_condition' => $condition,
            'clearance_granted' => $condition === TextbookReturnCondition::Good,
        ]);
    }

    public function solidarity(): static
    {
        return $this->state(fn (): array => [
            'distribution_type' => TextbookDistributionType::Solidarity,
            'notes' => 'مستفيد من حصة التضامن الوطني (منحة 5000 دج).',
        ]);
    }
}
