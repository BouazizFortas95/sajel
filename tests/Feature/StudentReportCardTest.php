<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Filament\Pages\OfficialReports;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentReportCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_teacher_prints_and_downloads_a_subject_scoped_report_card(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $otherTeacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج أ']);
        $student = Student::factory()->create(['group_id' => $group->id]);
        $ownSubject = Subject::factory()->create(['name' => 'الرياضيات']);
        $otherSubject = Subject::factory()->create(['name' => 'العلوم']);

        $ownSchedule = $this->makeSchedule($teacher, $classroom, $group->id, $ownSubject);
        $otherSchedule = $this->makeSchedule($otherTeacher, $classroom, $group->id, $otherSubject);
        $ownLog = DailyLog::factory()->completed()->create(['schedule_id' => $ownSchedule->id]);
        $otherLog = DailyLog::factory()->completed()->create(['schedule_id' => $otherSchedule->id]);
        Attendance::factory()->create([
            'daily_log_id' => $ownLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present->value,
        ]);
        Attendance::factory()->create([
            'daily_log_id' => $otherLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Absent->value,
        ]);
        $recentAbsenceLog = DailyLog::factory()->completed()->create([
            'schedule_id' => $ownSchedule->id,
            'date' => '2026-10-22',
        ]);
        Attendance::factory()->create([
            'daily_log_id' => $recentAbsenceLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Absent->value,
            'remarks' => 'غياب بعذر قيد المراجعة',
        ]);
        $recentLateLog = DailyLog::factory()->completed()->create([
            'schedule_id' => $ownSchedule->id,
            'date' => '2026-10-23',
        ]);
        Attendance::factory()->create([
            'daily_log_id' => $recentLateLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Late->value,
            'remarks' => 'تأخر عشر دقائق',
        ]);

        $ownAssessment = $this->makeAssessment($ownSchedule, $student, 'فرض الرياضيات', 16, 'عمل جيد');
        $this->makeAssessment($otherSchedule, $student, 'اختبار العلوم', 4, 'ملاحظة خاصة بمادة أخرى');
        $ownObservation = $this->makeObservation($student, $teacher, 'ملاحظة من أستاذ الرياضيات', $ownSchedule);
        $this->makeObservation($student, $otherTeacher, 'ملاحظة خاصة بمعلم آخر', $otherSchedule);
        $ownRemediation = $this->makeRemediation($student, $teacher, 'دعم في الرياضيات', $ownSchedule);
        $this->makeRemediation($student, $otherTeacher, 'دعم خارج اختصاص الأستاذ', $otherSchedule);

        $this->actingAs($teacher)
            ->get(route('print.student-report-card', ['locale' => 'ar', 'student' => $student]))
            ->assertOk()
            ->assertSee('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ')
            ->assertSee('الرياضيات')
            ->assertSee('فرض الرياضيات')
            ->assertSee('عمل جيد')
            ->assertDontSee('العلوم')
            ->assertDontSee('اختبار العلوم')
            ->assertDontSee('ملاحظة خاصة بمادة أخرى')
            ->assertSee('ملاحظة من أستاذ الرياضيات')
            ->assertDontSee('ملاحظة خاصة بمعلم آخر')
            ->assertSee('دعم في الرياضيات')
            ->assertDontSee('دعم خارج اختصاص الأستاذ')
            ->assertSee('66.7%')
            ->assertSee('معدل المادة على 20')
            ->assertSee('16.00')
            ->assertSee('تأخر عشر دقائق')
            ->assertSee('إمضاء الأستاذ(ة)')
            ->assertSee('تأشيرة الإدارة')
            ->assertSee('إطلاع وتوقيع ولي الأمر');

        $reportService = app(StudentReportCardService::class);
        $teacherReport = $reportService->viewData($student, $teacher, autoPrint: false);
        $this->assertSame(16.0, $teacherReport['gradesBySubject'][0]['average']);
        $this->assertSame(3, $teacherReport['attendanceTotal']);
        $this->assertCount(2, $teacherReport['recentAttendanceAlerts']);
        $this->assertSame('تأخر عشر دقائق', $teacherReport['recentAttendanceAlerts']->first()->remarks);
        $this->assertSame($classroom->name, $teacherReport['studentIdentity']['classroom']);
        $this->assertSame($ownObservation->id, $teacherReport['observations']->first()->id);
        $this->assertSame($ownRemediation->id, $teacherReport['remediations']->first()->id);
        $this->assertStringContainsString('وزارة التربية الوطنية', $reportService->renderHtml($student, $teacher, false));

        $this->mock(StudentReportCardService::class, function ($mock): void {
            $mock->shouldReceive('generatePdf')
                ->once()
                ->andReturn('%PDF-1.4 test');
        });

        $this->get(route('print.student-report-card.pdf', ['locale' => 'ar', 'student' => $student]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $ownAssessment->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_report_card_renders_self_contained_amber_print_layout(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج أ']);
        $student = Student::factory()->create(['group_id' => $group->id]);
        $schoolSetting = SchoolSetting::current();

        $response = $this->actingAs($admin)
            ->get(route('print.student-report-card', ['locale' => 'ar', 'student' => $student]));

        $response->assertOk()
            ->assertSee('@page', false)
            ->assertSee('size: A4 portrait', false)
            ->assertSee('Cairo', false)
            ->assertSee('print-color-adjust: exact', false)
            ->assertSee('kpi-strip', false)
            ->assertSee('grid-cols-5', false)
            ->assertSee('report-table', false)
            ->assertSee('signature-grid', false)
            ->assertSee('بطاقة هوية التلميذ')
            ->assertSee('نسبة الحضور')
            ->assertSee('حاضر')
            ->assertSee('غائب')
            ->assertSee('متأخر')
            ->assertSee('معفى')
            ->assertSee('إمضاء الأستاذ(ة)')
            ->assertSee('تأشيرة الإدارة')
            ->assertSee('إطلاع وتوقيع ولي الأمر')
            ->assertSee($schoolSetting->school_name)
            ->assertSee($schoolSetting->directorate_name);

        $this->assertStringContainsString('/build/assets/app-', $response->getContent());
    }

    public function test_teacher_cannot_view_report_card_for_a_student_outside_their_roster(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.student-report-card', ['locale' => 'ar', 'student' => $student]))
            ->assertForbidden();

        $this->get(route('print.student-report-card.pdf', ['locale' => 'ar', 'student' => $student]))
            ->assertForbidden();
    }

    public function test_admin_can_print_and_download_full_multi_subject_report_card(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج ب']);
        $student = Student::factory()->create(['group_id' => $group->id]);
        $mathSchedule = $this->makeSchedule($teacher, $classroom, $group->id, Subject::factory()->create(['name' => 'الرياضيات']));
        $scienceSchedule = $this->makeSchedule($teacher, $classroom, $group->id, Subject::factory()->create(['name' => 'العلوم']));

        $mathLog = DailyLog::factory()->completed()->create(['schedule_id' => $mathSchedule->id]);
        Attendance::factory()->create([
            'daily_log_id' => $mathLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Late->value,
        ]);
        $this->makeAssessment($mathSchedule, $student, 'تقييم رياضيات', 12, 'استمر في التقدم');
        $this->makeAssessment($scienceSchedule, $student, 'تقييم علوم', 18, null);
        BehaviorObservation::query()->create([
            'student_id' => $student->id,
            'category' => 'positive',
            'notes' => 'مبادرة إيجابية.',
            'date' => '2026-10-20',
            'is_followed_up' => true,
        ]);
        PedagogicalRemediation::query()->create([
            'student_id' => $student->id,
            'reason' => 'صعوبات في التعلم',
            'activities' => 'تمارين دعم',
            'session_date' => '2026-10-21',
            'attended' => true,
            'follow_up' => 'تحسن ملحوظ',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->get(route('print.student-report-card', ['locale' => 'ar', 'student' => $student]))
            ->assertOk()
            ->assertSee('الرياضيات')
            ->assertSee('العلوم')
            ->assertSee('تقييم رياضيات')
            ->assertSee('تقييم علوم')
            ->assertSee('مبادرة إيجابية.')
            ->assertSee('تحسن ملحوظ');

        $this->mock(StudentReportCardService::class, function ($mock): void {
            $mock->shouldReceive('generatePdf')
                ->once()
                ->andReturn('%PDF-1.4 test');
        });

        $this->get(route('print.student-report-card.pdf', ['locale' => 'ar', 'student' => $student]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_official_reports_page_offers_the_authorized_student_report_picker(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $otherTeacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج ج']);
        $student = Student::factory()->create([
            'first_name' => 'ليلى',
            'last_name' => 'علي',
            'group_id' => $group->id,
        ]);
        $outsideStudent = Student::factory()->create([
            'first_name' => 'يوسف',
            'last_name' => 'خالد',
        ]);
        $this->makeSchedule($teacher, $classroom, $group->id, Subject::factory()->create());
        $this->makeSchedule($otherTeacher, Classroom::factory()->create(), null, Subject::factory()->create());

        Livewire::actingAs($teacher)
            ->test(OfficialReports::class)
            ->call('setReport', 'report-card')
            ->assertSee(__('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ'))
            ->assertSee($student->full_name)
            ->assertSee($classroom->name)
            ->assertSee('الفوج ج')
            ->assertDontSee($outsideStudent->full_name)
            ->assertSee(route('print.student-report-card', ['locale' => 'en', 'student' => $student]))
            ->assertSee(route('print.student-report-card.pdf', ['locale' => 'en', 'student' => $student]))
            ->assertSee('reportCardClassroomId')
            ->assertSee('reportCardGroupId');
    }

    private function makeSchedule(User $teacher, Classroom $classroom, ?int $groupId, Subject $subject): Schedule
    {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $groupId,
            'subject_id' => $subject->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
    }

    private function makeAssessment(
        Schedule $schedule,
        Student $student,
        string $title,
        float $score,
        ?string $remarks,
    ): Assessment {
        $assessment = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => $title,
            'type' => AssessmentType::Quiz->value,
            'max_score' => 20,
        ]);

        Grade::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => $score,
            'remarks' => $remarks,
        ]);

        return $assessment;
    }

    private function makeObservation(Student $student, User $teacher, string $notes, ?Schedule $schedule = null): BehaviorObservation
    {
        $observation = new BehaviorObservation([
            'student_id' => $student->id,
            'schedule_id' => $schedule?->id,
            'category' => 'positive',
            'notes' => $notes,
            'date' => '2026-10-20',
            'is_followed_up' => true,
        ]);
        $observation->recorded_by_id = $teacher->id;
        $observation->save();

        return $observation;
    }

    private function makeRemediation(Student $student, User $teacher, string $reason, ?Schedule $schedule = null): PedagogicalRemediation
    {
        $remediation = new PedagogicalRemediation([
            'student_id' => $student->id,
            'schedule_id' => $schedule?->id,
            'reason' => $reason,
            'activities' => 'تمارين دعم',
            'session_date' => '2026-10-21',
            'attended' => true,
            'follow_up' => 'تحسن ملحوظ',
            'status' => 'completed',
        ]);
        $remediation->recorded_by_id = $teacher->id;
        $remediation->save();

        return $remediation;
    }
}
