<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SocialEligibilityCategory;
use App\Enums\SolidarityAidType;
use App\Enums\SolidarityBeneficiaryStatus;
use App\Enums\SolidarityCampaignStatus;
use App\Enums\SolidarityCommitteeRole;
use App\Filament\Pages\SchoolSolidarityHub;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentSolidarityBeneficiary;
use App\Models\User;
use App\Services\SchoolSolidarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSolidarityTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $financeOfficer;

    private User $teacher;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');

        $this->principal = User::factory()->create([
            'role' => Role::Principal,
        ]);

        $this->financeOfficer = User::factory()->create([
            'role' => Role::FinanceOfficer,
        ]);

        $this->teacher = User::factory()->create([
            'role' => Role::Teacher,
        ]);

        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $this->student = Student::factory()->create([
            'group_id' => $group->id,
            'matricule_number' => 'STU-2026-999',
            'first_name' => 'محمد',
            'last_name' => 'بن علي',
            'guardian_name' => 'عمر بن علي',
        ]);
    }

    // ─── 1. Schema & Enums ──────────────────────────────────────────────────

    public function test_solidarity_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('school_solidarity_campaigns'));
        $this->assertTrue(Schema::hasTable('student_solidarity_beneficiaries'));
        $this->assertTrue(Schema::hasTable('school_solidarity_committee_members'));
        $this->assertTrue(Schema::hasTable('school_solidarity_aid_distributions'));
    }

    public function test_solidarity_enums_and_labels(): void
    {
        $this->assertSame('قيد الإعداد وتجميع الملفات', SolidarityCampaignStatus::Planning->label());
        $this->assertSame('تمت مداولة لجنة المؤسسة', SolidarityCampaignStatus::Deliberated->label());

        $this->assertSame('يتيم (فاقد الأب أو الوالدين معاً)', SocialEligibilityCategory::Orphan->label());
        $this->assertSame('معوز (أرباب الأسر بدون أي دخل)', SocialEligibilityCategory::NeedyNoIncome->label());
        $this->assertSame('دخل ضعيف (أقل أو مساوٍ للأجر الأدنى SNMG)', SocialEligibilityCategory::LowIncome->label());

        $this->assertSame('ملف مودع', SolidarityBeneficiaryStatus::Submitted->label());
        $this->assertSame('مقبول بمداولة اللجنة', SolidarityBeneficiaryStatus::DeliberatedApproved->label());
        $this->assertSame('تم الصرف والتحويل', SolidarityBeneficiaryStatus::Paid->label());

        $this->assertSame('مدير المؤسسة (رئيس اللجنة)', SolidarityCommitteeRole::President->label());
        $this->assertSame('المقتصد / المسير المالي (المقرر)', SolidarityCommitteeRole::FinancialReporter->label());

        $this->assertSame('مجانية طقم الكتب المدرسية', SolidarityAidType::FreeTextbooks->label());
        $this->assertSame('الحقيبة والمحفظة المدرسية المجهزة', SolidarityAidType::SchoolBag->label());
    }

    public function test_can_manage_solidarity_permissions(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);

        $this->assertTrue($this->principal->canManageSolidarity());
        $this->assertTrue($this->financeOfficer->canManageSolidarity());
        $this->assertTrue($censor->canManageSolidarity());
        $this->assertTrue($admin->canManageSolidarity());

        $this->assertFalse($this->teacher->canManageSolidarity());
        $this->assertFalse($supervisor->canManageSolidarity());
    }

    // ─── 2. Service Layer Operations ────────────────────────────────────────

    public function test_service_generates_sequential_file_and_voucher_numbers(): void
    {
        $service = app(SchoolSolidarityService::class);

        $f1 = $service->generateFileNumber(2026);
        $this->assertSame('SOL/2026/001', $f1);

        $v1 = $service->generateAidVoucherNumber(2026);
        $this->assertSame('AID/2026/001', $v1);
    }

    public function test_service_creates_campaign_and_initializes_default_committee(): void
    {
        $service = app(SchoolSolidarityService::class);

        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
            'title' => 'حملة منحة التمدرس 5000 دج لسنة 2026/2027',
            'grant_amount' => 5000.00,
        ], $this->principal->id);

        $this->assertDatabaseHas('school_solidarity_campaigns', [
            'id' => $campaign->id,
            'campaign_year' => 2026,
            'grant_amount' => 5000.00,
        ]);

        $this->assertGreaterThanOrEqual(4, $campaign->committeeMembers()->count());
        $this->assertDatabaseHas('school_solidarity_committee_members', [
            'campaign_id' => $campaign->id,
            'committee_role' => 'president',
        ]);
        $this->assertDatabaseHas('school_solidarity_committee_members', [
            'campaign_id' => $campaign->id,
            'committee_role' => 'financial_reporter',
        ]);
    }

    public function test_service_registers_beneficiary_and_syncs_counts(): void
    {
        $service = app(SchoolSolidarityService::class);

        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $beneficiary = $service->registerBeneficiary([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'eligibility_category' => SocialEligibilityCategory::Orphan,
            'guardian_type' => 'mother',
            'guardian_national_id' => '1029384756',
            'guardian_full_name' => 'فاطمة الزهراء',
            'monthly_income' => 0.00,
            'ccp_account_number' => '98765432',
            'ccp_key' => '11',
        ], $this->financeOfficer->id);

        $this->assertDatabaseHas('student_solidarity_beneficiaries', [
            'id' => $beneficiary->id,
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'file_number' => 'SOL/2026/001',
            'eligibility_category' => 'orphan',
        ]);
    }

    public function test_service_updates_beneficiary_status_and_records_payment(): void
    {
        $service = app(SchoolSolidarityService::class);

        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $beneficiary = $service->registerBeneficiary([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'eligibility_category' => SocialEligibilityCategory::NeedyNoIncome,
            'guardian_national_id' => '1029384756',
            'guardian_full_name' => 'عمر بن علي',
        ]);

        // Deliberated / Approved
        $service->updateBeneficiaryStatus($beneficiary, SolidarityBeneficiaryStatus::DeliberatedApproved);
        $campaign->refresh();
        $this->assertSame(1, $campaign->approved_beneficiaries_count);

        // Disburse / Pay 5,000 DZD
        $service->markBeneficiaryPaid($beneficiary, 'VIR-CCP-2026-99', '2026-11-20');
        $campaign->refresh();

        $this->assertEquals(5000.00, (float) $campaign->total_disbursed_amount);
        $this->assertDatabaseHas('student_solidarity_beneficiaries', [
            'id' => $beneficiary->id,
            'status' => 'paid',
            'payment_reference' => 'VIR-CCP-2026-99',
        ]);
    }

    public function test_service_records_in_kind_aid_distribution(): void
    {
        $service = app(SchoolSolidarityService::class);

        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $distribution = $service->recordAidDistribution([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'aid_type' => SolidarityAidType::FreeTextbooks,
            'items_description' => 'طقم كتب السنة الرابعة متوسط كامل (مجاني)',
            'estimated_value' => 3800.00,
            'donor_entity' => 'وزارة التربية الوطنية',
        ], $this->financeOfficer->id);

        $this->assertDatabaseHas('school_solidarity_aid_distributions', [
            'id' => $distribution->id,
            'voucher_number' => 'AID/2026/001',
            'aid_type' => 'free_textbooks',
            'estimated_value' => 3800.00,
        ]);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolSolidarityService::class);

        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $beneficiary = $service->registerBeneficiary([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'eligibility_category' => SocialEligibilityCategory::Orphan,
            'guardian_national_id' => '1029384756',
            'guardian_full_name' => 'فاطمة الزهراء',
            'status' => SolidarityBeneficiaryStatus::DeliberatedApproved,
        ]);

        $nominativeData = $service->assembleNominativeListData($campaign);
        $this->assertSame(1, $nominativeData['total_count']);
        $this->assertEquals(5000.00, $nominativeData['total_amount']);

        $pvData = $service->assembleCommitteePvData($campaign);
        $this->assertSame(1, $pvData['statistics']['approved_count']);

        $distribution = $service->recordAidDistribution([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'aid_type' => SolidarityAidType::FreeTextbooks,
            'items_description' => 'كتب مجانية',
        ], $this->financeOfficer->id);

        $voucherData = $service->assembleAidVoucherData($distribution);
        $this->assertSame('AID/2026/001', $voucherData['distribution']['voucher_number']);
    }

    // ─── 3. Web & PDF Printable Routes ──────────────────────────────────────

    public function test_print_nominative_list_routes(): void
    {
        $service = app(SchoolSolidarityService::class);
        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        // Finance Officer can access
        $res = $this->actingAs($this->financeOfficer)->get("/ar/print/solidarity/campaign/{$campaign->id}/nominative-list");
        $res->assertOk();
        $res->assertSee('الكشف الإسمي للمستفيدين من منحة التمدرس الخاصة');

        // Teacher is forbidden (403)
        $forbiddenRes = $this->actingAs($this->teacher)->get("/ar/print/solidarity/campaign/{$campaign->id}/nominative-list");
        $forbiddenRes->assertForbidden();

        // PDF route
        $pdfRes = $this->actingAs($this->principal)->get("/ar/print/solidarity/campaign/{$campaign->id}/nominative-list/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    public function test_print_committee_pv_routes(): void
    {
        $service = app(SchoolSolidarityService::class);
        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $res = $this->actingAs($this->principal)->get("/ar/print/solidarity/campaign/{$campaign->id}/committee-pv");
        $res->assertOk();
        $res->assertSee('محضر مداولات لجنة التضامن المدرسي');

        $pdfRes = $this->actingAs($this->principal)->get("/ar/print/solidarity/campaign/{$campaign->id}/committee-pv/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    public function test_print_aid_voucher_routes(): void
    {
        $service = app(SchoolSolidarityService::class);
        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        $distribution = $service->recordAidDistribution([
            'campaign_id' => $campaign->id,
            'student_id' => $this->student->id,
            'aid_type' => SolidarityAidType::SchoolBag,
            'items_description' => 'محفظة مدرسية',
        ], $this->financeOfficer->id);

        $res = $this->actingAs($this->financeOfficer)->get("/ar/print/solidarity/aid/{$distribution->id}/voucher");
        $res->assertOk();
        $res->assertSee('سند ومحضر تسليم واستلام مجانية المستلزمات');

        $pdfRes = $this->actingAs($this->financeOfficer)->get("/ar/print/solidarity/aid/{$distribution->id}/voucher/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    // ─── 4. Filament Livewire Cockpit ───────────────────────────────────────

    public function test_solidarity_hub_renders_for_authorized_users(): void
    {
        Livewire::actingAs($this->principal)
            ->test(SchoolSolidarityHub::class)
            ->assertOk()
            ->assertSee('منظومة التضامن المدرسي، منحة 5000 دج');
    }

    public function test_solidarity_hub_forbids_unauthorized_users(): void
    {
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);

        // Teacher
        $this->actingAs($this->teacher);
        $this->assertFalse(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-solidarity-hub')->assertForbidden();

        // General Supervisor
        $this->actingAs($supervisor);
        $this->assertFalse(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-solidarity-hub')->assertForbidden();
    }

    public function test_solidarity_hub_can_create_campaign(): void
    {
        Livewire::actingAs($this->principal)
            ->test(SchoolSolidarityHub::class)
            ->set('newCampaignYear', 2027)
            ->set('newAcademicYear', '2027/2028')
            ->set('newCampaignTitle', 'حملة التضامن 2027')
            ->call('createCampaign')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_solidarity_campaigns', [
            'campaign_year' => 2027,
            'academic_year' => '2027/2028',
        ]);
    }

    public function test_solidarity_hub_can_register_and_approve_beneficiary(): void
    {
        $service = app(SchoolSolidarityService::class);
        $campaign = $service->createCampaign([
            'campaign_year' => 2026,
            'academic_year' => '2026/2027',
        ], $this->principal->id);

        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolSolidarityHub::class)
            ->set('selectedCampaignId', $campaign->id)
            ->set('newStudentId', $this->student->id)
            ->set('newEligibilityCategory', 'orphan')
            ->set('newGuardianFullName', 'عمر بن علي')
            ->set('newGuardianNationalId', '1122334455')
            ->call('registerBeneficiary')
            ->assertHasNoErrors();

        $beneficiary = StudentSolidarityBeneficiary::first();
        $this->assertNotNull($beneficiary);

        Livewire::actingAs($this->principal)
            ->test(SchoolSolidarityHub::class)
            ->set('selectedCampaignId', $campaign->id)
            ->call('approveBeneficiary', $beneficiary->id)
            ->assertHasNoErrors();

        $this->assertSame(SolidarityBeneficiaryStatus::DeliberatedApproved, $beneficiary->fresh()->status);
    }

    public function test_health_check_verifies_all_73_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present (73)');
    }
}
