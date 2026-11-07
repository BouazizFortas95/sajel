<?php

namespace App\Services;

use App\Enums\SafetyEquipmentStatus;
use App\Enums\SafetyIncidentStatus;
use App\Enums\SafetyIncidentType;
use App\Models\SafetyCommitteeMeeting;
use App\Models\SafetyEmergencyPlan;
use App\Models\SafetyEvacuationDrill;
use App\Models\SafetyFireEquipment;
use App\Models\SafetyInterventionTeam;
use App\Models\SchoolSafetyIncident;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolSafetyService
{
    /**
     * Generate sequential incident reference (e.g. ACC/2026/001).
     */
    public function generateIncidentNumber(int $year): string
    {
        $prefix = "ACC/{$year}/";
        $lastRecord = SchoolSafetyIncident::where('incident_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('incident_number');

        $sequence = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Generate sequential PSI plan code (e.g. PSI-2026-01).
     */
    public function generatePlanCode(int $year): string
    {
        $prefix = "PSI-{$year}-";
        $count = SafetyEmergencyPlan::where('plan_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential equipment code (e.g. EXT-001, CO-001).
     */
    public function generateEquipmentCode(string $prefix = 'EXT'): string
    {
        $count = SafetyFireEquipment::where('equipment_code', 'like', "{$prefix}-%")->count();

        return sprintf('%s-%03d', $prefix, $count + 1);
    }

    /**
     * Generate sequential evacuation drill code (e.g. DRIL-2026-01).
     */
    public function generateDrillCode(int $year): string
    {
        $prefix = "DRIL-{$year}-";
        $count = SafetyEvacuationDrill::where('drill_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential CHS committee session code (e.g. CHS-2026-01).
     */
    public function generateSessionCode(int $year): string
    {
        $prefix = "CHS-{$year}-";
        $count = SafetyCommitteeMeeting::where('session_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Declare and record a school or work accident.
     */
    public function declareAccident(array $data): SchoolSafetyIncident
    {
        return DB::transaction(function () use ($data) {
            $academicYear = $data['academic_year'] ?? '2026/2027';
            $year = (int) substr($academicYear, 0, 4);

            if (empty($data['incident_number'])) {
                $data['incident_number'] = $this->generateIncidentNumber($year);
            }

            if (! isset($data['insurance_declared'])) {
                $data['insurance_declared'] = false;
            }

            if (! isset($data['hospital_evacuation'])) {
                $data['hospital_evacuation'] = false;
            }

            // If student victim provided, sync name
            if (! empty($data['student_id'])) {
                $student = Student::find($data['student_id']);
                if ($student && empty($data['victim_name'])) {
                    $data['victim_name'] = $student->full_name;
                }
            } elseif (! empty($data['user_id'])) {
                $user = User::find($data['user_id']);
                if ($user && empty($data['victim_name'])) {
                    $data['victim_name'] = $user->name;
                }
            }

            return SchoolSafetyIncident::create($data);
        });
    }

    /**
     * Update incident status and legal declaration metadata.
     */
    public function updateAccidentStatus(
        SchoolSafetyIncident $incident,
        SafetyIncidentStatus $status,
        array $extra = []
    ): SchoolSafetyIncident {
        $incident->status = $status;

        if ($status === SafetyIncidentStatus::Declared && ! $incident->declaration_date) {
            $incident->declaration_date = Carbon::today();
            $incident->insurance_declared = true;
            $incident->directorate_notified = true;
        }

        foreach ($extra as $key => $value) {
            $incident->{$key} = $value;
        }

        $incident->save();

        return $incident;
    }

    /**
     * Create or update the School Internal Security Plan (PSI).
     */
    public function createOrUpdateSafetyPlan(array $data): SafetyEmergencyPlan
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $year = (int) substr($academicYear, 0, 4);

        if (empty($data['plan_code'])) {
            $data['plan_code'] = $this->generatePlanCode($year);
        }

        if (empty($data['assembly_points'])) {
            $data['assembly_points'] = [
                'الساحة المركزية الرئيسية (Zone de rassemblement 1)',
                'ملعب التربية البدنية والرياضية الشرقي (Zone de rassemblement 2)',
            ];
        }

        if (empty($data['emergency_contacts'])) {
            $data['emergency_contacts'] = [
                'الحماية المدنية (Protection Civile)' => '14 / 1021',
                'الشرطة والأمن الوطني (Police / Sûreté)' => '17 / 1548',
                'الدرك الوطني (Gendarmerie Nationale)' => '1055',
                'سونلغاز طوارئ الغاز (Sonelgaz Gaz)' => '3303',
                'الإسعاف الطبي الاستعجالي SAMU' => '115',
            ];
        }

        if (empty($data['hazardous_zones'])) {
            $data['hazardous_zones'] = [
                'غرفة التدفئة المركزية والمراجل (Chaufferie)',
                'محول الضغط العالي والكهرباء (Transformateur)',
                'مخزن الكواشف والمواد الكيميائية بالمخبر (Dépôt produits chimiques)',
            ];
        }

        return SafetyEmergencyPlan::updateOrCreate(
            ['academic_year' => $academicYear, 'plan_code' => $data['plan_code']],
            $data
        );
    }

    /**
     * Assign a staff member to an intervention or first-aid team.
     */
    public function addInterventionMember(SafetyEmergencyPlan $plan, array $data): SafetyInterventionTeam
    {
        return $plan->interventionTeams()->create($data);
    }

    /**
     * Register a safety or fire equipment item.
     */
    public function registerEquipment(array $data): SafetyFireEquipment
    {
        if (empty($data['equipment_code'])) {
            $type = $data['equipment_type'] ?? 'extinguisher_powder';
            $prefix = match ($type) {
                'co_detector' => 'CO-DET',
                'fire_alarm' => 'ALM',
                'emergency_lighting' => 'BAES',
                'gas_shutoff_valve' => 'V-GAZ',
                default => 'EXT',
            };
            $data['equipment_code'] = $this->generateEquipmentCode($prefix);
        }

        if (empty($data['last_inspection_date'])) {
            $data['last_inspection_date'] = Carbon::today();
        }

        if (empty($data['next_inspection_due_date'])) {
            $data['next_inspection_due_date'] = Carbon::parse($data['last_inspection_date'])->addYear();
        }

        return SafetyFireEquipment::create($data);
    }

    /**
     * Update equipment inspection certification and validity.
     */
    public function updateEquipmentInspection(
        SafetyFireEquipment $equipment,
        SafetyEquipmentStatus|string $status,
        ?Carbon $nextDue = null,
        ?string $inspector = null
    ): SafetyFireEquipment {
        $equipment->inspection_status = is_string($status) ? SafetyEquipmentStatus::from($status) : $status;
        $equipment->last_inspection_date = Carbon::today();
        $equipment->next_inspection_due_date = $nextDue ?? Carbon::today()->addYear();

        if ($inspector) {
            $equipment->inspector_entity = $inspector;
        }

        $equipment->save();

        return $equipment;
    }

    /**
     * Record an emergency disaster evacuation drill.
     */
    public function recordEvacuationDrill(array $data): SafetyEvacuationDrill
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $year = (int) substr($academicYear, 0, 4);

        if (empty($data['drill_code'])) {
            $data['drill_code'] = $this->generateDrillCode($year);
        }

        if (empty($data['total_persons_evacuated'])) {
            $data['total_persons_evacuated'] = ($data['students_evacuated_count'] ?? 0) + ($data['staff_evacuated_count'] ?? 0);
        }

        return SafetyEvacuationDrill::create($data);
    }

    /**
     * Record a session of the Hygiene and Safety Committee (CHS).
     */
    public function recordCommitteeMeeting(array $data): SafetyCommitteeMeeting
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $year = (int) substr($academicYear, 0, 4);

        if (empty($data['session_code'])) {
            $data['session_code'] = $this->generateSessionCode($year);
        }

        return SafetyCommitteeMeeting::create($data);
    }

    /**
     * Get real-time safety, accidents, and equipment telemetry.
     */
    public function getSafetyTelemetry(?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? '2026/2027';

        $totalIncidents = SchoolSafetyIncident::where('academic_year', $academicYear)->count();
        $studentAccidents = SchoolSafetyIncident::where('academic_year', $academicYear)
            ->where('incident_type', SafetyIncidentType::StudentAccident->value)
            ->count();
        $workAccidents = SchoolSafetyIncident::where('academic_year', $academicYear)
            ->where('incident_type', SafetyIncidentType::WorkAccident->value)
            ->count();
        $hospitalEvacuations = SchoolSafetyIncident::where('academic_year', $academicYear)
            ->where('hospital_evacuation', true)
            ->count();
        $pendingDeclarations = SchoolSafetyIncident::where('academic_year', $academicYear)
            ->where('insurance_declared', false)
            ->count();

        $equipmentTotal = SafetyFireEquipment::count();
        $equipmentOperational = SafetyFireEquipment::where('inspection_status', SafetyEquipmentStatus::Operational->value)
            ->where('next_inspection_due_date', '>=', Carbon::today())
            ->count();
        $equipmentDueOrExpired = SafetyFireEquipment::where(function ($query) {
            $query->where('inspection_status', '!=', SafetyEquipmentStatus::Operational->value)
                ->orWhere('next_inspection_due_date', '<', Carbon::today());
        })->count();

        $drillsCount = SafetyEvacuationDrill::where('academic_year', $academicYear)->count();
        $firstAidersCount = SafetyInterventionTeam::where('is_certified_first_aider', true)->count();

        return [
            'total_incidents' => $totalIncidents,
            'student_accidents' => $studentAccidents,
            'work_accidents' => $workAccidents,
            'hospital_evacuations' => $hospitalEvacuations,
            'pending_declarations' => $pendingDeclarations,
            'equipment_total' => $equipmentTotal,
            'equipment_operational' => $equipmentOperational,
            'equipment_due_or_expired' => $equipmentDueOrExpired,
            'operational_rate' => $equipmentTotal > 0 ? round(($equipmentOperational / $equipmentTotal) * 100, 1) : 100.0,
            'drills_count' => $drillsCount,
            'first_aiders_count' => $firstAidersCount,
        ];
    }

    /**
     * Assemble data for official Algerian School Accident Declaration (A4 Portrait).
     */
    public function assembleAccidentDeclarationData(SchoolSafetyIncident $incident): array
    {
        $school = SchoolSetting::first();
        $student = $incident->student;

        return [
            'incident' => $incident,
            'school' => $school,
            'student' => $student,
            'victim_type_label' => $incident->incident_type?->label() ?? __('حادث مدرسي'),
            'severity_label' => $incident->severity_level?->label() ?? __('متوسط'),
            'status_label' => $incident->status?->label() ?? __('مصرح به'),
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'daira' => $school?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $incident->academic_year,
            'formatted_incident_date' => $incident->incident_date?->format('Y-m-d H:i') ?? '',
            'formatted_declaration_date' => $incident->declaration_date?->format('Y-m-d') ?? Carbon::today()->format('Y-m-d'),
        ];
    }

    /**
     * Assemble data for official Evacuation Drill PV (A4 Landscape).
     */
    public function assembleEvacuationDrillPvData(SafetyEvacuationDrill $drill): array
    {
        $school = SchoolSetting::first();

        return [
            'drill' => $drill,
            'school' => $school,
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'daira' => $school?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $drill->academic_year,
            'formatted_date' => $drill->drill_date?->format('Y-m-d'),
            'scenario_label' => $drill->simulation_scenario?->label() ?? __('تمرين إخلاء ضد الأخطار الكبرى'),
            'formatted_duration' => $drill->formattedDuration(),
        ];
    }

    /**
     * Assemble data for Fire Safety & Gas Equipment Register (A4 Landscape).
     */
    public function assembleSafetyEquipmentRegisterData(?string $filter = null): array
    {
        $school = SchoolSetting::first();
        $query = SafetyFireEquipment::query()->orderBy('exact_location');

        if ($filter === 'operational') {
            $query->where('inspection_status', SafetyEquipmentStatus::Operational->value)
                ->where('next_inspection_due_date', '>=', Carbon::today());
        } elseif ($filter === 'due_or_expired') {
            $query->where(function ($q) {
                $q->where('inspection_status', '!=', SafetyEquipmentStatus::Operational->value)
                    ->orWhere('next_inspection_due_date', '<', Carbon::today());
            });
        }

        $equipments = $query->get();

        return [
            'equipments' => $equipments,
            'school' => $school,
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'academic_year' => '2026/2027',
            'generated_at' => Carbon::now()->format('Y-m-d H:i'),
            'total_count' => $equipments->count(),
        ];
    }
}
