<?php

namespace Tests\Feature;

use App\Enums\ParentsMeetingType;
use App\Enums\Role;
use App\Enums\SchoolBoardMemberCategory;
use App\Enums\SchoolBoardSessionStatus;
use App\Enums\SchoolBoardSessionTerm;
use App\Enums\SchoolBoardType;
use App\Enums\SchoolProjectStatus;
use App\Filament\Pages\SchoolBoardGovernanceHub;
use App\Models\ParentsAssociation;
use App\Models\ParentsAssociationMeeting;
use App\Models\SchoolBoardSession;
use App\Models\SchoolProject;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\SchoolBoardGovernanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolBoardGovernanceTest extends TestCase
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

    public function test_governance_tables_exist(): void
    {
        $tables = [
            'school_board_sessions',
            'school_board_members',
            'school_projects',
            'school_internal_regulations',
            'parents_associations',
            'parents_association_meetings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist.");
        }
    }

    public function test_governance_enums_and_labels(): void
    {
        $this->assertNotEmpty(SchoolBoardType::Ceg->label());
        $this->assertNotEmpty(SchoolBoardType::Ceg->badgeColor());

        $this->assertNotEmpty(SchoolBoardSessionTerm::Term1Opening->label());
        $this->assertNotEmpty(SchoolBoardSessionTerm::Term1Opening->badgeColor());

        $this->assertNotEmpty(SchoolBoardSessionStatus::Deliberated->label());
        $this->assertNotEmpty(SchoolBoardSessionStatus::Deliberated->badgeColor());

        $this->assertNotEmpty(SchoolBoardMemberCategory::Statutory->label());
        $this->assertNotEmpty(SchoolBoardMemberCategory::Statutory->badgeColor());

        $this->assertNotEmpty(SchoolProjectStatus::InProgress->label());
        $this->assertNotEmpty(SchoolProjectStatus::InProgress->badgeColor());

        $this->assertNotEmpty(ParentsMeetingType::OrdinaryGeneralAssembly->label());
        $this->assertNotEmpty(ParentsMeetingType::OrdinaryGeneralAssembly->badgeColor());
    }

    public function test_user_can_manage_school_governance_permissions(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $dev = User::factory()->create(['role' => Role::Developer]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($principal->canManageSchoolGovernance());
        $this->assertTrue($supervisor->canManageSchoolGovernance());
        $this->assertTrue($censor->canManageSchoolGovernance());
        $this->assertTrue($bursar->canManageSchoolGovernance());
        $this->assertTrue($dev->canManageSchoolGovernance());

        $this->assertFalse($teacher->canManageSchoolGovernance());
        $this->assertFalse($counselor->canManageSchoolGovernance());
    }

    public function test_service_generates_sequential_codes(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $sessionCode = $service->generateSessionCode(2026, SchoolBoardType::Ceg);
        $this->assertEquals('CEG-2026-01', $sessionCode);

        $projectCode = $service->generateProjectCode(2026);
        $this->assertEquals('PRJ-2026-01', $projectCode);

        $regulationCode = $service->generateRegulationCode(2026);
        $this->assertEquals('REG-2026-01', $regulationCode);

        $parentsCode = $service->generateParentsMeetingCode(2026);
        $this->assertEquals('APE-2026-01', $parentsCode);
    }

    public function test_service_records_board_session_and_validates_quorum(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $session = $service->recordBoardSession([
            'academic_year' => '2026/2027',
            'board_type' => SchoolBoardType::Ceg->value,
            'session_term' => SchoolBoardSessionTerm::Term1Opening->value,
            'session_date' => Carbon::today(),
            'session_time' => '14:30',
            'meeting_place' => 'قاعة الاجتماعات الرسمية',
            'president_name' => 'مدير المؤسسة',
            'rapporteur_name' => 'مستشار التربية',
            'quorum_required' => 8,
            'members_present_count' => 9,
            'agenda_items' => 'المصادقة على مشروع المؤسسة والنظام الداخلي',
            'deliberation_details' => 'مناقشات مستفيضة حول مؤشرات النجاعة',
            'decisions_summary' => 'المصادقة بالإجماع',
            'status' => SchoolBoardSessionStatus::Deliberated->value,
        ]);

        $this->assertInstanceOf(SchoolBoardSession::class, $session);
        $this->assertEquals('CEG-2026-01', $session->session_code);
        $this->assertTrue($session->is_quorum_reached);
        $this->assertDatabaseHas('school_board_sessions', [
            'id' => $session->id,
            'session_code' => 'CEG-2026-01',
            'is_quorum_reached' => true,
        ]);
    }

    public function test_service_auto_bootstraps_statutory_and_elected_members(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $service->autoBootstrapBoardMembers('2026/2027', SchoolBoardType::Ceg);

        $this->assertDatabaseCount('school_board_members', 9);

        $this->assertDatabaseHas('school_board_members', [
            'academic_year' => '2026/2027',
            'member_category' => SchoolBoardMemberCategory::Statutory->value,
            'institution_role' => 'مدير المؤسسة (رئيس المجلس)',
        ]);

        $this->assertDatabaseHas('school_board_members', [
            'academic_year' => '2026/2027',
            'member_category' => SchoolBoardMemberCategory::ElectedParent->value,
            'institution_role' => 'رئيس جمعية أولياء التلاميذ (APE)',
        ]);
    }

    public function test_service_creates_or_updates_school_project(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $project = $service->createOrUpdateSchoolProject([
            'project_code' => 'PRJ-2026-01',
            'title' => 'مشروع المؤسسة 2026-2029: تحسين نواتج التعلم',
            'start_year' => '2026',
            'end_year' => '2029',
            'completion_rate_percentage' => 40.0,
            'status' => SchoolProjectStatus::InProgress->value,
        ]);

        $this->assertInstanceOf(SchoolProject::class, $project);
        $this->assertEquals('PRJ-2026-01', $project->project_code);
        $this->assertCount(4, $project->strategic_axes);
        $this->assertEquals(40.0, (float) $project->completion_rate_percentage);
    }

    public function test_service_adopts_internal_regulations_and_deactivates_previous(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $reg1 = $service->adoptInternalRegulations([
            'academic_year' => '2026/2027',
            'title' => 'النظام الداخلي النسخة 1',
            'version' => '1.0',
            'key_rules_summary' => 'الانضباط والزي المدرسي',
        ]);

        $this->assertTrue($reg1->is_current_active);

        $reg2 = $service->adoptInternalRegulations([
            'academic_year' => '2026/2027',
            'title' => 'النظام الداخلي النسخة 2 المحينة',
            'version' => '2.0',
            'key_rules_summary' => 'قواعد إضافية للهواتف الذكية',
        ]);

        $this->assertTrue($reg2->is_current_active);
        $this->assertFalse($reg1->fresh()->is_current_active);
    }

    public function test_service_registers_ape_and_records_general_assembly_meeting(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $ape = $service->registerParentsAssociation([
            'academic_year' => '2026/2027',
            'association_name' => 'جمعية أولياء تلاميذ متوسطة العربي التبسي',
            'registration_number' => 'وصـل 42/2026',
            'president_name' => 'عمر بلحاج',
            'registered_parents_count' => 150,
            'status' => 'active',
        ]);

        $this->assertInstanceOf(ParentsAssociation::class, $ape);

        $meeting = $service->recordParentsMeeting($ape, [
            'meeting_type' => ParentsMeetingType::OrdinaryGeneralAssembly->value,
            'meeting_date' => Carbon::today(),
            'attendees_count' => 60,
            'agenda_topics' => 'تجديد المكتب والمصادقة على التقرير الأدبي والمالي',
            'status' => 'approved',
        ]);

        $this->assertInstanceOf(ParentsAssociationMeeting::class, $meeting);
        $this->assertEquals('APE-2026-01', $meeting->meeting_code);
        $this->assertEquals($ape->id, $meeting->parents_association_id);
    }

    public function test_service_computes_governance_telemetry(): void
    {
        $service = app(SchoolBoardGovernanceService::class);
        $service->autoBootstrapBoardMembers('2026/2027', SchoolBoardType::Ceg);

        $telemetry = $service->getGovernanceTelemetry('2026/2027');

        $this->assertArrayHasKey('sessions_count', $telemetry);
        $this->assertArrayHasKey('members_count', $telemetry);
        $this->assertEquals(9, $telemetry['members_count']);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolBoardGovernanceService::class);

        $session = $service->recordBoardSession([
            'academic_year' => '2026/2027',
            'board_type' => SchoolBoardType::Ceg->value,
            'session_term' => SchoolBoardSessionTerm::Term1Opening->value,
            'session_date' => Carbon::today(),
            'president_name' => 'مدير المؤسسة',
            'rapporteur_name' => 'مستشار التربية',
            'agenda_items' => 'المصادقة على الميزانية',
        ]);

        $sessionData = $service->assembleBoardSessionPvData($session);
        $this->assertArrayHasKey('session', $sessionData);
        $this->assertArrayHasKey('members', $sessionData);
        $this->assertEquals('متوسطة العربي التبسي', $sessionData['institution_name']);

        $project = $service->createOrUpdateSchoolProject([
            'project_code' => 'PRJ-2026-01',
            'title' => 'مشروع المؤسسة',
            'start_year' => '2026',
            'end_year' => '2029',
        ]);

        $projectData = $service->assembleSchoolProjectSummaryData($project);
        $this->assertArrayHasKey('project', $projectData);
        $this->assertArrayHasKey('strategic_axes', $projectData);

        $ape = $service->registerParentsAssociation([
            'academic_year' => '2026/2027',
            'association_name' => 'جمعية الأولياء',
            'president_name' => 'محمد سعيد',
        ]);

        $meeting = $service->recordParentsMeeting($ape, [
            'meeting_date' => Carbon::today(),
            'agenda_topics' => 'جلسة تنسيقية',
        ]);

        $meetingData = $service->assembleParentsGeneralAssemblyPvData($meeting);
        $this->assertArrayHasKey('meeting', $meetingData);
        $this->assertArrayHasKey('association', $meetingData);
    }

    public function test_print_board_session_pv_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolBoardGovernanceService::class);

        $session = $service->recordBoardSession([
            'academic_year' => '2026/2027',
            'board_type' => SchoolBoardType::Ceg->value,
            'session_term' => SchoolBoardSessionTerm::Term1Opening->value,
            'session_date' => Carbon::today(),
            'president_name' => 'مدير المؤسسة',
            'rapporteur_name' => 'مستشار التربية',
            'agenda_items' => 'المصادقة على الميزانية',
        ]);

        $response = $this->actingAs($principal)
            ->get(route('print.governance.board-session-pv', ['locale' => 'ar', 'session' => $session->id]));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.governance.board-session-pv.pdf', ['locale' => 'ar', 'session' => $session->id]));
        $pdfResponse->assertStatus(200);
    }

    public function test_print_school_project_summary_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolBoardGovernanceService::class);

        $project = $service->createOrUpdateSchoolProject([
            'project_code' => 'PRJ-2026-01',
            'title' => 'مشروع المؤسسة',
            'start_year' => '2026',
            'end_year' => '2029',
        ]);

        $response = $this->actingAs($principal)
            ->get(route('print.governance.school-project', ['locale' => 'ar', 'project' => $project->id]));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.governance.school-project.pdf', ['locale' => 'ar', 'project' => $project->id]));
        $pdfResponse->assertStatus(200);
    }

    public function test_print_parents_assembly_pv_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolBoardGovernanceService::class);

        $ape = $service->registerParentsAssociation([
            'academic_year' => '2026/2027',
            'association_name' => 'جمعية الأولياء',
            'president_name' => 'محمد سعيد',
        ]);

        $meeting = $service->recordParentsMeeting($ape, [
            'meeting_date' => Carbon::today(),
            'agenda_topics' => 'جلسة تنسيقية',
        ]);

        $response = $this->actingAs($principal)
            ->get(route('print.governance.parents-assembly-pv', ['locale' => 'ar', 'meeting' => $meeting->id]));
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($principal)
            ->get(route('print.governance.parents-assembly-pv.pdf', ['locale' => 'ar', 'meeting' => $meeting->id]));
        $pdfResponse->assertStatus(200);
    }

    public function test_governance_hub_renders_for_authorized_users(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);

        $response = $this->actingAs($principal)
            ->get('/ar/auth/school-board-governance-hub');

        $response->assertStatus(200);
    }

    public function test_governance_hub_forbids_unauthorized_users(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $response = $this->actingAs($teacher)
            ->get('/ar/auth/school-board-governance-hub');

        $response->assertStatus(403);
    }

    public function test_governance_hub_can_record_session_and_manage_project(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        Livewire::test(SchoolBoardGovernanceHub::class)
            ->set('newBoardType', SchoolBoardType::Ceg->value)
            ->set('newSessionTerm', SchoolBoardSessionTerm::Term2Budget->value)
            ->set('newSessionDate', '2026-11-15')
            ->set('newSessionTime', '15:00')
            ->set('newMeetingPlace', 'قاعة الاجتماعات الرسمية')
            ->set('presidentName', 'مدير المؤسسة')
            ->set('rapporteurName', 'ناظر الدروس')
            ->set('quorumRequired', 8)
            ->set('membersPresentCount', 9)
            ->set('agendaItems', 'دراسة مشروع الميزانية 2027')
            ->call('submitSession')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_board_sessions', [
            'agenda_items' => 'دراسة مشروع الميزانية 2027',
            'session_term' => SchoolBoardSessionTerm::Term2Budget->value,
        ]);

        Livewire::test(SchoolBoardGovernanceHub::class)
            ->set('projectTitle', 'مشروع المؤسسة المحين 2026-2029')
            ->set('projectStartYear', '2026')
            ->set('projectEndYear', '2029')
            ->set('projectCompletionRate', 55.5)
            ->call('submitProject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_projects', [
            'title' => 'مشروع المؤسسة المحين 2026-2029',
            'completion_rate_percentage' => 55.5,
        ]);
    }

    public function test_health_check_verifies_all_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->expectsOutputToContain('[PASS] schema: All required tables present')
            ->assertSuccessful();
    }
}
