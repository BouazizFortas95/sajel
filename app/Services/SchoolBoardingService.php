<?php

namespace App\Services;

use App\Enums\BoardingExitPassStatus;
use App\Enums\BoardingExitPassType;
use App\Enums\BoardingNightSessionType;
use App\Enums\DormitoryAllocationStatus;
use App\Enums\DormitoryWing;
use App\Models\BoardingExitPass;
use App\Models\BoardingNightSession;
use App\Models\DormitoryRoom;
use App\Models\SchoolBusRoute;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentBusSubscription;
use App\Models\StudentDormitoryAllocation;
use Illuminate\Support\Facades\DB;

class SchoolBoardingService
{
    /**
     * Generate sequential code for dormitory room (e.g. DORT-B-101).
     */
    public function generateRoomCode(DormitoryWing $wing, int $floor): string
    {
        $letter = $wing === DormitoryWing::Boys ? 'B' : 'G';
        $prefix = "DORT-{$letter}-{$floor}";
        $count = DormitoryRoom::where('room_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential exit pass number (e.g. PASS/2026/001).
     */
    public function generateExitPassNumber(int $year): string
    {
        $prefix = "PASS/{$year}/";
        $lastRecord = BoardingExitPass::where('pass_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('pass_number');

        $sequence = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Generate sequential bus card number (e.g. TRANS/2026/001).
     */
    public function generateBusCardNumber(int $year): string
    {
        $prefix = "TRANS/{$year}/";
        $lastRecord = StudentBusSubscription::where('card_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('card_number');

        $sequence = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Generate sequential bus route code (e.g. BUS-L01).
     */
    public function generateBusRouteCode(): string
    {
        $count = SchoolBusRoute::count();

        return sprintf('BUS-L%02d', $count + 1);
    }

    /**
     * Create a new dormitory room / pavilion.
     */
    public function createRoom(array $data): DormitoryRoom
    {
        $wing = $data['wing'] instanceof DormitoryWing ? $data['wing'] : DormitoryWing::from($data['wing'] ?? 'boys');
        $floor = (int) ($data['floor_level'] ?? 1);
        $code = $data['room_code'] ?? $this->generateRoomCode($wing, $floor);

        return DormitoryRoom::create([
            'wing' => $wing,
            'room_code' => $code,
            'room_name' => $data['room_name'] ?? sprintf('مرقد %s (طابق %d)', $wing->shortLabel(), $floor),
            'floor_level' => $floor,
            'bed_capacity' => $data['bed_capacity'] ?? 10,
            'active_occupants_count' => 0,
            'supervisor_in_charge' => $data['supervisor_in_charge'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Allocate a bed to a student in a dormitory room.
     */
    public function allocateBed(array $data): StudentDormitoryAllocation
    {
        return DB::transaction(function () use ($data) {
            $room = DormitoryRoom::findOrFail($data['dormitory_room_id']);
            $student = Student::findOrFail($data['student_id']);
            $academicYear = $data['academic_year'] ?? SchoolSetting::currentAcademicYear() ?? '2026/2027';

            // Check if student already has active allocation in this academic year
            $existing = StudentDormitoryAllocation::where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('status', DormitoryAllocationStatus::Active)
                ->first();

            if ($existing) {
                return $existing;
            }

            $allocation = StudentDormitoryAllocation::create([
                'dormitory_room_id' => $room->id,
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'bed_number' => $data['bed_number'] ?? sprintf('LIT-%02d', $room->active_occupants_count + 1),
                'locker_number' => $data['locker_number'] ?? sprintf('CAS-%02d', $room->active_occupants_count + 1),
                'check_in_date' => $data['check_in_date'] ?? now()->toDateString(),
                'status' => DormitoryAllocationStatus::Active,
                'linen_received' => $data['linen_received'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update room active occupants count
            $room->increment('active_occupants_count');

            // Set student's regime to boarder if not already
            if (! $student->isBoarder()) {
                $student->update(['regime' => 'boarder']);
            }

            return $allocation;
        });
    }

    /**
     * Vacate bed allocation and update room count.
     */
    public function vacateBed(StudentDormitoryAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            $allocation->update([
                'status' => DormitoryAllocationStatus::Vacated,
                'check_out_date' => now()->toDateString(),
            ]);

            $room = $allocation->dormitoryRoom;
            if ($room && $room->active_occupants_count > 0) {
                $room->decrement('active_occupants_count');
            }
        });
    }

    /**
     * Record evening study or night supervision session.
     */
    public function recordNightSession(array $data, int $userId): BoardingNightSession
    {
        $academicYear = $data['academic_year'] ?? SchoolSetting::currentAcademicYear() ?? '2026/2027';

        return BoardingNightSession::create([
            'academic_year' => $academicYear,
            'session_date' => $data['session_date'] ?? now()->toDateString(),
            'session_type' => $data['session_type'] ?? BoardingNightSessionType::EveningStudy,
            'supervisor_user_id' => $data['supervisor_user_id'] ?? $userId,
            'supervisor_name' => $data['supervisor_name'] ?? 'مساعد التربية المناوب',
            'boarders_present_count' => $data['boarders_present_count'] ?? 0,
            'boarders_absent_count' => $data['boarders_absent_count'] ?? 0,
            'sickbay_count' => $data['sickbay_count'] ?? 0,
            'authorized_leave_count' => $data['authorized_leave_count'] ?? 0,
            'incident_report' => $data['incident_report'] ?? null,
            'recorded_by' => $userId,
        ]);
    }

    /**
     * Issue an official weekend/holiday exit pass for a boarder.
     */
    public function issueExitPass(array $data, int $userId): BoardingExitPass
    {
        $year = (int) now()->year;
        $passNumber = $data['pass_number'] ?? $this->generateExitPassNumber($year);
        $academicYear = $data['academic_year'] ?? SchoolSetting::currentAcademicYear() ?? '2026/2027';

        return BoardingExitPass::create([
            'pass_number' => $passNumber,
            'student_id' => $data['student_id'],
            'academic_year' => $academicYear,
            'pass_type' => $data['pass_type'] ?? BoardingExitPassType::WeekendLeave,
            'departure_datetime' => $data['departure_datetime'] ?? now(),
            'expected_return_datetime' => $data['expected_return_datetime'] ?? now()->addDays(2),
            'escort_guardian_name' => $data['escort_guardian_name'],
            'destination' => $data['destination'],
            'status' => BoardingExitPassStatus::Authorized,
            'supervisor_signature_notes' => $data['supervisor_signature_notes'] ?? null,
            'authorized_by' => $userId,
        ]);
    }

    /**
     * Mark an exit pass as returned.
     */
    public function markPassReturned(BoardingExitPass $pass, ?string $returnTime = null): BoardingExitPass
    {
        $pass->update([
            'status' => BoardingExitPassStatus::Returned,
            'actual_return_datetime' => $returnTime ? now()->parse($returnTime) : now(),
        ]);

        return $pass->fresh();
    }

    /**
     * Create a school bus route.
     */
    public function createBusRoute(array $data): SchoolBusRoute
    {
        $code = $data['route_code'] ?? $this->generateBusRouteCode();

        return SchoolBusRoute::create([
            'route_code' => $code,
            'route_name' => $data['route_name'],
            'bus_license_plate' => $data['bus_license_plate'] ?? null,
            'provider_entity' => $data['provider_entity'] ?? 'بلدية المؤسسة',
            'driver_name' => $data['driver_name'],
            'driver_phone' => $data['driver_phone'] ?? null,
            'chaperone_name' => $data['chaperone_name'] ?? null,
            'passenger_capacity' => $data['passenger_capacity'] ?? 30,
            'subscribed_students_count' => 0,
            'morning_departure_time' => $data['morning_departure_time'] ?? '07:15',
            'evening_return_time' => $data['evening_return_time'] ?? '16:45',
            'stops_list' => $data['stops_list'] ?? 'المحطة المركزية',
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Subscribe a student to a bus route.
     */
    public function subscribeStudentToBus(array $data): StudentBusSubscription
    {
        return DB::transaction(function () use ($data) {
            $route = SchoolBusRoute::findOrFail($data['bus_route_id']);
            $year = (int) now()->year;
            $cardNumber = $data['card_number'] ?? $this->generateBusCardNumber($year);
            $academicYear = $data['academic_year'] ?? SchoolSetting::currentAcademicYear() ?? '2026/2027';

            $subscription = StudentBusSubscription::create([
                'bus_route_id' => $route->id,
                'student_id' => $data['student_id'],
                'academic_year' => $academicYear,
                'card_number' => $cardNumber,
                'pickup_stop' => $data['pickup_stop'] ?? 'المحطة الرئيسية',
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'expiry_date' => $data['expiry_date'] ?? now()->addMonths(9)->toDateString(),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            $route->increment('subscribed_students_count');

            return $subscription;
        });
    }

    /**
     * Get Boarding & Transportation Telemetry.
     */
    public function getBoardingTelemetry(?string $academicYear = null): array
    {
        $year = $academicYear ?? SchoolSetting::currentAcademicYear() ?? '2026/2027';

        $totalRooms = DormitoryRoom::count();
        $boysRooms = DormitoryRoom::where('wing', DormitoryWing::Boys)->count();
        $girlsRooms = DormitoryRoom::where('wing', DormitoryWing::Girls)->count();

        $totalBedCapacity = (int) DormitoryRoom::sum('bed_capacity');
        $occupiedBeds = (int) DormitoryRoom::sum('active_occupants_count');
        $availableBeds = max(0, $totalBedCapacity - $occupiedBeds);
        $occupancyRate = $totalBedCapacity > 0 ? round(($occupiedBeds / $totalBedCapacity) * 100, 1) : 0;

        $activeBoardersCount = StudentDormitoryAllocation::where('academic_year', $year)
            ->where('status', DormitoryAllocationStatus::Active)
            ->count();

        $activeBusRoutesCount = SchoolBusRoute::where('status', 'active')->count();
        $totalSubscribedBusStudents = (int) SchoolBusRoute::sum('subscribed_students_count');

        $activeExitPassesCount = BoardingExitPass::where('academic_year', $year)
            ->whereIn('status', [BoardingExitPassStatus::Authorized, BoardingExitPassStatus::Departed])
            ->count();

        return [
            'totalRooms' => $totalRooms,
            'boysRooms' => $boysRooms,
            'girlsRooms' => $girlsRooms,
            'totalBedCapacity' => $totalBedCapacity,
            'occupiedBeds' => $occupiedBeds,
            'availableBeds' => $availableBeds,
            'occupancyRate' => $occupancyRate,
            'activeBoardersCount' => $activeBoardersCount,
            'activeBusRoutesCount' => $activeBusRoutesCount,
            'totalSubscribedBusStudents' => $totalSubscribedBusStudents,
            'activeExitPassesCount' => $activeExitPassesCount,
        ];
    }

    /**
     * Assemble Dormitory Room Roster Data for Official Printing.
     */
    public function assembleDormitoryRosterData(DormitoryRoom $room): array
    {
        $setting = SchoolSetting::first();
        $allocations = $room->activeAllocations()
            ->with(['student.group.classroom'])
            ->get();

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
                'daira' => $setting?->daira ?? 'الجزائر الوسطى',
            ],
            'room' => [
                'id' => $room->id,
                'code' => $room->room_code,
                'name' => $room->room_name,
                'wing' => $room->wing->label(),
                'floor' => $room->floor_level,
                'capacity' => $room->bed_capacity,
                'occupied' => $room->active_occupants_count,
                'available' => max(0, $room->bed_capacity - $room->active_occupants_count),
                'supervisor' => $room->supervisor_in_charge ?? 'مساعد التربية المسؤول عن الجناح',
            ],
            'allocations' => $allocations->map(function ($item, $idx) {
                return [
                    'index' => $idx + 1,
                    'bed_number' => $item->bed_number,
                    'locker_number' => $item->locker_number ?? '—',
                    'student_name' => $item->student?->full_name ?? '—',
                    'matricule' => $item->student?->matricule_number ?? $item->student?->registration_number ?? '—',
                    'classroom' => $item->student?->group?->classroom?->name ?? '—',
                    'check_in_date' => $item->check_in_date->format('Y-m-d'),
                    'linen_received' => $item->linen_received ? 'نعم (مستلم)' : 'لا',
                    'notes' => $item->notes ?? '—',
                ];
            }),
            'academic_year' => SchoolSetting::currentAcademicYear() ?? '2026/2027',
            'generated_at' => now()->format('Y-m-d H:i'),
        ];
    }

    /**
     * Assemble Boarding Exit Pass Data for Official Printing.
     */
    public function assembleExitPassData(BoardingExitPass $pass): array
    {
        $setting = SchoolSetting::first();
        $student = $pass->student;

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
            ],
            'pass' => [
                'id' => $pass->id,
                'number' => $pass->pass_number,
                'type' => $pass->pass_type->label(),
                'departure' => $pass->departure_datetime->format('Y-m-d H:i'),
                'expected_return' => $pass->expected_return_datetime->format('Y-m-d H:i'),
                'escort' => $pass->escort_guardian_name,
                'destination' => $pass->destination,
                'status' => $pass->status->label(),
                'notes' => $pass->supervisor_signature_notes,
            ],
            'student' => [
                'full_name' => $student?->full_name ?? '—',
                'matricule' => $student?->matricule_number ?? $student?->registration_number ?? '—',
                'classroom' => $student?->group?->classroom?->name ?? '—',
                'guardian_name' => $student?->guardian_name ?? 'الولي الشرعي',
                'guardian_phone' => $student?->guardian_phone ?? '—',
            ],
            'academic_year' => $pass->academic_year,
            'authorizer' => [
                'name' => $pass->authorizedBy?->name ?? 'مستشار التربية',
                'role' => $pass->authorizedBy?->role?->label() ?? 'مستشار التربية',
            ],
            'generated_at' => now()->format('Y-m-d H:i'),
        ];
    }

    /**
     * Assemble Student Bus Subscription Card Data for Printing.
     */
    public function assembleBusCardData(StudentBusSubscription $sub): array
    {
        $setting = SchoolSetting::first();
        $student = $sub->student;
        $route = $sub->busRoute;

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
                'commune' => $setting?->commune ?? 'الجزائر الوسطى',
            ],
            'subscription' => [
                'id' => $sub->id,
                'card_number' => $sub->card_number,
                'pickup_stop' => $sub->pickup_stop,
                'issue_date' => $sub->issue_date->format('Y-m-d'),
                'expiry_date' => $sub->expiry_date?->format('Y-m-d') ?? 'نهاية الموسم الدراسي',
                'status' => $sub->status,
            ],
            'route' => [
                'code' => $route?->route_code ?? 'BUS-01',
                'name' => $route?->route_name ?? 'الخط المدرسي',
                'plate' => $route?->bus_license_plate ?? '—',
                'driver' => $route?->driver_name ?? 'سائق الحافلة',
                'driver_phone' => $route?->driver_phone ?? '—',
                'morning_time' => $route?->morning_departure_time ?? '07:15',
                'evening_time' => $route?->evening_return_time ?? '16:45',
            ],
            'student' => [
                'full_name' => $student?->full_name ?? '—',
                'matricule' => $student?->matricule_number ?? $student?->registration_number ?? '—',
                'date_of_birth' => $student?->date_of_birth?->format('Y-m-d') ?? '—',
                'classroom' => $student?->group?->classroom?->name ?? '—',
            ],
            'academic_year' => $sub->academic_year,
            'generated_at' => now()->format('Y-m-d'),
        ];
    }
}
