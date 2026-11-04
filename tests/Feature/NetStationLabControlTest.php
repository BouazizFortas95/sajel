<?php

namespace Tests\Feature;

use App\Enums\LabStatus;
use App\Livewire\LabSeatingPlan;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabStation;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\NetStationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NetStationLabControlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function netstation_service_locks_and_unlocks_all_stations_in_lab(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $lab = Lab::factory()->for($level)->create(['capacity' => 4]);

        for ($i = 1; $i <= 4; $i++) {
            LabStation::factory()->for($lab)->create([
                'station_number' => $i,
                'status' => LabStatus::Working,
                'is_locked' => false,
            ]);
        }

        $service = app(NetStationService::class);

        // Lock all
        $lockedCount = $service->lockAll($lab->id, 'توقفوا عن العمل وانتبهوا للشرح');
        $this->assertSame(4, $lockedCount);

        $this->assertSame(4, LabStation::query()->where('lab_id', $lab->id)->where('is_locked', true)->count());
        $this->assertDatabaseHas('lab_stations', [
            'lab_id' => $lab->id,
            'station_number' => 1,
            'is_locked' => true,
            'lock_message' => 'توقفوا عن العمل وانتبهوا للشرح',
        ]);

        // Unlock all
        $unlockedCount = $service->unlockAll($lab->id);
        $this->assertSame(4, $unlockedCount);
        $this->assertSame(0, LabStation::query()->where('lab_id', $lab->id)->where('is_locked', true)->count());
    }

    #[Test]
    public function netstation_service_broadcasts_message_and_clears(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $lab = Lab::factory()->for($level)->create();

        $station = LabStation::factory()->for($lab)->create([
            'station_number' => 1,
            'status' => LabStatus::Working,
        ]);

        $service = app(NetStationService::class);

        $service->broadcastMessage($lab->id, 'تمرين تطبيقي رقم 3 في مادة الإعلام الآلي');

        $this->assertSame('تمرين تطبيقي رقم 3 في مادة الإعلام الآلي', $station->fresh()->active_broadcast);

        $service->clearBroadcast($lab->id);
        $this->assertNull($station->fresh()->active_broadcast);
    }

    #[Test]
    public function netstation_service_toggles_internet_access(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $lab = Lab::factory()->for($level)->create();

        $station = LabStation::factory()->for($lab)->create([
            'station_number' => 1,
            'status' => LabStatus::Working,
            'internet_enabled' => true,
        ]);

        $service = app(NetStationService::class);

        $service->toggleInternet($lab->id, false);
        $this->assertFalse($station->fresh()->internet_enabled);

        $service->toggleInternet($lab->id, true);
        $this->assertTrue($station->fresh()->internet_enabled);
    }

    #[Test]
    public function student_station_client_route_renders_and_responds_to_pings(): void
    {
        SchoolSetting::create([
            'school_name' => 'متوسطة الشهيد زبانة',
            'academic_year' => '2026-2027',
        ]);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->for($group)->create(['first_name' => 'أسامة', 'last_name' => 'بن حمزة']);

        $lab = Lab::factory()->for($level)->create(['name' => 'مخبر الخوارزميات والبرمجة']);
        $station = LabStation::factory()->for($lab)->create([
            'station_number' => 5,
            'status' => LabStatus::Working,
            'is_locked' => false,
        ]);

        // Visit student kiosk screen
        $response = $this->get(route('lab.station-client', ['locale' => 'ar', 'labStation' => $station]));
        $response->assertOk()
            ->assertSee('مخبر الخوارزميات والبرمجة')
            ->assertSee('متوسطة الشهيد زبانة')
            ->assertSee('المحطة')
            ->assertSee('5');

        // Ping endpoint
        $pingResponse = $this->getJson(route('lab.station-ping', ['locale' => 'ar', 'labStation' => $station]));
        $pingResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.station_number', 5)
            ->assertJsonPath('data.is_locked', false);

        $this->assertNotNull($station->fresh()->last_ping_at);
        $this->assertTrue($station->fresh()->isOnline());
    }

    #[Test]
    public function student_station_client_shows_locked_overlay_when_station_is_locked(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $lab = Lab::factory()->for($level)->create(['name' => 'مخبر الإعلام الآلي 1']);
        $station = LabStation::factory()->for($lab)->create([
            'station_number' => 2,
            'device_type' => 'computer',
            'status' => LabStatus::Working,
            'is_locked' => true,
            'lock_message' => 'انتبه للأستاذ! سيتم بدء العرض التقديمي الآن.',
        ]);

        $this->get(route('lab.station-client', ['locale' => 'ar', 'labStation' => $station]))
            ->assertOk()
            ->assertSee('الشاشة مقفلة بطلب من الأستاذ')
            ->assertSee('انتبه للأستاذ! سيتم بدء العرض التقديمي الآن.');
    }

    #[Test]
    public function leader_pc_livewire_commands_trigger_locks_broadcasts_and_toggles(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();
        $group = Group::factory()->for($classroom)->create();

        $lab = Lab::factory()->for($level)->create(['name' => 'مخبر الإعلام الآلي 1']);
        for ($i = 1; $i <= 3; $i++) {
            LabStation::factory()->for($lab)->create([
                'station_number' => $i,
                'device_type' => 'computer',
                'status' => LabStatus::Working,
            ]);
        }

        $component = Livewire::test(LabSeatingPlan::class)
            ->set('labId', $lab->id)
            ->set('groupId', $group->id)
            ->assertSet('viewMode', 'control')
            ->call('lockAllStations', 'قفل شامل للمخبر')
            ->assertSet('lockedCount', 3);

        $this->assertSame(3, LabStation::query()->where('lab_id', $lab->id)->where('is_locked', true)->count());

        $component->call('unlockAllStations')
            ->assertSet('lockedCount', 0);

        $this->assertSame(0, LabStation::query()->where('lab_id', $lab->id)->where('is_locked', true)->count());

        // Single station toggle
        $firstStation = LabStation::query()->where('lab_id', $lab->id)->first();
        $component->call('toggleStationLock', $firstStation->id);
        $this->assertTrue($firstStation->fresh()->is_locked);

        // Broadcast message
        $component->set('broadcastMessageText', 'افتحوا تطبيق سكراتش وابدأوا المشروع')
            ->call('sendBroadcastMessage');

        $this->assertSame('افتحوا تطبيق سكراتش وابدأوا المشروع', $firstStation->fresh()->active_broadcast);

        $component->call('clearBroadcastMessage');
        $this->assertNull($firstStation->fresh()->active_broadcast);
    }
}
