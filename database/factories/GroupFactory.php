<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'name' => 'المجموعة '.fake()->randomElement(['أ', 'ب', 'ج', 'د', 'هـ']),
        ];
    }
}
