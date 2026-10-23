<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Filament\Pages\OfficialReports;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\OfficialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class OfficialReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_access_official_reports_page(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(OfficialReports::class)
            ->assertSuccessful()
            ->assertSee(__('التقارير الرسمية'))
            ->assertSee(__('جدول التوقيت الأسبوعي'))
            ->assertSee(__('تقرير الحضور والغيابات المجمع'))
            ->assertSee(__('كشف النقاط الرسمي'));
    }

    public function test_admin_can_access_official_reports_page(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        Livewire::actingAs($admin)
            ->test(OfficialReports::class)
            ->assertSuccessful()
            ->assertSee(__('التقارير الرسمية'));
    }

    public function test_guest_cannot_access_official_reports_page(): void
    {
        $this->get('/ar/auth/official-reports')
            ->assertRedirect(route('filament.auth.auth.login', ['locale' => 'ar']));
    }

    public function test_teacher_timetable_print_shows_own_schedules_only(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $otherTeacher = User::factory()->create(['name' => 'الأستاذ علي']);
        $classroom = Classroom::factory()->create([
            'name' => '1 م 1',
            'academic_year' => '2025/2026',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $otherSubject = Subject::factory()->create(['name' => 'الرياضيات']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Schedule::factory()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $otherSubject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $url = route('print.timetable', ['locale' => 'ar']);

        $this->actingAs($teacher)
            ->get($url)
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('size: A4 landscape', false)
            ->assertSee('window.print()', false)
            ->assertSee('الإعلام الآلي')
            ->assertSee('الأستاذة أمينة')
            ->assertSee('1 م 1')
            ->assertDontSee('الرياضيات')
            ->assertDontSee('الأستاذ علي');
    }

    public function test_admin_can_print_timetable_for_selected_teacher(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $url = route('print.timetable', [
            'locale' => 'ar',
            'teacher' => $teacher->id,
            'classroom' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertSee('الأستاذة أمينة')
            ->assertSee('الإعلام الآلي')
            ->assertSee('1 م 1');
    }

    public function test_teacher_cannot_print_another_teachers_timetable(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $url = route('print.timetable', [
            'locale' => 'ar',
            'teacher' => $otherTeacher->id,
        ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertForbidden();
    }

    public function test_teacher_cannot_print_classroom_they_do_not_teach(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();

        $url = route('print.timetable', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
        ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertForbidden();
    }

    public function test_attendance_summary_aggregates_statuses_and_scopes_to_classroom(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $otherTeacher = User::factory()->create(['name' => 'الأستاذ علي']);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
        ]);

        $otherSchedule = Schedule::factory()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
        ]);

        $student1 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'أمين',
            'last_name' => 'بلقاسم',
            'registration_number' => '001',
        ]);
        $student2 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'سارة',
            'last_name' => 'مرابط',
            'registration_number' => '002',
        ]);

        $completedLog = DailyLog::factory()->completed()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-20',
        ]);
        DailyLog::factory()->completed()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-21',
        ]);
        DailyLog::factory()->completed()->create([
            'schedule_id' => $otherSchedule->id,
            'date' => '2026-10-20',
        ]);

        Attendance::factory()->create([
            'daily_log_id' => $completedLog->id,
            'student_id' => $student1->id,
            'status' => AttendanceStatus::Present,
        ]);
        Attendance::factory()->create([
            'daily_log_id' => $completedLog->id,
            'student_id' => $student2->id,
            'status' => AttendanceStatus::Absent,
        ]);

        $url = route('print.attendance-summary', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'group' => $group->id,
            'subject' => $subject->id,
            'from' => '2026-10-20',
            'to' => '2026-10-21',
        ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('size: A4 portrait', false)
            ->assertSee('نسبة الحضور')
            ->assertSee('نسبة الغياب')
            ->assertSee('أمين بلقاسم')
            ->assertSee('سارة مرابط')
            ->assertSee('حاضر')
            ->assertSee('غائب')
            ->assertSee('الإعلام الآلي')
            ->assertSee('1 م 1');
    }

    public function test_attendance_summary_excludes_planned_logs_and_other_teachers(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $student = Student::factory()->create(['group_id' => $group->id]);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
        ]);

        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-20',
            'status' => DailyLogStatus::Planned->value,
        ]);

        $url = route('print.attendance-summary', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'group' => $group->id,
            'subject' => $subject->id,
            'from' => '2026-10-20',
            'to' => '2026-10-20',
        ]);

        $response = $this->actingAs($teacher)->get($url);

        $response->assertOk();
        $response->assertSee('لا توجد سجلات حضور في الفترة المحددة.');
    }

    public function test_attendance_summary_requires_classroom_and_valid_range(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.attendance-summary', ['locale' => 'ar']))
            ->assertSessionHasErrors(['classroom', 'from', 'to']);

        $this->actingAs($teacher)
            ->get(route('print.attendance-summary', [
                'locale' => 'ar',
                'classroom' => Classroom::factory()->create()->id,
                'from' => '2026-10-21',
                'to' => '2026-10-20',
            ]))
            ->assertSessionHasErrors(['to']);
    }

    public function test_teacher_cannot_access_another_classroom_attendance(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.attendance-summary', [
                'locale' => 'ar',
                'classroom' => $classroom->id,
                'from' => '2026-10-20',
                'to' => '2026-10-20',
            ]))
            ->assertForbidden();
    }

    public function test_grade_transcript_prints_assessments_scores_averages_and_ranks(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
        ]);

        $student1 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'سارة',
            'last_name' => 'مرابط',
            'registration_number' => '001',
        ]);
        $student2 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'أمين',
            'last_name' => 'بلقاسم',
            'registration_number' => '002',
        ]);
        $student3 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'يوسف',
            'last_name' => 'الدالي',
            'registration_number' => '003',
        ]);

        $assessment1 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => 'الفرض الأول',
            'type' => AssessmentType::Midterm,
            'date' => '2026-10-20',
            'max_score' => 20,
        ]);
        $assessment2 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => 'الفرض الثاني',
            'type' => AssessmentType::Exam,
            'date' => '2026-10-27',
            'max_score' => 20,
        ]);

        Grade::factory()->graded(15)->create([
            'assessment_id' => $assessment1->id,
            'student_id' => $student1->id,
        ]);
        Grade::factory()->graded(10)->create([
            'assessment_id' => $assessment2->id,
            'student_id' => $student1->id,
        ]);
        Grade::factory()->graded(18)->create([
            'assessment_id' => $assessment1->id,
            'student_id' => $student2->id,
        ]);
        Grade::factory()->graded(12)->create([
            'assessment_id' => $assessment2->id,
            'student_id' => $student2->id,
        ]);
        Grade::factory()->graded(8)->create([
            'assessment_id' => $assessment1->id,
            'student_id' => $student3->id,
        ]);

        $url = route('print.grade-transcript', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'group' => $group->id,
            'subject' => $subject->id,
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('size: A4 landscape', false)
            ->assertSee('الإعلام الآلي')
            ->assertSee('1 م 1')
            ->assertSee('الفرض الأول')
            ->assertSee('الفرض الثاني')
            ->assertSee('سارة مرابط')
            ->assertSee('أمين بلقاسم')
            ->assertSee('يوسف الدالي')
            ->assertSee('15.00')
            ->assertSee('10.00')
            ->assertSee('18.00')
            ->assertSee('12.00')
            ->assertSee('8.00')
            ->assertSee('الرتبة')
            ->assertSeeInOrder(['أمين بلقاسم', 'سارة مرابط', 'يوسف الدالي']);
    }

    public function test_grade_transcript_requires_classroom_and_subject(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.grade-transcript', ['locale' => 'ar']))
            ->assertSessionHasErrors(['classroom', 'subject']);

        $this->actingAs($teacher)
            ->get(route('print.grade-transcript', [
                'locale' => 'ar',
                'classroom' => Classroom::factory()->create()->id,
                'from' => '2026-10-20',
                'to' => '2026-10-20',
            ]))
            ->assertSessionHasErrors(['subject']);
    }

    public function test_teacher_cannot_access_another_classroom_transcript(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.grade-transcript', [
                'locale' => 'ar',
                'classroom' => $classroom->id,
                'subject' => $subject->id,
                'from' => '2026-10-20',
                'to' => '2026-10-20',
            ]))
            ->assertForbidden();
    }

    public function test_page_defaults_teacher_filters_to_own_schedule(): void
    {
        $teacher = User::factory()->create(['name' => 'المعلم خالد']);
        $classroom = Classroom::factory()->create(['name' => '1 م 2']);
        $subject = Subject::factory()->create(['name' => 'الفيزياء']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب',
        ]);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
        ]);

        Livewire::actingAs($teacher)
            ->test(OfficialReports::class)
            ->assertSet('timetableTeacherId', $teacher->id)
            ->assertSet('attendanceClassroomId', $classroom->id)
            ->assertSet('attendanceGroupId', $group->id)
            ->assertSet('attendanceSubjectId', $subject->id)
            ->assertSet('transcriptClassroomId', $classroom->id)
            ->assertSet('transcriptGroupId', $group->id)
            ->assertSet('transcriptSubjectId', $subject->id);
    }

    public function test_page_options_are_scoped_to_teacher(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $otherClassroom = Classroom::factory()->create(['name' => '2 م 1']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
        ]);
        Schedule::factory()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $otherClassroom->id,
        ]);

        Livewire::actingAs($teacher)
            ->test(OfficialReports::class)
            ->assertDontSee($otherClassroom->name)
            ->assertSee($classroom->name);
    }

    public function test_unsupported_locale_on_print_routes_returns_404(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.timetable', ['locale' => 'zz']))
            ->assertNotFound();

        $this->actingAs($teacher)
            ->get(route('print.attendance-summary', ['locale' => 'zz']))
            ->assertNotFound();

        $this->actingAs($teacher)
            ->get(route('print.grade-transcript', ['locale' => 'zz']))
            ->assertNotFound();
    }

    public function test_pdf_routes_generate_downloads_when_service_is_mocked(): void
    {
        $this->mock(OfficialReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('timetableViewData')->once()->andReturn([]);
            $mock->shouldReceive('pdfResponse')->once()->andReturn(
                response()->streamDownload(fn () => print 'pdf-content', 'جدول-التوقيت-2026-10-20.pdf', [
                    'Content-Type' => 'application/pdf',
                ])
            );
        });

        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.timetable.pdf', ['locale' => 'ar']))
            ->assertDownload();
    }

    public function test_teacher_sees_only_own_classrooms_in_page_options(): void
    {
        $teacher = User::factory()->create();
        $owned = Classroom::factory()->create(['name' => 'القسم المملوك']);
        $notOwned = Classroom::factory()->create(['name' => 'القسم غير المملوك']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $owned->id,
        ]);

        Livewire::actingAs($teacher)
            ->test(OfficialReports::class)
            ->assertSee($owned->name)
            ->assertDontSee($notOwned->name);
    }
}
