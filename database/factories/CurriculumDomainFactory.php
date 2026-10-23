<?php

namespace Database\Factories;

use App\Models\CurriculumDomain;
use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumDomain>
 */
class CurriculumDomainFactory extends Factory
{
    protected $model = CurriculumDomain::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'level_id' => Level::factory(),
            'name' => 'ميدان '.fake()->unique()->numberBetween(1, 999999),
            'code' => null,
            'description' => null,
            'sort_order' => 0,
        ];
    }
}
