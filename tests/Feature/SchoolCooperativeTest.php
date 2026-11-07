<?php

namespace Tests\Feature;

use App\Enums\BotanicalPlantCategory;
use App\Enums\CooperativeProjectStatus;
use App\Enums\CooperativeProjectType;
use App\Enums\CooperativeTransactionType;
use App\Enums\EcoActionCategory;
use App\Enums\GardenIrrigationType;
use App\Enums\GardenPlotStatus;
use App\Enums\PlantHealthStatus;
use App\Enums\Role;
use App\Filament\Pages\SchoolCooperativeHub;
use App\Models\BotanicalPlantsInventory;
use App\Models\CooperativeFinancialLedger;
use App\Models\CooperativeProjectsInitiative;
use App\Models\EcoGreenClubAction;
use App\Models\PedagogicalGardenPlot;
use App\Models\User;
use App\Services\SchoolCooperativeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolCooperativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cooperative_tables_exist(): void
    {
        $tables = [
            'school_cooperatives',
            'cooperative_projects_initiatives',
            'cooperative_financial_ledger',
            'pedagogical_garden_plots',
            'botanical_plants_inventory',
            'eco_green_club_actions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist.");
        }
    }

    public function test_cooperative_enums_and_labels(): void
    {
        $this->assertSame('solidarity_charity', CooperativeProjectType::SolidarityCharity->value);
        $this->assertNotEmpty(CooperativeProjectType::SolidarityCharity->label());
        $this->assertNotEmpty(CooperativeProjectType::SolidarityCharity->badgeColor());

        $this->assertSame('active', CooperativeProjectStatus::Active->value);
        $this->assertNotEmpty(CooperativeProjectStatus::Active->label());
        $this->assertNotEmpty(CooperativeProjectStatus::Active->badgeColor());

        $this->assertSame('income_membership_dues', CooperativeTransactionType::IncomeMembershipDues->value);
        $this->assertTrue(CooperativeTransactionType::IncomeMembershipDues->isIncome());
        $this->assertFalse(CooperativeTransactionType::ExpenseSolidarityKits->isIncome());
        $this->assertSame('success', CooperativeTransactionType::IncomeMembershipDues->badgeColor());
        $this->assertSame('danger', CooperativeTransactionType::ExpenseSolidarityKits->badgeColor());

        $this->assertSame('drip_irrigation', GardenIrrigationType::DripIrrigation->value);
        $this->assertNotEmpty(GardenIrrigationType::DripIrrigation->label());
        $this->assertNotEmpty(GardenIrrigationType::DripIrrigation->badgeColor());

        $this->assertSame('active_planted', GardenPlotStatus::ActivePlanted->value);
        $this->assertNotEmpty(GardenPlotStatus::ActivePlanted->label());
        $this->assertNotEmpty(GardenPlotStatus::ActivePlanted->badgeColor());

        $this->assertSame('aromatic_medicinal', BotanicalPlantCategory::AromaticMedicinal->value);
        $this->assertNotEmpty(BotanicalPlantCategory::AromaticMedicinal->label());
        $this->assertNotEmpty(BotanicalPlantCategory::AromaticMedicinal->badgeColor());

        $this->assertSame('thriving_good', PlantHealthStatus::ThrivingGood->value);
        $this->assertNotEmpty(PlantHealthStatus::ThrivingGood->label());
        $this->assertNotEmpty(PlantHealthStatus::ThrivingGood->badgeColor());

        $this->assertSame('tree_planting', EcoActionCategory::TreePlanting->value);
        $this->assertNotEmpty(EcoActionCategory::TreePlanting->label());
        $this->assertNotEmpty(EcoActionCategory::TreePlanting->badgeColor());
    }

    public function test_user_can_manage_school_cooperative_permissions(): void
    {
        $allowedRoles = [
            Role::Principal,
            Role::FinanceOfficer,
            Role::GeneralSupervisor,
            Role::Teacher,
            Role::Censor,
            Role::Admin,
            Role::Developer,
        ];

        foreach ($allowedRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($user->canManageSchoolCooperative(), "Role {$role->value} should be allowed.");
        }

        $forbiddenRoles = [
            Role::Counselor,
        ];

        foreach ($forbiddenRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($user->canManageSchoolCooperative(), "Role {$role->value} should be forbidden.");
        }
    }

    public function test_service_generates_sequential_codes(): void
    {
        $service = app(SchoolCooperativeService::class);

        $prjCode = $service->generateProjectCode('2026/2027');
        $this->assertMatchesRegularExpression('/^COOP-PRJ-2026-2027-\d{3}$/', $prjCode);

        $txRef = $service->generateTransactionReference('2026/2027');
        $this->assertMatchesRegularExpression('/^COOP-FIN-2026-2027-\d{3}$/', $txRef);

        $plotCode = $service->generateGardenPlotCode();
        $this->assertMatchesRegularExpression('/^PLOT-\d{2}$/', $plotCode);

        $pltCode = $service->generatePlantCode();
        $this->assertMatchesRegularExpression('/^PLT-\d{3}$/', $pltCode);

        $ecoCode = $service->generateEcoActionCode('2026/2027');
        $this->assertMatchesRegularExpression('/^ECO-ACT-2026-2027-\d{3}$/', $ecoCode);
    }

    public function test_service_gets_or_creates_cooperative(): void
    {
        $service = app(SchoolCooperativeService::class);
        $coop = $service->getOrCreateCooperative('2026/2027');

        $this->assertNotNull($coop);
        $this->assertSame('2026/2027', $coop->academic_year);
        $this->assertNotEmpty($coop->cooperative_name);
        $this->assertNotEmpty($coop->motto_or_slogan);

        // Repeated call returns existing instance
        $coop2 = $service->getOrCreateCooperative('2026/2027');
        $this->assertSame($coop->id, $coop2->id);
    }

    public function test_service_creates_cooperative_project_and_updates_status(): void
    {
        $service = app(SchoolCooperativeService::class);
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $project = $service->createProject([
            'academic_year' => '2026/2027',
            'title' => 'معرض المشغولات اليدوية والرسم المدرسي',
            'project_type' => CooperativeProjectType::HandicraftsExhibition,
            'target_audience' => 'تلاميذ وأولياء الأمور',
            'start_date' => '2026-11-01',
            'budget_allocated' => 20000.00,
            'status' => CooperativeProjectStatus::Active,
            'coordinator_user_id' => $teacher->id,
        ]);

        $this->assertNotNull($project);
        $this->assertSame('معرض المشغولات اليدوية والرسم المدرسي', $project->title);
        $this->assertSame(CooperativeProjectStatus::Active, $project->status);
        $this->assertSame(20000.00, (float) $project->budget_allocated);

        $updated = $service->updateProjectStatus($project->id, CooperativeProjectStatus::Completed, 18500.00, 'تم تحقيق مبيعات ممتازة وتكريم الفائزين.');
        $this->assertSame(CooperativeProjectStatus::Completed, $updated->status);
        $this->assertSame(18500.00, (float) $updated->budget_spent);
        $this->assertStringContainsString('تم تحقيق مبيعات', $updated->achievements_notes);
    }

    public function test_service_bootstraps_default_garden_plots(): void
    {
        $service = app(SchoolCooperativeService::class);
        $created = $service->bootstrapDefaultGardenPlots();

        $this->assertSame(4, $created);
        $this->assertSame(4, PedagogicalGardenPlot::count());

        // Idempotent: second call creates 0
        $createdAgain = $service->bootstrapDefaultGardenPlots();
        $this->assertSame(0, $createdAgain);
    }

    public function test_service_adds_botanical_plants(): void
    {
        $service = app(SchoolCooperativeService::class);
        $service->bootstrapDefaultGardenPlots();
        $plot = PedagogicalGardenPlot::first();

        $plant = $service->addBotanicalPlant([
            'pedagogical_garden_plot_id' => $plot->id,
            'common_name_ar' => 'إكليل الجبل (اليازير)',
            'common_name_fr' => 'Romarin',
            'botanical_name' => 'Rosmarinus officinalis',
            'plant_category' => BotanicalPlantCategory::AromaticMedicinal,
            'quantity_planted' => 25,
            'planting_date' => '2026-10-15',
            'health_status' => PlantHealthStatus::ThrivingGood,
        ]);

        $this->assertNotNull($plant);
        $this->assertSame('إكليل الجبل (اليازير)', $plant->common_name_ar);
        $this->assertSame(25, $plant->quantity_planted);
        $this->assertSame($plot->id, $plant->pedagogical_garden_plot_id);
    }

    public function test_service_records_eco_green_club_action(): void
    {
        $service = app(SchoolCooperativeService::class);
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $action = $service->recordEcoAction([
            'academic_year' => '2026/2027',
            'action_title' => 'حملة غرس 30 شجرة بالمحيط المدرسي',
            'action_category' => EcoActionCategory::TreePlanting,
            'execution_date' => '2026-11-05',
            'participants_count' => 45,
            'trees_planted_count' => 30,
            'waste_collected_kg' => 0.00,
            'supervising_teacher_id' => $teacher->id,
            'partner_organization' => 'محافظة الغابات',
        ]);

        $this->assertNotNull($action);
        $this->assertSame(30, $action->trees_planted_count);
        $this->assertSame(45, $action->participants_count);
        $this->assertSame('محافظة الغابات', $action->partner_organization);
    }

    public function test_service_records_financial_transactions_and_updates_treasury_balance(): void
    {
        $service = app(SchoolCooperativeService::class);
        $coop = $service->getOrCreateCooperative('2026/2027');
        $coop->update(['treasury_balance' => 0.00]);

        $teacher = User::factory()->create(['role' => Role::Teacher]);

        // Record income
        $txIncome = $service->recordFinancialTransaction([
            'academic_year' => '2026/2027',
            'transaction_date' => '2026-11-01',
            'transaction_type' => CooperativeTransactionType::IncomeMembershipDues,
            'amount_dzd' => 10000.00,
            'beneficiary_or_source' => 'اشتراكات التلاميذ السنوية',
            'recorded_by_user_id' => $teacher->id,
        ]);

        $this->assertNotNull($txIncome);
        $coop->refresh();
        $this->assertSame(10000.00, (float) $coop->treasury_balance);

        // Record expense
        $txExpense = $service->recordFinancialTransaction([
            'academic_year' => '2026/2027',
            'transaction_date' => '2026-11-03',
            'transaction_type' => CooperativeTransactionType::ExpenseGardenToolsSeeds,
            'amount_dzd' => 3500.00,
            'beneficiary_or_source' => 'شراء بذور وشتلات للمشتلة',
            'recorded_by_user_id' => $teacher->id,
        ]);

        $this->assertNotNull($txExpense);
        $coop->refresh();
        $this->assertSame(6500.00, (float) $coop->treasury_balance);
    }

    public function test_service_computes_cooperative_telemetry(): void
    {
        $service = app(SchoolCooperativeService::class);
        $service->bootstrapDefaultGardenPlots();
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $service->createProject([
            'academic_year' => '2026/2027',
            'title' => 'مشروع التضامن المدرسي',
            'project_type' => CooperativeProjectType::SolidarityCharity,
            'start_date' => '2026-11-01',
            'status' => CooperativeProjectStatus::Active,
        ]);

        $service->recordEcoAction([
            'academic_year' => '2026/2027',
            'action_title' => 'يوم الفرز الانتقائي للبلاستيك',
            'action_category' => EcoActionCategory::WasteRecycling,
            'execution_date' => '2026-11-04',
            'participants_count' => 25,
            'waste_collected_kg' => 45.5,
        ]);

        $telemetry = $service->getCooperativeTelemetry('2026/2027');

        $this->assertIsArray($telemetry);
        $this->assertSame('2026/2027', $telemetry['academicYear']);
        $this->assertGreaterThanOrEqual(1, $telemetry['activeProjectsCount']);
        $this->assertSame(4, $telemetry['gardenPlotsCount']);
        $this->assertGreaterThanOrEqual(45.5, $telemetry['wasteRecycledKg']);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolCooperativeService::class);
        $service->bootstrapDefaultGardenPlots();
        $plot = PedagogicalGardenPlot::first();

        $project = $service->createProject([
            'academic_year' => '2026/2027',
            'title' => 'مشروع بيئي بيداغوجي',
            'project_type' => CooperativeProjectType::BotanicalGardening,
            'start_date' => '2026-11-01',
            'status' => CooperativeProjectStatus::Active,
        ]);

        $action = $service->recordEcoAction([
            'academic_year' => '2026/2027',
            'action_title' => 'غرس شتلات الزيتون',
            'action_category' => EcoActionCategory::TreePlanting,
            'execution_date' => '2026-11-05',
            'participants_count' => 20,
            'trees_planted_count' => 10,
        ]);

        $prjData = $service->assembleCooperativeProjectFicheData($project);
        $this->assertArrayHasKey('project', $prjData);
        $this->assertArrayHasKey('cooperative', $prjData);

        $plotData = $service->assembleBotanicalGardenFicheData($plot);
        $this->assertArrayHasKey('plot', $plotData);
        $this->assertArrayHasKey('plants', $plotData);

        $actData = $service->assembleEcoActionReportData($action);
        $this->assertArrayHasKey('action', $actData);
        $this->assertArrayHasKey('supervising_teacher', $actData);

        $bilanData = $service->assembleCooperativeAnnualBilanData('2026/2027');
        $this->assertArrayHasKey('projects', $bilanData);
        $this->assertArrayHasKey('transactions', $bilanData);
        $this->assertArrayHasKey('telemetry', $bilanData);
    }

    public function test_print_project_fiche_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $service = app(SchoolCooperativeService::class);
        $project = $service->createProject([
            'academic_year' => '2026/2027',
            'title' => 'مشروع التضامن مع التلاميذ المعوزين',
            'project_type' => CooperativeProjectType::SolidarityCharity,
            'start_date' => '2026-11-01',
            'status' => CooperativeProjectStatus::Active,
        ]);

        $responseHtml = $this->get(route('print.cooperative.project-fiche', [
            'locale' => 'ar',
            'project' => $project->id,
        ]));
        $responseHtml->assertOk();
        $responseHtml->assertSee($project->project_code);

        $responsePdf = $this->get(route('print.cooperative.project-fiche.pdf', [
            'locale' => 'ar',
            'project' => $project->id,
        ]));
        $responsePdf->assertOk();
    }

    public function test_print_garden_plot_fiche_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $service = app(SchoolCooperativeService::class);
        $service->bootstrapDefaultGardenPlots();
        $plot = PedagogicalGardenPlot::first();

        $responseHtml = $this->get(route('print.cooperative.garden-fiche', [
            'locale' => 'ar',
            'plot' => $plot->id,
        ]));
        $responseHtml->assertOk();
        $responseHtml->assertSee($plot->plot_code);

        $responsePdf = $this->get(route('print.cooperative.garden-fiche.pdf', [
            'locale' => 'ar',
            'plot' => $plot->id,
        ]));
        $responsePdf->assertOk();
    }

    public function test_print_eco_action_report_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $service = app(SchoolCooperativeService::class);
        $action = $service->recordEcoAction([
            'academic_year' => '2026/2027',
            'action_title' => 'حملة نظافة وتشجير المحيط المدرسي',
            'action_category' => EcoActionCategory::TreePlanting,
            'execution_date' => '2026-11-05',
            'participants_count' => 30,
            'trees_planted_count' => 15,
        ]);

        $responseHtml = $this->get(route('print.cooperative.eco-report', [
            'locale' => 'ar',
            'action' => $action->id,
        ]));
        $responseHtml->assertOk();
        $responseHtml->assertSee($action->action_code);

        $responsePdf = $this->get(route('print.cooperative.eco-report.pdf', [
            'locale' => 'ar',
            'action' => $action->id,
        ]));
        $responsePdf->assertOk();
    }

    public function test_print_cooperative_annual_bilan_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $responseHtml = $this->get(route('print.cooperative.annual-bilan', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $responseHtml->assertOk();

        $responsePdf = $this->get(route('print.cooperative.annual-bilan.pdf', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $responsePdf->assertOk();
    }

    public function test_cooperative_hub_renders_for_authorized_users(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($teacher);

        Livewire::test(SchoolCooperativeHub::class)
            ->assertSuccessful()
            ->assertSee(__('التعاونية المدرسية، الحديقة البيداغوجية والنوادي الخضراء'))
            ->call('switchTab', 'garden')
            ->assertSuccessful()
            ->call('switchTab', 'eco_club')
            ->assertSuccessful()
            ->call('switchTab', 'treasury')
            ->assertSuccessful();
    }

    public function test_cooperative_hub_forbids_unauthorized_users(): void
    {
        $counselor = User::factory()->create(['role' => Role::Counselor]);
        $this->actingAs($counselor);

        $this->assertFalse(SchoolCooperativeHub::canAccess());
    }

    public function test_cooperative_hub_can_save_project_plot_plant_action_transaction(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($teacher);

        Livewire::test(SchoolCooperativeHub::class)
            ->set('projectTitle', 'مشروع الحديقة البيئية والتضامن')
            ->set('projectType', 'botanical_gardening')
            ->set('startDate', '2026-11-01')
            ->set('budgetAllocated', 12000.00)
            ->call('saveProject')
            ->assertHasNoErrors();

        $this->assertTrue(CooperativeProjectsInitiative::where('title', 'مشروع الحديقة البيئية والتضامن')->exists());

        Livewire::test(SchoolCooperativeHub::class)
            ->set('plotName', 'حوض تجارب البستنة العضوية')
            ->set('surfaceArea', 50.00)
            ->set('irrigationType', 'drip_irrigation')
            ->call('savePlot')
            ->assertHasNoErrors();

        $plot = PedagogicalGardenPlot::where('plot_name', 'حوض تجارب البستنة العضوية')->first();
        $this->assertNotNull($plot);

        Livewire::test(SchoolCooperativeHub::class)
            ->set('selectedPlotId', $plot->id)
            ->set('commonNameAr', 'شجيرة الليمون')
            ->set('quantityPlanted', 5)
            ->set('plantCategory', 'fruit_tree')
            ->call('savePlant')
            ->assertHasNoErrors();

        $this->assertTrue(BotanicalPlantsInventory::where('common_name_ar', 'شجيرة الليمون')->exists());

        Livewire::test(SchoolCooperativeHub::class)
            ->set('ecoTitle', 'حملة تنظيف وغرس الأشجار')
            ->set('ecoCategory', 'tree_planting')
            ->set('executionDate', '2026-11-06')
            ->set('participantsCount', 40)
            ->set('treesPlantedCount', 20)
            ->call('saveEcoAction')
            ->assertHasNoErrors();

        $this->assertTrue(EcoGreenClubAction::where('action_title', 'حملة تنظيف وغرس الأشجار')->exists());

        Livewire::test(SchoolCooperativeHub::class)
            ->set('transactionDate', '2026-11-06')
            ->set('transactionType', 'income_membership_dues')
            ->set('transactionAmount', 8000.00)
            ->set('beneficiaryOrSource', 'اشتراكات الأعضاء')
            ->call('saveTransaction')
            ->assertHasNoErrors();

        $this->assertTrue(CooperativeFinancialLedger::where('beneficiary_or_source', 'اشتراكات الأعضاء')->exists());
    }

    public function test_health_check_verifies_schema(): void
    {
        $this->artisan('sajel:health-check')
            ->assertSuccessful()
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
