<?php

namespace App\Filament\Pages;

use App\Enums\BoardingExitPassType;
use App\Enums\BoardingNightSessionType;
use App\Models\BoardingExitPass;
use App\Models\BoardingNightSession;
use App\Models\DormitoryRoom;
use App\Models\SchoolBusRoute;
use App\Models\Student;
use App\Models\StudentBusSubscription;
use App\Models\StudentDormitoryAllocation;
use App\Models\User;
use App\Services\SchoolBoardingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolBoardingHub extends Page
{
    protected static string $view = 'filament.pages.school-boarding-hub';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 4;

    public string $activeTab = 'rooms'; // rooms | night_sessions | exit_passes | transport

    public string $wingFilter = 'all'; // all | boys | girls

    public ?int $selectedRoomId = null;

    public string $searchQuery = '';

    // ─── Modal State: Add Dormitory Room ────────────────────────
    public bool $isRoomModalOpen = false;

    public string $newRoomWing = 'boys';

    public string $newRoomCode = '';

    public string $newRoomName = '';

    public int $newRoomFloor = 1;

    public int $newRoomCapacity = 10;

    public string $newRoomSupervisor = '';

    public string $newRoomNotes = '';

    // ─── Modal State: Allocate Bed to Student ───────────────────
    public bool $isAllocationModalOpen = false;

    public ?int $allocationRoomId = null;

    public ?int $allocationStudentId = null;

    public string $allocationBedNumber = '';

    public string $allocationLockerNumber = '';

    public string $allocationCheckInDate = '';

    public bool $allocationLinenReceived = true;

    public string $allocationNotes = '';

    // ─── Modal State: Record Night Session ──────────────────────
    public bool $isNightSessionModalOpen = false;

    public string $sessionDate = '';

    public string $sessionType = 'evening_study';

    public ?int $sessionSupervisorId = null;

    public string $sessionSupervisorName = '';

    public int $sessionPresentCount = 0;

    public int $sessionAbsentCount = 0;

    public int $sessionSickbayCount = 0;

    public int $sessionAuthorizedLeaveCount = 0;

    public string $sessionIncidentReport = '';

    // ─── Modal State: Issue Exit Pass ───────────────────────────
    public bool $isExitPassModalOpen = false;

    public ?int $passStudentId = null;

    public string $passType = 'weekend_leave';

    public string $passDepartureDatetime = '';

    public string $passReturnDatetime = '';

    public string $passEscortName = '';

    public string $passDestination = '';

    public string $passNotes = '';

    // ─── Modal State: Add Bus Route ─────────────────────────────
    public bool $isBusRouteModalOpen = false;

    public string $newRouteCode = '';

    public string $newRouteName = '';

    public string $newBusPlate = '';

    public string $newRouteProvider = 'بلدية المؤسسة';

    public string $newDriverName = '';

    public string $newDriverPhone = '';

    public string $newChaperoneName = '';

    public int $newRouteCapacity = 30;

    public string $newMorningDeparture = '07:15';

    public string $newEveningReturn = '16:45';

    public string $newRouteStops = '';

    // ─── Modal State: Subscribe Student to Bus ──────────────────
    public bool $isBusSubModalOpen = false;

    public ?int $subRouteId = null;

    public ?int $subStudentId = null;

    public string $subPickupStop = '';

    public string $subNotes = '';

    public function mount(): void
    {
        $today = now(config('app.timezone', 'Africa/Algiers'));
        $this->sessionDate = $today->format('Y-m-d');
        $this->allocationCheckInDate = $today->format('Y-m-d');
        $this->passDepartureDatetime = $today->copy()->setTime(16, 0)->format('Y-m-d\TH:i');
        $this->passReturnDatetime = $today->copy()->addDays(2)->setTime(18, 0)->format('Y-m-d\TH:i');

        $firstRoom = DormitoryRoom::first();
        if ($firstRoom) {
            $this->selectedRoomId = $firstRoom->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageBoarding();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('النظام الداخلي والإيواء والنقل');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة النظام الداخلي، الإيواء والمراقد، والمداومة الليلية والنقل المدرسي');
    }

    // ─── Dormitory Room Actions ──────────────────────────────────

    public function openRoomModal(): void
    {
        $this->newRoomCode = '';
        $this->newRoomName = '';
        $this->newRoomFloor = 1;
        $this->newRoomCapacity = 10;
        $this->newRoomSupervisor = '';
        $this->newRoomNotes = '';
        $this->isRoomModalOpen = true;
    }

    public function closeRoomModal(): void
    {
        $this->isRoomModalOpen = false;
    }

    public function createRoom(SchoolBoardingService $service): void
    {
        $room = $service->createRoom([
            'wing' => $this->newRoomWing,
            'room_code' => $this->newRoomCode,
            'room_name' => $this->newRoomName ?: sprintf('مرقد %s (طابق %d)', $this->newRoomWing === 'boys' ? 'الذكور' : 'الإناث', $this->newRoomFloor),
            'floor_level' => $this->newRoomFloor,
            'bed_capacity' => $this->newRoomCapacity,
            'supervisor_in_charge' => $this->newRoomSupervisor,
            'notes' => $this->newRoomNotes,
        ]);

        $this->selectedRoomId = $room->id;
        $this->isRoomModalOpen = false;

        Notification::make()
            ->title(__('تم إنشاء مرقد الإيواء بنجاح'))
            ->success()
            ->send();
    }

    public function openAllocationModal(?int $roomId = null): void
    {
        $this->allocationRoomId = $roomId ?? $this->selectedRoomId;
        $this->allocationStudentId = null;
        $this->allocationBedNumber = '';
        $this->allocationLockerNumber = '';
        $this->allocationLinenReceived = true;
        $this->allocationNotes = '';
        $this->isAllocationModalOpen = true;
    }

    public function closeAllocationModal(): void
    {
        $this->isAllocationModalOpen = false;
    }

    public function allocateBed(SchoolBoardingService $service): void
    {
        if (! $this->allocationRoomId || ! $this->allocationStudentId) {
            Notification::make()->title(__('يرجى اختيار المرقد والتلميذ النزيل'))->danger()->send();

            return;
        }

        $service->allocateBed([
            'dormitory_room_id' => $this->allocationRoomId,
            'student_id' => $this->allocationStudentId,
            'bed_number' => $this->allocationBedNumber,
            'locker_number' => $this->allocationLockerNumber,
            'check_in_date' => $this->allocationCheckInDate,
            'linen_received' => $this->allocationLinenReceived,
            'notes' => $this->allocationNotes,
        ]);

        $this->isAllocationModalOpen = false;

        Notification::make()
            ->title(__('تم تخصيص السرير وتسجيل التلميذ بالمرقد الداخلي بنجاح'))
            ->success()
            ->send();
    }

    public function vacateBed(int $allocationId, SchoolBoardingService $service): void
    {
        $allocation = StudentDormitoryAllocation::findOrFail($allocationId);
        $service->vacateBed($allocation);

        Notification::make()
            ->title(__('تم إخلاء السرير واستلام الفراش والخزانة بنجاح'))
            ->info()
            ->send();
    }

    // ─── Night Session Actions ───────────────────────────────────

    public function openNightSessionModal(): void
    {
        $this->sessionDate = now()->format('Y-m-d');
        $this->sessionType = 'evening_study';
        $this->sessionSupervisorId = Auth::id();
        $this->sessionSupervisorName = Auth::user()?->name ?? 'مساعد التربية المناوب';
        $telemetry = app(SchoolBoardingService::class)->getBoardingTelemetry();
        $this->sessionPresentCount = $telemetry['activeBoardersCount'];
        $this->sessionAbsentCount = 0;
        $this->sessionSickbayCount = 0;
        $this->sessionAuthorizedLeaveCount = 0;
        $this->sessionIncidentReport = '';
        $this->isNightSessionModalOpen = true;
    }

    public function closeNightSessionModal(): void
    {
        $this->isNightSessionModalOpen = false;
    }

    public function recordNightSession(SchoolBoardingService $service): void
    {
        $service->recordNightSession([
            'session_date' => $this->sessionDate,
            'session_type' => BoardingNightSessionType::from($this->sessionType),
            'supervisor_user_id' => $this->sessionSupervisorId,
            'supervisor_name' => $this->sessionSupervisorName,
            'boarders_present_count' => $this->sessionPresentCount,
            'boarders_absent_count' => $this->sessionAbsentCount,
            'sickbay_count' => $this->sessionSickbayCount,
            'authorized_leave_count' => $this->sessionAuthorizedLeaveCount,
            'incident_report' => $this->sessionIncidentReport,
        ], Auth::id());

        $this->isNightSessionModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل محضر المداومة والمذاكرة الليلية بنجاح'))
            ->success()
            ->send();
    }

    // ─── Exit Pass Actions ───────────────────────────────────────

    public function openExitPassModal(): void
    {
        $this->passStudentId = null;
        $this->passType = 'weekend_leave';
        $this->passEscortName = '';
        $this->passDestination = '';
        $this->passNotes = '';
        $this->isExitPassModalOpen = true;
    }

    public function closeExitPassModal(): void
    {
        $this->isExitPassModalOpen = false;
    }

    public function onPassStudentSelected(): void
    {
        if ($this->passStudentId) {
            $student = Student::find($this->passStudentId);
            if ($student) {
                $this->passEscortName = $student->guardian_name ?? '';
            }
        }
    }

    public function issueExitPass(SchoolBoardingService $service): void
    {
        if (! $this->passStudentId) {
            Notification::make()->title(__('يرجى اختيار التلميذ الداخلي'))->danger()->send();

            return;
        }

        $service->issueExitPass([
            'student_id' => $this->passStudentId,
            'pass_type' => BoardingExitPassType::from($this->passType),
            'departure_datetime' => $this->passDepartureDatetime,
            'expected_return_datetime' => $this->passReturnDatetime,
            'escort_guardian_name' => $this->passEscortName ?: 'الولي الشرعي',
            'destination' => $this->passDestination ?: 'مقر سكن العائلة',
            'supervisor_signature_notes' => $this->passNotes,
        ], Auth::id());

        $this->isExitPassModalOpen = false;

        Notification::make()
            ->title(__('تم إصدار رخصة وبطاقة خروج التلميذ الداخلي بنجاح'))
            ->success()
            ->send();
    }

    public function markPassReturned(int $passId, SchoolBoardingService $service): void
    {
        $pass = BoardingExitPass::findOrFail($passId);
        $service->markPassReturned($pass);

        Notification::make()
            ->title(__('تم تأكيد عودة التلميذ والتحاقه بالمرقد الداخلي بنجاح'))
            ->success()
            ->send();
    }

    // ─── Transport Actions ───────────────────────────────────────

    public function openBusRouteModal(): void
    {
        $this->newRouteCode = '';
        $this->newRouteName = '';
        $this->newBusPlate = '';
        $this->newDriverName = '';
        $this->newDriverPhone = '';
        $this->newChaperoneName = '';
        $this->newRouteCapacity = 30;
        $this->newMorningDeparture = '07:15';
        $this->newEveningReturn = '16:45';
        $this->newRouteStops = 'محطة القرية — البلدية — المؤسسة';
        $this->isBusRouteModalOpen = true;
    }

    public function closeBusRouteModal(): void
    {
        $this->isBusRouteModalOpen = false;
    }

    public function createBusRoute(SchoolBoardingService $service): void
    {
        $service->createBusRoute([
            'route_code' => $this->newRouteCode,
            'route_name' => $this->newRouteName,
            'bus_license_plate' => $this->newBusPlate,
            'provider_entity' => $this->newRouteProvider,
            'driver_name' => $this->newDriverName,
            'driver_phone' => $this->newDriverPhone,
            'chaperone_name' => $this->newChaperoneName,
            'passenger_capacity' => $this->newRouteCapacity,
            'morning_departure_time' => $this->newMorningDeparture,
            'evening_return_time' => $this->newEveningReturn,
            'stops_list' => $this->newRouteStops,
        ]);

        $this->isBusRouteModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل خط حافلة النقل المدرسي بنجاح'))
            ->success()
            ->send();
    }

    public function openBusSubModal(?int $routeId = null): void
    {
        $this->subRouteId = $routeId;
        $this->subStudentId = null;
        $this->subPickupStop = '';
        $this->subNotes = '';
        $this->isBusSubModalOpen = true;
    }

    public function closeBusSubModal(): void
    {
        $this->isBusSubModalOpen = false;
    }

    public function subscribeStudentToBus(SchoolBoardingService $service): void
    {
        if (! $this->subRouteId || ! $this->subStudentId) {
            Notification::make()->title(__('يرجى اختيار خط النقل والتلميذ'))->danger()->send();

            return;
        }

        $service->subscribeStudentToBus([
            'bus_route_id' => $this->subRouteId,
            'student_id' => $this->subStudentId,
            'pickup_stop' => $this->subPickupStop ?: 'المحطة الرئيسية',
            'notes' => $this->subNotes,
        ]);

        $this->isBusSubModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل اشتراك التلميذ وإصدار بطاقة ركوب النقل المدرسي بنجاح'))
            ->success()
            ->send();
    }

    // ─── View Properties ─────────────────────────────────────────

    public function getTelemetryProperty(SchoolBoardingService $service): array
    {
        return $service->getBoardingTelemetry();
    }

    public function getRoomsProperty()
    {
        $query = DormitoryRoom::query();
        if ($this->wingFilter !== 'all') {
            $query->where('wing', $this->wingFilter);
        }

        return $query->orderBy('floor_level')->orderBy('room_code')->get();
    }

    public function getSelectedRoomProperty(): ?DormitoryRoom
    {
        return $this->selectedRoomId
            ? DormitoryRoom::with(['activeAllocations.student.group.classroom'])->find($this->selectedRoomId)
            : null;
    }

    public function getNightSessionsProperty()
    {
        return BoardingNightSession::with(['supervisorUser', 'recordedBy'])
            ->latest('session_date')
            ->paginate(12);
    }

    public function getExitPassesProperty()
    {
        $query = BoardingExitPass::with(['student.group.classroom', 'authorizedBy']);
        if ($this->searchQuery) {
            $q = '%'.$this->searchQuery.'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('pass_number', 'like', $q)
                    ->orWhere('escort_guardian_name', 'like', $q)
                    ->orWhere('destination', 'like', $q)
                    ->orWhereHas('student', function ($sq) use ($q) {
                        $sq->where('first_name', 'like', $q)
                            ->orWhere('last_name', 'like', $q);
                    });
            });
        }

        return $query->latest('departure_datetime')->paginate(12);
    }

    public function getBusRoutesProperty()
    {
        return SchoolBusRoute::withCount('subscriptions')->get();
    }

    public function getBusSubscriptionsProperty()
    {
        return StudentBusSubscription::with(['busRoute', 'student.group.classroom'])
            ->latest('id')
            ->paginate(15);
    }

    public function getStudentsListProperty()
    {
        return Student::with('group.classroom')
            ->active()
            ->orderBy('last_name')
            ->limit(100)
            ->get();
    }

    public function getBoarderCandidatesProperty()
    {
        return Student::with('group.classroom')
            ->active()
            ->orderBy('last_name')
            ->limit(100)
            ->get();
    }
}
