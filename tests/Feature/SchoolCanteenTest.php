<?php

namespace Tests\Feature;

use App\Enums\CanteenSheetStatus;
use App\Enums\ChronicConditionType;
use App\Enums\FoodItemCategory;
use App\Enums\Role;
use App\Filament\Pages\SchoolCanteenHub;
use App\Models\CanteenDailySheet;
use App\Models\CanteenFoodItem;
use App\Models\CanteenWeeklyMenu;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolCanteenService;
use App\Services\SchoolRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolCanteenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        SchoolSetting::create([
            'school_name' => 'متوسطة الشهيد زبانة',
            'academic_year' => '2026/2027',
            'directorate_name' => 'مديرية التربية لولاية وهران',
        ]);
    }

    public function test_standard_algerian_pantry_items_seeding_and_low_stock(): void
    {
        $service = app(SchoolCanteenService::class);

        $seededCount = $service->seedStandardAlgerianPantryItems();
        $this->assertGreaterThan(10, $seededCount);

        // Seeding again is idempotent
        $secondRun = $service->seedStandardAlgerianPantryItems();
        $this->assertSame(0, $secondRun);

        $this->assertDatabaseHas('canteen_food_items', [
            'name' => 'عدس يابس ممتاز',
            'category' => FoodItemCategory::DryGoods->value,
        ]);

        $this->assertDatabaseHas('canteen_food_items', [
            'name' => 'زيت المائدة النباتي',
            'category' => FoodItemCategory::OilsCondiments->value,
        ]);

        // Low stock detection
        $item = CanteenFoodItem::where('name', 'عدس يابس ممتاز')->first();
        $item->current_stock = 15.0;
        $item->safety_threshold = 20.0;
        $item->save();

        $this->assertTrue($item->isStockLow());
        $this->assertGreaterThanOrEqual(1, CanteenFoodItem::lowStock()->count());
    }

    public function test_daily_sheet_lifecycle_and_beneficiary_calculation(): void
    {
        // Create half-board and day students
        Student::factory()->count(15)->create(['regime' => 'half_board']);
        Student::factory()->count(5)->create(['regime' => 'external']);

        $service = app(SchoolCanteenService::class);
        $date = '2026-11-15';
        $sheet = $service->getOrCreateDailySheet($date, '2026/2027');

        $this->assertInstanceOf(CanteenDailySheet::class, $sheet);
        $this->assertSame('2026-11-15', $sheet->sheet_date->format('Y-m-d'));
        $this->assertSame(15, $sheet->half_board_students_present);
        $this->assertSame(33, $sheet->total_beneficiaries_count); // 15 + 0 + 12 + 6
        $this->assertSame(CanteenSheetStatus::Draft, $sheet->status);
    }

    public function test_canteen_stock_consumption_and_cost_recalculation(): void
    {
        $service = app(SchoolCanteenService::class);
        $sheet = $service->getOrCreateDailySheet('2026-11-16', '2026/2027');

        $item = CanteenFoodItem::factory()->create([
            'name' => 'لحم بقري محلي',
            'category' => FoodItemCategory::MeatFish,
            'unit' => 'كغ',
            'unit_price' => 1600.00,
            'current_stock' => 50.00,
            'safety_threshold' => 10.00,
        ]);

        // Add consumption entry: 20 kg
        $entry = $service->addConsumptionEntry($sheet, $item->id, 20.0);

        $this->assertDatabaseHas('canteen_consumption_entries', [
            'id' => $entry->id,
            'canteen_daily_sheet_id' => $sheet->id,
            'canteen_food_item_id' => $item->id,
            'quantity_consumed' => 20.0,
            'unit_price' => 1600.00,
            'total_item_cost' => 32000.00,
        ]);

        $item->refresh();
        $this->assertEquals(30.0, (float) $item->current_stock);

        $sheet->refresh();
        $this->assertEquals(32000.00, (float) $sheet->total_cost);
        $this->assertGreaterThan(0, (float) $sheet->cost_per_meal);

        // Delete consumption entry: restores stock
        $service->removeConsumptionEntry($entry);

        $item->refresh();
        $this->assertEquals(50.0, (float) $item->current_stock);

        $sheet->refresh();
        $this->assertEquals(0.00, (float) $sheet->total_cost);
        $this->assertEquals(0.00, (float) $sheet->cost_per_meal);
    }

    public function test_certify_daily_sheet(): void
    {
        $admin = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolCanteenService::class);
        $sheet = $service->getOrCreateDailySheet('2026-11-17', '2026/2027');

        $this->assertFalse($sheet->isCertified());

        $certifiedSheet = $service->certifyDailySheet($sheet, $admin);

        $this->assertTrue($certifiedSheet->isCertified());
        $this->assertSame(CanteenSheetStatus::Certified, $certifiedSheet->status);
        $this->assertSame($admin->id, $certifiedSheet->certified_by_id);
        $this->assertNotNull($certifiedSheet->certified_at);
    }

    public function test_dietary_allergy_and_chronic_illness_alerts_for_half_boarders(): void
    {
        $halfBoardStudent = Student::factory()->create([
            'regime' => 'half_board',
            'first_name' => 'يوسف',
            'last_name' => 'قاسمي',
        ]);

        $dayStudent = Student::factory()->create([
            'regime' => 'external',
            'first_name' => 'أمين',
            'last_name' => 'بن علي',
        ]);

        StudentMedicalRecord::factory()->create([
            'student_id' => $halfBoardStudent->id,
            'has_chronic_illness' => true,
            'chronic_condition_type' => ChronicConditionType::SevereAllergy,
            'chronic_illness_details' => 'حساسية مفرطة من الفول السوداني ومشتقات الحليب',
            'emergency_protocol_notes' => 'حقنة أدرينالين فورية وتجنب مشتقات الحليب والبيض',
        ]);

        StudentMedicalRecord::factory()->create([
            'student_id' => $dayStudent->id,
            'has_chronic_illness' => true,
            'chronic_condition_type' => ChronicConditionType::Diabetes,
        ]);

        $service = app(SchoolCanteenService::class);
        $overview = $service->getCanteenOverview('2026/2027', '2026-11-15');

        $this->assertCount(1, $overview['studentsWithDietaryAlerts']);
        $this->assertSame($halfBoardStudent->id, $overview['studentsWithDietaryAlerts']->first()->id);
    }

    public function test_weekly_menu_management(): void
    {
        $service = app(SchoolCanteenService::class);

        $menu = $service->saveWeeklyMenu([
            'academic_year' => '2026/2027',
            'week_number' => 11,
            'week_start_date' => '2026-11-15',
            'sunday_menu' => 'شوربة خضار + كفتة لحم عجل + بطاطا + تفاح',
            'monday_menu' => 'عدس بني بالخضار + بيض مسلوق + سلطة + برتقال',
            'tuesday_menu' => 'كسكسي بالدجاج والخضار + سلطة + ياغورت',
            'wednesday_menu' => 'لوبيا بيضاء باللحم + سلطة شمندر + فاكهة موسمية',
            'thursday_menu' => 'أرز مفور بالتونة والصلصة + جبن + موزة',
            'nutrition_calories_approx' => 860,
            'is_approved_by_doctor' => true,
            'approved_by_principal' => true,
        ]);

        $this->assertDatabaseHas('canteen_weekly_menus', [
            'id' => $menu->id,
            'week_number' => 11,
            'nutrition_calories_approx' => 860,
        ]);

        $printableData = $service->assembleWeeklyMenuData($menu);
        $this->assertArrayHasKey('menu', $printableData);
        $this->assertArrayHasKey('school', $printableData);
        $this->assertArrayHasKey('academicYear', $printableData);
    }

    public function test_school_canteen_print_preview_and_pdf_routes(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $sheet = CanteenDailySheet::factory()->create();
        $menu = CanteenWeeklyMenu::factory()->create();

        // 1. Daily Sheet Preview & PDF (Authorized Bursar)
        $sheetResponse = $this->actingAs($bursar)->get(route('print.canteen.daily-sheet', [
            'locale' => 'ar',
            'sheet' => $sheet->id,
        ]));
        $sheetResponse->assertStatus(200);
        $sheetResponse->assertSee($sheet->reference_number);

        $sheetPdf = $this->actingAs($bursar)->get(route('print.canteen.daily-sheet.pdf', [
            'locale' => 'ar',
            'sheet' => $sheet->id,
        ]));
        $sheetPdf->assertStatus(200);

        // 2. Weekly Menu Preview & PDF (Teacher can view menu poster)
        $menuResponse = $this->actingAs($teacher)->get(route('print.canteen.weekly-menu', [
            'locale' => 'ar',
            'menu' => $menu->id,
        ]));
        $menuResponse->assertStatus(200);
        $menuResponse->assertSee('البرنامج الأسبوعي للوجبات الغذائية');

        $menuPdf = $this->actingAs($teacher)->get(route('print.canteen.weekly-menu.pdf', [
            'locale' => 'ar',
            'menu' => $menu->id,
        ]));
        $menuPdf->assertStatus(200);

        // 3. Periodic Bilan Preview & PDF
        $bilanResponse = $this->actingAs($bursar)->get(route('print.canteen.periodic-bilan', [
            'locale' => 'ar',
            'month' => '11',
        ]));
        $bilanResponse->assertStatus(200);
        $bilanResponse->assertSee('الكشف الشهري لحركة الإطعام والوجبات المقدمة');

        $bilanPdf = $this->actingAs($bursar)->get(route('print.canteen.periodic-bilan.pdf', [
            'locale' => 'ar',
            'month' => '11',
        ]));
        $bilanPdf->assertStatus(200);

        // 4. Unauthorized Access: Counselor should get 403 on Daily Sheet FJC
        $deniedResponse = $this->actingAs($counselor)->get(route('print.canteen.daily-sheet', [
            'locale' => 'ar',
            'sheet' => $sheet->id,
        ]));
        $deniedResponse->assertStatus(403);
    }

    public function test_school_canteen_hub_filament_page(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $foodItem = CanteenFoodItem::factory()->create([
            'name' => 'أرز بسمتي أبيض',
            'unit_price' => 140.00,
            'current_stock' => 100.0,
        ]);

        Livewire::actingAs($bursar)
            ->test(SchoolCanteenHub::class)
            ->assertOk()
            ->set('selectedDate', '2026-11-20')
            ->call('setTab', 'pantry')
            ->assertSet('activeTab', 'pantry')
            ->call('setTab', 'menus')
            ->assertSet('activeTab', 'menus')
            ->call('setTab', 'bilan')
            ->assertSet('activeTab', 'bilan')
            ->call('setTab', 'sheet')
            ->assertSet('activeTab', 'sheet')
            // Test modal actions
            ->call('openPantryModal')
            ->assertSet('isPantryModalOpen', true)
            ->set('pantryName', 'فاصولياء بيضاء ممتازة')
            ->set('pantryCategory', 'dry_goods')
            ->set('pantryUnit', 'كغ')
            ->set('pantryUnitPrice', 240.0)
            ->set('pantryCurrentStock', 60.0)
            ->set('pantryThreshold', 15.0)
            ->call('savePantryItem')
            ->assertSet('isPantryModalOpen', false)
            ->assertHasNoErrors()
            // Test add consumption entry
            ->call('openAddEntryModal')
            ->assertSet('isAddEntryModalOpen', true)
            ->set('entryFoodItemId', $foodItem->id)
            ->set('entryQuantity', 25.0)
            ->set('entryUnitPrice', 140.0)
            ->call('saveConsumptionEntry')
            ->assertSet('isAddEntryModalOpen', false)
            ->assertHasNoErrors()
            // Test certify sheet
            ->call('certifySheet')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('canteen_food_items', [
            'name' => 'فاصولياء بيضاء ممتازة',
            'unit_price' => 240.0,
        ]);

        $this->assertDatabaseHas('canteen_consumption_entries', [
            'canteen_food_item_id' => $foodItem->id,
            'quantity_consumed' => 25.0,
        ]);

        $sheet = CanteenDailySheet::where('sheet_date', '2026-11-20')->first();
        $this->assertNotNull($sheet);
        $this->assertTrue($sheet->isCertified());
    }

    public function test_backup_and_health_check_include_all_canteen_tables(): void
    {
        $backupService = app(SchoolBackupService::class);
        $restoreService = app(SchoolRestoreService::class);
        $backupTables = $backupService->getTables();
        $restoreOrder = $restoreService->getTables();

        $canteenTables = [
            'canteen_food_items',
            'canteen_daily_sheets',
            'canteen_consumption_entries',
            'canteen_weekly_menus',
        ];

        foreach ($canteenTables as $table) {
            $this->assertContains($table, $backupTables, "Backup service missing {$table}");
            $this->assertContains($table, $restoreOrder, "Restore service missing {$table}");
        }

        $this->artisan('sajel:health-check')
            ->expectsOutputToContain('All required tables present')
            ->assertExitCode(0);
    }
}
