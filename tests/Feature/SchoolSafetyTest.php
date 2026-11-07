<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SafetyDrillScenario;
use App\Enums\SafetyEquipmentStatus;
use App\Enums\SafetyEquipmentType;
use App\Enums\SafetyIncidentSeverity;
use App\Enums\SafetyIncidentStatus;
use App\Enums\SafetyIncidentType;
use App\Enums\SafetyInterventionTeamType;
use App\Filament\Pages\SchoolSafetyHub;
use App\Models\SafetyEmergencyPlan;
use App\Models\SafetyEvacuationDrill;
use App\Models\SafetyFireEquipment;
use App\Models\SafetyInterventionTeam;
use App\Models\SchoolSafetyIncident;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolSafetyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'name' => 'متوسطة العربي التبسي',
            'wilaya' => 'الجزائر',
            'daira' => 'الجزائر الوسطى',
            'commune' => 'سيدي امحمد',
            'establishment_code' => '16001',
        ]);
    }

    public function test_safety_tables_exist(): void
    {
        $tables = [
            'safety_emergency_plans',
            'safety_intervention_teams',
            'school_safety_incidents',
            'safety_fire_equipment',
            'safety_evacuation_drills',
            'safety_committee_meetings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist.");
        }
    }

    public function test_safety_enums_and_labels(): void
    {
        $this->assertNotEmpty(SafetyIncidentType::StudentAccident->label());
        $this->assertNotEmpty(SafetyIncidentType::StudentAccident->badgeColor());

        $this->assertNotEmpty(SafetyIncidentSeverity::Severe->label());
        $this->assertNotEmpty(SafetyIncidentSeverity::Severe->badgeColor());

        $this->assertNotEmpty(SafetyIncidentStatus::Declared->label());
        $this->assertNotEmpty(SafetyIncidentStatus::Declared->badgeColor());

        $this->assertNotEmpty(SafetyEquipmentType::ExtinguisherPowder->label());
        $this->assertNotEmpty(SafetyEquipmentType::ExtinguisherPowder->badgeColor());

        $this->assertNotEmpty(SafetyEquipmentStatus::Operational->label());
        $this->assertNotEmpty(SafetyEquipmentStatus::Operational->badgeColor());

        $this->assertNotEmpty(SafetyDrillScenario::Earthquake->label());
        $this->assertNotEmpty(SafetyDrillScenario::Earthquake->badgeColor());

        $this->assertNotEmpty(SafetyInterventionTeamType::FirstAid->label());
        $this->assertNotEmpty(SafetyInterventionTeamType::FirstAid->badgeColor());
    }

    public function test_user_can_manage_school_safety_permissions(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $dev = User::factory()->create(['role' => Role::Developer]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($principal->canManageSchoolSafety());
        $this->assertTrue($supervisor->canManageSchoolSafety());
        $this->assertTrue($censor->canManageSchoolSafety());
        $this->assertTrue($bursar->canManageSchoolSafety());
        $this->assertTrue($dev->canManageSchoolSafety());

        $this->assertFalse($teacher->canManageSchoolSafety());
        $this->assertFalse($counselor->canManageSchoolSafety());
    }

    public function test_service_generates_sequential_codes(): void
    {
        $service = app(SchoolSafetyService::class);

        $incidentCode = $service->generateIncidentNumber(2026);
        $this->assertEquals('ACC/2026/001', $incidentCode);

        $planCode = $service->generatePlanCode(2026);
        $this->assertEquals('PSI-2026-01', $planCode);

        $equipCode = $service->generateEquipmentCode('EXT');
        $this->assertEquals('EXT-001', $equipCode);

        $drillCode = $service->generateDrillCode(2026);
        $this->assertEquals('DRIL-2026-01', $drillCode);

        $sessionCode = $service->generateSessionCode(2026);
        $this->assertEquals('CHS-2026-01', $sessionCode);
    }

    public function test_service_declares_accident_and_links_student(): void
    {
        $service = app(SchoolSafetyService::class);
        $student = Student::factory()->create([
            'first_name' => 'بلقاسم',
            'last_name' => 'بن عمار',
        ]);

        $incident = $service->declareAccident([
            'academic_year' => '2026/2027',
            'incident_type' => SafetyIncidentType::StudentAccident->value,
            'severity_level' => SafetyIncidentSeverity::Severe->value,
            'incident_date' => Carbon::now(),
            'location' => 'الملعب الرياضي',
            'victim_type' => 'student',
            'student_id' => $student->id,
            'circumstances_description' => 'سقوط أثناء تمرين الوثب الطويل في حصة التربية البدنية',
            'bodily_injuries_description' => 'التواء حاد في الكاحل مع ورم',
            'hospital_evacuation' => true,
            'hospital_name' => 'العيادة الجوارية الاستعجالية',
            'temporary_incapacity_days' => 5,
        ]);

        $this->assertInstanceOf(SchoolSafetyIncident::class, $incident);
        $this->assertEquals('ACC/2026/001', $incident->incident_number);
        $this->assertEquals($student->full_name, $incident->victim_name);
        $this->assertTrue($incident->hospital_evacuation);
        $this->assertDatabaseHas('school_safety_incidents', [
            'id' => $incident->id,
            'student_id' => $student->id,
            'incident_number' => 'ACC/2026/001',
        ]);
    }

    public function test_service_updates_accident_status_and_marks_declared(): void
    {
        $service = app(SchoolSafetyService::class);
        $incident = $service->declareAccident([
            'academic_year' => '2026/2027',
            'incident_type' => SafetyIncidentType::StudentAccident->value,
            'severity_level' => SafetyIncidentSeverity::Moderate->value,
            'incident_date' => Carbon::now(),
            'location' => 'الفناء الرئيسي',
            'victim_type' => 'student',
            'victim_name' => 'طارق زياني',
            'circumstances_description' => 'تعثر أثناء الاستراحة',
            'status' => SafetyIncidentStatus::InTreatment->value,
        ]);

        $this->assertFalse($incident->insurance_declared);

        $updated = $service->updateAccidentStatus($incident, SafetyIncidentStatus::Declared);

        $this->assertEquals(SafetyIncidentStatus::Declared, $updated->status);
        $this->assertTrue($updated->insurance_declared);
        $this->assertTrue($updated->directorate_notified);
        $this->assertNotNull($updated->declaration_date);
    }

    public function test_service_creates_or_updates_safety_plan_and_adds_intervention_member(): void
    {
        $service = app(SchoolSafetyService::class);

        $plan = $service->createOrUpdateSafetyPlan([
            'academic_year' => '2026/2027',
            'title' => 'مخطط الأمن الداخلي 2026',
            'crisis_cell_leader' => 'مدير المؤسسة',
            'evacuation_coordinator' => 'مستشار التربية',
        ]);

        $this->assertInstanceOf(SafetyEmergencyPlan::class, $plan);
        $this->assertCount(2, $plan->assembly_points);

        $member = $service->addInterventionMember($plan, [
            'team_type' => SafetyInterventionTeamType::FirstAid->value,
            'member_name' => 'سمير قدور',
            'institution_role' => 'أستاذ علوم طبيعية',
            'assigned_zone' => 'المخابر والجناح ب',
            'is_certified_first_aider' => true,
        ]);

        $this->assertInstanceOf(SafetyInterventionTeam::class, $member);
        $this->assertTrue($member->is_certified_first_aider);
        $this->assertEquals($plan->id, $member->safety_plan_id);
    }

    public function test_service_registers_equipment_and_updates_inspection(): void
    {
        $service = app(SchoolSafetyService::class);

        $equipment = $service->registerEquipment([
            'equipment_type' => SafetyEquipmentType::ExtinguisherPowder->value,
            'exact_location' => 'غرفة التدفئة المركزية Chaufferie',
            'last_inspection_date' => Carbon::today()->subMonths(2),
            'next_inspection_due_date' => Carbon::today()->addMonths(10),
            'inspection_status' => SafetyEquipmentStatus::Operational->value,
        ]);

        $this->assertInstanceOf(SafetyFireEquipment::class, $equipment);
        $this->assertEquals('EXT-001', $equipment->equipment_code);
        $this->assertTrue($equipment->isOperational());
        $this->assertFalse($equipment->isExpiredOrDue());

        // Update inspection
        $updated = $service->updateEquipmentInspection(
            $equipment,
            SafetyEquipmentStatus::NeedsRefill,
            Carbon::today()->addMonths(6),
            'مؤسسة صيانة الإطفاء المعتمدة'
        );

        $this->assertEquals(SafetyEquipmentStatus::NeedsRefill, $updated->inspection_status);
        $this->assertFalse($updated->isOperational());
    }

    public function test_service_records_evacuation_drill_and_formats_duration(): void
    {
        $service = app(SchoolSafetyService::class);

        $drill = $service->recordEvacuationDrill([
            'academic_year' => '2026/2027',
            'drill_date' => Carbon::today(),
            'drill_time' => '10:15',
            'simulation_scenario' => SafetyDrillScenario::Earthquake->value,
            'evacuation_time_seconds' => 165, // 2m 45s
            'students_evacuated_count' => 320,
            'staff_evacuated_count' => 25,
            'assembly_zone_used' => 'الساحة المركزية',
            'evaluation_rating' => 'excellent',
        ]);

        $this->assertInstanceOf(SafetyEvacuationDrill::class, $drill);
        $this->assertEquals(345, $drill->total_persons_evacuated);
        $this->assertEquals('2 د 45 ث', $drill->formattedDuration());
    }

    public function test_service_records_committee_meeting(): void
    {
        $service = app(SchoolSafetyService::class);

        $meeting = $service->recordCommitteeMeeting([
            'academic_year' => '2026/2027',
            'meeting_date' => Carbon::today(),
            'session_type' => 'ordinary_term_1',
            'meeting_chair' => 'مدير المؤسسة',
            'agenda_items' => 'فحص شبكة التدفئة وطفايات الحريق ومخارج الطوارئ',
        ]);

        $this->assertDatabaseHas('safety_committee_meetings', [
            'id' => $meeting->id,
            'session_code' => 'CHS-2026-01',
        ]);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolSafetyService::class);
        $incident = $service->declareAccident([
            'academic_year' => '2026/2027',
            'incident_type' => SafetyIncidentType::StudentAccident->value,
            'severity_level' => SafetyIncidentSeverity::Moderate->value,
            'incident_date' => Carbon::now(),
            'location' => 'الفناء',
            'victim_type' => 'student',
            'victim_name' => 'ياسين بلخير',
            'circumstances_description' => 'سقوط عارض',
        ]);

        $data = $service->assembleAccidentDeclarationData($incident);
        $this->assertArrayHasKey('incident', $data);
        $this->assertArrayHasKey('institution_name', $data);

        $drill = $service->recordEvacuationDrill([
            'academic_year' => '2026/2027',
            'drill_date' => Carbon::today(),
            'drill_time' => '10:00',
            'simulation_scenario' => SafetyDrillScenario::FireOutbreak->value,
            'evacuation_time_seconds' => 120,
            'assembly_zone_used' => 'الساحة',
        ]);

        $drillData = $service->assembleEvacuationDrillPvData($drill);
        $this->assertArrayHasKey('drill', $drillData);
        $this->assertEquals('2 د 00 ث', $drillData['formatted_duration']);

        $regData = $service->assembleSafetyEquipmentRegisterData();
        $this->assertArrayHasKey('equipments', $regData);
    }

    public function test_print_accident_declaration_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolSafetyService::class);
        $incident = $service->declareAccident([
            'academic_year' => '2026/2027',
            'incident_type' => SafetyIncidentType::StudentAccident->value,
            'severity_level' => SafetyIncidentSeverity::Moderate->value,
            'incident_date' => Carbon::now(),
            'location' => 'الفناء',
            'victim_type' => 'student',
            'victim_name' => 'ياسين بلخير',
            'circumstances_description' => 'سقوط عارض',
        ]);

        $response = $this->actingAs($principal)
            ->get(route('print.safety.accident-declaration', ['locale' => 'ar', 'incident' => $incident->id]));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.safety.accident-declaration.pdf', ['locale' => 'ar', 'incident' => $incident->id]));
        $pdfResponse->assertStatus(200);
    }

    public function test_print_drill_pv_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolSafetyService::class);
        $drill = $service->recordEvacuationDrill([
            'academic_year' => '2026/2027',
            'drill_date' => Carbon::today(),
            'drill_time' => '10:00',
            'simulation_scenario' => SafetyDrillScenario::FireOutbreak->value,
            'evacuation_time_seconds' => 120,
            'assembly_zone_used' => 'الساحة',
        ]);

        $response = $this->actingAs($principal)
            ->get(route('print.safety.drill-pv', ['locale' => 'ar', 'drill' => $drill->id]));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.safety.drill-pv.pdf', ['locale' => 'ar', 'drill' => $drill->id]));
        $pdfResponse->assertStatus(200);
    }

    public function test_print_equipment_register_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);

        $response = $this->actingAs($principal)
            ->get(route('print.safety.equipment-register', ['locale' => 'ar']));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.safety.equipment-register.pdf', ['locale' => 'ar']));
        $pdfResponse->assertStatus(200);
    }

    public function test_safety_hub_renders_for_authorized_users(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);

        $response = $this->actingAs($principal)
            ->get('/ar/auth/school-safety-hub');

        $response->assertStatus(200);
    }

    public function test_safety_hub_forbids_unauthorized_users(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $response = $this->actingAs($teacher)
            ->get('/ar/auth/school-safety-hub');

        $response->assertStatus(403);
    }

    public function test_safety_hub_can_submit_accident_and_equipment(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        Livewire::test(SchoolSafetyHub::class)
            ->set('incidentType', SafetyIncidentType::StudentAccident->value)
            ->set('severityLevel', SafetyIncidentSeverity::Moderate->value)
            ->set('incidentDate', '2026-11-08T10:30')
            ->set('location', 'مخبر الفيزياء')
            ->set('victimType', 'student')
            ->set('victimName', 'أمين بن علي')
            ->set('circumstancesDescription', 'انزلاق طفيف أثناء التجربة المخبرية')
            ->call('submitAccident')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_safety_incidents', [
            'victim_name' => 'أمين بن علي',
            'location' => 'مخبر الفيزياء',
        ]);

        Livewire::test(SchoolSafetyHub::class)
            ->set('newEquipmentType', SafetyEquipmentType::CoDetector->value)
            ->set('newEquipmentLocation', 'مرقد الإناث - الطابق 1')
            ->set('newEquipmentLastInspection', '2026-11-01')
            ->set('newEquipmentNextDue', '2027-11-01')
            ->set('newEquipmentStatus', SafetyEquipmentStatus::Operational->value)
            ->call('submitEquipment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('safety_fire_equipment', [
            'exact_location' => 'مرقد الإناث - الطابق 1',
        ]);
    }

    public function test_health_check_verifies_all_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->expectsOutputToContain('[PASS] schema: All required tables present')
            ->assertSuccessful();
    }
}
