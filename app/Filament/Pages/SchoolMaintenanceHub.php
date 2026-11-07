<?php

namespace App\Filament\Pages;

use App\Enums\BoilerFuelType;
use App\Enums\FacilityCondition;
use App\Enums\FacilityType;
use App\Enums\HeatingInspectionStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\UtilityMeterType;
use App\Enums\UtilityPaymentStatus;
use App\Enums\WaterPotabilityStatus;
use App\Enums\WaterTankType;
use App\Models\CentralHeatingLog;
use App\Models\EnergyUtilityMeter;
use App\Models\MaintenanceWorkOrder;
use App\Models\SchoolBuildingFacility;
use App\Models\User;
use App\Models\UtilityConsumptionRecord;
use App\Models\WaterTankHygieneInspection;
use App\Services\SchoolMaintenanceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolMaintenanceHub extends Page
{
    protected static string $view = 'filament.pages.school-maintenance-hub';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 6;

    public string $activeTab = 'work_orders'; // work_orders | central_heating | water_hygiene | utilities_energy

    public string $academicYear = '2026/2027';

    public string $searchQuery = '';

    public string $categoryFilter = 'all';

    public string $priorityFilter = 'all';

    // ─── Modal State: Create Work Order ─────────────────────────
    public bool $isWorkOrderModalOpen = false;

    public ?int $selectedFacilityId = null;

    public string $exactLocation = '';

    public string $workCategory = 'electricity';

    public string $priorityLevel = 'normal';

    public string $issueDescription = '';

    public string $reportedByName = '';

    public string $reportDate = '';

    public string $scheduledDate = '';

    public string $interventionType = 'InternalStaff';

    public string $contractorName = '';

    public float $estimatedCost = 0.0;

    // ─── Modal State: Complete Work Order ───────────────────────
    public bool $isCompleteModalOpen = false;

    public ?int $completingOrderId = null;

    public string $completionDate = '';

    public float $actualCost = 0.0;

    public string $technicianNotes = '';

    // ─── Modal State: Add Facility ──────────────────────────────
    public bool $isFacilityModalOpen = false;

    public string $facilityName = '';

    public string $facilityType = 'pedagogical_wing';

    public string $buildingBlock = 'المبنى A';

    public int $totalRoomsCount = 6;

    public string $conditionRating = 'good';

    public bool $hasHeating = true;

    public bool $hasWater = true;

    public bool $hasGas = true;

    // ─── Modal State: Heating Inspection ────────────────────────
    public bool $isHeatingModalOpen = false;

    public string $boilerCode = 'CHA-01';

    public string $boilerBrand = 'Chappée / Riello';

    public ?int $installationYear = 2020;

    public string $fuelType = 'natural_gas';

    public string $heatingInspectionDate = '';

    public string $heatingInspectorEntity = 'مكتب المراقبة المعتمد والتقني الداخلي';

    public string $burnerState = 'Optimal';

    public bool $safetyValveTested = true;

    public float $pressureBar = 1.60;

    public int $waterTempCelsius = 65;

    public bool $coDetectorTested = true;

    public bool $roomVentilationOk = true;

    public bool $chimneysCleaned = true;

    public string $heatingStatus = 'compliant_approved';

    public string $heatingObservations = '';

    public string $heatingCertifiedBy = '';

    // ─── Modal State: Water Tank Disinfection ───────────────────
    public bool $isWaterModalOpen = false;

    public string $tankCode = 'RES-01 (باشة رئيسية)';

    public string $tankType = 'underground_bache';

    public float $tankCapacityM3 = 30.0;

    public string $waterInspectionDate = '';

    public string $waterOperationType = 'غسيل، إفراغ وتطهير كيميائي بالكلور';

    public string $disinfectantAgent = 'هيبوكلوريت الصوديوم / ماء جافيل';

    public string $executedByEntity = 'المكتب البلدي للنظافة والصحة BHM';

    public float $residualChlorine = 0.40;

    public string $waterClearance = 'compliant_potable';

    public string $nextWaterDueDate = '';

    public string $waterLabCertRef = '';

    public string $waterNotes = '';

    // ─── Modal State: Utility Meter ─────────────────────────────
    public bool $isMeterModalOpen = false;

    public string $meterType = 'electricity';

    public string $meterProvider = 'Sonelgaz';

    public string $meterSerial = '';

    public string $meterLocation = 'المدخل الرئيسي للمؤسسة';

    public string $subscribedPower = '380V / 30A';

    public string $tariffCategory = 'Tarif Moyenne Tension';

    // ─── Modal State: Utility Bill ──────────────────────────────
    public bool $isBillModalOpen = false;

    public ?int $selectedMeterId = null;

    public string $billingQuarter = 'Q1';

    public string $readingStartDate = '';

    public string $readingEndDate = '';

    public int $previousIndex = 0;

    public int $currentIndex = 0;

    public float $invoicedAmountDzd = 0.0;

    public string $invoiceNumber = '';

    public string $billPaymentStatus = 'received_pending_audit';

    public string $meterAuditor = '';

    public string $billRemarks = '';

    public static function getNavigationGroup(): ?string
    {
        return __('المصالح المالية والكتاب المدرسي');
    }

    public static function getNavigationLabel(): string
    {
        return __('صيانة المنشآت والتدفئة والطاقة');
    }

    public function getTitle(): string|Htmlable
    {
        return __('صيانة المنشآت المدرسية، التدفئة المركزية وسجل الطاقة والمرافق');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolMaintenance();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->reportDate = $today;
        $this->scheduledDate = Carbon::today()->addDays(2)->format('Y-m-d');
        $this->completionDate = $today;
        $this->heatingInspectionDate = $today;
        $this->waterInspectionDate = $today;
        $this->nextWaterDueDate = Carbon::today()->addDays(90)->format('Y-m-d');
        $this->readingStartDate = Carbon::today()->subMonths(3)->format('Y-m-d');
        $this->readingEndDate = $today;

        $user = Auth::user();
        $this->reportedByName = $user?->name ?? 'مسؤول الصيانة';
        $this->heatingCertifiedBy = $user?->name ?? 'التقني المكلف / ممثل الحماية المدنية';
        $this->meterAuditor = $user?->name ?? 'المقتصد / نائب المقتصد';

        // Auto bootstrap default facilities if empty
        app(SchoolMaintenanceService::class)->bootstrapDefaultFacilities();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openWorkOrderModal(): void
    {
        $this->isWorkOrderModalOpen = true;
    }

    public function closeWorkOrderModal(): void
    {
        $this->isWorkOrderModalOpen = false;
    }

    public function openFacilityModal(): void
    {
        $this->isFacilityModalOpen = true;
    }

    public function closeFacilityModal(): void
    {
        $this->isFacilityModalOpen = false;
    }

    public function openHeatingModal(): void
    {
        $this->isHeatingModalOpen = true;
    }

    public function closeHeatingModal(): void
    {
        $this->isHeatingModalOpen = false;
    }

    public function openWaterModal(): void
    {
        $this->isWaterModalOpen = true;
    }

    public function closeWaterModal(): void
    {
        $this->isWaterModalOpen = false;
    }

    public function openMeterModal(): void
    {
        $this->isMeterModalOpen = true;
    }

    public function closeMeterModal(): void
    {
        $this->isMeterModalOpen = false;
    }

    public function openBillModal(): void
    {
        $firstMeter = EnergyUtilityMeter::first();
        if ($firstMeter) {
            $this->selectedMeterId = $firstMeter->id;
            $latest = $firstMeter->latestRecord();
            $this->previousIndex = $latest?->current_index ?? 0;
            $this->currentIndex = $this->previousIndex + 150;
        }
        $this->isBillModalOpen = true;
    }

    public function closeBillModal(): void
    {
        $this->isBillModalOpen = false;
    }

    public function openCompleteModal(int $orderId): void
    {
        $order = MaintenanceWorkOrder::find($orderId);
        if ($order) {
            $this->completingOrderId = $orderId;
            $this->actualCost = (float) $order->estimated_cost;
            $this->completionDate = Carbon::today()->format('Y-m-d');
            $this->technicianNotes = 'تم إصلاح العطب واستبدال القطع التالفة وتشغيل التجهيزات بحالة جيدة.';
            $this->isCompleteModalOpen = true;
        }
    }

    public function closeCompleteModal(): void
    {
        $this->isCompleteModalOpen = false;
        $this->completingOrderId = null;
    }

    public function saveWorkOrder(): void
    {
        $this->validate([
            'exactLocation' => 'required|string|max:255',
            'issueDescription' => 'required|string',
            'reportedByName' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);

        $order = $service->createWorkOrder([
            'academic_year' => $this->academicYear,
            'facility_id' => $this->selectedFacilityId,
            'exact_location' => $this->exactLocation,
            'category' => MaintenanceCategory::from($this->workCategory),
            'priority_level' => MaintenancePriority::from($this->priorityLevel),
            'issue_description' => $this->issueDescription,
            'reported_by_user_id' => Auth::id(),
            'reported_by_name' => $this->reportedByName,
            'report_date' => $this->reportDate,
            'scheduled_date' => $this->scheduledDate ?: null,
            'intervention_type' => $this->interventionType,
            'contractor_name' => $this->contractorName ?: null,
            'estimated_cost' => $this->estimatedCost,
            'status' => MaintenanceStatus::Reported,
        ]);

        $this->closeWorkOrderModal();

        Notification::make()
            ->title(__('تم تسجيل أمر الأشغال بنجاح'))
            ->body(__('رقم أمر الصيانة: :num', ['num' => $order->work_order_number]))
            ->success()
            ->send();
    }

    public function saveCompletedOrder(): void
    {
        if (! $this->completingOrderId) {
            return;
        }

        $order = MaintenanceWorkOrder::find($this->completingOrderId);
        if ($order) {
            $service = app(SchoolMaintenanceService::class);
            $service->updateWorkOrderStatus($order, MaintenanceStatus::Completed, [
                'completion_date' => $this->completionDate,
                'actual_cost' => $this->actualCost,
                'technician_notes' => $this->technicianNotes,
            ]);

            Notification::make()
                ->title(__('تم تأكيد إنجاز الأشغال وغلق الأمر'))
                ->body(__('تم تحديث حالة أمر الصيانة :num إلى منجز', ['num' => $order->work_order_number]))
                ->success()
                ->send();
        }

        $this->closeCompleteModal();
    }

    public function saveFacility(): void
    {
        $this->validate([
            'facilityName' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);
        $facility = $service->createFacility([
            'facility_name' => $this->facilityName,
            'facility_type' => FacilityType::from($this->facilityType),
            'building_block' => $this->buildingBlock,
            'total_rooms_count' => $this->totalRoomsCount,
            'condition_rating' => FacilityCondition::from($this->conditionRating),
            'has_heating' => $this->hasHeating,
            'has_water_connection' => $this->hasWater,
            'has_gas_connection' => $this->hasGas,
        ]);

        $this->closeFacilityModal();

        Notification::make()
            ->title(__('تم إضافة الهيكل بنجاح'))
            ->body(__('رمز الهيكل: :code', ['code' => $facility->facility_code]))
            ->success()
            ->send();
    }

    public function saveHeatingInspection(): void
    {
        $this->validate([
            'boilerCode' => 'required|string|max:50',
            'heatingCertifiedBy' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);
        $log = $service->recordHeatingInspection([
            'academic_year' => $this->academicYear,
            'boiler_code' => $this->boilerCode,
            'boiler_brand_model' => $this->boilerBrand,
            'installation_year' => $this->installationYear,
            'fuel_type' => BoilerFuelType::from($this->fuelType),
            'inspection_date' => $this->heatingInspectionDate,
            'inspector_entity' => $this->heatingInspectorEntity,
            'burner_operational_state' => $this->burnerState,
            'safety_valve_tested' => $this->safetyValveTested,
            'pressure_bar' => $this->pressureBar,
            'water_temp_celsius' => $this->waterTempCelsius,
            'co_detector_tested' => $this->coDetectorTested,
            'room_ventilation_ok' => $this->roomVentilationOk,
            'chimneys_cleaned' => $this->chimneysCleaned,
            'status' => HeatingInspectionStatus::from($this->heatingStatus),
            'observations_and_repairs' => $this->heatingObservations,
            'certified_by_name' => $this->heatingCertifiedBy,
        ]);

        $this->closeHeatingModal();

        Notification::make()
            ->title(__('تم تقييد محضر معاينة التدفئة المركزية'))
            ->body(__('المرجع: :ref', ['ref' => $log->log_reference]))
            ->success()
            ->send();
    }

    public function saveWaterDisinfection(): void
    {
        $this->validate([
            'tankCode' => 'required|string|max:100',
            'executedByEntity' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);
        $inspection = $service->recordWaterTankDisinfection([
            'academic_year' => $this->academicYear,
            'tank_name_code' => $this->tankCode,
            'tank_type' => WaterTankType::from($this->tankType),
            'capacity_cubic_meters' => $this->tankCapacityM3,
            'inspection_date' => $this->waterInspectionDate,
            'operation_type' => $this->waterOperationType,
            'chemical_agent_used' => $this->disinfectantAgent,
            'executed_by' => $this->executedByEntity,
            'residual_chlorine_ppm' => $this->residualChlorine,
            'water_potable_clearance' => WaterPotabilityStatus::from($this->waterClearance),
            'next_disinfection_due_date' => $this->nextWaterDueDate,
            'certification_ref' => $this->waterLabCertRef,
            'notes' => $this->waterNotes,
        ]);

        $this->closeWaterModal();

        Notification::make()
            ->title(__('تم تسجيل محضر تطهير الخزان بنجاح'))
            ->body(__('المرجع: :ref', ['ref' => $inspection->inspection_reference]))
            ->success()
            ->send();
    }

    public function saveUtilityMeter(): void
    {
        $this->validate([
            'meterSerial' => 'required|string|max:100',
            'meterLocation' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);
        $meter = $service->registerUtilityMeter([
            'meter_type' => UtilityMeterType::from($this->meterType),
            'utility_provider' => $this->meterProvider,
            'meter_serial_number' => $this->meterSerial,
            'meter_location' => $this->meterLocation,
            'subscribed_power_or_flow' => $this->subscribedPower,
            'tariff_category' => $this->tariffCategory,
            'is_active' => true,
        ]);

        $this->closeMeterModal();

        Notification::make()
            ->title(__('تم تسجيل عداد الطاقة بنجاح'))
            ->body(__('رمز العداد: :code', ['code' => $meter->meter_code]))
            ->success()
            ->send();
    }

    public function saveUtilityBill(): void
    {
        $this->validate([
            'selectedMeterId' => 'required|integer',
            'previousIndex' => 'required|numeric|min:0',
            'currentIndex' => 'required|numeric|gte:previousIndex',
            'invoicedAmountDzd' => 'required|numeric|min:0',
            'meterAuditor' => 'required|string|max:255',
        ]);

        $service = app(SchoolMaintenanceService::class);
        $record = $service->recordUtilityBill([
            'academic_year' => $this->academicYear,
            'energy_utility_meter_id' => $this->selectedMeterId,
            'billing_period_quarter' => $this->billingQuarter,
            'reading_date_start' => $this->readingStartDate,
            'reading_date_end' => $this->readingEndDate,
            'previous_index' => $this->previousIndex,
            'current_index' => $this->currentIndex,
            'invoiced_amount_dzd' => $this->invoicedAmountDzd,
            'invoice_number' => $this->invoiceNumber,
            'payment_status' => UtilityPaymentStatus::from($this->billPaymentStatus),
            'meter_auditor_name' => $this->meterAuditor,
            'remarks' => $this->billRemarks,
        ]);

        $this->closeBillModal();

        Notification::make()
            ->title(__('تم تسجيل الفاتورة والمطابقة بنجاح'))
            ->body(__('المرجع: :ref — الاستهلاك: :c', ['ref' => $record->bill_reference, 'c' => $record->consumption_units]))
            ->success()
            ->send();
    }

    public function getTelemetryProperty(): array
    {
        return app(SchoolMaintenanceService::class)->getMaintenanceTelemetry($this->academicYear);
    }

    public function getWorkOrdersProperty()
    {
        $query = MaintenanceWorkOrder::with(['facility', 'reportedByUser', 'approvedByUser'])
            ->where('academic_year', $this->academicYear);

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->priorityFilter !== 'all') {
            $query->where('priority_level', $this->priorityFilter);
        }

        if (! empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('work_order_number', 'like', "%{$this->searchQuery}%")
                    ->orWhere('exact_location', 'like', "%{$this->searchQuery}%")
                    ->orWhere('issue_description', 'like', "%{$this->searchQuery}%")
                    ->orWhere('reported_by_name', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->latest('report_date')->get();
    }

    public function getFacilitiesProperty()
    {
        return SchoolBuildingFacility::withCount('workOrders')->get();
    }

    public function getHeatingLogsProperty()
    {
        return CentralHeatingLog::where('academic_year', $this->academicYear)
            ->latest('inspection_date')
            ->get();
    }

    public function getWaterInspectionsProperty()
    {
        return WaterTankHygieneInspection::where('academic_year', $this->academicYear)
            ->latest('inspection_date')
            ->get();
    }

    public function getMetersProperty()
    {
        return EnergyUtilityMeter::with(['consumptionRecords' => function ($q) {
            $q->where('academic_year', $this->academicYear);
        }])->get();
    }

    public function getBillsProperty()
    {
        return UtilityConsumptionRecord::with('meter')
            ->where('academic_year', $this->academicYear)
            ->latest('reading_date_end')
            ->get();
    }
}

