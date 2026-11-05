<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\DayOfWeek;
use App\Enums\StudentAwardType;
use App\Filament\Pages\StudentAwardsHub;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentAwardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAwardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_awards_hub_page_accessible_to_both_admin_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $this->actingAs($admin)
            ->get(StudentAwardsHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('لوحة الشرف والشهادات التقديرية');

        $this->actingAs($teacher)
            ->get(StudentAwardsHub::getUrl(['locale' => 'ar', 'classroom' => $classroom->id]))
            ->assertOk()
            ->assertSee('لوحة الشرف والشهادات التقديرية');

        auth()->logout();
        $this->get(StudentAwardsHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_student_award_service_calculates_accurate_averages_and_rankings(): void
    {
        $service = app(StudentAwardService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 1']);
        $group = Group::factory()->for($classroom)->create();

        $math = Subject::factory()->create(['name' => 'رياضيات']);
        $arabic = Subject::factory()->create(['name' => 'لغة عربية']);

        $teacher = User::factory()->teacher()->create();

        $scheduleMath = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $math->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $scheduleArabic = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $arabic->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        // Student A: High scores (Math 18, Arabic 16 -> Average 17)
        $studentA = Student::factory()->for($group)->create([
            'first_name' => 'أحمد',
            'last_name' => 'بن علي',
        ]);

        // Student B: Medium scores (Math 14, Arabic 12 -> Average 13)
        $studentB = Student::factory()->for($group)->create([
            'first_name' => 'مريم',
            'last_name' => 'قادري',
        ]);

        $assessmentMath = Assessment::factory()->create([
            'schedule_id' => $scheduleMath->id,
            'type' => AssessmentType::Exam,
            'date' => '2026-11-10',
            'max_score' => 20,
        ]);

        $assessmentArabic = Assessment::factory()->create([
            'schedule_id' => $scheduleArabic->id,
            'type' => AssessmentType::Exam,
            'date' => '2026-11-12',
            'max_score' => 20,
        ]);

        Grade::factory()->create(['assessment_id' => $assessmentMath->id, 'student_id' => $studentA->id, 'score' => 18]);
        Grade::factory()->create(['assessment_id' => $assessmentArabic->id, 'student_id' => $studentA->id, 'score' => 16]);

        Grade::factory()->create(['assessment_id' => $assessmentMath->id, 'student_id' => $studentB->id, 'score' => 14]);
        Grade::factory()->create(['assessment_id' => $assessmentArabic->id, 'student_id' => $studentB->id, 'score' => 12]);

        $result = $service->computeClassroomAwards($classroom, 1);

        $this->assertSame(2, $result['kpis']['total_students']);
        $this->assertSame(2, $result['kpis']['graded_students']);
        $this->assertSame(17.0, $result['kpis']['highest_average']);
        $this->assertSame(13.0, $result['kpis']['lowest_average']);

        $students = $result['students'];
        $firstStudent = $students->first();
        $this->assertSame($studentA->id, $firstStudent['student']->id);
        $this->assertSame(1, $firstStudent['rank']);
        $this->assertSame(17.0, $firstStudent['general_average']);
        $this->assertSame(StudentAwardType::Excellence, $firstStudent['primary_award']);

        $secondStudent = $students->last();
        $this->assertSame($studentB->id, $secondStudent['student']->id);
        $this->assertSame(2, $secondStudent['rank']);
        $this->assertSame(13.0, $secondStudent['general_average']);
        $this->assertSame(StudentAwardType::HonorRoll, $secondStudent['primary_award']);
    }

    public function test_student_award_service_allocates_excellence_award_for_16_average_and_clean_record(): void
    {
        $service = app(StudentAwardService::class);

        // Score >= 16.0 with 0 negative behavior => Excellence
        $awards = $service->determineEligibleAwards(16.5, 2, 0, 0, 10);
        $this->assertContains(StudentAwardType::Excellence, $awards);

        // Score >= 16.0 with negative behavior remarks => Downgraded to Encouragement
        $downgraded = $service->determineEligibleAwards(17.0, 1, 1, 0, 10);
        $this->assertNotContains(StudentAwardType::Excellence, $downgraded);
        $this->assertContains(StudentAwardType::Encouragement, $downgraded);
    }

    public function test_student_award_service_allocates_encouragement_award_for_14_to_16_average(): void
    {
        $service = app(StudentAwardService::class);

        $awards = $service->determineEligibleAwards(14.5, 0, 0, 0, 10);
        $this->assertContains(StudentAwardType::Encouragement, $awards);
        $this->assertNotContains(StudentAwardType::Excellence, $awards);
    }

    public function test_student_award_service_allocates_honor_roll_for_12_to_14_average(): void
    {
        $service = app(StudentAwardService::class);

        $awards = $service->determineEligibleAwards(12.75, 0, 0, 0, 10);
        $this->assertContains(StudentAwardType::HonorRoll, $awards);
        $this->assertNotContains(StudentAwardType::Encouragement, $awards);
    }

    public function test_student_award_service_allocates_good_conduct_award_for_zero_absences_and_clean_discipline(): void
    {
        $service = app(StudentAwardService::class);

        // 0 unexcused absences, 0 negative observations, positive behavior => Good conduct
        $awards = $service->determineEligibleAwards(11.0, 3, 0, 0, 15);
        $this->assertContains(StudentAwardType::GoodConduct, $awards);

        // With 1 unexcused absence => No good conduct
        $awardsWithAbsence = $service->determineEligibleAwards(11.0, 3, 0, 1, 15);
        $this->assertNotContains(StudentAwardType::GoodConduct, $awardsWithAbsence);
    }

    public function test_student_award_service_scopes_to_trimester_date_ranges(): void
    {
        $service = app(StudentAwardService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();
        $teacher = User::factory()->teacher()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        $student = Student::factory()->for($group)->create();

        // Trimester 1 Assessment (Nov 2026) -> Score 18
        $assessmentT1 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Quiz,
            'date' => '2026-11-05',
            'max_score' => 20,
        ]);
        Grade::factory()->create(['assessment_id' => $assessmentT1->id, 'student_id' => $student->id, 'score' => 18]);

        // Trimester 2 Assessment (Feb 2027) -> Score 10
        $assessmentT2 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Quiz,
            'date' => '2027-02-15',
            'max_score' => 20,
        ]);
        Grade::factory()->create(['assessment_id' => $assessmentT2->id, 'student_id' => $student->id, 'score' => 10]);

        // When evaluating Trimester 1, only the 18 score should be computed
        $t1Result = $service->computeClassroomAwards($classroom, 1);
        $t1Student = collect($t1Result['students'])->firstWhere('student.id', $student->id);
        $this->assertSame(18.0, $t1Student['general_average']);
        $this->assertSame(StudentAwardType::Excellence, $t1Student['primary_award']);

        // When evaluating Trimester 2, only the 10 score should be computed
        $t2Result = $service->computeClassroomAwards($classroom, 2);
        $t2Student = collect($t2Result['students'])->firstWhere('student.id', $student->id);
        $this->assertSame(10.0, $t2Student['general_average']);
        $this->assertNotSame(StudentAwardType::Excellence, $t2Student['primary_award']);
    }

    public function test_print_single_student_award_certificate_renders_a4_landscape_official_layout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '4 م 2']);
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->for($group)->create([
            'first_name' => 'خالد',
            'last_name' => 'زروقي',
        ]);

        $this->get(route('print.student-award.single', [
            'locale' => 'ar',
            'student' => $student,
            'awardType' => StudentAwardType::Excellence->value,
            'trimester' => 1,
        ]))
            ->assertOk()
            ->assertSee('الجمهورية الجزائرية الديمقراطية الشعبية')
            ->assertSee('وزارة التربية الوطنية')
            ->assertSee('شَهَادَةُ امْتِيَازٍ وَتَفَوُّقٍ')
            ->assertSee('خالد زروقي')
            ->assertSee('4 م 2');
    }

    public function test_print_batch_classroom_award_certificates_renders_all_honored_students(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 3']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();
        $teacher = User::factory()->teacher()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $studentA = Student::factory()->for($group)->create(['first_name' => 'سامي', 'last_name' => 'محمودي']);
        $studentB = Student::factory()->for($group)->create(['first_name' => 'نور', 'last_name' => 'بلقاسم']);

        $assessment = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Exam,
            'date' => '2026-11-20',
            'max_score' => 20,
        ]);

        Grade::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $studentA->id, 'score' => 17]);
        Grade::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $studentB->id, 'score' => 15]);

        $this->get(route('print.student-awards.batch', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertOk()
            ->assertSee('سامي محمودي')
            ->assertSee('نور بلقاسم')
            ->assertSee('شَهَادَةُ امْتِيَازٍ وَتَفَوُّقٍ')
            ->assertSee('شَهَادَةُ تَشْجِيعٍ وَتَقْدِيرٍ');
    }

    public function test_print_classroom_honor_roll_sheet_renders_official_bulletin_table(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 4']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();
        $teacher = User::factory()->teacher()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        $student = Student::factory()->for($group)->create(['first_name' => 'إيناس', 'last_name' => 'حداد']);

        $assessment = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Exam,
            'date' => '2026-11-15',
            'max_score' => 20,
        ]);

        Grade::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 16.5]);

        $this->get(route('print.classroom-honor-roll', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertOk()
            ->assertSee('قائمة لوحة الشرف والمتفوقين للفصل الدراسي')
            ->assertSee('إيناس حداد')
            ->assertSee('2 م 4')
            ->assertSee('16.50');
    }

    public function test_pdf_download_routes_stream_proper_headers_and_filenames(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 3']);
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->for($group)->create();

        // 1. Single PDF mock
        $this->mock(StudentAwardService::class, function ($mock) use ($classroom, $student): void {
            $mock->shouldReceive('generateSingleCertificatePdf')
                ->once()
                ->withArgs(fn ($argStudent, $award, $trimester) => $argStudent->id === $student->id && $award === StudentAwardType::Excellence->value && $trimester === 1)
                ->andReturn('%PDF-1.4 Mocked Single Certificate');

            $mock->shouldReceive('generateClassroomCertificatesPdf')
                ->once()
                ->withArgs(fn ($argClassroom, $trimester, $groupId, $awardFilter) => $argClassroom->id === $classroom->id && $trimester === 1)
                ->andReturn('%PDF-1.4 Mocked Batch Certificates');

            $mock->shouldReceive('generateHonorRollSheetPdf')
                ->once()
                ->withArgs(fn ($argClassroom, $trimester, $groupId) => $argClassroom->id === $classroom->id && $trimester === 1)
                ->andReturn('%PDF-1.4 Mocked Honor Roll');
        });

        $this->get(route('print.student-award.single.pdf', [
            'locale' => 'ar',
            'student' => $student,
            'awardType' => StudentAwardType::Excellence->value,
            'trimester' => 1,
        ]))
            ->assertDownload('certificate-student-'.$student->id.'-excellence.pdf');

        $this->get(route('print.student-awards.batch.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertDownload('certificates-class-'.$classroom->id.'-trimester-1.pdf');

        $this->get(route('print.classroom-honor-roll.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'trimester' => 1,
        ]))
            ->assertDownload('honor-roll-class-'.$classroom->id.'-trimester-1.pdf');
    }

    public function test_teacher_cannot_view_or_print_awards_for_unassigned_classroom(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $unassignedClassroom = Classroom::factory()->for($level)->create(['name' => 'غريب 1']);
        $unassignedGroup = Group::factory()->for($unassignedClassroom)->create();
        $unassignedStudent = Student::factory()->for($unassignedGroup)->create();

        $this->get(route('print.student-awards.batch', [
            'locale' => 'ar',
            'classroom' => $unassignedClassroom,
            'trimester' => 1,
        ]))
            ->assertForbidden();

        $this->get(route('print.classroom-honor-roll', [
            'locale' => 'ar',
            'classroom' => $unassignedClassroom,
            'trimester' => 1,
        ]))
            ->assertForbidden();

        $this->get(route('print.student-award.single', [
            'locale' => 'ar',
            'student' => $unassignedStudent,
            'awardType' => StudentAwardType::Excellence->value,
            'trimester' => 1,
        ]))
            ->assertForbidden();
    }
}
