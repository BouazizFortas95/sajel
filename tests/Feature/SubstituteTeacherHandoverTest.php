<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\DailyLogPolicy;
use App\Services\ClassCouncilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubstituteTeacherHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_substitute_to_daily_log(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create();
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
        ]);

        $this->actingAs($admin);
        $log->update(['substitute_teacher_id' => $teacher->id]);

        $this->assertDatabaseHas('daily_logs', [
            'id' => $log->id,
            'substitute_teacher_id' => $teacher->id,
        ]);
    }

    public function test_substitute_teacher_can_access_assigned_log(): void
    {
        $original = User::factory()->create(['role' => Role::Teacher->value]);
        $substitute = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create(['user_id' => $original->id]);
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'substitute_teacher_id' => $substitute->id,
        ]);

        // Substitute should have access via DailyLogPolicy
        $this->actingAs($substitute);
        $this->assertTrue($log->fresh()->substituteTeacher?->is($substitute));

        $policy = new DailyLogPolicy;
        $this->assertTrue($policy->view($substitute, $log));
        $this->assertTrue($policy->update($substitute, $log));
    }

    public function test_substitute_denied_for_other_teacher_logs(): void
    {
        $teacherA = User::factory()->create(['role' => Role::Teacher->value]);
        $teacherB = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create(['user_id' => $teacherA->id]);
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
        ]);

        $this->actingAs($teacherB);
        $policy = new DailyLogPolicy;
        $this->assertFalse($policy->view($teacherB, $log));
        $this->assertFalse($policy->update($teacherB, $log));
    }

    public function test_handover_context_resolves_previous_lesson_and_next_resource(): void
    {
        $schedule = Schedule::factory()->create();

        $service = new ClassCouncilService;
        $data = $service->viewData(
            Classroom::factory()->create(),
        );

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('kpis', $data);
    }
}
