<?php

namespace App\Filament\Pages;

use App\Enums\OfficialExamCenterStatus;
use App\Models\OfficialExamCenter;
use App\Models\User;
use App\Services\OfficialExamCenterService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class OfficialExamCenterHub extends Page
{
    protected static string $view = 'filament.pages.official-exam-center-hub';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 3;

    public string $activeTab = 'centers_and_rooms'; // centers_and_rooms | supervisors | incidents

    public ?int $selectedCenterId = null;

    // Modal state: Create Center
    public bool $isCenterModalOpen = false;

    public string $centerExamType = 'bem';

    public int $centerSessionYear = 2026;

    public string $centerName = '';

    public ?int $centerHeadUserId = null;

    public string $centerViceHeadName = '';

    public string $centerSecretariatChief = '';

    public string $centerStartDate = '';

    public string $centerEndDate = '';

    public string $centerNotes = '';

    // Modal state: Generate Rooms
    public bool $isGenerateRoomsModalOpen = false;

    public int $roomsToGenerate = 10;

    public int $candidatesPerRoom = 20;

    public ?int $startRegistrationNumber = null;

    // Modal state: Assign Supervisor
    public bool $isSupervisorModalOpen = false;

    public ?int $supervisorRoomId = null;

    public ?int $supervisorUserId = null;

    public string $supervisorName = '';

    public string $supervisorOrigin = '';

    public string $supervisorDutyRole = 'room_supervisor';

    public string $supervisorConvocation = '';

    // Modal state: Add Incident / Envelope PV
    public bool $isIncidentModalOpen = false;

    public ?int $incidentRoomId = null;

    public string $incidentType = 'envelope_opening';

    public string $incidentDate = '';

    public string $incidentSessionTime = 'morning';

    public string $incidentSubject = '';

    public string $incidentCandidateReg = '';

    public string $incidentCandidateName = '';

    public string $incidentDescription = '';

    public string $incidentSeizedEvidence = '';

    public string $incidentMeasuresTaken = '';

    public function mount(): void
    {
        $today = now(config('app.timezone', 'Africa/Algiers'));
        $todayStr = $today->format('Y-m-d');

        $this->centerStartDate = $todayStr;
        $this->centerEndDate = $today->copy()->addDays(3)->format('Y-m-d');
        $this->incidentDate = $todayStr;

        $firstCenter = OfficialExamCenter::latest('session_year')->first();
        if ($firstCenter) {
            $this->selectedCenterId = $firstCenter->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageOfficialExams();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التقويمات والامتحانات والمجالس');
    }

    public static function getNavigationLabel(): string
    {
        return __('مراكز الامتحانات الرسمية (BEM / BAC)');
    }

    public function getTitle(): string|Htmlable
    {
        return __('تسيير مراكز الامتحانات الرسمية (BEM / BAC) وتأطير الحراسة');
    }

    // ─── Center Actions ─────────────────────────────────────────

    public function openCenterModal(): void
    {
        $this->isCenterModalOpen = true;
    }

    public function closeCenterModal(): void
    {
        $this->isCenterModalOpen = false;
    }

    public function saveCenter(OfficialExamCenterService $service): void
    {
        $this->validate([
            'centerExamType' => 'required|string',
            'centerSessionYear' => 'required|integer|min:2020|max:2035',
            'centerName' => 'required|string|max:255',
            'centerStartDate' => 'required|date',
            'centerEndDate' => 'required|date|after_or_equal:centerStartDate',
        ]);

        $center = $service->createCenter([
            'exam_type' => $this->centerExamType,
            'session_year' => $this->centerSessionYear,
            'center_name' => $this->centerName,
            'head_user_id' => $this->centerHeadUserId ?: Auth::id(),
            'vice_head_name' => $this->centerViceHeadName ?: null,
            'secretariat_chief_name' => $this->centerSecretariatChief ?: null,
            'start_date' => $this->centerStartDate,
            'end_date' => $this->centerEndDate,
            'status' => OfficialExamCenterStatus::Planning->value,
            'notes' => $this->centerNotes ?: null,
        ]);

        $this->selectedCenterId = $center->id;
        $this->isCenterModalOpen = false;
        $this->reset(['centerName', 'centerViceHeadName', 'centerSecretariatChief', 'centerNotes']);

        Notification::make()
            ->title(__('تم إنشاء مركز إجراء الامتحان الرسمي بنجاح'))
            ->success()
            ->send();
    }

    // ─── Room Generation Actions ────────────────────────────────

    public function openGenerateRoomsModal(): void
    {
        $this->isGenerateRoomsModalOpen = true;
    }

    public function closeGenerateRoomsModal(): void
    {
        $this->isGenerateRoomsModalOpen = false;
    }

    public function executeGenerateRooms(OfficialExamCenterService $service): void
    {
        $this->validate([
            'selectedCenterId' => 'required|exists:official_exam_centers,id',
            'roomsToGenerate' => 'required|integer|min:1|max:50',
            'candidatesPerRoom' => 'required|integer|min:5|max:35',
        ]);

        $center = OfficialExamCenter::findOrFail($this->selectedCenterId);
        $service->generateRooms(
            $center,
            $this->roomsToGenerate,
            $this->candidatesPerRoom,
            $this->startRegistrationNumber
        );

        $this->isGenerateRoomsModalOpen = false;

        Notification::make()
            ->title(__('تم توليد وتوزيع قاعات الامتحان وأرقام المترشحين بنجاح'))
            ->success()
            ->send();
    }

    // ─── Supervisor Actions ─────────────────────────────────────

    public function openSupervisorModal(): void
    {
        $this->isSupervisorModalOpen = true;
    }

    public function closeSupervisorModal(): void
    {
        $this->isSupervisorModalOpen = false;
    }

    public function saveSupervisor(OfficialExamCenterService $service): void
    {
        $this->validate([
            'selectedCenterId' => 'required|exists:official_exam_centers,id',
            'supervisorName' => 'required|string|max:255',
            'supervisorDutyRole' => 'required|string',
        ]);

        $service->assignSupervisor([
            'center_id' => $this->selectedCenterId,
            'room_id' => $this->supervisorRoomId ?: null,
            'user_id' => $this->supervisorUserId ?: null,
            'supervisor_name' => $this->supervisorName,
            'origin_institution' => $this->supervisorOrigin ?: null,
            'duty_role' => $this->supervisorDutyRole,
            'convocation_reference' => $this->supervisorConvocation ?: null,
        ]);

        $this->isSupervisorModalOpen = false;
        $this->reset(['supervisorRoomId', 'supervisorUserId', 'supervisorName', 'supervisorOrigin', 'supervisorConvocation']);

        Notification::make()
            ->title(__('تم تسجيل وتعيين الأستاذ الحارس بنجاح'))
            ->success()
            ->send();
    }

    // ─── Incident Actions ───────────────────────────────────────

    public function openIncidentModal(): void
    {
        $this->isIncidentModalOpen = true;
    }

    public function closeIncidentModal(): void
    {
        $this->isIncidentModalOpen = false;
    }

    public function saveIncident(OfficialExamCenterService $service): void
    {
        $this->validate([
            'selectedCenterId' => 'required|exists:official_exam_centers,id',
            'incidentType' => 'required|string',
            'incidentDate' => 'required|date',
            'incidentSubject' => 'required|string|max:255',
            'incidentDescription' => 'required|string',
        ]);

        $service->recordIncident([
            'center_id' => $this->selectedCenterId,
            'room_id' => $this->incidentRoomId ?: null,
            'incident_type' => $this->incidentType,
            'incident_date' => $this->incidentDate,
            'session_time' => $this->incidentSessionTime,
            'subject_name' => $this->incidentSubject,
            'candidate_registration_number' => $this->incidentCandidateReg ?: null,
            'candidate_name' => $this->incidentCandidateName ?: null,
            'description' => $this->incidentDescription,
            'seized_evidence' => $this->incidentSeizedEvidence ?: null,
            'measures_taken' => $this->incidentMeasuresTaken ?: null,
            'reported_by' => Auth::id(),
        ]);

        $this->isIncidentModalOpen = false;
        $this->reset([
            'incidentRoomId',
            'incidentSubject',
            'incidentCandidateReg',
            'incidentCandidateName',
            'incidentDescription',
            'incidentSeizedEvidence',
            'incidentMeasuresTaken',
        ]);

        Notification::make()
            ->title(__('تم حفظ وتوثيق المحضر الرسمي بنجاح'))
            ->success()
            ->send();
    }

    // ─── View Data Accessor ─────────────────────────────────────

    public function getViewData(): array
    {
        /** @var OfficialExamCenterService $service */
        $service = app(OfficialExamCenterService::class);

        $centers = OfficialExamCenter::latest('session_year')->get();
        $selectedCenter = $this->selectedCenterId
            ? OfficialExamCenter::find($this->selectedCenterId)
            : $centers->first();

        $telemetry = $selectedCenter
            ? $service->getCenterTelemetry($selectedCenter)
            : [
                'totalRooms' => 0,
                'totalCandidates' => 0,
                'totalSupervisors' => 0,
                'roomChiefsCount' => 0,
                'roomSupervisorsCount' => 0,
                'corridorGuardsCount' => 0,
                'secretariatCount' => 0,
                'incidentsCount' => 0,
                'coveragePercentage' => 0.0,
            ];

        $rooms = $selectedCenter
            ? $selectedCenter->rooms()->with('supervisors')->get()
            : collect();

        $supervisors = $selectedCenter
            ? $selectedCenter->supervisors()->with('room')->get()
            : collect();

        $incidents = $selectedCenter
            ? $selectedCenter->incidents()->with(['room', 'reporter'])->get()
            : collect();

        $teachers = User::whereNotNull('name')->orderBy('name')->get();

        return [
            'centers' => $centers,
            'selectedCenter' => $selectedCenter,
            'telemetry' => $telemetry,
            'rooms' => $rooms,
            'supervisors' => $supervisors,
            'incidents' => $incidents,
            'teachers' => $teachers,
        ];
    }
}
