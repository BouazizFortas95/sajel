<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyLogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_logs_list_page_renders_for_teacher(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['user_id' => $user->id]);
        DailyLog::factory()->create(['schedule_id' => $schedule->id]);

        $response = $this->actingAs($user)->get('/en/auth/daily-logs');

        $response->assertOk();
        $response->assertSee('New Daily log');
    }

    public function test_edit_daily_log_page_renders_for_teacher(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['user_id' => $user->id]);
        $log = DailyLog::factory()->create(['schedule_id' => $schedule->id]);

        $response = $this->actingAs($user)->get("/en/auth/daily-logs/{$log->id}/edit");

        $response->assertOk();
        $response->assertSee('Confirm completion');
    }
}
