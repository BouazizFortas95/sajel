<?php

namespace Tests\Feature;

use App\Enums\OfficialExamCenterStatus;
use App\Enums\OfficialExamDutyRole;
use App\Enums\OfficialExamIncidentType;
use App\Enums\OfficialExamType;
use App\Enums\Role;
use App\Filament\Pages\OfficialExamCenterHub;
use App\Models\OfficialExamCenter;
use App\Models\OfficialExamIncident;
use App\Models\OfficialExamRoom;
use App\Models\OfficialExamSupervisor;
use App\Models\User;
use App\Services\OfficialExamCenterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class OfficialExamCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $generalSupervisor;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');

        $this->principal = User::factory()->create([
            'role' => Role::Principal,
        ]);

        $this->generalSupervisor = User::factory()->create([
            'role' => Role::GeneralSupervisor,
        ]);

        $this->teacher = User::factory()->create([
            'role' => Role::Teacher,
        ]);
    }

    // ─── 1. Schema & Enums ──────────────────────────────────────────────────

    public function test_official_exam_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('official_exam_centers'));
        $this->assertTrue(Schema::hasTable('official_exam_rooms'));
        $this->assertTrue(Schema::hasTable('official_exam_supervisors'));
        $this->assertTrue(Schema::hasTable('official_exam_incidents'));
    }

    public function test_official_exam_enums(): void
    {
        $this->assertSame('امتحان شهادة التعليم المتوسط (BEM)', OfficialExamType::Bem->label());
        $this->assertSame('امتحان شهادة البكالوريا (BAC)', OfficialExamType::Bac->label());
        $this->assertSame('BEM', OfficialExamType::Bem->abbreviation());

        $this->assertSame('قيد التحضير والتنظيم', OfficialExamCenterStatus::Planning->label());
        $this->assertSame('الامتحان جارٍ رسمياً', OfficialExamCenterStatus::Active->label());

        $this->assertSame('رئيس القاعة المسؤول', OfficialExamDutyRole::RoomChief->label());
        $this->assertSame('أستاذ حارس بالقاعة', OfficialExamDutyRole::RoomSupervisor->label());

        $this->assertSame('محضر فتح وتأكيد سلامة أظرفة المواضيع', OfficialExamIncidentType::EnvelopeOpening->label());
        $this->assertSame('محضر إثبات محاولة غش (المرسوم 20-241)', OfficialExamIncidentType::CheatingAttempt->label());
    }

    public function test_can_manage_official_exams_permissions(): void
    {
        $this->assertTrue($this->principal->canManageOfficialExams());
        $this->assertTrue($this->generalSupervisor->canManageOfficialExams());
        $this->assertFalse($this->teacher->canManageOfficialExams());
    }

    // ─── 2. Service Operations ──────────────────────────────────────────────

    public function test_service_creates_center_with_auto_generated_code(): void
    {
        $service = app(OfficialExamCenterService::class);

        $center = $service->createCenter([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_name' => 'مركز الإجراء متوسطة الشهداء',
            'head_user_id' => $this->principal->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $this->assertDatabaseHas('official_exam_centers', [
            'id' => $center->id,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء متوسطة الشهداء',
            'session_year' => 2026,
        ]);
    }

    public function test_service_generates_rooms_and_updates_candidate_metrics(): void
    {
        $service = app(OfficialExamCenterService::class);

        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء متوسطة الشهداء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $service->generateRooms($center, 5, 20, 26001001);

        $this->assertCount(5, $center->rooms);
        $this->assertDatabaseHas('official_exam_rooms', [
            'center_id' => $center->id,
            'room_number' => 1,
            'candidate_start_number' => '26001001',
            'candidate_end_number' => '26001020',
            'candidate_count' => 20,
        ]);

        $center->refresh();
        $this->assertSame(5, $center->room_count);
        $this->assertSame(100, $center->candidate_count);
    }

    public function test_service_assigns_supervisors_and_duty_roles(): void
    {
        $service = app(OfficialExamCenterService::class);

        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bac->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BAC-2026-001',
            'center_name' => 'مركز ثانوية الأمير عبد القادر',
            'start_date' => '2026-06-14',
            'end_date' => '2026-06-18',
        ]);

        $room = OfficialExamRoom::create([
            'center_id' => $center->id,
            'room_number' => 1,
            'room_label' => 'القاعة رقم 01',
        ]);

        $supervisor = $service->assignSupervisor([
            'center_id' => $center->id,
            'room_id' => $room->id,
            'supervisor_name' => 'الأستاذ أحمد بلخير',
            'origin_institution' => 'متوسطة الإخوة الشهداء',
            'duty_role' => OfficialExamDutyRole::RoomChief->value,
            'convocation_reference' => 'CONV-2026-88',
        ]);

        $this->assertDatabaseHas('official_exam_supervisors', [
            'id' => $supervisor->id,
            'supervisor_name' => 'الأستاذ أحمد بلخير',
            'duty_role' => OfficialExamDutyRole::RoomChief->value,
        ]);
    }

    public function test_service_generates_incident_number_and_records_minute(): void
    {
        $service = app(OfficialExamCenterService::class);

        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $room = OfficialExamRoom::create([
            'center_id' => $center->id,
            'room_number' => 2,
            'room_label' => 'القاعة رقم 02',
        ]);

        $incident = $service->recordIncident([
            'center_id' => $center->id,
            'room_id' => $room->id,
            'incident_type' => OfficialExamIncidentType::CheatingAttempt->value,
            'incident_date' => '2026-06-09',
            'session_time' => 'morning',
            'subject_name' => 'الرياضيات',
            'candidate_registration_number' => '26001035',
            'candidate_name' => 'التلميذ س. م',
            'description' => 'ضبط هاتف نقال وسماعة بحوزة المترشح أثناء الإجابة',
            'seized_evidence' => 'هاتف ذكي وسماعات لاسلكية',
            'measures_taken' => 'سحب المحجوزات وإشعار رئيس المركز والملاحظ الوزاري',
            'reported_by' => $this->principal->id,
        ]);

        $this->assertSame('INC/BEM/2026/001', $incident->incident_number);
        $this->assertDatabaseHas('official_exam_incidents', [
            'id' => $incident->id,
            'incident_number' => 'INC/BEM/2026/001',
            'incident_type' => OfficialExamIncidentType::CheatingAttempt->value,
        ]);
    }

    public function test_service_calculates_center_telemetry(): void
    {
        $service = app(OfficialExamCenterService::class);

        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $service->generateRooms($center, 4, 20);

        OfficialExamSupervisor::create([
            'center_id' => $center->id,
            'supervisor_name' => 'رئيس قاعة 1',
            'duty_role' => OfficialExamDutyRole::RoomChief->value,
        ]);

        OfficialExamSupervisor::create([
            'center_id' => $center->id,
            'supervisor_name' => 'حارس قاعة 1',
            'duty_role' => OfficialExamDutyRole::RoomSupervisor->value,
        ]);

        $telemetry = $service->getCenterTelemetry($center);

        $this->assertSame(4, $telemetry['totalRooms']);
        $this->assertSame(80, $telemetry['totalCandidates']);
        $this->assertSame(2, $telemetry['totalSupervisors']);
        $this->assertSame(1, $telemetry['roomChiefsCount']);
        $this->assertSame(1, $telemetry['roomSupervisorsCount']);
        // 4 rooms * 3 supervisors required = 12. 2 / 12 = 16.7%
        $this->assertEquals(16.7, $telemetry['coveragePercentage']);
    }

    // ─── 3. Filament Livewire Cockpit ───────────────────────────────────────

    public function test_official_exam_hub_renders_for_authorized_users(): void
    {
        Livewire::actingAs($this->generalSupervisor)
            ->test(OfficialExamCenterHub::class)
            ->assertSuccessful()
            ->assertSee('تسيير مراكز الامتحانات الرسمية (BEM / BAC) وتأطير الحراسة');
    }

    public function test_official_exam_hub_forbids_unauthorized_users(): void
    {
        $this->actingAs($this->teacher)
            ->get('/ar/auth/official-exam-center-hub')
            ->assertForbidden();
    }

    public function test_official_exam_hub_creates_center_livewire_action(): void
    {
        Livewire::actingAs($this->principal)
            ->test(OfficialExamCenterHub::class)
            ->set('centerExamType', 'bem')
            ->set('centerSessionYear', 2026)
            ->set('centerName', 'مركز متوسطة العربي التبسي')
            ->set('centerStartDate', '2026-06-08')
            ->set('centerEndDate', '2026-06-10')
            ->call('saveCenter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('official_exam_centers', [
            'center_name' => 'مركز متوسطة العربي التبسي',
            'session_year' => 2026,
        ]);
    }

    public function test_official_exam_hub_generates_rooms_livewire_action(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bac->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BAC-2026-009',
            'center_name' => 'مركز ثانوية هواري بومدين',
            'start_date' => '2026-06-14',
            'end_date' => '2026-06-18',
        ]);

        Livewire::actingAs($this->principal)
            ->test(OfficialExamCenterHub::class)
            ->set('selectedCenterId', $center->id)
            ->set('roomsToGenerate', 6)
            ->set('candidatesPerRoom', 20)
            ->call('executeGenerateRooms')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('official_exam_rooms', 6);
    }

    public function test_official_exam_hub_assigns_supervisor_livewire_action(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-008',
            'center_name' => 'مركز متوسطة مالك بن نبي',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        Livewire::actingAs($this->principal)
            ->test(OfficialExamCenterHub::class)
            ->set('selectedCenterId', $center->id)
            ->set('supervisorName', 'الأستاذة فاطمة الزهراء')
            ->set('supervisorOrigin', 'متوسطة الإدريسي')
            ->set('supervisorDutyRole', 'room_chief')
            ->call('saveSupervisor')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('official_exam_supervisors', [
            'supervisor_name' => 'الأستاذة فاطمة الزهراء',
            'duty_role' => 'room_chief',
        ]);
    }

    public function test_official_exam_hub_records_incident_livewire_action(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-007',
            'center_name' => 'مركز متوسطة بن باديس',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        Livewire::actingAs($this->principal)
            ->test(OfficialExamCenterHub::class)
            ->set('selectedCenterId', $center->id)
            ->set('incidentType', 'envelope_opening')
            ->set('incidentDate', '2026-06-08')
            ->set('incidentSubject', 'اللغة العربية')
            ->set('incidentDescription', 'تم فتح ظرف مواضيع اللغة العربية بحضور شاهدين والتأكد من سلامة الشمع الأمني')
            ->call('saveIncident')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('official_exam_incidents', [
            'center_id' => $center->id,
            'subject_name' => 'اللغة العربية',
            'incident_type' => 'envelope_opening',
        ]);
    }

    // ─── 4. Print & PDF Routes ──────────────────────────────────────────────

    public function test_print_center_roster_routes(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء متوسطة الشهداء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        // HTML preview
        $response = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/center/{$center->id}/roster");

        $response->assertSuccessful();
        $response->assertSee('مخطط توزيع القاعات وجدول تأطير الحراسة والأمانة');

        // PDF stream
        $pdfResponse = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/center/{$center->id}/roster/pdf");

        $pdfResponse->assertSuccessful();
    }

    public function test_print_envelope_pv_routes(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $incident = OfficialExamIncident::create([
            'center_id' => $center->id,
            'incident_number' => 'INC/BEM/2026/001',
            'incident_type' => OfficialExamIncidentType::EnvelopeOpening->value,
            'incident_date' => '2026-06-08',
            'session_time' => 'morning',
            'subject_name' => 'اللغة العربية',
            'description' => 'معاينة سلامة الأظرفة',
        ]);

        // HTML preview
        $response = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/incident/{$incident->id}/envelope-pv");

        $response->assertSuccessful();
        $response->assertSee('محضر فتح أظرفة مواضيع الامتحان وتأكيد سلامتها');

        // PDF stream
        $pdfResponse = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/incident/{$incident->id}/envelope-pv/pdf");

        $pdfResponse->assertSuccessful();
    }

    public function test_print_incident_pv_routes(): void
    {
        $center = OfficialExamCenter::create([
            'exam_type' => OfficialExamType::Bem->value,
            'session_year' => 2026,
            'center_code' => 'CEN-BEM-2026-001',
            'center_name' => 'مركز الإجراء',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
        ]);

        $incident = OfficialExamIncident::create([
            'center_id' => $center->id,
            'incident_number' => 'INC/BEM/2026/002',
            'incident_type' => OfficialExamIncidentType::CheatingAttempt->value,
            'incident_date' => '2026-06-08',
            'session_time' => 'morning',
            'subject_name' => 'اللغة العربية',
            'candidate_name' => 'التلميذ كمال',
            'candidate_registration_number' => '26001099',
            'description' => 'محاولة غش',
        ]);

        // HTML preview
        $response = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/incident/{$incident->id}/pv");

        $response->assertSuccessful();
        $response->assertSee('محضر إثبات محاولة غش / إخلال بسير الامتحان الرسمي');

        // PDF stream
        $pdfResponse = $this->actingAs($this->principal)
            ->get("/ar/print/official-exam/incident/{$incident->id}/pv/pdf");

        $pdfResponse->assertSuccessful();
    }

    // ─── 5. Health Check ────────────────────────────────────────────────────

    public function test_health_check_verifies_all_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
