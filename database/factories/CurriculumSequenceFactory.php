<?php

namespace Database\Factories;

use App\Models\CurriculumDomain;
use App\Models\CurriculumSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumSequence>
 */
class CurriculumSequenceFactory extends Factory
{
    protected $model = CurriculumSequence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_domain_id' => CurriculumDomain::factory(),
            'name' => 'مقطع '.fake()->unique()->numberBetween(1, 999999),
            'code' => null,
            'description' => null,
            'sort_order' => 0,
        ];
    }
}
