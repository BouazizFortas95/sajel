<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Livewire\DataShowMode;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DataShowModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_selects_today_log(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'day_of_week' => DayOfWeek::cases()[today()->dayOfWeek],
        ]);
        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => today(),
            'title' => 'درس اليوم',
        ]);

        Livewire::actingAs($teacher)
            ->test(DataShowMode::class)
            ->assertSuccessful()
            ->call('pickRandomStudent')
            ->assertSuccessful()
            ->assertSet('content', fn ($c) => count($c) > 0);
    }

    public function test_teacher_scoped_log_access(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(DataShowMode::class)
            ->assertSuccessful();
    }

    public function test_filament_page_renders_data_show_as_a_separate_livewire_component(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $page = Livewire::actingAs($teacher)
            ->test(\App\Filament\Pages\DataShowMode::class)
            ->assertSeeLivewire(DataShowMode::class);

        $html = $page->html();
        $this->assertStringContainsString('class="contents"', $html);
        $this->assertSame(2, substr_count($html, 'wire:id='));
    }
}
