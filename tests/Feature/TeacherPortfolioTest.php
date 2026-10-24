<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_own_portfolio(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $this->actingAs($teacher)
            ->get(route('print.teacher-portfolio', ['locale' => 'ar', 'user' => $teacher]))
            ->assertOk();
    }

    public function test_teacher_denied_other_teacher_portfolio(): void
    {
        $teacherA = User::factory()->create(['role' => Role::Teacher->value]);
        $teacherB = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacherB)
            ->get(route('print.teacher-portfolio', ['locale' => 'ar', 'user' => $teacherA]))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_teacher_portfolio(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($admin)
            ->get(route('print.teacher-portfolio', ['locale' => 'ar', 'user' => $teacher]))
            ->assertOk();
    }
}
