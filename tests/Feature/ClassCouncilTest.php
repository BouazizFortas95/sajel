<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ClassCouncilHub;
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
use Livewire\Livewire;
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

    public function test_roster_count_and_both_schedule_scopes_feed_averages_and_recommendations(): void
    {
        app()->setLocale('ar');

        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $groupA = Group::factory()->create(['classroom_id' => $classroom->id, 'name' => 'الفوج أ']);
        $groupB = Group::factory()->create(['classroom_id' => $classroom->id, 'name' => 'الفوج ب']);
        $math = Subject::factory()->create(['name' => 'الرياضيات']);
        $science = Subject::factory()->create(['name' => 'العلوم']);

        $classroomMathSchedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => null,
            'subject_id' => $math->id,
            'user_id' => $admin->id,
        ]);
        $groupMathSchedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $groupA->id,
            'subject_id' => $math->id,
            'user_id' => $admin->id,
        ]);
        $groupScienceSchedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $groupB->id,
            'subject_id' => $science->id,
            'user_id' => $admin->id,
        ]);

        $gradedFromClassroom = Student::factory()->create(['group_id' => $groupA->id]);
        $gradedFromGroup = Student::factory()->create(['group_id' => $groupB->id]);
        $ungraded = Student::factory()->create(['group_id' => $groupA->id]);

        $this->recordGrade($classroomMathSchedule, $gradedFromClassroom, 12);
        $this->recordGrade($groupMathSchedule, $gradedFromClassroom, 18);
        $this->recordGrade($groupScienceSchedule, $gradedFromGroup, 16);

        $service = app(ClassCouncilService::class);
        $data = $service->viewData($classroom);
        $deliberations = collect($data['studentDeliberations'])->keyBy(fn (array $row): int => $row['student']->id);

        $this->assertCount(3, $data['studentDeliberations']);
        $this->assertSame(3, $data['kpis']['total_students']);
        $this->assertSame(2, $data['kpis']['graded_students']);
        $this->assertSame(1, $data['kpis']['pending_students']);
        $this->assertSame(15.5, $data['kpis']['class_general_average']);
        $this->assertSame(100.0, $data['kpis']['pass_rate']);

        $classroomGradeRow = $deliberations->get($gradedFromClassroom->id);
        $this->assertSame(15.0, $classroomGradeRow['generalAverage']);
        $this->assertSame(
            15.0,
            collect($classroomGradeRow['perSubjectAverages'])->firstWhere('subject_id', $math->id)['average'],
        );
        $this->assertSame('تشجيع', $classroomGradeRow['recommendation']);
        $this->assertSame('encouragement', $classroomGradeRow['recommendation_key']);

        $groupGradeRow = $deliberations->get($gradedFromGroup->id);
        $this->assertSame(16.0, $groupGradeRow['generalAverage']);
        $this->assertSame('تهنئة (امتياز)', $groupGradeRow['recommendation']);
        $this->assertSame('success', $groupGradeRow['recommendation_color']);

        $pendingRow = $deliberations->get($ungraded->id);
        $this->assertNull($pendingRow['generalAverage']);
        $this->assertSame('بانتظار رصد العلامات', $pendingRow['recommendation']);
        $this->assertSame('pending', $pendingRow['recommendation_key']);
        $this->assertSame('gray', $pendingRow['recommendation_color']);

        $this->assertSame(
            ['excellence' => 1, 'encouragement' => 1, 'honor_roll' => 0, 'acceptable' => 0, 'warning' => 0, 'pending' => 1],
            $data['honorDistribution'],
        );

        $html = $service->renderHtml($classroom);
        $this->assertStringNotContainsString('tbd', $html);
        $this->assertStringContainsString('بانتظار رصد العلامات', $html);
        $this->assertStringContainsString('تهنئة (امتياز)', $html);

        $this->actingAs($admin)
            ->get(route('print.class-council', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertOk()
            ->assertDontSee('tbd')
            ->assertSee('مجلس القسم — محضر المداولات');

        Livewire::actingAs($admin)
            ->test(ClassCouncilHub::class)
            ->assertSuccessful()
            ->assertSee('مداولات القسم')
            ->assertSee('اختر القسم')
            ->assertSee('عدد التلاميذ')
            ->assertSee('بانتظار رصد العلامات')
            ->assertDontSee('tbd');
    }

    private function recordGrade(Schedule $schedule, Student $student, float $score): void
    {
        $assessment = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => 'تقييم',
            'max_score' => 20,
        ]);

        Grade::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => $score,
        ]);
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

    public function test_teacher_class_council_hub_uses_classroom_groups_for_schedule_scope(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'user_id' => $teacher->id,
        ]);

        $this->actingAs($teacher);
        $page = app(ClassCouncilHub::class);
        $page->mount();

        $this->assertSame($classroom->id, $page->classroomId);
    }
}
