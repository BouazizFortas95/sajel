<?php

namespace Database\Factories;

use App\Models\ActiveLearningStrategy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActiveLearningStrategyFactory extends Factory
{
    protected $model = ActiveLearningStrategy::class;

    public function definition(): array
    {
        $names = [
            'Think-Pair-Share',
            'Exit Ticket',
            'Jigsaw',
            'Round Robin',
            'Gallery Walk',
            'Concept Mapping',
            'Role Play',
            'Peer Instruction',
        ];
        $name = $this->faker->randomElement($names);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'icon' => 'heroicon-o-light-bulb',
            'color' => $this->faker->randomElement(['info', 'primary', 'warning', 'success']),
            'tags' => $this->faker->randomElements(['collaborative', 'individual', 'discussion', 'assessment'], 2),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 20),
        ];
    }
}
