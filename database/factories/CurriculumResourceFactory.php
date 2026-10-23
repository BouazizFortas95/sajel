<?php

namespace Database\Factories;

use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumResource>
 */
class CurriculumResourceFactory extends Factory
{
    protected $model = CurriculumResource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_sequence_id' => CurriculumSequence::factory(),
            'name' => 'مورد '.fake()->unique()->numberBetween(1, 999999),
            'code' => null,
            'description' => null,
            'sort_order' => 0,
        ];
    }
}
