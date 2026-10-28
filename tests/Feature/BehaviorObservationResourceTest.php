<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BehaviorObservation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehaviorObservationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_behavior_observations_list_with_follow_up_badges(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        BehaviorObservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'category' => 'positive',
            'notes' => 'مشاركة مميزة خلال الحصة.',
            'date' => today(),
            'is_followed_up' => true,
        ]);

        $this->actingAs($admin)
            ->get('/en/auth/behavior-observations')
            ->assertOk()
            ->assertSee(__('نعم'));
    }
}
