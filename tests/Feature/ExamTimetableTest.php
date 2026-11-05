<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\DayOfWeek;
use App\Filament\Pages\ExamTimetableHub;
use App\Filament\Resources\AssessmentResource\Pages\CreateAssessment;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamTimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_timetable_hub_page_accessible_to_both_admin_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(ExamTimetableHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('رزنامة الفروض والامتحانات');

        $this->actingAs($teacher)
            ->get(ExamTimetableHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('رزنامة الفروض والامتحانات');

        auth()->logout();
        $this->get(ExamTimetableHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_exam_conflict_service_detects_max_2_tests_per_day_violation(): void
    {
        $service = app(ExamConflictService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();

        $mathSubject = Subject::factory()->create(['name' => 'الرياضيات']);
        $physicsSubject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);
        $arabicSubject = Subject::factory()->create(['name' => 'اللغة العربية']);

        $mathSchedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $mathSubject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $physicsSchedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $physicsSubject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $arabicSchedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $arabicSubject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $date = '2026-11-15';

        // Schedule 2 midterms on the same day for this classroom
        Assessment::factory()->create([
            'schedule_id' => $mathSchedule->id,
            'type' => AssessmentType::Midterm,
            'date' => $date,
            'title' => 'فرض الرياضيات',
        ]);

        Assessment::factory()->create([
            'schedule_id' => $physicsSchedule->id,
            'type' => AssessmentType::Midterm,
            'date' => $date,
            'title' => 'فرض الفيزياء',
        ]);

        // Attempting to schedule a 3rd Midterm should trigger daily_limit conflict
        $conflictsFor3rd = $service->checkConflicts($arabicSchedule, $date, AssessmentType::Midterm);
        $this->assertNotEmpty($conflictsFor3rd);
        $this->assertSame('daily_limit', $conflictsFor3rd[0]['type']);
        $this->assertSame('danger', $conflictsFor3rd[0]['severity']);

        // A Quiz (اختبار قصير) is NOT restricted by the daily major limit
        $conflictsForQuiz = $service->checkConflicts($arabicSchedule, $date, AssessmentType::Quiz);
        $this->assertEmpty($conflictsForQuiz);
    }

    public function test_exam_conflict_service_detects_time_overlap_collision_for_same_classroom(): void
    {
        $service = app(ExamConflictService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();

        $schedule1 = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $schedule2 = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]);

        $date = '2026-11-16';

        Assessment::factory()->create([
            'schedule_id' => $schedule1->id,
            'type' => AssessmentType::Quiz,
            'date' => $date,
            'title' => 'تقييم الحصة الأولى',
        ]);

        $conflicts = $service->checkConflicts($schedule2, $date, AssessmentType::Quiz);

        $timeOverlap = collect($conflicts)->firstWhere('type', 'time_overlap');
        $this->assertNotNull($timeOverlap);
        $this->assertSame('danger', $timeOverlap['severity']);
    }

    public function test_exam_conflict_service_detects_teacher_double_booking(): void
    {
        $service = app(ExamConflictService::class);

        $teacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroomA = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $classroomB = Classroom::factory()->for($level)->create(['name' => '1 م 2']);

        $scheduleA = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroomA->id,
            'day_of_week' => DayOfWeek::Tuesday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $scheduleB = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroomB->id,
            'day_of_week' => DayOfWeek::Tuesday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $date = '2026-11-17';

        Assessment::factory()->create([
            'schedule_id' => $scheduleA->id,
            'type' => AssessmentType::Quiz,
            'date' => $date,
            'title' => 'تقييم قسم أ',
        ]);

        $conflicts = $service->checkConflicts($scheduleB, $date, AssessmentType::Quiz);

        $teacherClash = collect($conflicts)->firstWhere('type', 'teacher_collision');
        $this->assertNotNull($teacherClash);
        $this->assertSame('warning', $teacherClash['severity']);
    }

    public function test_assessment_resource_form_validates_daily_conflict_limit(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();
        $subject = Subject::factory()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Wednesday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $date = '2026-11-18';

        // Pre-create 2 midterms on this date for the classroom
        for ($i = 1; $i <= 2; $i++) {
            $otherSchedule = Schedule::factory()->create([
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Wednesday,
                'start_time' => sprintf('%02d:00', 8 + $i),
                'end_time' => sprintf('%02d:00', 9 + $i),
            ]);

            Assessment::factory()->create([
                'schedule_id' => $otherSchedule->id,
                'type' => AssessmentType::Midterm,
                'date' => $date,
            ]);
        }

        Livewire::test(CreateAssessment::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'title' => 'الفرض الثالث المخالف',
                'type' => AssessmentType::Midterm->value,
                'date' => $date,
                'max_score' => 20,
            ])
            ->call('create')
            ->assertHasFormErrors(['date']);
    }

    public function test_exam_timetable_hub_displays_classroom_calendar_and_conflict_badges(): void
    {
        app()->setLocale('ar');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);

        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Midterm,
            'date' => '2026-10-15',
            'title' => 'الفرض الأول في الرياضيات',
        ]);

        Livewire::test(ExamTimetableHub::class)
            ->set('classroomId', $classroom->id)
            ->set('trimester', 1)
            ->assertSee('1 م 1')
            ->assertSee('الرياضيات')
            ->assertSee('الفرض الأول في الرياضيات')
            ->assertSee('الفصل الأول')
            ->assertSee('حالات التضارب')
            ->assertSet('conflictsCount', 0);
    }

    public function test_print_exam_timetable_route_renders_official_a4_layout(): void
    {
        app()->setLocale('ar');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 3']);
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);

        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Midterm,
            'date' => '2026-10-20',
            'title' => 'الفرض الأول',
            'max_score' => 20,
        ]);

        $this->get(route('print.exam-timetable', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertOk()
            ->assertSee('الجمهورية الجزائرية الديمقراطية الشعبية')
            ->assertSee('وزارة التربية الوطنية')
            ->assertSee('رزنامة الفروض المحروسة والامتحانات الرسمية')
            ->assertSee('2 م 3')
            ->assertSee('اللغة العربية')
            ->assertSee('لا يجوز برمجة أكثر من فرضين محروسين في اليوم الواحد للقسم الواحد');
    }

    public function test_print_exam_timetable_pdf_route_generates_stream_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 2']);

        $this->mock(ExamConflictService::class, function ($mock) use ($classroom): void {
            $mock->shouldReceive('generatePdf')
                ->once()
                ->withArgs(fn ($argClassroom, $trimester) => $argClassroom->id === $classroom->id && $trimester === 1)
                ->andReturn('%PDF-1.4 Mocked Content');
        });

        $this->get(route('print.exam-timetable.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertDownload('exam-timetable-class-'.$classroom->id.'-trimester-1.pdf');
    }

    public function test_teacher_cannot_view_exam_timetable_for_unassigned_classroom(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $unassignedClassroom = Classroom::factory()->for($level)->create();

        $this->get(route('print.exam-timetable', [
            'locale' => 'ar',
            'classroom' => $unassignedClassroom,
            'trimester' => 1,
        ]))
            ->assertForbidden();
    }
}
