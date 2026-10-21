<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyLogResource\Pages\CreateDailyLog;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_create_page_prefills_the_schedule_from_the_query_string(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($teacher);

        Livewire::withQueryParams(['schedule_id' => $schedule->id])
            ->test(CreateDailyLog::class)
            ->assertOk()
            ->assertSet('data.schedule_id', $schedule->id)
            ->assertSet('data.date', today()->toDateString())
            ->assertSet('data.title', __('درس: ').$subject->name);
    }

    public function test_create_page_uses_the_requested_date(): void
    {
        $teacher = User::factory()->create();
        $schedule = Schedule::factory()->create(['user_id' => $teacher->id]);

        $this->actingAs($teacher);

        Livewire::withQueryParams([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-19',
        ])
            ->test(CreateDailyLog::class)
            ->assertOk()
            ->assertSet('data.schedule_id', $schedule->id)
            ->assertSet('data.date', '2026-10-19');
    }

    public function test_create_page_ignores_another_teachers_schedule(): void
    {
        $teacher = User::factory()->create();
        $otherSchedule = Schedule::factory()->create([
            'subject_id' => Subject::factory()->create(['name' => 'الفيزياء'])->id,
        ]);

        $this->actingAs($teacher);

        Livewire::withQueryParams(['schedule_id' => $otherSchedule->id])
            ->test(CreateDailyLog::class)
            ->assertOk()
            ->assertSet('data.schedule_id', null);
    }
}
