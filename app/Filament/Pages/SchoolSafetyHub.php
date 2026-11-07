<?php

namespace App\Filament\Pages;

use App\Enums\SafetyEquipmentStatus;
use App\Enums\SafetyIncidentStatus;
use App\Models\SafetyEmergencyPlan;
use App\Models\SafetyEvacuationDrill;
use App\Models\SafetyFireEquipment;
use App\Models\SchoolSafetyIncident;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolSafetyService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolSafetyHub extends Page
{
    protected static string $view = 'filament.pages.school-safety-hub';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 5;

    public string $activeTab = 'incidents'; // incidents | equipment | drills | psi_plan

    public string $searchQuery = '';

    public string $incidentTypeFilter = 'all';

    public string $severityFilter = 'all';

    public string $equipmentFilter = 'all'; // all | operational | due_or_expired

    // ─── Modal State: Declare Accident ──────────────────────────
    public bool $isAccidentModalOpen = false;

    public string $academicYear = '2026/2027';

    public string $incidentType = 'student_accident';

    public string $severityLevel = 'minor';

    public string $incidentDate = '';

    public string $location = '';

    public string $victimType = 'student';

    public ?int $victimStudentId = null;

    public ?int $victimUserId = null;

    public string $victimName = '';

    public string $circumstancesDescription = '';

    public string $bodilyInjuriesDescription = '';

    public string $immediateCareAdministered = '';

    public bool $hospitalEvacuation = false;

    public string $hospitalName = '';

    public string $medicalCertificateRef = '';

    public int $temporaryIncapacityDays = 0;

    public string $witnessesStatements = '';

    public string $supervisingPersonnel = '';

    public string $investigatingOfficer = '';

    // ─── Modal State: Register Equipment ────────────────────────
    public bool $isEquipmentModalOpen = false;

    public string $newEquipmentType = 'extinguisher_powder';

    public string $newEquipmentLocation = '';

    public string $newEquipmentInstallDate = '';

    public string $newEquipmentLastInspection = '';

    public string $newEquipmentNextDue = '';

    public string $newEquipmentStatus = 'operational';

    public string $newEquipmentInspector = '';

    public string $newEquipmentNotes = '';

    // ─── Modal State: Quick Inspection Update ───────────────────
    public bool $isInspectModalOpen = false;

    public ?int $selectedEquipmentId = null;

    public string $inspectStatus = 'operational';

    public string $inspectNextDue = '';

    public string $inspectEntity = '';

    // ─── Modal State: Record Evacuation Drill ───────────────────
    public bool $isDrillModalOpen = false;

    public string $drillDate = '';

    public string $drillTime = '10:00';

    public string $drillScenario = 'earthquake';

    public int $drillMinutes = 2;

    public int $drillSeconds = 30;

    public int $drillStudentsCount = 0;

    public int $drillStaffCount = 0;

    public string $drillAssemblyZone = 'الساحة المركزية الرئيسية';

    public string $drillPartner = 'الوحدة الرئيسية للحماية المدنية';

    public string $drillRepresentatives = '';

    public string $drillRating = 'good';

    public string $drillBottlenecks = '';

    public string $drillCorrectiveMeasures = '';

    // ─── Modal State: Add Intervention Member ───────────────────
    public bool $isMemberModalOpen = false;

    public string $memberTeamType = 'first_aid';

    public string $memberName = '';

    public string $memberRole = '';

    public ?int $memberUserId = null;

    public string $memberZone = '';

    public string $memberPhone = '';

    public bool $memberIsCertified = false;

    public string $memberNotes = '';

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('الوقاية والأمن والحوادث (PSI)');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة الوقاية والأمن الداخلي والحوادث المدرسية (PSI)');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolSafety();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->incidentDate = Carbon::now()->format('Y-m-d\TH:i');
        $this->drillDate = Carbon::today()->format('Y-m-d');
        $this->newEquipmentInstallDate = Carbon::today()->format('Y-m-d');
        $this->newEquipmentLastInspection = Carbon::today()->format('Y-m-d');
        $this->newEquipmentNextDue = Carbon::today()->addYear()->format('Y-m-d');
        $this->inspectNextDue = Carbon::today()->addYear()->format('Y-m-d');

        // Ensure default PSI plan exists
        app(SchoolSafetyService::class)->createOrUpdateSafetyPlan([
            'academic_year' => $this->academicYear,
            'title' => 'المخطط الداخلي للأمن والوقاية من الأخطار الكبرى (PSI) '.$this->academicYear,
            'crisis_cell_leader' => 'مدير المؤسسة التعليمية',
            'evacuation_coordinator' => 'مستشار التربية الرئيسي',
            'first_aid_leader' => 'طبيب / ممرض الصحة المدرسية (UDS)',
            'technical_safety_officer' => 'المقتصد / المسير المالي',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ─── Accident Declaration Actions ───────────────────────────

    public function openAccidentModal(): void
    {
        $this->reset([
            'victimStudentId', 'victimUserId', 'victimName',
            'circumstancesDescription', 'bodilyInjuriesDescription',
            'immediateCareAdministered', 'hospitalEvacuation', 'hospitalName',
            'medicalCertificateRef', 'temporaryIncapacityDays', 'witnessesStatements',
            'supervisingPersonnel', 'investigatingOfficer',
        ]);
        $this->incidentDate = Carbon::now()->format('Y-m-d\TH:i');
        $this->isAccidentModalOpen = true;
    }

    public function closeAccidentModal(): void
    {
        $this->isAccidentModalOpen = false;
    }

    public function submitAccident(): void
    {
        $this->validate([
            'incidentType' => 'required|string',
            'severityLevel' => 'required|string',
            'incidentDate' => 'required',
            'location' => 'required|string',
            'victimType' => 'required|string',
            'circumstancesDescription' => 'required|string',
        ]);

        $service = app(SchoolSafetyService::class);

        $service->declareAccident([
            'academic_year' => $this->academicYear,
            'incident_type' => $this->incidentType,
            'severity_level' => $this->severityLevel,
            'incident_date' => Carbon::parse($this->incidentDate),
            'location' => $this->location,
            'victim_type' => $this->victimType,
            'student_id' => $this->victimStudentId,
            'user_id' => $this->victimUserId,
            'victim_name' => $this->victimName ?: ($this->victimStudentId ? Student::find($this->victimStudentId)?->full_name : 'ضحية'),
            'circumstances_description' => $this->circumstancesDescription,
            'bodily_injuries_description' => $this->bodilyInjuriesDescription,
            'immediate_care_administered' => $this->immediateCareAdministered,
            'hospital_evacuation' => $this->hospitalEvacuation,
            'hospital_name' => $this->hospitalName,
            'medical_certificate_ref' => $this->medicalCertificateRef,
            'temporary_incapacity_days' => $this->temporaryIncapacityDays,
            'witnesses_statements' => $this->witnessesStatements,
            'supervising_personnel_at_time' => $this->supervisingPersonnel,
            'investigating_officer_name' => $this->investigatingOfficer ?: Auth::user()?->name,
            'status' => SafetyIncidentStatus::InTreatment->value,
        ]);

        $this->closeAccidentModal();

        Notification::make()
            ->title(__('تم تسجيل ملف الحادث المدرسي بنجاح وتوليد الرقم المرجعي'))
            ->success()
            ->send();
    }

    public function markIncidentDeclared(int $incidentId): void
    {
        $incident = SchoolSafetyIncident::findOrFail($incidentId);
        $service = app(SchoolSafetyService::class);
        $service->updateAccidentStatus($incident, SafetyIncidentStatus::Declared);

        Notification::make()
            ->title(__('تم تأكيد التصريح الرسمي لدى مصالح التأمين ومديرية التربية'))
            ->success()
            ->send();
    }

    public function markIncidentSettled(int $incidentId): void
    {
        $incident = SchoolSafetyIncident::findOrFail($incidentId);
        $service = app(SchoolSafetyService::class);
        $service->updateAccidentStatus($incident, SafetyIncidentStatus::Settled);

        Notification::make()
            ->title(__('تمت تسوية ملف الحادث وتأكيد شفاء الضحية'))
            ->success()
            ->send();
    }

    // ─── Fire Equipment Actions ─────────────────────────────────

    public function openEquipmentModal(): void
    {
        $this->newEquipmentLocation = '';
        $this->newEquipmentInspector = 'مؤسسة فحص وصيانة أجهزة الإطفاء المعتمدة';
        $this->newEquipmentNotes = '';
        $this->isEquipmentModalOpen = true;
    }

    public function closeEquipmentModal(): void
    {
        $this->isEquipmentModalOpen = false;
    }

    public function submitEquipment(): void
    {
        $this->validate([
            'newEquipmentType' => 'required|string',
            'newEquipmentLocation' => 'required|string',
            'newEquipmentLastInspection' => 'required|date',
            'newEquipmentNextDue' => 'required|date',
        ]);

        $service = app(SchoolSafetyService::class);

        $service->registerEquipment([
            'equipment_type' => $this->newEquipmentType,
            'exact_location' => $this->newEquipmentLocation,
            'installation_date' => $this->newEquipmentInstallDate ? Carbon::parse($this->newEquipmentInstallDate) : null,
            'last_inspection_date' => Carbon::parse($this->newEquipmentLastInspection),
            'next_inspection_due_date' => Carbon::parse($this->newEquipmentNextDue),
            'inspection_status' => $this->newEquipmentStatus,
            'inspector_entity' => $this->newEquipmentInspector,
            'notes' => $this->newEquipmentNotes,
        ]);

        $this->closeEquipmentModal();

        Notification::make()
            ->title(__('تم قيد جهاز السلامة والإطفاء بنجاح في السجل الرسمي'))
            ->success()
            ->send();
    }

    public function openInspectModal(int $equipmentId): void
    {
        $equipment = SafetyFireEquipment::findOrFail($equipmentId);
        $this->selectedEquipmentId = $equipment->id;
        $this->inspectStatus = $equipment->inspection_status?->value ?? 'operational';
        $this->inspectNextDue = Carbon::today()->addYear()->format('Y-m-d');
        $this->inspectEntity = $equipment->inspector_entity ?? 'المؤسسة المعتمدة';
        $this->isInspectModalOpen = true;
    }

    public function closeInspectModal(): void
    {
        $this->isInspectModalOpen = false;
        $this->selectedEquipmentId = null;
    }

    public function submitInspection(): void
    {
        if (! $this->selectedEquipmentId) {
            return;
        }

        $equipment = SafetyFireEquipment::findOrFail($this->selectedEquipmentId);
        $service = app(SchoolSafetyService::class);

        $service->updateEquipmentInspection(
            $equipment,
            $this->inspectStatus,
            Carbon::parse($this->inspectNextDue),
            $this->inspectEntity
        );

        $this->closeInspectModal();

        Notification::make()
            ->title(__('تم تحيين نتائج الفحص الفني الدوري للجهاز وتمديد الصلاحية'))
            ->success()
            ->send();
    }

    // ─── Disaster Evacuation Drill Actions ──────────────────────

    public function openDrillModal(): void
    {
        $this->drillDate = Carbon::today()->format('Y-m-d');
        $this->drillTime = '10:15';
        $this->drillStudentsCount = Student::where('is_active', true)->count() ?: 350;
        $this->drillStaffCount = User::count() ?: 28;
        $this->drillBottlenecks = '';
        $this->drillCorrectiveMeasures = '';
        $this->isDrillModalOpen = true;
    }

    public function closeDrillModal(): void
    {
        $this->isDrillModalOpen = false;
    }

    public function submitDrill(): void
    {
        $this->validate([
            'drillDate' => 'required|date',
            'drillScenario' => 'required|string',
            'drillAssemblyZone' => 'required|string',
        ]);

        $durationSeconds = ($this->drillMinutes * 60) + $this->drillSeconds;
        $service = app(SchoolSafetyService::class);

        $service->recordEvacuationDrill([
            'academic_year' => $this->academicYear,
            'drill_date' => Carbon::parse($this->drillDate),
            'drill_time' => $this->drillTime,
            'simulation_scenario' => $this->drillScenario,
            'evacuation_time_seconds' => $durationSeconds,
            'students_evacuated_count' => $this->drillStudentsCount,
            'staff_evacuated_count' => $this->drillStaffCount,
            'total_persons_evacuated' => $this->drillStudentsCount + $this->drillStaffCount,
            'assembly_zone_used' => $this->drillAssemblyZone,
            'civil_protection_partner' => $this->drillPartner,
            'civil_protection_representatives' => $this->drillRepresentatives,
            'evaluation_rating' => $this->drillRating,
            'bottlenecks_and_remarks' => $this->drillBottlenecks,
            'corrective_measures' => $this->drillCorrectiveMeasures,
            'status' => 'completed',
        ]);

        $this->closeDrillModal();

        Notification::make()
            ->title(__('تم تحرير واعتماد محضر تمرين الإخلاء الافتراضي بنجاح'))
            ->success()
            ->send();
    }

    // ─── PSI Intervention Squad Actions ─────────────────────────

    public function openMemberModal(): void
    {
        $this->memberName = '';
        $this->memberRole = '';
        $this->memberZone = '';
        $this->memberPhone = '';
        $this->memberIsCertified = false;
        $this->memberNotes = '';
        $this->isMemberModalOpen = true;
    }

    public function closeMemberModal(): void
    {
        $this->isMemberModalOpen = false;
    }

    public function submitMember(): void
    {
        $this->validate([
            'memberTeamType' => 'required|string',
            'memberName' => 'required|string',
        ]);

        $plan = SafetyEmergencyPlan::firstOrCreate(
            ['academic_year' => $this->academicYear],
            [
                'plan_code' => 'PSI-2026-01',
                'title' => 'المخطط الداخلي للأمن (PSI) '.$this->academicYear,
                'crisis_cell_leader' => 'مدير المؤسسة',
                'evacuation_coordinator' => 'مستشار التربية',
            ]
        );

        $service = app(SchoolSafetyService::class);
        $service->addInterventionMember($plan, [
            'team_type' => $this->memberTeamType,
            'member_name' => $this->memberName,
            'institution_role' => $this->memberRole,
            'user_id' => $this->memberUserId,
            'assigned_zone' => $this->memberZone,
            'phone_number' => $this->memberPhone,
            'is_certified_first_aider' => $this->memberIsCertified,
            'notes' => $this->memberNotes,
        ]);

        $this->closeMemberModal();

        Notification::make()
            ->title(__('تم تعيين عضو فوج التدخل في مخطط الأمن الداخلي بنجاح'))
            ->success()
            ->send();
    }

    // ─── Data Accessors ─────────────────────────────────────────

    public function getTelemetryProperty(): array
    {
        return app(SchoolSafetyService::class)->getSafetyTelemetry($this->academicYear);
    }

    public function getIncidentsProperty()
    {
        $query = SchoolSafetyIncident::where('academic_year', $this->academicYear)
            ->orderByDesc('incident_date');

        if ($this->incidentTypeFilter !== 'all') {
            $query->where('incident_type', $this->incidentTypeFilter);
        }

        if ($this->severityFilter !== 'all') {
            $query->where('severity_level', $this->severityFilter);
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('victim_name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('incident_number', 'like', "%{$this->searchQuery}%")
                    ->orWhere('location', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->get();
    }

    public function getEquipmentsProperty()
    {
        $query = SafetyFireEquipment::query()->orderBy('exact_location');

        if ($this->equipmentFilter === 'operational') {
            $query->where('inspection_status', SafetyEquipmentStatus::Operational->value)
                ->where('next_inspection_due_date', '>=', Carbon::today());
        } elseif ($this->equipmentFilter === 'due_or_expired') {
            $query->where(function ($q) {
                $q->where('inspection_status', '!=', SafetyEquipmentStatus::Operational->value)
                    ->orWhere('next_inspection_due_date', '<', Carbon::today());
            });
        }

        return $query->get();
    }

    public function getDrillsProperty()
    {
        return SafetyEvacuationDrill::where('academic_year', $this->academicYear)
            ->orderByDesc('drill_date')
            ->get();
    }

    public function getPsiPlanProperty(): ?SafetyEmergencyPlan
    {
        return SafetyEmergencyPlan::with('interventionTeams')->where('academic_year', $this->academicYear)->first();
    }

    public function getStudentsProperty()
    {
        return Student::where('is_active', true)->orderBy('last_name')->get();
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get();
    }
}
