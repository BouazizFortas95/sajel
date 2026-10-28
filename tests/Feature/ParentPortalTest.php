<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signed_url_renders_portal(): void
    {
        $student = Student::factory()->create();
        $url = URL::temporarySignedRoute('parent.student-portal', now()->addDays(90), [
            'locale' => 'ar',
            'student' => $student,
        ]);

        $this->get($url)->assertOk();
    }

    public function test_expired_url_rejected(): void
    {
        $student = Student::factory()->create();
        $url = URL::temporarySignedRoute('parent.student-portal', now()->subDays(1), [
            'locale' => 'ar', 'student' => $student,
        ]);
        $this->get($url)->assertStatus(403);
    }

    public function test_teacher_generates_portal_for_own_student(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();

        $this->get(route('parent.student-portal', ['locale' => 'ar', 'student' => $student]))
            ->assertStatus(403);
    }

    public function test_portal_recent_logs_do_not_leak_other_groups_in_the_same_classroom(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'مجموعة الطالب',
        ]);
        $otherGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'مجموعة أخرى',
        ]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $this->completedLog($classroom, $group, 'درس مجموعتي');
        $this->completedLog($classroom, null, 'درس القسم كاملاً');
        $this->completedLog($classroom, $otherGroup, 'درس مجموعة أخرى');

        $url = URL::temporarySignedRoute('parent.student-portal', now()->addDays(90), [
            'locale' => 'ar',
            'student' => $student,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('درس مجموعتي')
            ->assertSee('درس القسم كاملاً')
            ->assertDontSee('درس مجموعة أخرى');
    }

    private function completedLog(Classroom $classroom, ?Group $group, string $title): DailyLog
    {
        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => $group?->id,
            'subject_id' => Subject::factory()->create()->id,
        ]);

        return DailyLog::factory()->completed()->create([
            'schedule_id' => $schedule->id,
            'title' => $title,
            'date' => today(),
        ]);
    }
}
