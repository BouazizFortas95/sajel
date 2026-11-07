<?php

namespace Tests\Feature;

use App\Enums\BoardingExitPassStatus;
use App\Enums\BoardingExitPassType;
use App\Enums\BoardingNightSessionType;
use App\Enums\DormitoryAllocationStatus;
use App\Enums\DormitoryWing;
use App\Enums\Role;
use App\Filament\Pages\SchoolBoardingHub;
use App\Models\Classroom;
use App\Models\DormitoryRoom;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolBoardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolBoardingTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $generalSupervisor;

    private User $teacher;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');

        $this->principal = User::factory()->create([
            'role' => Role::Principal,
        ]);

        $this->generalSupervisor = User::factory()->create([
            'role' => Role::GeneralSupervisor,
        ]);

        $this->teacher = User::factory()->create([
            'role' => Role::Teacher,
        ]);

        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $this->student = Student::factory()->create([
            'group_id' => $group->id,
            'matricule_number' => 'STU-BRD-001',
            'first_name' => 'ياسين',
            'last_name' => 'بوضياف',
            'guardian_name' => 'العربي بوضياف',
            'regime' => 'externe',
        ]);
    }

    // ─── 1. Schema & Enums ──────────────────────────────────────────────────

    public function test_boarding_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('dormitory_rooms'));
        $this->assertTrue(Schema::hasTable('student_dormitory_allocations'));
        $this->assertTrue(Schema::hasTable('boarding_night_sessions'));
        $this->assertTrue(Schema::hasTable('boarding_exit_passes'));
        $this->assertTrue(Schema::hasTable('school_bus_routes'));
        $this->assertTrue(Schema::hasTable('student_bus_subscriptions'));
    }

    public function test_boarding_enums(): void
    {
        $this->assertSame('جناح الذكور (Pavillon Garçons)', DormitoryWing::Boys->label());
        $this->assertSame('جناح الإناث (Pavillon Filles)', DormitoryWing::Girls->label());

        $this->assertSame('مقيم حالياً بالمرقد', DormitoryAllocationStatus::Active->label());
        $this->assertSame('أخلى المرقد وسلّم الفراش', DormitoryAllocationStatus::Vacated->label());

        $this->assertSame('مذاكرة مسائية محروسة (Étude du soir)', BoardingNightSessionType::EveningStudy->label());
        $this->assertSame('مداومة ليلية بالمراقد (Permanence de nuit)', BoardingNightSessionType::NightSupervision->label());

        $this->assertSame('خروج عطلة نهاية الأسبوع (Sortie de week-end)', BoardingExitPassType::WeekendLeave->label());
        $this->assertSame('مرخص ومقبول', BoardingExitPassStatus::Authorized->label());
        $this->assertSame('عاد والتحق بالمرقد', BoardingExitPassStatus::Returned->label());
    }

    public function test_can_manage_boarding_permissions(): void
    {
        $censor = User::factory()->create(['role' => Role::Censor]);
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($this->principal->canManageBoarding());
        $this->assertTrue($this->generalSupervisor->canManageBoarding());
        $this->assertTrue($censor->canManageBoarding());
        $this->assertTrue($bursar->canManageBoarding());

        $this->assertFalse($this->teacher->canManageBoarding());
        $this->assertFalse($counselor->canManageBoarding());
    }

    // ─── 2. Service Operations ──────────────────────────────────────────────

    public function test_service_generates_sequential_codes_and_pass_numbers(): void
    {
        $service = app(SchoolBoardingService::class);

        $code = $service->generateRoomCode(DormitoryWing::Boys, 1);
        $this->assertSame('DORT-B-101', $code);

        $passNumber = $service->generateExitPassNumber(2026);
        $this->assertSame('PASS/2026/001', $passNumber);

        $busCard = $service->generateBusCardNumber(2026);
        $this->assertSame('TRANS/2026/001', $busCard);

        $busCode = $service->generateBusRouteCode();
        $this->assertSame('BUS-L01', $busCode);
    }

    public function test_service_creates_room_and_allocates_bed_to_student(): void
    {
        $service = app(SchoolBoardingService::class);

        $room = $service->createRoom([
            'wing' => 'boys',
            'room_name' => 'مرقد أول نوفمبر',
            'floor_level' => 1,
            'bed_capacity' => 12,
        ]);

        $this->assertDatabaseHas('dormitory_rooms', [
            'id' => $room->id,
            'room_name' => 'مرقد أول نوفمبر',
            'active_occupants_count' => 0,
        ]);

        $allocation = $service->allocateBed([
            'dormitory_room_id' => $room->id,
            'student_id' => $this->student->id,
            'bed_number' => 'LIT-01',
            'locker_number' => 'CAS-01',
        ]);

        $room->refresh();
        $this->student->refresh();

        $this->assertSame(1, $room->active_occupants_count);
        $this->assertTrue($this->student->isBoarder());
        $this->assertDatabaseHas('student_dormitory_allocations', [
            'id' => $allocation->id,
            'dormitory_room_id' => $room->id,
            'student_id' => $this->student->id,
            'bed_number' => 'LIT-01',
            'status' => 'active',
        ]);
    }

    public function test_service_vacates_bed_and_decrements_occupant_count(): void
    {
        $service = app(SchoolBoardingService::class);

        $room = $service->createRoom([
            'wing' => 'boys',
            'room_name' => 'مرقد الشهيد عميروش',
            'bed_capacity' => 8,
        ]);

        $allocation = $service->allocateBed([
            'dormitory_room_id' => $room->id,
            'student_id' => $this->student->id,
        ]);

        $room->refresh();
        $this->assertSame(1, $room->active_occupants_count);

        $service->vacateBed($allocation);

        $room->refresh();
        $allocation->refresh();

        $this->assertSame(0, $room->active_occupants_count);
        $this->assertSame(DormitoryAllocationStatus::Vacated, $allocation->status);
        $this->assertNotNull($allocation->check_out_date);
    }

    public function test_service_records_night_session(): void
    {
        $service = app(SchoolBoardingService::class);

        $session = $service->recordNightSession([
            'session_type' => BoardingNightSessionType::NightSupervision,
            'supervisor_name' => 'أحمد مساعد تربية',
            'boarders_present_count' => 45,
            'boarders_absent_count' => 2,
            'sickbay_count' => 1,
            'authorized_leave_count' => 5,
            'incident_report' => 'هدوء تام طيلة فترة المداومة الليلية',
        ], $this->generalSupervisor->id);

        $this->assertDatabaseHas('boarding_night_sessions', [
            'id' => $session->id,
            'supervisor_name' => 'أحمد مساعد تربية',
            'boarders_present_count' => 45,
            'boarders_absent_count' => 2,
        ]);
    }

    public function test_service_issues_and_returns_exit_pass(): void
    {
        $service = app(SchoolBoardingService::class);

        $pass = $service->issueExitPass([
            'student_id' => $this->student->id,
            'pass_type' => BoardingExitPassType::WeekendLeave,
            'escort_guardian_name' => 'العربي بوضياف',
            'destination' => 'بلدية الأربعاء',
        ], $this->generalSupervisor->id);

        $this->assertSame(BoardingExitPassStatus::Authorized, $pass->status);
        $this->assertDatabaseHas('boarding_exit_passes', [
            'id' => $pass->id,
            'pass_number' => 'PASS/2026/001',
            'destination' => 'بلدية الأربعاء',
        ]);

        $service->markPassReturned($pass);
        $pass->refresh();

        $this->assertSame(BoardingExitPassStatus::Returned, $pass->status);
        $this->assertNotNull($pass->actual_return_datetime);
    }

    public function test_service_creates_bus_route_and_subscribes_student(): void
    {
        $service = app(SchoolBoardingService::class);

        $route = $service->createBusRoute([
            'route_name' => 'خط قرية النصر — الثانوية',
            'bus_license_plate' => '05555-126-16',
            'driver_name' => 'السعيد بلقاسم',
            'passenger_capacity' => 35,
        ]);

        $this->assertDatabaseHas('school_bus_routes', [
            'id' => $route->id,
            'route_code' => 'BUS-L01',
            'subscribed_students_count' => 0,
        ]);

        $subscription = $service->subscribeStudentToBus([
            'bus_route_id' => $route->id,
            'student_id' => $this->student->id,
            'pickup_stop' => 'محطة ساحة الشهداء',
        ]);

        $route->refresh();

        $this->assertSame(1, $route->subscribed_students_count);
        $this->assertDatabaseHas('student_bus_subscriptions', [
            'id' => $subscription->id,
            'card_number' => 'TRANS/2026/001',
            'pickup_stop' => 'محطة ساحة الشهداء',
        ]);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolBoardingService::class);

        $room = $service->createRoom(['wing' => 'boys', 'room_name' => 'مرقد 1']);
        $service->allocateBed(['dormitory_room_id' => $room->id, 'student_id' => $this->student->id]);
        $rosterData = $service->assembleDormitoryRosterData($room);
        $this->assertSame('مرقد 1', $rosterData['room']['name']);
        $this->assertCount(1, $rosterData['allocations']);

        $pass = $service->issueExitPass([
            'student_id' => $this->student->id,
            'escort_guardian_name' => 'الولي',
            'destination' => 'العاصمة',
        ], $this->generalSupervisor->id);
        $passData = $service->assembleExitPassData($pass);
        $this->assertSame('PASS/2026/001', $passData['pass']['number']);

        $route = $service->createBusRoute(['route_name' => 'خط 1', 'driver_name' => 'السائق']);
        $sub = $service->subscribeStudentToBus(['bus_route_id' => $route->id, 'student_id' => $this->student->id]);
        $busData = $service->assembleBusCardData($sub);
        $this->assertSame('TRANS/2026/001', $busData['subscription']['card_number']);
    }

    // ─── 3. Web & PDF Printable Routes ──────────────────────────────────────

    public function test_print_room_roster_routes(): void
    {
        $service = app(SchoolBoardingService::class);
        $room = $service->createRoom(['wing' => 'boys', 'room_name' => 'مرقد الشرف']);

        // General Supervisor can access
        $res = $this->actingAs($this->generalSupervisor)->get("/ar/print/boarding/room/{$room->id}/roster");
        $res->assertOk();
        $res->assertSee('مخطط توزيع نزلاء المرقد وجرد الأسرّة والخزانات الفردية');

        // Teacher is forbidden (403)
        $this->actingAs($this->teacher)->get("/ar/print/boarding/room/{$room->id}/roster")->assertForbidden();

        // PDF route
        $pdfRes = $this->actingAs($this->principal)->get("/ar/print/boarding/room/{$room->id}/roster/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    public function test_print_exit_pass_routes(): void
    {
        $service = app(SchoolBoardingService::class);
        $pass = $service->issueExitPass([
            'student_id' => $this->student->id,
            'escort_guardian_name' => 'الولي',
            'destination' => 'العاصمة',
        ], $this->generalSupervisor->id);

        $res = $this->actingAs($this->generalSupervisor)->get("/ar/print/boarding/pass/{$pass->id}/billet");
        $res->assertOk();
        $res->assertSee('بطاقة ورخصة خروج تلميذ(ة) داخلي');

        $pdfRes = $this->actingAs($this->generalSupervisor)->get("/ar/print/boarding/pass/{$pass->id}/billet/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    public function test_print_bus_card_routes(): void
    {
        $service = app(SchoolBoardingService::class);
        $route = $service->createBusRoute(['route_name' => 'خط 1', 'driver_name' => 'السائق']);
        $sub = $service->subscribeStudentToBus(['bus_route_id' => $route->id, 'student_id' => $this->student->id]);

        $res = $this->actingAs($this->generalSupervisor)->get("/ar/print/boarding/transport/{$sub->id}/card");
        $res->assertOk();
        $res->assertSee('بطاقة ركوب واشتراك النقل المدرسي');

        $pdfRes = $this->actingAs($this->generalSupervisor)->get("/ar/print/boarding/transport/{$sub->id}/card/pdf");
        $this->assertContains($pdfRes->status(), [200, 302]);
    }

    // ─── 4. Filament Livewire Cockpit ───────────────────────────────────────

    public function test_boarding_hub_renders_for_authorized_users(): void
    {
        Livewire::actingAs($this->generalSupervisor)
            ->test(SchoolBoardingHub::class)
            ->assertOk()
            ->assertSee('المراقد وتوزيع الأسرّة والخزانات');
    }

    public function test_boarding_hub_forbids_unauthorized_users(): void
    {
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->actingAs($this->teacher);
        $this->assertFalse(SchoolBoardingHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-boarding-hub')->assertForbidden();

        $this->actingAs($counselor);
        $this->assertFalse(SchoolBoardingHub::shouldRegisterNavigation());
        $this->get('/ar/auth/school-boarding-hub')->assertForbidden();
    }

    public function test_boarding_hub_can_create_room_and_allocate_bed(): void
    {
        Livewire::actingAs($this->generalSupervisor)
            ->test(SchoolBoardingHub::class)
            ->set('newRoomWing', 'boys')
            ->set('newRoomName', 'مرقد النجوم')
            ->set('newRoomFloor', 2)
            ->set('newRoomCapacity', 15)
            ->call('createRoom')
            ->assertHasNoErrors();

        $room = DormitoryRoom::where('room_name', 'مرقد النجوم')->first();
        $this->assertNotNull($room);

        Livewire::actingAs($this->generalSupervisor)
            ->test(SchoolBoardingHub::class)
            ->set('allocationRoomId', $room->id)
            ->set('allocationStudentId', $this->student->id)
            ->set('allocationBedNumber', 'LIT-10')
            ->set('allocationLockerNumber', 'CAS-10')
            ->call('allocateBed')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_dormitory_allocations', [
            'dormitory_room_id' => $room->id,
            'student_id' => $this->student->id,
            'bed_number' => 'LIT-10',
            'status' => 'active',
        ]);
    }

    public function test_health_check_verifies_all_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
