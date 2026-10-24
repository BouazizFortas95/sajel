<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassCouncilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassCouncilTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_computes_averages_and_recommendations(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'user_id' => User::factory()->create(['role' => Role::Admin->value])->id,
        ]);

        $student = Student::factory()->create(['group_id' => Group::factory()->create(['classroom_id' => $classroom->id])->id]);

        $assessment = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => 'Test',
            'max_score' => 20,
        ]);

        Grade::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 18,
        ]);

        app()->setLocale('ar');
        $service = new ClassCouncilService;
        $data = $service->viewData($classroom);

        $del = collect($data['studentDeliberations'])->first(fn ($d) => $d['student']->id === $student->id);
        $this->assertNotNull($del);
        $this->assertNotNull($del['generalAverage']);
        $this->assertGreaterThan(0, $del['generalAverage']);
        $this->assertTrue($del['passed']);
        $this->assertNotEmpty($del['recommendation']);
    }

    public function test_admin_can_view_and_print_class_council(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)
            ->get(route('print.class-council', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertOk();
    }

    public function test_teacher_can_only_access_scheduled_classrooms(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $scheduledClassroom = Classroom::factory()->create();
        $unrelatedClassroom = Classroom::factory()->create();

        $group = Group::factory()->create(['classroom_id' => $scheduledClassroom->id]);
        Schedule::factory()->create([
            'classroom_id' => $scheduledClassroom->id,
            'group_id' => $group->id,
            'user_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('print.class-council', ['locale' => 'ar', 'classroom' => $scheduledClassroom]))
            ->assertOk();

        $this->actingAs($teacher)
            ->get(route('print.class-council', ['locale' => 'ar', 'classroom' => $unrelatedClassroom]))
            ->assertForbidden();
    }
}
