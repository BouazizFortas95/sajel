<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySchedulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_the_my_schedule_page(): void
    {
        $teacher = User::factory()->create();

        $today = DayOfWeek::cases()[today()->dayOfWeek];

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => Subject::factory()->create(['name' => 'الرياضيات'])->id,
            'day_of_week' => $today->value,
        ]);

        $response = $this->actingAs($teacher)->get('/en/auth/my-schedule');

        $response->assertOk();
        $response->assertSee('My weekly schedule');
        $response->assertSee('Day view');
        $response->assertSee('الرياضيات');
    }

    public function test_teacher_can_switch_to_the_week_view(): void
    {
        $teacher = User::factory()->create();

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => Subject::factory()->create(['name' => 'الفيزياء'])->id,
            'day_of_week' => DayOfWeek::Wednesday->value,
        ]);

        $response = $this->actingAs($teacher)->get('/en/auth/my-schedule?view=week');

        $response->assertOk();
        $response->assertSee('Weekly lesson schedule');
        $response->assertSee('Week view');
        $response->assertSee('الفيزياء');
    }

    public function test_admin_cannot_access_the_teacher_schedule_page(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin)
            ->get('/en/auth/my-schedule')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_the_panel_login(): void
    {
        $this->get('/en/auth/my-schedule')
            ->assertRedirect('/en/auth/login');
    }
}
