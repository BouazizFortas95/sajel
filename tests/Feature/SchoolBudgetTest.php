<?php

namespace Tests\Feature;

use App\Enums\BudgetType;
use App\Enums\CommitmentStatus;
use App\Enums\MandateStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Pages\SchoolBudgetHub;
use App\Models\BudgetCommitment;
use App\Models\PaymentMandate;
use App\Models\SchoolBudgetLine;
use App\Models\User;
use App\Services\SchoolBudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolBudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $financeOfficer;

    private User $teacher;

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
    }

    // ─── 1. Schema & Enums ──────────────────────────────────────────────────

    public function test_budget_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('school_budget_lines'));
        $this->assertTrue(Schema::hasTable('budget_commitments'));
        $this->assertTrue(Schema::hasTable('payment_mandates'));
    }

    public function test_budget_enums(): void
    {
        $this->assertSame('نفقات التسيير والتجهيز', BudgetType::Expenditure->label());
        $this->assertSame('الإيرادات والمداخيل', BudgetType::Revenue->label());

        $this->assertSame('ملتزم به قيد الإنجاز', CommitmentStatus::Committed->label());
        $this->assertSame('مصفى ومسدد', CommitmentStatus::Liquidated->label());

        $this->assertSame('مؤشر ومقبول للدفع', MandateStatus::Approved->label());
        $this->assertSame('تحويل عبر الخزينة العمومية', PaymentMethod::TreasuryTransfer->label());
    }

    public function test_can_manage_school_budget_permissions(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $developer = User::factory()->create(['role' => Role::Developer]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        // Authorized: Accountant (المحاسب) or Higher Authority (سلطة أعلى: المدير، المطور، مدير النظام)
        $this->assertTrue($this->financeOfficer->canManageSchoolBudget());
        $this->assertTrue($this->principal->canManageSchoolBudget());
        $this->assertTrue($admin->canManageSchoolBudget());
        $this->assertTrue($developer->canManageSchoolBudget());

        // Forbidden: Censor (الناظر ليس محاسباً ولا سلطة أعلى منه) and all other roles
        $this->assertFalse($censor->canManageSchoolBudget());
        $this->assertFalse($supervisor->canManageSchoolBudget());
        $this->assertFalse($counselor->canManageSchoolBudget());
        $this->assertFalse($this->teacher->canManageSchoolBudget());
    }

    // ─── 2. Service Operations ──────────────────────────────────────────────

    public function test_service_seeds_standard_budget_grid(): void
    {
        $service = app(SchoolBudgetService::class);
        $service->seedStandardBudgetGrid(2026);

        $this->assertDatabaseHas('school_budget_lines', [
            'fiscal_year' => 2026,
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2: نفقات التسيير والوسائل البيداغوجية',
        ]);

        $this->assertDatabaseHas('school_budget_lines', [
            'fiscal_year' => 2026,
            'budget_type' => BudgetType::Revenue->value,
            'chapter' => 'الباب 1: إعانات الدولة والجماعات المحلية',
        ]);
    }

    public function test_service_generates_sequential_references(): void
    {
        $service = app(SchoolBudgetService::class);

        $bc = $service->generateOrderReference(2026);
        $eng = $service->generateCommitmentNumber(2026);
        $mand = $service->generateMandateNumber(2026);

        $this->assertSame('BC/2026/001', $bc);
        $this->assertSame('ENG/2026/001', $eng);
        $this->assertSame('MAND/2026/001', $mand);
    }

    public function test_service_records_commitment_and_updates_budget_line(): void
    {
        $service = app(SchoolBudgetService::class);

        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2: نفقات التسيير',
            'article' => 'المادة 1: مخابر العلوم',
            'allocated_amount' => 500000.00,
            'committed_amount' => 0.00,
            'liquidated_amount' => 0.00,
        ]);

        $commitment = $service->recordCommitment([
            'budget_line_id' => $line->id,
            'supplier_name' => 'مؤسسة التجهيزات العلمية',
            'supplier_tax_number' => '001234567890123',
            'order_date' => '2026-03-10',
            'subject' => 'اقتناء مجاهر وأنابيب اختبار',
            'estimated_amount' => 120000.00,
            'created_by' => $this->financeOfficer->id,
        ]);

        $this->assertDatabaseHas('budget_commitments', [
            'id' => $commitment->id,
            'order_reference' => 'BC/2026/001',
            'estimated_amount' => 120000.00,
            'status' => CommitmentStatus::Committed->value,
        ]);

        $line->refresh();
        $this->assertEquals(120000.00, $line->committed_amount);
        $this->assertEquals(380000.00, $line->availableBalance());
        $this->assertEquals(24.0, $line->utilizationRate());
    }

    public function test_service_records_mandate_and_liquidates_expenditure(): void
    {
        $service = app(SchoolBudgetService::class);

        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2: نفقات التسيير',
            'article' => 'المادة 1: مخابر العلوم',
            'allocated_amount' => 500000.00,
            'committed_amount' => 0.00,
            'liquidated_amount' => 0.00,
        ]);

        $commitment = $service->recordCommitment([
            'budget_line_id' => $line->id,
            'supplier_name' => 'مؤسسة التجهيزات العلمية',
            'order_date' => '2026-03-10',
            'subject' => 'اقتناء مجاهر وأنابيب اختبار',
            'estimated_amount' => 120000.00,
            'created_by' => $this->financeOfficer->id,
        ]);

        $mandate = $service->recordPaymentMandate([
            'commitment_id' => $commitment->id,
            'mandate_date' => '2026-03-25',
            'invoice_number' => 'FAC-2026-88',
            'invoice_date' => '2026-03-20',
            'reception_slip_number' => 'BL-2026-44',
            'liquidated_amount' => 120000.00,
            'payment_method' => PaymentMethod::TreasuryTransfer->value,
            'status' => MandateStatus::Approved->value,
            'authorized_by' => $this->principal->id,
            'paid_by' => $this->financeOfficer->id,
        ]);

        $this->assertDatabaseHas('payment_mandates', [
            'id' => $mandate->id,
            'mandate_number' => 'MAND/2026/001',
            'invoice_number' => 'FAC-2026-88',
        ]);

        $commitment->refresh();
        $this->assertSame(CommitmentStatus::Liquidated, $commitment->status);
        $this->assertTrue($commitment->isLiquidated());

        $line->refresh();
        $this->assertEquals(120000.00, $line->liquidated_amount);
    }

    public function test_budget_kpis_calculation(): void
    {
        $service = app(SchoolBudgetService::class);

        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 1',
            'article' => 'المادة 1',
            'allocated_amount' => 1000000.00,
            'committed_amount' => 400000.00,
            'liquidated_amount' => 300000.00,
        ]);

        $kpis = $service->getBudgetKpis(2026);

        $this->assertEquals(1000000.00, $kpis['totalAllocated']);
        $this->assertEquals(400000.00, $kpis['totalCommitted']);
        $this->assertEquals(300000.00, $kpis['totalLiquidated']);
        $this->assertEquals(600000.00, $kpis['availableBalance']);
        $this->assertEquals(40.0, $kpis['utilizationRate']);
    }

    // ─── 3. Filament Hub Livewire Component ─────────────────────────────────

    public function test_budget_hub_renders_for_authorized_users(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $developer = User::factory()->create(['role' => Role::Developer]);

        // Accountant (المحاسب)
        $this->actingAs($this->financeOfficer);
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertOk();

        // Higher Authority (المدير، مدير المنصة، المطور)
        $this->actingAs($this->principal);
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertOk();

        $this->actingAs($admin);
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertOk();

        $this->actingAs($developer);
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertOk();

        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolBudgetHub::class)
            ->assertSuccessful()
            ->assertSee('تسيير الميزانية والمحاسبة المدرسية وسجل الالتزامات')
            ->assertSee('خريطة الميزانية والاعتمادات');
    }

    public function test_budget_hub_forbids_unauthorized_users(): void
    {
        $censor = User::factory()->create(['role' => Role::Censor]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        // Teacher
        $this->actingAs($this->teacher);
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertForbidden();

        // Censor (الناظر)
        $this->actingAs($censor);
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertForbidden();

        // General Supervisor (مستشار التربية)
        $this->actingAs($supervisor);
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertForbidden();

        // Counselor (مستشار التوجيه)
        $this->actingAs($counselor);
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-budget-hub')->assertForbidden();
    }

    public function test_budget_hub_can_seed_standard_grid(): void
    {
        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolBudgetHub::class)
            ->call('seedStandardGrid')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_budget_lines', [
            'fiscal_year' => 2026,
            'chapter' => 'الباب 2: نفقات التسيير والوسائل البيداغوجية',
        ]);
    }

    public function test_budget_hub_can_create_budget_line(): void
    {
        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolBudgetHub::class)
            ->set('lineBudgetType', 'expenditure')
            ->set('lineChapter', 'الباب 5: نفقات طارئة')
            ->set('lineArticle', 'المادة 1: احتياطي استثنائي')
            ->set('lineAllocatedAmount', 250000.00)
            ->call('saveLine')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_budget_lines', [
            'fiscal_year' => 2026,
            'chapter' => 'الباب 5: نفقات طارئة',
            'allocated_amount' => 250000.00,
        ]);
    }

    public function test_budget_hub_can_create_commitment(): void
    {
        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2',
            'article' => 'المادة 2',
            'allocated_amount' => 300000.00,
        ]);

        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolBudgetHub::class)
            ->set('selectedBudgetLineId', $line->id)
            ->set('supplierName', 'مكتبة النجاح')
            ->set('orderDate', '2026-04-01')
            ->set('commitmentSubject', 'شراء سجلات إدارية')
            ->set('commitmentEstimatedAmount', 45000.00)
            ->call('saveCommitment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('budget_commitments', [
            'supplier_name' => 'مكتبة النجاح',
            'estimated_amount' => 45000.00,
        ]);
    }

    public function test_budget_hub_can_create_mandate(): void
    {
        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2',
            'article' => 'المادة 2',
            'allocated_amount' => 300000.00,
        ]);

        $commitment = BudgetCommitment::create([
            'commitment_number' => 'ENG/2026/001',
            'budget_line_id' => $line->id,
            'supplier_name' => 'مكتبة النجاح',
            'order_reference' => 'BC/2026/001',
            'order_date' => '2026-04-01',
            'subject' => 'شراء سجلات إدارية',
            'estimated_amount' => 45000.00,
            'status' => CommitmentStatus::Committed->value,
        ]);

        Livewire::actingAs($this->financeOfficer)
            ->test(SchoolBudgetHub::class)
            ->set('selectedCommitmentId', $commitment->id)
            ->set('mandateDate', '2026-04-10')
            ->set('invoiceNumber', 'INV-2026-09')
            ->set('invoiceDate', '2026-04-05')
            ->set('mandateLiquidatedAmount', 45000.00)
            ->set('paymentMethod', 'treasury_transfer')
            ->call('saveMandate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payment_mandates', [
            'invoice_number' => 'INV-2026-09',
            'liquidated_amount' => 45000.00,
        ]);
    }

    // ─── 4. Print & PDF Routes ──────────────────────────────────────────────

    public function test_print_budget_ledger_routes(): void
    {
        SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2: نفقات التسيير',
            'article' => 'المادة 1',
            'allocated_amount' => 500000.00,
        ]);

        // HTML preview
        $response = $this->actingAs($this->financeOfficer)
            ->get('/ar/print/budget/ledger/2026');

        $response->assertSuccessful();
        $response->assertSee('كشف تنفيذ ميزانية المؤسسة وسجل تسيير النفقات');

        // PDF stream
        $pdfResponse = $this->actingAs($this->financeOfficer)
            ->get('/ar/print/budget/ledger/2026/pdf');

        $pdfResponse->assertSuccessful();
    }

    public function test_print_purchase_order_routes(): void
    {
        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2',
            'article' => 'المادة 1',
            'allocated_amount' => 500000.00,
        ]);

        $commitment = BudgetCommitment::create([
            'commitment_number' => 'ENG/2026/001',
            'budget_line_id' => $line->id,
            'supplier_name' => 'شركة التجهيزات المدرسية',
            'order_reference' => 'BC/2026/001',
            'order_date' => '2026-02-15',
            'subject' => 'مستلزمات مدرسية',
            'estimated_amount' => 80000.00,
        ]);

        // HTML preview
        $response = $this->actingAs($this->financeOfficer)
            ->get("/ar/print/budget/purchase-order/{$commitment->id}");

        $response->assertSuccessful();
        $response->assertSee('BON DE COMMANDE');
        $response->assertSee('شركة التجهيزات المدرسية');

        // PDF stream
        $pdfResponse = $this->actingAs($this->financeOfficer)
            ->get("/ar/print/budget/purchase-order/{$commitment->id}/pdf");

        $pdfResponse->assertSuccessful();
    }

    public function test_print_payment_mandate_routes(): void
    {
        $line = SchoolBudgetLine::create([
            'fiscal_year' => 2026,
            'academic_year' => '2026/2027',
            'budget_type' => BudgetType::Expenditure->value,
            'chapter' => 'الباب 2',
            'article' => 'المادة 1',
            'allocated_amount' => 500000.00,
        ]);

        $commitment = BudgetCommitment::create([
            'commitment_number' => 'ENG/2026/001',
            'budget_line_id' => $line->id,
            'supplier_name' => 'شركة التجهيزات المدرسية',
            'order_reference' => 'BC/2026/001',
            'order_date' => '2026-02-15',
            'subject' => 'مستلزمات مدرسية',
            'estimated_amount' => 80000.00,
        ]);

        $mandate = PaymentMandate::create([
            'mandate_number' => 'MAND/2026/001',
            'commitment_id' => $commitment->id,
            'mandate_date' => '2026-02-28',
            'invoice_number' => 'FAC-8877',
            'invoice_date' => '2026-02-20',
            'liquidated_amount' => 80000.00,
            'payment_method' => PaymentMethod::TreasuryTransfer->value,
            'status' => MandateStatus::Approved->value,
        ]);

        // HTML preview
        $response = $this->actingAs($this->financeOfficer)
            ->get("/ar/print/budget/mandate/{$mandate->id}");

        $response->assertSuccessful();
        $response->assertSee('MANDAT DE PAIEMENT');
        $response->assertSee('FAC-8877');

        // PDF stream
        $pdfResponse = $this->actingAs($this->financeOfficer)
            ->get("/ar/print/budget/mandate/{$mandate->id}/pdf");

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
