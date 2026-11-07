<?php

namespace App\Services;

use App\Enums\BoilerFuelType;
use App\Enums\FacilityCondition;
use App\Enums\FacilityType;
use App\Enums\HeatingInspectionStatus;
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
use App\Models\UtilityConsumptionRecord;
use App\Models\WaterTankHygieneInspection;
use Carbon\Carbon;

class SchoolMaintenanceService
{
    public function currentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;

        return $now->month >= 9 ? "{$year}/".($year + 1) : ($year - 1)."/{$year}";
    }

    public function generateWorkOrderNumber(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = MaintenanceWorkOrder::where('academic_year', $year)->count() + 1;

        return sprintf('ORD-%s-%03d', $cleanYear, $count);
    }

    public function generateFacilityCode(FacilityType $type): string
    {
        $prefix = match ($type) {
            FacilityType::PedagogicalWing => 'FAC-PED',
            FacilityType::AdministrativeWing => 'FAC-ADM',
            FacilityType::BoardingDormitory => 'FAC-DOR',
            FacilityType::RefectoryKitchen => 'FAC-REF',
            FacilityType::SportsHall => 'FAC-SPT',
            FacilityType::WorkshopLab => 'FAC-LAB',
            FacilityType::StaffHousing => 'FAC-LOG',
            FacilityType::ExteriorYard => 'FAC-EXT',
        };

        $count = SchoolBuildingFacility::where('facility_type', $type)->count() + 1;

        return sprintf('%s-%02d', $prefix, $count);
    }

    public function generateHeatingLogReference(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = CentralHeatingLog::where('academic_year', $year)->count() + 1;

        return sprintf('CHAUF-%s-%03d', $cleanYear, $count);
    }

    public function generateWaterInspectionReference(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = WaterTankHygieneInspection::where('academic_year', $year)->count() + 1;

        return sprintf('BACHE-%s-%03d', $cleanYear, $count);
    }

    public function generateBillReference(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = UtilityConsumptionRecord::where('academic_year', $year)->count() + 1;

        return sprintf('BILL-%s-%03d', $cleanYear, $count);
    }

    public function generateMeterCode(UtilityMeterType $type): string
    {
        $prefix = match ($type) {
            UtilityMeterType::Electricity => 'MTR-ELEC',
            UtilityMeterType::NaturalGas => 'MTR-GAZ',
            UtilityMeterType::PotableWater => 'MTR-EAU',
        };

        $count = EnergyUtilityMeter::where('meter_type', $type)->count() + 1;

        return sprintf('%s-%02d', $prefix, $count);
    }

    public function createFacility(array $data): SchoolBuildingFacility
    {
        $type = $data['facility_type'] instanceof FacilityType
            ? $data['facility_type']
            : FacilityType::from($data['facility_type']);

        $code = $data['facility_code'] ?? $this->generateFacilityCode($type);

        return SchoolBuildingFacility::create([
            'facility_code' => $code,
            'facility_name' => $data['facility_name'],
            'facility_type' => $type,
            'building_block' => $data['building_block'] ?? null,
            'total_rooms_count' => $data['total_rooms_count'] ?? 1,
            'condition_rating' => $data['condition_rating'] ?? FacilityCondition::Good,
            'has_heating' => $data['has_heating'] ?? true,
            'has_water_connection' => $data['has_water_connection'] ?? true,
            'has_gas_connection' => $data['has_gas_connection'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function bootstrapDefaultFacilities(): int
    {
        $defaults = [
            [
                'facility_name' => 'الجناح البيداغوجي (أ) - حجرات التدريس',
                'facility_type' => FacilityType::PedagogicalWing,
                'building_block' => 'المبنى A',
                'total_rooms_count' => 12,
                'condition_rating' => FacilityCondition::Good,
            ],
            [
                'facility_name' => 'الجناح البيداغوجي (ب) - مخابر العلوم والورشات',
                'facility_type' => FacilityType::WorkshopLab,
                'building_block' => 'المبنى B',
                'total_rooms_count' => 6,
                'condition_rating' => FacilityCondition::Good,
            ],
            [
                'facility_name' => 'الجناح الإداري والمصالح الاقتصادية',
                'facility_type' => FacilityType::AdministrativeWing,
                'building_block' => 'المبنى المركزي',
                'total_rooms_count' => 8,
                'condition_rating' => FacilityCondition::Good,
            ],
            [
                'facility_name' => 'المطعم المدرسي وقاعة الإطعام والمطبخ',
                'facility_type' => FacilityType::RefectoryKitchen,
                'building_block' => 'المبنى C',
                'total_rooms_count' => 4,
                'condition_rating' => FacilityCondition::Good,
            ],
            [
                'facility_name' => 'مرقد النظام الداخلي والإيواء',
                'facility_type' => FacilityType::BoardingDormitory,
                'building_block' => 'المبنى D',
                'total_rooms_count' => 8,
                'condition_rating' => FacilityCondition::Acceptable,
            ],
            [
                'facility_name' => 'المحطة المركزية للتدفئة والغلايات (Chaufferie)',
                'facility_type' => FacilityType::WorkshopLab,
                'building_block' => 'الملحق التقني',
                'total_rooms_count' => 2,
                'condition_rating' => FacilityCondition::Good,
            ],
            [
                'facility_name' => 'قاعة التربية البدنية والرياضية والملاعب',
                'facility_type' => FacilityType::SportsHall,
                'building_block' => 'الميدان الرياضي',
                'total_rooms_count' => 3,
                'condition_rating' => FacilityCondition::Good,
            ],
        ];

        $created = 0;
        foreach ($defaults as $item) {
            $existing = SchoolBuildingFacility::where('facility_name', $item['facility_name'])->first();
            if (! $existing) {
                $this->createFacility($item);
                $created++;
            }
        }

        return $created;
    }

    public function createWorkOrder(array $data): MaintenanceWorkOrder
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $orderNumber = $data['work_order_number'] ?? $this->generateWorkOrderNumber($academicYear);

        return MaintenanceWorkOrder::create([
            'work_order_number' => $orderNumber,
            'academic_year' => $academicYear,
            'facility_id' => $data['facility_id'] ?? null,
            'exact_location' => $data['exact_location'],
            'category' => $data['category'],
            'priority_level' => $data['priority_level'] ?? MaintenancePriority::Normal,
            'issue_description' => $data['issue_description'],
            'reported_by_user_id' => $data['reported_by_user_id'] ?? null,
            'reported_by_name' => $data['reported_by_name'],
            'report_date' => $data['report_date'] ?? Carbon::today(),
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'completion_date' => $data['completion_date'] ?? null,
            'intervention_type' => $data['intervention_type'] ?? 'InternalStaff',
            'contractor_name' => $data['contractor_name'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'actual_cost' => $data['actual_cost'] ?? 0,
            'status' => $data['status'] ?? MaintenanceStatus::Reported,
            'technician_notes' => $data['technician_notes'] ?? null,
            'approved_by_user_id' => $data['approved_by_user_id'] ?? null,
        ]);
    }

    public function updateWorkOrderStatus(MaintenanceWorkOrder $workOrder, MaintenanceStatus $status, array $extra = []): MaintenanceWorkOrder
    {
        $workOrder->status = $status;

        if ($status === MaintenanceStatus::Completed) {
            $workOrder->completion_date = $extra['completion_date'] ?? Carbon::today();
            if (isset($extra['actual_cost'])) {
                $workOrder->actual_cost = $extra['actual_cost'];
            }
        }

        if (isset($extra['technician_notes'])) {
            $workOrder->technician_notes = $extra['technician_notes'];
        }

        if (isset($extra['scheduled_date'])) {
            $workOrder->scheduled_date = $extra['scheduled_date'];
        }

        $workOrder->save();

        return $workOrder;
    }

    public function recordHeatingInspection(array $data): CentralHeatingLog
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $ref = $data['log_reference'] ?? $this->generateHeatingLogReference($academicYear);

        // Auto evaluate status if not forced
        $safetyValve = (bool) ($data['safety_valve_tested'] ?? true);
        $coDetector = (bool) ($data['co_detector_tested'] ?? true);
        $ventilation = (bool) ($data['room_ventilation_ok'] ?? true);
        $chimneys = (bool) ($data['chimneys_cleaned'] ?? true);

        $burnerState = $data['burner_operational_state'] ?? 'Optimal';

        if (! isset($data['status'])) {
            if ($burnerState === 'DefectiveDanger' || ! $safetyValve || ! $coDetector || ! $ventilation) {
                $status = HeatingInspectionStatus::ShutDownEmergency;
            } elseif ($burnerState === 'FunctionalWithAnomalies' || ! $chimneys) {
                $status = HeatingInspectionStatus::ConditionalUse;
            } else {
                $status = HeatingInspectionStatus::CompliantApproved;
            }
        } else {
            $status = $data['status'];
        }

        return CentralHeatingLog::create([
            'log_reference' => $ref,
            'academic_year' => $academicYear,
            'boiler_code' => $data['boiler_code'] ?? 'CHA-01',
            'boiler_brand_model' => $data['boiler_brand_model'] ?? null,
            'installation_year' => $data['installation_year'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? BoilerFuelType::NaturalGas,
            'inspection_date' => $data['inspection_date'] ?? Carbon::today(),
            'inspector_entity' => $data['inspector_entity'] ?? 'InternalTechnician',
            'burner_operational_state' => $burnerState,
            'safety_valve_tested' => $safetyValve,
            'pressure_bar' => $data['pressure_bar'] ?? 1.50,
            'water_temp_celsius' => $data['water_temp_celsius'] ?? 65,
            'co_detector_tested' => $coDetector,
            'room_ventilation_ok' => $ventilation,
            'chimneys_cleaned' => $chimneys,
            'status' => $status,
            'observations_and_repairs' => $data['observations_and_repairs'] ?? null,
            'certified_by_name' => $data['certified_by_name'],
        ]);
    }

    public function recordWaterTankDisinfection(array $data): WaterTankHygieneInspection
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $ref = $data['inspection_reference'] ?? $this->generateWaterInspectionReference($academicYear);

        $inspectionDate = isset($data['inspection_date']) ? Carbon::parse($data['inspection_date']) : Carbon::today();
        $nextDueDate = isset($data['next_disinfection_due_date'])
            ? Carbon::parse($data['next_disinfection_due_date'])
            : $inspectionDate->copy()->addDays(90);

        return WaterTankHygieneInspection::create([
            'inspection_reference' => $ref,
            'academic_year' => $academicYear,
            'tank_name_code' => $data['tank_name_code'],
            'tank_type' => $data['tank_type'] ?? WaterTankType::UndergroundBache,
            'capacity_cubic_meters' => $data['capacity_cubic_meters'] ?? 20.00,
            'inspection_date' => $inspectionDate,
            'operation_type' => $data['operation_type'] ?? 'ChlorinationDisinfection',
            'chemical_agent_used' => $data['chemical_agent_used'] ?? 'Eau de Javel / Hypochlorite de sodium',
            'executed_by' => $data['executed_by'] ?? 'MunicipalHygieneOffice_BHM',
            'residual_chlorine_ppm' => $data['residual_chlorine_ppm'] ?? 0.50,
            'water_potable_clearance' => $data['water_potable_clearance'] ?? WaterPotabilityStatus::CompliantPotable,
            'next_disinfection_due_date' => $nextDueDate,
            'certification_ref' => $data['certification_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function registerUtilityMeter(array $data): EnergyUtilityMeter
    {
        $type = $data['meter_type'] instanceof UtilityMeterType
            ? $data['meter_type']
            : UtilityMeterType::from($data['meter_type']);

        $code = $data['meter_code'] ?? $this->generateMeterCode($type);

        return EnergyUtilityMeter::create([
            'meter_code' => $code,
            'meter_type' => $type,
            'utility_provider' => $data['utility_provider'] ?? $type->defaultProvider(),
            'meter_serial_number' => $data['meter_serial_number'],
            'meter_location' => $data['meter_location'],
            'subscribed_power_or_flow' => $data['subscribed_power_or_flow'] ?? null,
            'tariff_category' => $data['tariff_category'] ?? null,
            'unit_of_measure' => $data['unit_of_measure'] ?? $type->unitOfMeasure(),
            'is_active' => $data['is_active'] ?? true,
            'installation_date' => $data['installation_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function recordUtilityBill(array $data): UtilityConsumptionRecord
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $billRef = $data['bill_reference'] ?? $this->generateBillReference($academicYear);

        $prevIndex = (int) $data['previous_index'];
        $currIndex = (int) $data['current_index'];
        $consumption = max(0, $currIndex - $prevIndex);

        // Check for consumption surge anomaly compared to average
        $meterId = $data['energy_utility_meter_id'];
        $avgConsumption = UtilityConsumptionRecord::where('energy_utility_meter_id', $meterId)->avg('consumption_units');
        $anomaly = false;
        if ($avgConsumption && $avgConsumption > 0 && $consumption > ($avgConsumption * 1.5)) {
            $anomaly = true;
        }

        return UtilityConsumptionRecord::create([
            'bill_reference' => $billRef,
            'academic_year' => $academicYear,
            'energy_utility_meter_id' => $meterId,
            'billing_period_quarter' => $data['billing_period_quarter'] ?? 'Q1',
            'reading_date_start' => $data['reading_date_start'],
            'reading_date_end' => $data['reading_date_end'],
            'previous_index' => $prevIndex,
            'current_index' => $currIndex,
            'consumption_units' => $consumption,
            'invoiced_amount_dzd' => $data['invoiced_amount_dzd'] ?? 0,
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_reception_date' => $data['invoice_reception_date'] ?? Carbon::today(),
            'payment_status' => $data['payment_status'] ?? UtilityPaymentStatus::ReceivedPendingAudit,
            'meter_auditor_name' => $data['meter_auditor_name'],
            'anomaly_detected' => $data['anomaly_detected'] ?? $anomaly,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    public function getMaintenanceTelemetry(?string $academicYear = null): array
    {
        $year = $academicYear ?? $this->currentAcademicYear();

        $totalFacilities = SchoolBuildingFacility::count();
        $facilitiesNeedingRestoration = SchoolBuildingFacility::whereIn('condition_rating', [
            FacilityCondition::NeedsRestoration->value,
            FacilityCondition::Dilapidated->value,
        ])->count();

        $workOrdersQuery = MaintenanceWorkOrder::where('academic_year', $year);
        $totalOrders = (clone $workOrdersQuery)->count();
        $openOrders = (clone $workOrdersQuery)->whereIn('status', [
            MaintenanceStatus::Reported->value,
            MaintenanceStatus::Scheduled->value,
            MaintenanceStatus::InProgress->value,
        ])->count();
        $urgentOrders = (clone $workOrdersQuery)->whereIn('priority_level', [
            MaintenancePriority::EmergencySafety->value,
            MaintenancePriority::Urgent->value,
        ])->whereNotIn('status', [MaintenanceStatus::Completed->value, MaintenanceStatus::Cancelled->value])->count();
        $completedOrders = (clone $workOrdersQuery)->where('status', MaintenanceStatus::Completed->value)->count();

        $heatingLogsQuery = CentralHeatingLog::where('academic_year', $year);
        $totalHeatingLogs = (clone $heatingLogsQuery)->count();
        $compliantHeatingLogs = (clone $heatingLogsQuery)->where('status', HeatingInspectionStatus::CompliantApproved->value)->count();
        $heatingComplianceRate = $totalHeatingLogs > 0 ? (int) round(($compliantHeatingLogs / $totalHeatingLogs) * 100) : 100;

        $waterInspections = WaterTankHygieneInspection::where('academic_year', $year)->get();
        $waterTanksCount = $waterInspections->pluck('tank_name_code')->unique()->count();
        $compliantWaterCount = $waterInspections->where('water_potable_clearance', WaterPotabilityStatus::CompliantPotable)->count();
        $waterSafeRate = $waterInspections->count() > 0 ? (int) round(($compliantWaterCount / $waterInspections->count()) * 100) : 100;

        $activeMetersCount = EnergyUtilityMeter::where('is_active', true)->count();
        $totalUtilityBillsDzd = UtilityConsumptionRecord::where('academic_year', $year)->sum('invoiced_amount_dzd');

        return [
            'academic_year' => $year,
            'total_facilities' => $totalFacilities,
            'facilities_needing_restoration' => $facilitiesNeedingRestoration,
            'total_work_orders' => $totalOrders,
            'open_work_orders' => $openOrders,
            'urgent_emergencies' => $urgentOrders,
            'completed_work_orders' => $completedOrders,
            'total_heating_logs' => $totalHeatingLogs,
            'heating_compliance_rate' => $heatingComplianceRate,
            'water_tanks_count' => $waterTanksCount,
            'water_safe_rate' => $waterSafeRate,
            'active_meters_count' => $activeMetersCount,
            'total_utility_bills_volume_dzd' => (float) $totalUtilityBillsDzd,
        ];
    }

    public function assembleWorkOrderData(MaintenanceWorkOrder $workOrder): array
    {
        return [
            'work_order' => $workOrder,
            'facility' => $workOrder->facility,
            'reported_by' => $workOrder->reported_by_name,
            'approved_by' => $workOrder->approvedByUser?->name ?? 'مدير المؤسسة / المقتصد',
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $workOrder->academic_year,
            'order_number' => $workOrder->work_order_number,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleHeatingSafetyPvData(CentralHeatingLog $log): array
    {
        return [
            'heating_log' => $log,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $log->academic_year,
            'log_reference' => $log->log_reference,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleWaterHygieneCertificateData(WaterTankHygieneInspection $inspection): array
    {
        return [
            'inspection' => $inspection,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $inspection->academic_year,
            'reference' => $inspection->inspection_reference,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleEnergyConsumptionBilanData(string $academicYear): array
    {
        $meters = EnergyUtilityMeter::with(['consumptionRecords' => function ($q) use ($academicYear) {
            $q->where('academic_year', $academicYear)->orderBy('reading_date_start');
        }])->get();

        $records = UtilityConsumptionRecord::with('meter')
            ->where('academic_year', $academicYear)
            ->orderBy('reading_date_end')
            ->get();

        $totalElectricityDzd = $records->where('meter.meter_type', UtilityMeterType::Electricity)->sum('invoiced_amount_dzd');
        $totalGasDzd = $records->where('meter.meter_type', UtilityMeterType::NaturalGas)->sum('invoiced_amount_dzd');
        $totalWaterDzd = $records->where('meter.meter_type', UtilityMeterType::PotableWater)->sum('invoiced_amount_dzd');

        return [
            'academic_year' => $academicYear,
            'meters' => $meters,
            'records' => $records,
            'total_electricity_dzd' => (float) $totalElectricityDzd,
            'total_gas_dzd' => (float) $totalGasDzd,
            'total_water_dzd' => (float) $totalWaterDzd,
            'grand_total_dzd' => (float) ($totalElectricityDzd + $totalGasDzd + $totalWaterDzd),
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }
}
