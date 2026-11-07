<?php

namespace Tests\Feature;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Enums\InventoryReformStatus;
use App\Enums\Role;
use App\Filament\Pages\SchoolInventoryHub;
use App\Models\InventoryAllocation;
use App\Models\InventoryItem;
use App\Models\InventoryReform;
use App\Models\User;
use App\Services\SchoolInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolInventoryTest extends TestCase
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

    public function test_inventory_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('inventory_items'));
        $this->assertTrue(Schema::hasTable('inventory_allocations'));
        $this->assertTrue(Schema::hasTable('inventory_reforms'));
    }

    public function test_inventory_category_codes(): void
    {
        $this->assertSame('01', InventoryCategory::Furniture->code());
        $this->assertSame('02', InventoryCategory::ScientificLab->code());
        $this->assertSame('03', InventoryCategory::AudiovisualIt->code());
        $this->assertSame('أثاث مدرسي ومكتبي', InventoryCategory::Furniture->label());
    }

    public function test_inventory_condition_labels(): void
    {
        $this->assertSame('حالة جيدة ومستغلة', InventoryCondition::Good->label());
        $this->assertSame('مسقط من الجرد', InventoryCondition::Reformed->label());
        $this->assertSame('متدهور / بحاجة لإصلاح', InventoryCondition::Damaged->label());
    }

    public function test_inventory_reform_status_labels(): void
    {
        $this->assertSame('مقترح للإسقاط من الجرد', InventoryReformStatus::Proposed->label());
        $this->assertSame('مصادق عليه من مجلس التوجيه', InventoryReformStatus::ApprovedByBoard->label());
    }

    // ─── 2. Service Operations ──────────────────────────────────────────────

    public function test_service_generates_statutory_inventory_number(): void
    {
        $service = app(SchoolInventoryService::class);

        $number1 = $service->generateInventoryNumber(InventoryCategory::ScientificLab, 2026);
        $this->assertSame('02/0001/2026', $number1);

        InventoryItem::create([
            'inventory_number' => '02/0001/2026',
            'category' => InventoryCategory::ScientificLab,
            'designation' => 'مجهر ضوئي',
            'acquisition_date' => '2026-09-15',
            'location_name' => 'مخبر العلوم 1',
        ]);

        $number2 = $service->generateInventoryNumber(InventoryCategory::ScientificLab, 2026);
        $this->assertSame('02/0002/2026', $number2);
    }

    public function test_service_registers_inventory_item(): void
    {
        $service = app(SchoolInventoryService::class);

        $item = $service->registerItem([
            'category' => 'furniture',
            'designation' => 'مكتب أستاذ خشبي',
            'acquisition_date' => '2026-10-01',
            'location_name' => 'قاعة 04',
            'acquisition_cost' => 15000.00,
            'quantity' => 2,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $item);
        $this->assertStringStartsWith('01/', $item->inventory_number);
        $this->assertDatabaseHas('inventory_items', [
            'designation' => 'مكتب أستاذ خشبي',
            'location_name' => 'قاعة 04',
        ]);
    }

    public function test_service_updates_condition_and_reforms_asset(): void
    {
        $service = app(SchoolInventoryService::class);

        $item = InventoryItem::create([
            'inventory_number' => '01/0010/2026',
            'category' => InventoryCategory::Furniture,
            'designation' => 'كرسي تلميذ مزدوج',
            'acquisition_date' => '2026-01-10',
            'location_name' => 'قاعة 01',
            'condition' => InventoryCondition::Good,
        ]);

        $updated = $service->updateCondition($item, InventoryCondition::Reformed, 'كسر غير قابل للتلحيم');

        $this->assertSame(InventoryCondition::Reformed, $updated->condition);
        $this->assertTrue($updated->is_reformed);
        $this->assertNotNull($updated->reformed_at);
        $this->assertSame('كسر غير قابل للتلحيم', $updated->reform_reason);
    }

    public function test_service_records_room_allocation(): void
    {
        $service = app(SchoolInventoryService::class);

        $alloc = $service->recordAllocation([
            'room_name' => 'مخبر الفيزياء 1',
            'room_type' => 'مخبر علمي',
            'responsible_user_id' => $this->teacher->id,
            'notes' => 'تم تسليم العهدة كاملة',
        ]);

        $this->assertInstanceOf(InventoryAllocation::class, $alloc);
        $this->assertStringStartsWith('LOC/', $alloc->allocation_ref);
        $this->assertDatabaseHas('inventory_allocations', [
            'room_name' => 'مخبر الفيزياء 1',
        ]);
    }

    public function test_service_records_reform_and_marks_items(): void
    {
        $itemA = InventoryItem::create([
            'inventory_number' => '03/0001/2026',
            'category' => InventoryCategory::AudiovisualIt,
            'designation' => 'شاشة عرض قديمة',
            'acquisition_date' => '2020-05-10',
            'acquisition_cost' => 25000.00,
            'location_name' => 'المخزن',
        ]);

        $itemB = InventoryItem::create([
            'inventory_number' => '03/0002/2026',
            'category' => InventoryCategory::AudiovisualIt,
            'designation' => 'طابعة ليزرية معطلة',
            'acquisition_date' => '2019-03-12',
            'acquisition_cost' => 18000.00,
            'location_name' => 'المخزن',
        ]);

        $service = app(SchoolInventoryService::class);
        $reform = $service->recordReform([
            'reform_date' => '2026-11-01',
            'committee_president_id' => $this->principal->id,
            'commission_observations' => 'تقادم غير قابل للإصلاح',
        ], [$itemA->id, $itemB->id]);

        $this->assertInstanceOf(InventoryReform::class, $reform);
        $this->assertSame(2, $reform->total_items_count);
        $this->assertEquals(43000.00, $reform->total_estimated_value);

        $this->assertTrue($itemA->fresh()->is_reformed);
        $this->assertTrue($itemB->fresh()->is_reformed);
    }

    // ─── 3. Print Routes & Access Control ───────────────────────────────────

    public function test_print_general_ledger_accessible_to_finance_officer(): void
    {
        $this->actingAs($this->financeOfficer);

        $response = $this->get(route('print.inventory.general-ledger', [
            'locale' => 'ar',
            'category' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee('سجل الجرد الدائم العام والخاص');
    }

    public function test_print_general_ledger_forbidden_to_teacher(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('print.inventory.general-ledger', [
            'locale' => 'ar',
        ]));

        $response->assertForbidden();
    }

    public function test_print_allocation_sheet_accessible(): void
    {
        $this->actingAs($this->principal);

        $alloc = InventoryAllocation::create([
            'allocation_ref' => 'LOC/2026/099',
            'room_name' => 'المكتبة المدرسية',
            'room_type' => 'المكتبة',
        ]);

        $response = $this->get(route('print.inventory.allocation-sheet', [
            'locale' => 'ar',
            'allocation' => $alloc->id,
        ]));

        $response->assertOk();
        $response->assertSee('بطاقة تخصيص العتاد والتجهيز');
    }

    public function test_print_reform_pv_accessible(): void
    {
        $this->actingAs($this->principal);

        $reform = InventoryReform::create([
            'reform_number' => 'REF/2026/099',
            'reform_date' => '2026-11-05',
        ]);

        $response = $this->get(route('print.inventory.reform-pv', [
            'locale' => 'ar',
            'reform' => $reform->id,
        ]));

        $response->assertOk();
        $response->assertSee('محضر معاينة وإسقاط التجهيزات غير الصالحة للاستعمال');
    }

    public function test_school_inventory_hub_filament_page_accessible(): void
    {
        $this->actingAs($this->financeOfficer);

        $response = $this->get(SchoolInventoryHub::getUrl());
        $response->assertOk();
    }

    // ─── 4. Health Check ────────────────────────────────────────────────────

    public function test_health_check_verifies_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
