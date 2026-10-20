<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_the_teacher_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Teacher, $user->fresh()->role);
        $this->assertTrue($user->fresh()->isTeacher());
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertTrue($user->fresh()->canAccessPanel(config('filament.panel') ?? new Panel));
    }

    public function test_teacher_is_denied_the_user_resource(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)->get('/en/auth/users')->assertForbidden();
    }

    public function test_admin_can_access_the_user_resource(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin)->get('/en/auth/users')->assertOk();
    }

    public function test_teacher_cannot_open_another_teachers_daily_log(): void
    {
        $teacher = User::factory()->create();
        $otherLog = DailyLog::factory()->create(); // belongs to another teacher

        // The teacher-scoped query hides the record entirely (404, not 403),
        // so its existence is never revealed.
        $this->actingAs($teacher)
            ->get("/en/auth/daily-logs/{$otherLog->id}/edit")
            ->assertNotFound();
    }

    public function test_admin_can_open_any_daily_log(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $log = DailyLog::factory()->create(); // another teacher's log

        $this->actingAs($admin)
            ->get("/en/auth/daily-logs/{$log->id}/edit")
            ->assertOk();
    }

    public function test_teacher_schedule_list_shows_only_own_records(): void
    {
        $teacher = User::factory()->create();

        $ownClassroom = Classroom::factory()->create(['name' => 'قاعة الأمل']);
        $otherClassroom = Classroom::factory()->create(['name' => 'قاعة الغد']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $ownClassroom->id,
            'group_id' => null,
        ]);
        Schedule::factory()->create([
            'classroom_id' => $otherClassroom->id,
            'group_id' => null,
        ]);

        $response = $this->actingAs($teacher)->get('/en/auth/schedules');

        $response->assertOk();
        $response->assertSee('قاعة الأمل');
        $response->assertDontSee('قاعة الغد');
    }

    public function test_admin_schedule_list_shows_all_records(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $classroomA = Classroom::factory()->create(['name' => 'قاعة الشروق']);
        $classroomB = Classroom::factory()->create(['name' => 'قاعة النجاح']);

        Schedule::factory()->create(['classroom_id' => $classroomA->id, 'group_id' => null]);
        Schedule::factory()->create(['classroom_id' => $classroomB->id, 'group_id' => null]);

        $response = $this->actingAs($admin)->get('/en/auth/schedules');

        $response->assertOk();
        $response->assertSee('قاعة الشروق');
        $response->assertSee('قاعة النجاح');
    }

    public function test_policies_scope_teachers_and_bypass_for_admins(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $ownSchedule = Schedule::factory()->create(['user_id' => $teacher->id]);
        $otherSchedule = Schedule::factory()->create();

        // Schedules: ownership for teachers, full access for admins.
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownSchedule));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherSchedule));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherSchedule));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $otherSchedule));

        // Users: management is admin-only.
        $this->assertFalse(Gate::forUser($teacher)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherTeacher));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherTeacher));
    }
}
