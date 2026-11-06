<?php

namespace Tests\Feature;

use App\Enums\BloodType;
use App\Enums\ChronicConditionType;
use App\Enums\DentalHygieneStatus;
use App\Enums\EpsDispensationStatus;
use App\Enums\HearingCondition;
use App\Enums\Role;
use App\Filament\Pages\SchoolHealthHub;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolHealthService;
use App\Services\SchoolRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $supervisor;

    private User $teacher;

    private Student $student;

    private SchoolSetting $schoolSetting;

    private SchoolHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ar');

        $this->schoolSetting = SchoolSetting::create([
            'academic_year' => '2026/2027',
            'school_name' => 'متوسطة الشهيد العربي بن مهيدي',
            'directorate_name' => 'مديرية التربية لولاية الوادي',
        ]);

        $this->admin = User::factory()->create([
            'role' => Role::Principal->value,
        ]);

        $this->supervisor = User::factory()->create([
            'role' => Role::GeneralSupervisor->value,
        ]);

        $this->teacher = User::factory()->create([
            'role' => Role::Teacher->value,
        ]);

        $this->student = Student::factory()->create([
            'first_name' => 'أحمد',
            'last_name' => 'بشير',
            'gender' => 'male',
        ]);

        $this->healthService = app(SchoolHealthService::class);
    }

    public function test_health_service_records_screening_and_computes_bmi_properly(): void
    {
        $record = $this->healthService->recordScreening(
            student: $this->student,
            data: [
                'screening_date' => now()->toDateString(),
                'doctor_name' => 'د. بوعلام حكيم',
                'uds_name' => 'وحدة الكشف والمتابعة',
                'blood_type' => BloodType::OPositive,
                'weight_kg' => 45.0,
                'height_cm' => 150.0,
                'visual_acuity_right' => '10/10',
                'visual_acuity_left' => '9/10',
                'wears_glasses' => false,
                'hearing_condition' => HearingCondition::Normal,
                'dental_hygiene' => DentalHygieneStatus::Good,
                'dental_caries_count' => 0,
            ]
        );

        $this->assertInstanceOf(StudentMedicalRecord::class, $record);
        $this->assertNotNull($record->screening_date);
        $this->assertSame(45.0, (float) $record->weight_kg);
        $this->assertSame(150.0, (float) $record->height_cm);
        $this->assertEquals(20.0, $record->bmi, '', 0.1);
        $this->assertStringContainsString('طبيعي', $record->bmi_classification);
    }

    public function test_health_service_manages_chronic_condition_and_pai(): void
    {
        $record = $this->healthService->updateChronicCondition(
            student: $this->student,
            data: [
                'has_chronic_illness' => true,
                'chronic_condition_type' => ChronicConditionType::Asthma,
                'chronic_illness_details' => 'ربو تحسسي مع أزمات تنفسية',
                'emergency_protocol_notes' => 'استعمال بخاخ الفينتولين جرعتين عند الشعور بضيق التنفس',
                'emergency_medication_at_school' => true,
            ]
        );

        $this->assertTrue($record->has_chronic_illness);
        $this->assertSame(ChronicConditionType::Asthma, $record->chronic_condition_type);
        $this->assertTrue($record->emergency_medication_at_school);
        $this->assertSame('ربو تحسسي مع أزمات تنفسية', $record->chronic_illness_details);
    }

    public function test_health_service_grants_and_verifies_eps_dispensation(): void
    {
        $this->assertFalse($this->student->isEpsDispensed());

        $record = $this->healthService->grantEpsDispensation(
            student: $this->student,
            status: EpsDispensationStatus::Temporary,
            data: [
                'eps_dispensation_reason' => 'كسر حديث في الذراع الأيسر يستوجب الراحة',
                'eps_dispensation_start_date' => now()->toDateString(),
                'eps_dispensation_end_date' => now()->addDays(30)->toDateString(),
                'eps_medical_certificate_ref' => 'CERT-2026-042',
            ]
        );

        $this->assertTrue($record->isExemptFromEps());
        $this->assertSame(EpsDispensationStatus::Temporary, $record->eps_dispensation_status);
        $this->assertSame('CERT-2026-042', $record->eps_medical_certificate_ref);
    }

    public function test_health_service_aggregates_summary_stats_and_bilan(): void
    {
        // Student 1: Screened, Normal, Fit
        StudentMedicalRecord::factory()->create([
            'student_id' => $this->student->id,
            'academic_year' => '2026/2027',
            'screening_date' => now()->toDateString(),
            'wears_glasses' => true,
            'dental_caries_count' => 2,
            'has_chronic_illness' => false,
            'eps_dispensation_status' => EpsDispensationStatus::Fit,
        ]);

        // Student 2: Screened, Asthma, EPS Annual Dispensed
        $student2 = Student::factory()->create(['gender' => 'female']);
        StudentMedicalRecord::factory()->create([
            'student_id' => $student2->id,
            'academic_year' => '2026/2027',
            'screening_date' => now()->toDateString(),
            'has_chronic_illness' => true,
            'chronic_condition_type' => ChronicConditionType::Asthma,
            'emergency_medication_at_school' => true,
            'eps_dispensation_status' => EpsDispensationStatus::Annual,
        ]);

        $overview = $this->healthService->getHealthOverview('2026/2027');
        $this->assertSame(2, $overview['screened_count']);
        $this->assertSame(1, $overview['glasses_count']);
        $this->assertSame(2, $overview['caries_total']);
        $this->assertSame(1, $overview['chronic_count']);
        $this->assertSame(1, $overview['eps_exempt_count']);
        $this->assertSame(1, $overview['emergency_med_count']);

        $this->assertArrayHasKey('chronic_by_type', $overview);
        $this->assertArrayHasKey('eps_by_status', $overview);
        $this->assertArrayHasKey('level_breakdown', $overview);
    }

    public function test_school_health_controller_previews_are_accessible_to_admin_and_staff(): void
    {
        $record = StudentMedicalRecord::factory()->create([
            'student_id' => $this->student->id,
            'academic_year' => '2026/2027',
            'screening_date' => now()->toDateString(),
            'eps_dispensation_status' => EpsDispensationStatus::Annual,
            'eps_dispensation_reason' => 'عجز صحي معتمد',
        ]);

        // 1. Admin accessing Student Dossier Preview
        $response = $this->actingAs($this->admin)
            ->get(route('print.school-health.student-dossier', ['locale' => 'ar', 'record' => $record->id]));
        $response->assertOk()
            ->assertViewIs('print.student-medical-dossier')
            ->assertSee($this->student->first_name)
            ->assertSee('بطاقة الفحص الطبي والمتابعة الصحية');

        // 2. Education Staff (General Supervisor) accessing EPS Dispensation Preview
        $response = $this->actingAs($this->supervisor)
            ->get(route('print.school-health.eps-dispensation', ['locale' => 'ar', 'record' => $record->id]));
        $response->assertOk()
            ->assertViewIs('print.eps-medical-dispensation')
            ->assertSee('شهادة الإعفاء الطبي من ممارسة التربية البدنية والرياضية')
            ->assertSee($this->student->full_name);

        // 3. Admin accessing Annual UDS Bilan Preview
        $response = $this->actingAs($this->admin)
            ->get(route('print.school-health.annual-uds-bilan', ['locale' => 'ar', 'year' => '2026/2027']));
        $response->assertOk()
            ->assertViewIs('print.annual-uds-bilan')
            ->assertSee('الحصيلة الإحصائية السنوية للصحة المدرسية (UDS)');

        // 4. Unauthorized teacher trying to view official health bilan preview should be forbidden (403)
        $this->actingAs($this->teacher)
            ->get(route('print.school-health.annual-uds-bilan', ['locale' => 'ar']))
            ->assertForbidden();
    }

    public function test_school_health_hub_renders_and_switches_tabs(): void
    {
        StudentMedicalRecord::factory()->create([
            'student_id' => $this->student->id,
            'academic_year' => '2026/2027',
            'screening_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(SchoolHealthHub::class)
            ->assertOk()
            ->assertSee('منظومة الصحة المدرسية ووحدة الكشف والمتابعة (UDS)')
            ->assertSet('activeTab', 'screenings')
            ->set('activeTab', 'chronic')
            ->assertSet('activeTab', 'chronic')
            ->set('activeTab', 'eps')
            ->assertSet('activeTab', 'eps')
            ->set('activeTab', 'uds_bilan')
            ->assertSet('activeTab', 'uds_bilan');
    }

    public function test_school_health_hub_modal_actions_save_records(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SchoolHealthHub::class)
            ->call('openScreeningModal', $this->student->id)
            ->set('weightKg', 48.5)
            ->set('heightCm', 155.0)
            ->set('bloodType', BloodType::APositive->value)
            ->set('visualAcuityRight', '10/10')
            ->set('visualAcuityLeft', '10/10')
            ->set('wearsGlasses', false)
            ->set('dentalHygiene', DentalHygieneStatus::Good->value)
            ->call('saveScreening')
            ->assertHasNoErrors()
            ->assertNotified();

        $this->assertDatabaseHas('student_medical_records', [
            'student_id' => $this->student->id,
            'weight_kg' => 48.5,
            'height_cm' => 155.0,
            'blood_type' => BloodType::APositive->value,
        ]);
    }

    public function test_student_medical_records_table_is_registered_in_backup_and_restore(): void
    {
        $backupService = app(SchoolBackupService::class);
        $restoreService = app(SchoolRestoreService::class);

        $this->assertContains('student_medical_records', $backupService->getTables());
        $this->assertContains('student_medical_records', $restoreService->getTables());
    }
}
