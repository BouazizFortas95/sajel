<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DeveloperDashboardStats;
use App\Models\User;
use App\Services\DatabaseSwitchService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseSwitchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    protected function tearDown(): void
    {
        // Always restore seeded mode after testing to keep environment consistent
        try {
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
            DB::purge('sqlite');
            DB::reconnect('sqlite');
            app(DatabaseSwitchService::class)->switch(DatabaseSwitchService::MODE_SEEDED);
        } catch (\Throwable) {
            // Ignore
        }

        parent::tearDown();
    }

    public function test_service_can_report_status_and_detect_mode(): void
    {
        $service = app(DatabaseSwitchService::class);
        $status = $service->getStatus();

        $this->assertArrayHasKey('mode', $status);
        $this->assertArrayHasKey('users_count', $status);
        $this->assertArrayHasKey('students_count', $status);
        $this->assertArrayHasKey('files', $status);
    }

    public function test_artisan_switch_db_command_switches_to_clean_and_seeded(): void
    {
        $service = app(DatabaseSwitchService::class);

        // 1. Switch to Clean
        $this->artisan('sajel:switch-db', ['mode' => 'clean'])
            ->assertSuccessful()
            ->expectsOutputToContain('CLEAN');

        $this->assertTrue($service->isClean());
        $this->assertEquals(1, DB::table('users')->count());
        $this->assertEquals(0, DB::table('students')->count());

        // Developer exists in clean DB
        $dev = DB::table('users')->where('email', 'dev@sajel.test')->first();
        $this->assertNotNull($dev);
        $this->assertEquals('developer', $dev->role);

        // Platform settings exist in clean DB
        $platform = DB::table('platform_settings')->first();
        $this->assertNotNull($platform);

        // 2. Switch back to Seeded
        $this->artisan('sajel:switch-db', ['mode' => 'seeded'])
            ->assertSuccessful()
            ->expectsOutputToContain('SEEDED');

        $this->assertTrue($service->isSeeded());
        $this->assertGreaterThan(1, DB::table('users')->count());
        $this->assertGreaterThan(0, DB::table('students')->count());
    }

    public function test_alias_db_switch_command_works(): void
    {
        $this->artisan('db:switch', ['--status' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('SAJEL DATABASE STATUS');
    }

    public function test_developer_dashboard_can_switch_database_via_livewire(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->call('switchDatabase', 'clean')
            ->assertHasNoErrors();

        $service = app(DatabaseSwitchService::class);
        $this->assertTrue($service->isClean());

        // Restore seeded
        Livewire::test(DeveloperDashboardStats::class)
            ->call('switchDatabase', 'seeded')
            ->assertHasNoErrors();

        $this->assertTrue($service->isSeeded());
    }
}
