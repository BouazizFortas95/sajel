<?php

namespace Database\Factories;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Models\ClubMembership;
use App\Models\SchoolClub;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClubMembershipFactory extends Factory
{
    protected $model = ClubMembership::class;

    public function definition(): array
    {
        return [
            'school_club_id' => SchoolClub::factory(),
            'student_id' => Student::factory(),
            'role' => ClubMemberRole::ActiveMember,
            'joined_at' => now()->subDays(fake()->numberBetween(10, 60))->toDateString(),
            'status' => ClubMemberStatus::Active,
            'notes' => 'تلميذ شغوف بالأنشطة ولديه مهارات تواصل متميزة.',
        ];
    }

    public function president(): static
    {
        return $this->state(fn (): array => [
            'role' => ClubMemberRole::President,
        ]);
    }

    public function secretary(): static
    {
        return $this->state(fn (): array => [
            'role' => ClubMemberRole::Secretary,
        ]);
    }
}

