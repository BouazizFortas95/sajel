<?php

namespace Tests\Feature;

use App\Enums\CounselingSessionReason;
use App\Enums\CounselingSessionStatus;
use App\Enums\FamilyStatus;
use App\Filament\Pages\StudentCounselingHub;
use App\Models\Classroom;
use App\Models\CounselingSession;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentCounselingProfile;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolRestoreService;
use App\Services\StudentCounselingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentCounselingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected Classroom $classroom;

    protected Group $group;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'school_name' => 'متوسطة الشهيد مفدي زكرياء',
            'directorate_name' => 'الجزائر وسط',
            'academic_year' => '2026/2027',
            'principal_name' => 'الأستاذ المدير',
            'counselor_name' => 'مستشار التوجيه أحمد',
        ]);

        $this->admin = User::factory()->admin()->create();
        $this->teacher = User::factory()->teacher()->create();

        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['order' => 1, 'code' => 'CEM']
        );

        $level = Level::create([
            'name' => 'السنة الثالثة متوسط',
            'education_phase_id' => $phase->id,
            'order' => 3,
        ]);

        $this->classroom = Classroom::create([
            'name' => '3م1',
            'level_id' => $level->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $this->group = Group::create([
            'name' => 'فوج 3م1',
            'classroom_id' => $this->classroom->id,
            'capacity' => 35,
        ]);

        $this->student = Student::factory()->create([
            'group_id' => $this->group->id,
            'first_name' => 'سامي',
            'last_name' => 'بلمختار',
            'gender' => 'male',
            'is_active' => true,
        ]);
    }

    public function test_student_counseling_profile_creation_and_scopes(): void
    {
        $profile = StudentCounselingProfile::factory()->create([
            'student_id' => $this->student->id,
            'family_status' => FamilyStatus::OrphanFather,
            'social_grant' => true,
            'pe_exemption' => true,
            'chronic_conditions' => ['ربو حاد'],
            'learning_difficulties' => ['صعوبة في الحساب'],
            'repeated_years_count' => 1,
        ]);

        $this->assertInstanceOf(StudentCounselingProfile::class, $profile);
        $this->assertSame(FamilyStatus::OrphanFather, $profile->family_status);
        $this->assertTrue($profile->social_grant);
        $this->assertTrue($profile->hasRiskFactors());
        $this->assertStringContainsString('ربو حاد', $profile->chronicConditionsList());
        $this->assertStringContainsString('صعوبة في الحساب', $profile->learningDifficultiesList());

        $this->assertSame(1, StudentCounselingProfile::withSocialGrant()->count());
        $this->assertSame(1, StudentCounselingProfile::withPeExemption()->count());
        $this->assertSame(1, StudentCounselingProfile::withChronicConditions()->count());
        $this->assertSame(1, StudentCounselingProfile::withLearningDifficulties()->count());
    }

    public function test_counseling_session_creation_and_scopes(): void
    {
        app()->setLocale('ar');

        $session = CounselingSession::factory()->create([
            'student_id' => $this->student->id,
            'counselor_id' => $this->admin->id,
            'academic_year' => '2026/2027',
            'reason_category' => CounselingSessionReason::AcademicDifficulty,
            'status' => CounselingSessionStatus::Open,
            'interviewee_type' => 'student_and_guardian',
            'observations' => 'تم استدعاء التلميذ رفقة الولي لتشخيص تراجع نتائجه في مادة الرياضيات.',
        ]);

        $this->assertInstanceOf(CounselingSession::class, $session);
        $this->assertSame('التلميذ بحضور الولي', $session->intervieweeLabel());
        $this->assertSame(1, CounselingSession::forAcademicYear('2026/2027')->count());
        $this->assertSame(1, CounselingSession::open()->count());
        $this->assertSame(0, CounselingSession::resolved()->count());
    }

    public function test_service_get_or_create_and_update_profile(): void
    {
        $service = app(StudentCounselingService::class);

        $profile = $service->getOrCreateProfile($this->student);
        $this->assertSame($this->student->id, $profile->student_id);
        $this->assertSame(FamilyStatus::BothParents, $profile->family_status);

        $updated = $service->updateProfile($this->student, [
            'family_status' => FamilyStatus::DivorcedSeparated,
            'father_job' => 'موظف إداري',
            'chronic_conditions' => 'حساسية مفرطة, ضعف بصر',
            'learning_difficulties' => 'عسر قراءة, تشتت انتباه',
            'social_grant' => true,
        ]);

        $this->assertSame(FamilyStatus::DivorcedSeparated, $updated->family_status);
        $this->assertCount(2, $updated->chronic_conditions);
        $this->assertCount(2, $updated->learning_difficulties);
        $this->assertTrue($updated->social_grant);
    }

    public function test_service_record_and_update_session_status(): void
    {
        $service = app(StudentCounselingService::class);

        $session = $service->recordSession($this->student, $this->admin, [
            'session_date' => '2026-11-05',
            'reason_category' => CounselingSessionReason::BehavioralIssue,
            'interviewee_type' => 'student_only',
            'observations' => 'سلوك اندفاعي متكرر خلال حصص التربية البدنية.',
            'action_plan' => 'اتفاق شرفي مع التلميذ للمواظبة وضبط النفس.',
            'status' => CounselingSessionStatus::Open,
        ]);

        $this->assertSame(CounselingSessionReason::BehavioralIssue, $session->reason_category);
        $this->assertSame(CounselingSessionStatus::Open, $session->status);

        $resolved = $service->updateSessionStatus($session, CounselingSessionStatus::Resolved, 'تحسن ملحوظ في السلوك وانضباط تام.');
        $this->assertSame(CounselingSessionStatus::Resolved, $resolved->status);
        $this->assertSame('تحسن ملحوظ في السلوك وانضباط تام.', $resolved->action_plan);
    }

    public function test_service_compute_counseling_kpis(): void
    {
        $service = app(StudentCounselingService::class);

        $service->updateProfile($this->student, [
            'family_status' => FamilyStatus::OrphanBoth,
            'social_grant' => true,
            'chronic_conditions' => 'سكري أطفال',
            'pe_exemption' => true,
            'learning_difficulties' => 'صعوبة استيعاب',
        ]);

        $service->recordSession($this->student, $this->admin, [
            'reason_category' => CounselingSessionReason::PsychologicalSupport,
            'status' => CounselingSessionStatus::Resolved,
            'observations' => 'جلسة دعم نفسي واستماع.',
        ]);

        $kpis = $service->computeCounselingKpis('2026/2027');

        $this->assertSame(1, $kpis['total_students']);
        $this->assertSame(1, $kpis['orphan_count']);
        $this->assertSame(1, $kpis['social_grant_count']);
        $this->assertSame(1, $kpis['chronic_count']);
        $this->assertSame(1, $kpis['pe_exemption_count']);
        $this->assertSame(1, $kpis['difficulties_count']);
        $this->assertSame(1, $kpis['total_sessions']);
        $this->assertSame(1, $kpis['resolved_sessions']);
        $this->assertEquals(100.0, $kpis['resolution_rate']);
    }

    public function test_service_assemble_student_dossier_data(): void
    {
        $service = app(StudentCounselingService::class);
        $data = $service->assembleStudentDossierData($this->student);

        $this->assertArrayHasKey('student', $data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('sessions', $data);
        $this->assertArrayHasKey('school', $data);
        $this->assertSame($this->student->id, $data['student']->id);
    }

    public function test_print_routes_accessible_by_admin_and_authorized_teacher(): void
    {
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
        ]);

        $session = CounselingSession::factory()->create([
            'student_id' => $this->student->id,
            'counselor_id' => $this->admin->id,
            'academic_year' => '2026/2027',
            'reason_category' => CounselingSessionReason::AcademicDifficulty,
            'status' => CounselingSessionStatus::Open,
        ]);

        // Admin Access
        $this->actingAs($this->admin);

        $this->get(route('print.counseling.dossier', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertSee('بطاقة المتابعة والتوجيه النفسي والتربوي للتلميذ')
            ->assertSee($this->student->full_name);

        $this->get(route('print.counseling.session', ['locale' => 'ar', 'session' => $session->id]))
            ->assertOk()
            ->assertSee('محضر وتقرير مقابلة إرشادية فردية');

        $this->get(route('print.counseling.ledger', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('سجل المتابعات النفسية والتربوية والمقابلات الإرشادية السنوي');

        // Teacher scheduled for this student's classroom has access to dossier and session
        $this->actingAs($this->teacher);

        $this->get(route('print.counseling.dossier', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk();

        $this->get(route('print.counseling.session', ['locale' => 'ar', 'session' => $session->id]))
            ->assertOk();

        // But teacher is forbidden from annual institutional register ledger
        $this->get(route('print.counseling.ledger', ['locale' => 'ar']))
            ->assertForbidden();

        // Another teacher without schedule is forbidden
        $otherTeacher = User::factory()->teacher()->create();
        $this->actingAs($otherTeacher);

        $this->get(route('print.counseling.dossier', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertForbidden();
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $this->actingAs($this->admin);

        $session = CounselingSession::factory()->create([
            'student_id' => $this->student->id,
            'counselor_id' => $this->admin->id,
            'academic_year' => '2026/2027',
        ]);

        $this->mock(StudentCounselingService::class, function ($mock) use ($session) {
            $mock->shouldReceive('generateDossierPdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $this->student->id))
                ->andReturn('%PDF-1.4 mock dossier document');

            $mock->shouldReceive('generateSessionPdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $session->id))
                ->andReturn('%PDF-1.4 mock session document');

            $mock->shouldReceive('generateAnnualLedgerPdf')
                ->once()
                ->andReturn('%PDF-1.4 mock ledger document');
        });

        $resp1 = $this->get(route('print.counseling.dossier.pdf', ['locale' => 'ar', 'student' => $this->student->id]));
        $resp1->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $resp2 = $this->get(route('print.counseling.session.pdf', ['locale' => 'ar', 'session' => $session->id]));
        $resp2->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $resp3 = $this->get(route('print.counseling.ledger.pdf', ['locale' => 'ar']));
        $resp3->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_filament_student_counseling_hub_livewire_page(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StudentCounselingHub::class)
            ->assertSuccessful()
            ->set('activeTab', 'profiles')
            ->call('openProfileModal', $this->student->id)
            ->assertSet('showProfileModal', true)
            ->set('familyStatus', FamilyStatus::OrphanFather->value)
            ->set('chronicConditionsText', 'ربو تحسسي')
            ->set('socialGrant', true)
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('showProfileModal', false)
            ->set('activeTab', 'sessions')
            ->call('openSessionModal', $this->student->id)
            ->assertSet('showSessionModal', true)
            ->set('sessionReason', CounselingSessionReason::AcademicDifficulty->value)
            ->set('sessionIntervieweeType', 'student_only')
            ->set('sessionObservations', 'متابعة بيداغوجية حول التحصيل في الرياضيات.')
            ->call('saveSession')
            ->assertHasNoErrors()
            ->assertSet('showSessionModal', false);

        $profile = StudentCounselingProfile::where('student_id', $this->student->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame(FamilyStatus::OrphanFather, $profile->family_status);
        $this->assertTrue($profile->social_grant);

        $session = CounselingSession::where('student_id', $this->student->id)->first();
        $this->assertNotNull($session);
        $this->assertSame(CounselingSessionReason::AcademicDifficulty, $session->reason_category);

        // Toggle status action
        Livewire::test(StudentCounselingHub::class)
            ->call('toggleSessionStatus', $session->id, CounselingSessionStatus::Resolved->value)
            ->assertHasNoErrors();

        $this->assertSame(CounselingSessionStatus::Resolved, $session->fresh()->status);
    }

    public function test_backup_and_restore_services_include_counseling_tables(): void
    {
        $backupService = app(SchoolBackupService::class);
        $this->assertContains('student_counseling_profiles', $backupService->getTables());
        $this->assertContains('counseling_sessions', $backupService->getTables());

        $restoreService = app(SchoolRestoreService::class);
        $reflector = new \ReflectionClass($restoreService);
        $property = $reflector->getProperty('tables');
        $property->setAccessible(true);
        $allowed = $property->getValue($restoreService);

        $this->assertContains('student_counseling_profiles', $allowed);
        $this->assertContains('counseling_sessions', $allowed);
    }
}
