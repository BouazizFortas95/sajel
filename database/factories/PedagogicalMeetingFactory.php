<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\PedagogicalMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedagogicalMeetingFactory extends Factory
{
    protected $model = PedagogicalMeeting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => Role::Teacher->value]),
            'title' => $this->faker->randomElement([
                'ندوة تكوينية مع المفتش',
                'اجتماع تنسيقي للمادة',
                'مجلس القسم',
                'مجلس التعليم',
            ]),
            'meeting_type' => $this->faker->randomElement([
                'inspector_seminar',
                'coordination_meeting',
                'class_council',
                'pedagogical_council',
            ]),
            'date' => $this->faker->dateTimeBetween('-6 months', '+1 month')->format('Y-m-d'),
            'location' => $this->faker->optional(0.7)->word(),
            'supervisor_name' => $this->faker->optional(0.6)->name(),
            'agenda' => $this->faker->paragraph(2),
            'recommendations' => $this->faker->optional(0.7)->paragraph(1),
        ];
    }
}
