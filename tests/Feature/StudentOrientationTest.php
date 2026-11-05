<?php

namespace Tests\Feature;

use App\Enums\OrientationAppealStatus;
use App\Enums\OrientationStream;
use App\Enums\Role;
use App\Filament\Pages\OrientationHub;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentOrientation;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolRestoreService;
use App\Services\StudentOrientationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Browsershot\Browsershot;
use Tests\TestCase;

class StudentOrientationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected Level $level4am;

    protected Classroom $classroom4am;

    protected Group $group4am;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'academic_year' => '2026/2027',
            'school_name' => 'متوسطة الاختبار النموذجية',
        ]);

        $phase = EducationPhase::factory()->create(['name' => 'التعليم المتوسط']);
        $this->level4am = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'name' => 'السنة الرابعة متوسط',
            'grade_number' => 4,
        ]);

        $this->classroom4am = Classroom::factory()->create([
            'level_id' => $this->level4am->id,
            'name' => '4AM-1',
        ]);

        $this->group4am = Group::factory()->create([
            'classroom_id' => $this->classroom4am->id,
            'name' => 'الفوج 1',
        ]);

        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->teacher = User::factory()->create(['role' => Role::Teacher]);

        $this->student = Student::factory()->create([
            'group_id' => $this->group4am->id,
            'first_name' => 'ياسين',
            'last_name' => 'بلقاسم',
            'is_active' => true,
        ]);
    }

    public function test_student_orientation_model_factory_and_scopes(): void
    {
        $orientation = StudentOrientation::factory()->create([
            'student_id' => $this->student->id,
            'academic_year' => '2026/2027',
            'scientific_profile_score' => 15.50,
            'literary_profile_score' => 12.00,
            'annual_general_average' => 14.20,
            'first_choice' => OrientationStream::ScienceTech,
            'final_decision' => OrientationStream::ScienceTech,
            'is_admitted' => true,
        ]);

        $this->assertTrue($orientation->student->is($this->student));
        $this->assertEquals(OrientationStream::ScienceTech, $orientation->first_choice);
        $this->assertEquals(OrientationStream::ScienceTech, $orientation->recommendedStream());
        $this->assertEquals(3.50, $orientation->profileGap());
        $this->assertTrue($orientation->hasSatisfiedWish());

        $this->assertCount(1, StudentOrientation::forAcademicYear('2026/2027')->get());
        $this->assertCount(1, StudentOrientation::admitted()->get());
        $this->assertCount(1, StudentOrientation::byStream(OrientationStream::ScienceTech)->get());
    }

    public function test_service_computes_student_profile_scores(): void
    {
        $math = Subject::factory()->create(['name' => 'الرياضيات']);
        $physics = Subject::factory()->create(['name' => 'العلوم الفيزيائية والتكنولوجيا']);
        $arabic = Subject::factory()->create(['name' => 'اللغة العربية']);

        $schedMath = Schedule::factory()->create([
            'classroom_id' => $this->classroom4am->id,
            'subject_id' => $math->id,
        ]);
        $schedPhys = Schedule::factory()->create([
            'classroom_id' => $this->classroom4am->id,
            'subject_id' => $physics->id,
        ]);
        $schedArab = Schedule::factory()->create([
            'classroom_id' => $this->classroom4am->id,
            'subject_id' => $arabic->id,
        ]);

        $assMath = Assessment::factory()->create(['schedule_id' => $schedMath->id, 'max_score' => 20]);
        $assPhys = Assessment::factory()->create(['schedule_id' => $schedPhys->id, 'max_score' => 20]);
        $assArab = Assessment::factory()->create(['schedule_id' => $schedArab->id, 'max_score' => 20]);

        Grade::factory()->create(['assessment_id' => $assMath->id, 'student_id' => $this->student->id, 'score' => 16.0]);
        Grade::factory()->create(['assessment_id' => $assPhys->id, 'student_id' => $this->student->id, 'score' => 14.0]);
        Grade::factory()->create(['assessment_id' => $assArab->id, 'student_id' => $this->student->id, 'score' => 11.0]);

        $service = app(StudentOrientationService::class);
        $profile = $service->computeStudentProfile($this->student, '2026/2027');

        $this->assertEquals(15.0, $profile['scientific_score']);
        $this->assertEquals(11.0, $profile['literary_score']);
        $this->assertEquals(OrientationStream::ScienceTech, $profile['recommended_stream']);
        $this->assertEquals(4.0, $profile['gap']);
    }

    public function test_service_wishes_and_pre_orientation(): void
    {
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($this->student, '2026/2027');

        $service->updateWishes($orientation, [
            'first_choice' => 'arts_humanities',
            'second_choice' => 'science_tech',
            'guardian_approved' => true,
            'guardian_notes' => 'رغبة مؤكدة من التلميذ والولي',
        ]);

        $orientation->refresh();
        $this->assertEquals(OrientationStream::ArtsHumanities, $orientation->first_choice);
        $this->assertTrue($orientation->guardian_approved);

        $service->recordPreOrientation($orientation, OrientationStream::ArtsHumanities, 'موافقة مبدئية');
        $orientation->refresh();
        $this->assertEquals(OrientationStream::ArtsHumanities, $orientation->pre_orientation_choice);
    }

    public function test_service_deliberation_and_admission_rules(): void
    {
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($this->student, '2026/2027');
        $orientation->update(['annual_general_average' => 9.50]);

        // Case 1: BEM score >= 10.0 gives direct admission
        $service->recordFinalDecision($orientation, OrientationStream::ScienceTech, 11.0, 'نجاح في BEM');
        $orientation->refresh();
        $this->assertTrue($orientation->is_admitted);
        $this->assertEquals(OrientationStream::ScienceTech, $orientation->final_decision);

        // Case 2: BEM score 9.0 and Annual 9.50 gives rescue average 9.25 (not admitted)
        $service->recordFinalDecision($orientation, OrientationStream::RepeatYear, 9.0, 'دون معدل الإنقاذ');
        $orientation->refresh();
        $this->assertFalse($orientation->is_admitted);
        $this->assertEquals(OrientationStream::RepeatYear, $orientation->final_decision);

        // Case 3: BEM score 9.50 and Annual 11.50 gives rescue average 10.50 (admitted)
        $orientation->update(['annual_general_average' => 11.50]);
        $service->recordFinalDecision($orientation, OrientationStream::ArtsHumanities, 9.50, 'قبول بمعدل الإنقاذ');
        $orientation->refresh();
        $this->assertTrue($orientation->is_admitted);
        $this->assertEquals(10.50, $orientation->admission_average);
    }

    public function test_service_appeal_flow(): void
    {
        $service = app(StudentOrientationService::class);
        $orientation = $service->getOrCreateOrientation($this->student, '2026/2027');
        $orientation->update(['final_decision' => OrientationStream::ArtsHumanities]);

        // Submit appeal
        $service->recordAppeal($orientation, OrientationAppealStatus::Submitted, null, 'طلب إعادة التوجيه إلى العلوم');
        $orientation->refresh();
        $this->assertEquals(OrientationAppealStatus::Submitted, $orientation->appeal_status);

        // Accept appeal
        $service->recordAppeal($orientation, OrientationAppealStatus::Accepted, OrientationStream::ScienceTech, 'قرار اللجنة الولائية رقم 45');
        $orientation->refresh();
        $this->assertEquals(OrientationAppealStatus::Accepted, $orientation->appeal_status);
        $this->assertEquals(OrientationStream::ScienceTech, $orientation->effectiveDecision());
    }

    public function test_kpis_computation(): void
    {
        $service = app(StudentOrientationService::class);

        // Student 1
        $or1 = $service->getOrCreateOrientation($this->student, '2026/2027');
        $service->updateWishes($or1, ['first_choice' => 'science_tech', 'guardian_approved' => true]);
        $service->recordFinalDecision($or1, OrientationStream::ScienceTech, 14.0);

        // Student 2
        $st2 = Student::factory()->create(['group_id' => $this->group4am->id, 'is_active' => true]);
        $or2 = $service->getOrCreateOrientation($st2, '2026/2027');
        $service->recordFinalDecision($or2, OrientationStream::ArtsHumanities, 11.0);

        $kpis = $service->getOrientationKpis('2026/2027');

        $this->assertEquals(2, $kpis['total_candidates']);
        $this->assertEquals(2, $kpis['deliberated_count']);
        $this->assertEquals(1, $kpis['streams']['science_tech']['count']);
        $this->assertEquals(1, $kpis['streams']['arts_humanities']['count']);
        $this->assertEquals(2, $kpis['admitted_count']);
        $this->assertEquals(100.0, $kpis['admission_rate']);
    }

    public function test_print_wishes_and_notification_routes_accessible_by_admin_and_teacher(): void
    {
        // Teacher assigned to 4AM classroom schedule
        Schedule::factory()->create([
            'classroom_id' => $this->classroom4am->id,
            'user_id' => $this->teacher->id,
        ]);

        // Admin can view wishes card
        $this->actingAs($this->admin)
            ->get(route('print.orientation.wishes', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertSee('بطاقة الرغبات للتوجيه')
            ->assertSee($this->student->full_name);

        // Teacher can view wishes card
        $this->actingAs($this->teacher)
            ->get(route('print.orientation.wishes', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertSee($this->student->full_name);

        // Admin can view notification
        $this->actingAs($this->admin)
            ->get(route('print.orientation.notification', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertSee('إشعار رسمي بقرار مجلس القبول والتوجيه');

        // Admin can view PV
        $this->actingAs($this->admin)
            ->get(route('print.orientation.pv', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('محضر جلسة مجلس القبول والتوجيه');

        // Non-assigned teacher is forbidden from PV
        $unrelatedTeacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.orientation.pv', ['locale' => 'ar']))
            ->assertForbidden();
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $mock = \Mockery::mock('overload:'.Browsershot::class);
        $mock->shouldReceive('html->format->showBackground->margins->pdf')
            ->andReturn('%PDF-1.4 Mocked Wishes PDF');
        $mock->shouldReceive('html->format->landscape->showBackground->margins->pdf')
            ->andReturn('%PDF-1.4 Mocked PV PDF');

        $this->actingAs($this->admin)
            ->get(route('print.orientation.wishes.pdf', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get(route('print.orientation.notification.pdf', ['locale' => 'ar', 'student' => $this->student->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get(route('print.orientation.pv.pdf', ['locale' => 'ar']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_filament_orientation_hub_livewire_page(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(OrientationHub::class)
            ->assertOk()
            ->assertSet('activeTab', 'wishes')
            ->call('setTab', 'deliberations')
            ->assertSet('activeTab', 'deliberations')
            ->call('setTab', 'appeals')
            ->assertSet('activeTab', 'appeals')
            ->call('openWishesModal', $this->student->id)
            ->assertSet('isWishesModalOpen', true)
            ->set('editFirstChoice', 'arts_humanities')
            ->set('editGuardianApproved', true)
            ->call('saveWishes')
            ->assertSet('isWishesModalOpen', false)
            ->call('openDeliberationModal', $this->student->id)
            ->assertSet('isDeliberationModalOpen', true)
            ->set('deliberatingDecision', 'arts_humanities')
            ->set('deliberatingBemScore', 13.50)
            ->call('saveDeliberation')
            ->assertSet('isDeliberationModalOpen', false);

        $orientation = StudentOrientation::where('student_id', $this->student->id)->first();
        $this->assertNotNull($orientation);
        $this->assertEquals(OrientationStream::ArtsHumanities, $orientation->first_choice);
        $this->assertEquals(OrientationStream::ArtsHumanities, $orientation->final_decision);
        $this->assertTrue($orientation->is_admitted);
    }

    public function test_backup_and_restore_services_include_student_orientations(): void
    {
        $backup = app(SchoolBackupService::class);
        $this->assertContains('student_orientations', $backup->getTables());

        $restore = app(SchoolRestoreService::class);
        $ref = new \ReflectionClass($restore);
        $tablesProp = $ref->getProperty('tables');
        $tablesProp->setAccessible(true);
        $this->assertContains('student_orientations', $tablesProp->getValue($restore));
    }
}
