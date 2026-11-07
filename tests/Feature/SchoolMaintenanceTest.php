<?php

namespace Tests\Feature;

use App\Enums\BoilerFuelType;
use App\Enums\FacilityCondition;
use App\Enums\FacilityType;
use App\Enums\HeatingInspectionStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Enums\UtilityMeterType;
use App\Enums\UtilityPaymentStatus;
use App\Enums\WaterPotabilityStatus;
use App\Enums\WaterTankType;
use App\Filament\Pages\SchoolMaintenanceHub;
use App\Models\EnergyUtilityMeter;
use App\Models\SchoolBuildingFacility;
use App\Models\User;
use App\Models\WaterTankHygieneInspection;
use App\Services\SchoolMaintenanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_tables_exist(): void
    {
        $tables = [
            'school_building_facilities',
            'maintenance_work_orders',
            'central_heating_logs',
            'water_tank_hygiene_inspections',
            'energy_utility_meters',
            'utility_consumption_records',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist.");
        }
    }

    public function test_maintenance_enums_and_labels(): void
    {
        $this->assertNotEmpty(FacilityType::PedagogicalWing->label());
        $this->assertNotEmpty(FacilityCondition::Good->label());
        $this->assertNotEmpty(MaintenanceCategory::CentralHeating->label());
        $this->assertNotEmpty(MaintenancePriority::EmergencySafety->label());
        $this->assertNotEmpty(MaintenanceStatus::Completed->label());
        $this->assertNotEmpty(BoilerFuelType::NaturalGas->label());
        $this->assertNotEmpty(HeatingInspectionStatus::CompliantApproved->label());
        $this->assertNotEmpty(WaterTankType::UndergroundBache->label());
        $this->assertNotEmpty(WaterPotabilityStatus::CompliantPotable->label());
        $this->assertNotEmpty(UtilityMeterType::Electricity->label());
        $this->assertEquals('kWh', UtilityMeterType::Electricity->unitOfMeasure());
        $this->assertEquals('m³', UtilityMeterType::PotableWater->unitOfMeasure());
        $this->assertNotEmpty(UtilityPaymentStatus::AuditedApproved->label());
    }

    public function test_user_can_manage_school_maintenance_permissions(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($principal->canManageSchoolMaintenance());
        $this->assertTrue($bursar->canManageSchoolMaintenance());
        $this->assertTrue($supervisor->canManageSchoolMaintenance());
        $this->assertTrue($censor->canManageSchoolMaintenance());
        $this->assertFalse($teacher->canManageSchoolMaintenance());
        $this->assertFalse($counselor->canManageSchoolMaintenance());
    }

    public function test_service_generates_sequential_codes(): void
    {
        $service = app(SchoolMaintenanceService::class);

        $orderNum = $service->generateWorkOrderNumber('2026/2027');
        $this->assertStringStartsWith('ORD-2026-2027-', $orderNum);

        $facCode = $service->generateFacilityCode(FacilityType::PedagogicalWing);
        $this->assertEquals('FAC-PED-01', $facCode);

        $chaufRef = $service->generateHeatingLogReference('2026/2027');
        $this->assertStringStartsWith('CHAUF-2026-2027-', $chaufRef);

        $bacheRef = $service->generateWaterInspectionReference('2026/2027');
        $this->assertStringStartsWith('BACHE-2026-2027-', $bacheRef);

        $billRef = $service->generateBillReference('2026/2027');
        $this->assertStringStartsWith('BILL-2026-2027-', $billRef);

        $meterCode = $service->generateMeterCode(UtilityMeterType::Electricity);
        $this->assertEquals('MTR-ELEC-01', $meterCode);
    }

    public function test_service_bootstraps_and_creates_facility(): void
    {
        $service = app(SchoolMaintenanceService::class);
        $count = $service->bootstrapDefaultFacilities();
        $this->assertGreaterThanOrEqual(5, $count);

        $facility = $service->createFacility([
            'facility_name' => 'جناح المخابر الجديد',
            'facility_type' => FacilityType::WorkshopLab,
            'building_block' => 'المبنى E',
            'total_rooms_count' => 4,
            'condition_rating' => FacilityCondition::Good,
        ]);

        $this->assertInstanceOf(SchoolBuildingFacility::class, $facility);
        $this->assertDatabaseHas('school_building_facilities', [
            'facility_name' => 'جناح المخابر الجديد',
            'building_block' => 'المبنى E',
        ]);
    }

    public function test_service_creates_and_updates_work_order(): void
    {
        $service = app(SchoolMaintenanceService::class);
        $facility = $service->createFacility([
            'facility_name' => 'الجناح الإداري',
            'facility_type' => FacilityType::AdministrativeWing,
        ]);

        $order = $service->createWorkOrder([
            'academic_year' => '2026/2027',
            'facility_id' => $facility->id,
            'exact_location' => 'مكتب المقتصد',
            'category' => MaintenanceCategory::Electricity,
            'priority_level' => MaintenancePriority::Urgent,
            'issue_description' => 'انقطاع التيار الكهربائي في القواطع الرئيسية',
            'reported_by_name' => 'المقتصد',
            'report_date' => Carbon::today(),
            'estimated_cost' => 15000,
        ]);

        $this->assertEquals(MaintenanceStatus::Reported, $order->status);
        $this->assertEquals(15000, $order->estimated_cost);

        $updated = $service->updateWorkOrderStatus($order, MaintenanceStatus::Completed, [
            'actual_cost' => 14500,
            'technician_notes' => 'تم استبدال القاطع التفاضلي 30mA والشبكة تعمل بامتياز.',
        ]);

        $this->assertEquals(MaintenanceStatus::Completed, $updated->status);
        $this->assertEquals(14500, $updated->actual_cost);
        $this->assertNotNull($updated->completion_date);
    }

    public function test_service_records_heating_inspection_and_evaluates_compliance(): void
    {
        $service = app(SchoolMaintenanceService::class);

        // Compliant inspection
        $log1 = $service->recordHeatingInspection([
            'academic_year' => '2026/2027',
            'boiler_code' => 'CHA-01',
            'burner_operational_state' => 'Optimal',
            'safety_valve_tested' => true,
            'co_detector_tested' => true,
            'room_ventilation_ok' => true,
            'chimneys_cleaned' => true,
            'certified_by_name' => 'مهندس الصيانة والحماية المدنية',
        ]);

        $this->assertEquals(HeatingInspectionStatus::CompliantApproved, $log1->status);
        $this->assertTrue($log1->isFullySafe());

        // Dangerous failure inspection -> Emergency shutdown
        $log2 = $service->recordHeatingInspection([
            'academic_year' => '2026/2027',
            'boiler_code' => 'CHA-02',
            'burner_operational_state' => 'DefectiveDanger',
            'safety_valve_tested' => false,
            'co_detector_tested' => false,
            'certified_by_name' => 'عون المراقبة',
        ]);

        $this->assertEquals(HeatingInspectionStatus::ShutDownEmergency, $log2->status);
        $this->assertFalse($log2->isFullySafe());
    }

    public function test_service_records_water_tank_disinfection(): void
    {
        $service = app(SchoolMaintenanceService::class);

        $inspection = $service->recordWaterTankDisinfection([
            'academic_year' => '2026/2027',
            'tank_name_code' => 'Bâche Principale 40m³',
            'tank_type' => WaterTankType::UndergroundBache,
            'capacity_cubic_meters' => 40.0,
            'executed_by' => 'المكتب البلدي للنظافة BHM',
            'residual_chlorine_ppm' => 0.45,
            'water_potable_clearance' => WaterPotabilityStatus::CompliantPotable,
        ]);

        $this->assertInstanceOf(WaterTankHygieneInspection::class, $inspection);
        $this->assertTrue($inspection->isPotableAndValid());
        $this->assertNotNull($inspection->next_disinfection_due_date);
    }

    public function test_service_manages_utility_meters_and_bills(): void
    {
        $service = app(SchoolMaintenanceService::class);

        $meter = $service->registerUtilityMeter([
            'meter_type' => UtilityMeterType::Electricity,
            'utility_provider' => 'Sonelgaz',
            'meter_serial_number' => 'SN-987456',
            'meter_location' => 'المدخل الرئيسي',
        ]);

        $this->assertInstanceOf(EnergyUtilityMeter::class, $meter);

        $bill = $service->recordUtilityBill([
            'academic_year' => '2026/2027',
            'energy_utility_meter_id' => $meter->id,
            'billing_period_quarter' => 'Q1',
            'reading_date_start' => Carbon::parse('2026-09-01'),
            'reading_date_end' => Carbon::parse('2026-11-30'),
            'previous_index' => 1000,
            'current_index' => 1450,
            'invoiced_amount_dzd' => 45000,
            'invoice_number' => 'SONEL-2026-99',
            'payment_status' => UtilityPaymentStatus::AuditedApproved,
            'meter_auditor_name' => 'المقتصد',
        ]);

        $this->assertEquals(450, $bill->consumption_units);
        $this->assertEquals(45000, $bill->invoiced_amount_dzd);
    }

    public function test_service_computes_telemetry(): void
    {
        $service = app(SchoolMaintenanceService::class);
        $service->bootstrapDefaultFacilities();

        $telemetry = $service->getMaintenanceTelemetry('2026/2027');

        $this->assertArrayHasKey('total_facilities', $telemetry);
        $this->assertArrayHasKey('open_work_orders', $telemetry);
        $this->assertArrayHasKey('urgent_emergencies', $telemetry);
        $this->assertArrayHasKey('completed_work_orders', $telemetry);
        $this->assertArrayHasKey('heating_compliance_rate', $telemetry);
        $this->assertArrayHasKey('total_utility_bills_volume_dzd', $telemetry);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolMaintenanceService::class);

        $order = $service->createWorkOrder([
            'academic_year' => '2026/2027',
            'exact_location' => 'المطبخ',
            'category' => MaintenanceCategory::PlumbingSanitary,
            'issue_description' => 'تسرب مائي في حنفيات المطبخ',
            'reported_by_name' => 'الطباخ الرئيسي',
        ]);

        $orderData = $service->assembleWorkOrderData($order);
        $this->assertArrayHasKey('work_order', $orderData);
        $this->assertArrayHasKey('school_name', $orderData);

        $hlog = $service->recordHeatingInspection([
            'academic_year' => '2026/2027',
            'boiler_code' => 'CHA-01',
            'certified_by_name' => 'المشرف',
        ]);

        $hlogData = $service->assembleHeatingSafetyPvData($hlog);
        $this->assertArrayHasKey('heating_log', $hlogData);

        $winsp = $service->recordWaterTankDisinfection([
            'academic_year' => '2026/2027',
            'tank_name_code' => 'خزان علوي',
            'executed_by' => 'أعوان المؤسسة',
        ]);

        $winspData = $service->assembleWaterHygieneCertificateData($winsp);
        $this->assertArrayHasKey('inspection', $winspData);

        $bilanData = $service->assembleEnergyConsumptionBilanData('2026/2027');
        $this->assertArrayHasKey('grand_total_dzd', $bilanData);
    }

    public function test_print_work_order_routes(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $service = app(SchoolMaintenanceService::class);

        $order = $service->createWorkOrder([
            'academic_year' => '2026/2027',
            'exact_location' => 'المخبر 02',
            'category' => MaintenanceCategory::GlazingRoofing,
            'issue_description' => 'زجاج نافذة مكسور',
            'reported_by_name' => 'أستاذ العلوم',
        ]);

        $responseHtml = $this->actingAs($bursar)->get(route('print.maintenance.work-order', [
            'locale' => 'ar',
            'workOrder' => $order->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($bursar)->get(route('print.maintenance.work-order.pdf', [
            'locale' => 'ar',
            'workOrder' => $order->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_heating_pv_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolMaintenanceService::class);

        $hlog = $service->recordHeatingInspection([
            'academic_year' => '2026/2027',
            'boiler_code' => 'CHA-01',
            'certified_by_name' => 'المهندس المعتمد',
        ]);

        $responseHtml = $this->actingAs($principal)->get(route('print.maintenance.heating-pv', [
            'locale' => 'ar',
            'log' => $hlog->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($principal)->get(route('print.maintenance.heating-pv.pdf', [
            'locale' => 'ar',
            'log' => $hlog->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_water_register_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolMaintenanceService::class);

        $winsp = $service->recordWaterTankDisinfection([
            'academic_year' => '2026/2027',
            'tank_name_code' => 'باشة أرضية',
            'executed_by' => 'BHM',
        ]);

        $responseHtml = $this->actingAs($principal)->get(route('print.maintenance.water-register', [
            'locale' => 'ar',
            'inspection' => $winsp->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($principal)->get(route('print.maintenance.water-register.pdf', [
            'locale' => 'ar',
            'inspection' => $winsp->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_energy_bilan_routes(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);

        $responseHtml = $this->actingAs($bursar)->get(route('print.maintenance.energy-bilan', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($bursar)->get(route('print.maintenance.energy-bilan.pdf', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_maintenance_hub_renders_for_authorized_users(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);

        $this->actingAs($bursar);
        Livewire::test(SchoolMaintenanceHub::class)
            ->assertStatus(200)
            ->assertSee(__('صيانة المنشآت المدرسية، التدفئة المركزية وسجل الطاقة والمرافق'));
    }

    public function test_maintenance_hub_forbids_unauthorized_users(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $this->actingAs($teacher);
        Livewire::test(SchoolMaintenanceHub::class)
            ->assertForbidden();
    }

    public function test_maintenance_hub_can_save_work_order_and_record_heating(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);

        $this->actingAs($bursar);

        Livewire::test(SchoolMaintenanceHub::class)
            ->set('exactLocation', 'الممر الرئيسي')
            ->set('workCategory', 'masonry_painting')
            ->set('priorityLevel', 'normal')
            ->set('issueDescription', 'إعادة دهن الجدار بعد إصلاح التسرب')
            ->set('reportedByName', 'المقتصد')
            ->call('saveWorkOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('maintenance_work_orders', [
            'exact_location' => 'الممر الرئيسي',
            'reported_by_name' => 'المقتصد',
        ]);

        Livewire::test(SchoolMaintenanceHub::class)
            ->set('boilerCode', 'CHA-HUB-01')
            ->set('heatingCertifiedBy', 'ممثل الحماية المدنية')
            ->call('saveHeatingInspection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('central_heating_logs', [
            'boiler_code' => 'CHA-HUB-01',
            'certified_by_name' => 'ممثل الحماية المدنية',
        ]);
    }

    public function test_health_check_verifies_all_97_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('All required tables present (97)');
    }
}
