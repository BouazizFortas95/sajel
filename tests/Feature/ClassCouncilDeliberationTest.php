<?php

namespace Tests\Feature;

use App\Enums\ClassCouncilSessionStatus;
use App\Enums\CouncilHonors;
use App\Enums\CouncilSanctions;
use App\Enums\DailyLogStatus;
use App\Enums\EndOfYearDecision;
use App\Enums\PacingStatus;
use App\Enums\Role;
use App\Models\Assessment;
use App\Models\ClassCouncilDecision;
use App\Models\ClassCouncilSession;
use App\Models\Classroom;
use App\Models\CurriculumPacingAudit;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectCoordinationProtocol;
use App\Models\User;
use App\Services\ClassCouncilDeliberationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClassCouncilDeliberationTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $censor;

    private User $teacher;

    private User $otherUser;

    private Classroom $classroom;

    private Group $group;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');

        $this->principal = User::factory()->create([
            'role' => Role::Principal,
        ]);

        $this->censor = User::factory()->create([
            'role' => Role::Censor,
        ]);

        $this->teacher = User::factory()->create([
            'role' => Role::Teacher,
        ]);

        $this->otherUser = User::factory()->create([
            'role' => Role::FinanceOfficer,
        ]);

        $phase = EducationPhase::create([
            'name' => 'التعليم المتوسط',
            'code' => 'CEM',
            'phase_abbreviation' => 'م',
        ]);

        $level = Level::create([
            'education_phase_id' => $phase->id,
            'name' => 'السنة الرابعة متوسط',
            'grade_number' => 4,
        ]);

        $this->classroom = Classroom::create([
            'level_id' => $level->id,
            'name' => '4 م 1',
            'academic_year' => '2026/2027',
        ]);

        $this->group = Group::create([
            'classroom_id' => $this->classroom->id,
            'name' => 'فوج 1',
        ]);

        $this->subject = Subject::create([
            'name' => 'اللغة العربية',
            'color' => '#1a237e',
        ]);
    }

    // ─── 1. Schema & Enums ──────────────────────────────────────────────────

    public function test_all_phase47_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('class_council_sessions'));
        $this->assertTrue(Schema::hasTable('class_council_decisions'));
        $this->assertTrue(Schema::hasTable('subject_coordination_protocols'));
        $this->assertTrue(Schema::hasTable('curriculum_pacing_audits'));
    }

    public function test_council_session_status_enum(): void
    {
        app()->setLocale('ar');
        $this->assertSame('مسودة قيد الإعداد', ClassCouncilSessionStatus::Draft->label());
        $this->assertSame('تمت المداولة', ClassCouncilSessionStatus::Deliberated->label());
        $this->assertSame('محضر مصادق عليه رسميّاً', ClassCouncilSessionStatus::Certified->label());
    }

    public function test_council_honors_thresholds(): void
    {
        $this->assertSame(CouncilHonors::Felicitations, CouncilHonors::fromAverage(16.5));
        $this->assertSame(CouncilHonors::Encouragement, CouncilHonors::fromAverage(14.5));
        $this->assertSame(CouncilHonors::TableauHonneur, CouncilHonors::fromAverage(12.5));
        $this->assertSame(CouncilHonors::None, CouncilHonors::fromAverage(9.5));
    }

    public function test_council_sanctions_thresholds(): void
    {
        $this->assertSame(CouncilSanctions::BlameTravail, CouncilSanctions::fromAverage(5.5));
        $this->assertSame(CouncilSanctions::AvertissementTravail, CouncilSanctions::fromAverage(7.5));
        $this->assertSame(CouncilSanctions::None, CouncilSanctions::fromAverage(11.0));
    }

    public function test_end_of_year_decision_labels(): void
    {
        $this->assertSame('ينتقل إلى المستوى الأعلى', EndOfYearDecision::Admis->label());
        $this->assertSame('يعيد السنة', EndOfYearDecision::Redouble->label());
    }

    public function test_pacing_status_calculation(): void
    {
        $this->assertSame(PacingStatus::Ahead, PacingStatus::fromExecution(10, 13));
        $this->assertSame(PacingStatus::OnTrack, PacingStatus::fromExecution(10, 10));
        $this->assertSame(PacingStatus::MinorDelay, PacingStatus::fromExecution(10, 8));
        $this->assertSame(PacingStatus::CriticalDelay, PacingStatus::fromExecution(10, 5));
    }

    // ─── 2. Service Deliberation Operations ─────────────────────────────────

    public function test_service_creates_or_gets_session(): void
    {
        $service = app(ClassCouncilDeliberationService::class);

        $session = $service->getOrCreateSession($this->classroom, 1, '2026/2027', $this->principal);

        $this->assertInstanceOf(ClassCouncilSession::class, $session);
        $this->assertSame(1, $session->trimester);
        $this->assertSame('2026/2027', $session->academic_year);
        $this->assertSame(ClassCouncilSessionStatus::Draft, $session->status);
    }

    public function test_service_syncs_decisions_and_ranks_from_grades(): void
    {
        $studentA = Student::create([
            'group_id' => $this->group->id,
            'first_name' => 'أحمد',
            'last_name' => 'بن علي',
        ]);

        $studentB = Student::create([
            'group_id' => $this->group->id,
            'first_name' => 'سارة',
            'last_name' => 'بوعلام',
        ]);

        $schedule = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'group_id' => $this->group->id,
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'sunday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'title' => 'اختبار الفصل',
            'type' => 'exam',
            'date' => '2026-11-01',
            'max_score' => 20,
        ]);

        Grade::create([
            'assessment_id' => $assessment->id,
            'student_id' => $studentA->id,
            'score' => 16.0, // Should get Felicitations, Rank 1
        ]);

        Grade::create([
            'assessment_id' => $assessment->id,
            'student_id' => $studentB->id,
            'score' => 6.5,  // Should get AvertissementTravail, Rank 2
        ]);

        $service = app(ClassCouncilDeliberationService::class);
        $session = $service->getOrCreateSession($this->classroom, 1, '2026/2027', $this->principal);

        $synced = $service->syncDecisionsFromGrades($session);

        $this->assertSame(ClassCouncilSessionStatus::Deliberated, $synced->status);
        $this->assertSame(2, $synced->total_students);
        $this->assertEquals(11.25, $synced->class_average);
        $this->assertEquals(16.0, $synced->highest_average);
        $this->assertEquals(6.5, $synced->lowest_average);
        $this->assertEquals(50.0, $synced->success_rate);

        $decisionA = ClassCouncilDecision::where('class_council_session_id', $session->id)
            ->where('student_id', $studentA->id)
            ->first();

        $decisionB = ClassCouncilDecision::where('class_council_session_id', $session->id)
            ->where('student_id', $studentB->id)
            ->first();

        $this->assertSame(1, $decisionA->rank);
        $this->assertSame(CouncilHonors::Felicitations, $decisionA->honors);

        $this->assertSame(2, $decisionB->rank);
        $this->assertSame(CouncilSanctions::AvertissementTravail, $decisionB->sanctions);
    }

    public function test_service_certifies_session(): void
    {
        $service = app(ClassCouncilDeliberationService::class);
        $session = $service->getOrCreateSession($this->classroom, 1, '2026/2027', $this->principal);

        $certified = $service->certifySession($session, 'مصادق عليه بإجماع أعضاء المجلس');

        $this->assertSame(ClassCouncilSessionStatus::Certified, $certified->status);
        $this->assertSame('مصادق عليه بإجماع أعضاء المجلس', $certified->council_decisions_summary);
        $this->assertTrue($certified->isCertified());
    }

    // ─── 3. Subject Coordination Protocols ──────────────────────────────────

    public function test_service_records_subject_coordination_protocol(): void
    {
        $service = app(ClassCouncilDeliberationService::class);

        $protocol = $service->recordSubjectCoordination([
            'academic_year' => '2026/2027',
            'subject_id' => $this->subject->id,
            'trimester' => 1,
            'meeting_date' => '2026-10-15',
            'coordinator_id' => $this->teacher->id,
            'attendees' => [$this->teacher->id],
            'topic' => 'تنسيق الفروض المحروسة وضبط التدرج',
            'syllabus_pacing_notes' => 'مطابق للتدرج السنوي',
            'unified_exams_notes' => 'توحيد مواضيع الفروض',
            'difficulties_and_remediations' => 'دعم الفئات المتعثرة في التعبير',
            'recommendations' => 'توفير معاجم إضافية',
            'recorded_by' => $this->principal->id,
        ]);

        $this->assertInstanceOf(SubjectCoordinationProtocol::class, $protocol);
        $this->assertDatabaseHas('subject_coordination_protocols', [
            'topic' => 'تنسيق الفروض المحروسة وضبط التدرج',
            'subject_id' => $this->subject->id,
        ]);
    }

    // ─── 4. Curriculum Pacing Audit ─────────────────────────────────────────

    public function test_service_audits_curriculum_pacing(): void
    {
        $schedule = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'group_id' => $this->group->id,
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // Create 10 completed daily logs
        for ($i = 0; $i < 10; $i++) {
            DailyLog::create([
                'schedule_id' => $schedule->id,
                'date' => "2026-10-0{$i}",
                'title' => "درس رقم {$i}",
                'content_data' => ['objectives' => 'اختبار المورد'],
                'actual_content' => 'إنجاز المورد المعرفي',
                'status' => DailyLogStatus::Completed,
            ]);
        }

        $service = app(ClassCouncilDeliberationService::class);
        $audit = $service->auditCurriculumPacing($this->classroom, $this->subject, 1, '2026/2027', $this->censor);

        $this->assertInstanceOf(CurriculumPacingAudit::class, $audit);
        $this->assertSame(10, $audit->completed_resources_count);
        $this->assertGreaterThan(0, $audit->execution_percentage);
        $this->assertInstanceOf(PacingStatus::class, $audit->pacing_status);
    }

    // ─── 5. Web Routes Access Control ───────────────────────────────────────

    public function test_print_session_pv_accessible_to_principal(): void
    {
        $this->actingAs($this->principal);

        $service = app(ClassCouncilDeliberationService::class);
        $session = $service->getOrCreateSession($this->classroom, 1, '2026/2027', $this->principal);

        $response = $this->get(route('print.class-council-deliberation.pv', [
            'locale' => 'ar',
            'session' => $session->id,
        ]));

        $response->assertOk();
        $response->assertSee('محضر مداولات مجلس القسم');
    }

    public function test_print_subject_coordination_pv_accessible(): void
    {
        $this->actingAs($this->principal);

        $service = app(ClassCouncilDeliberationService::class);
        $protocol = $service->recordSubjectCoordination([
            'subject_id' => $this->subject->id,
            'trimester' => 1,
            'meeting_date' => '2026-10-15',
            'topic' => 'جلسة تنسيق أسبوعية',
        ]);

        $response = $this->get(route('print.subject-coordination.pv', [
            'locale' => 'ar',
            'protocol' => $protocol->id,
        ]));

        $response->assertOk();
        $response->assertSee('محضر جلسة التنسيق البيداغوجي للمادة');
    }

    public function test_print_curriculum_pacing_audit_accessible(): void
    {
        $this->actingAs($this->censor);

        $response = $this->get(route('print.curriculum-pacing-audit', [
            'locale' => 'ar',
            'year' => '2026-2027',
            'trimester' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('كشف تدقيق ومتابعة نسب إنجاز المناهج والتدرج السنوي');
    }

    // ─── 6. Health Check ────────────────────────────────────────────────────

    public function test_health_check_verifies_all_58_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present (58)');
    }
}
