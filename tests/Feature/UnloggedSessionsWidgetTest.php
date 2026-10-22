<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Filament\Widgets\UnloggedSessionsWidget;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnloggedSessionsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_only_teachers_can_view_the_widget(): void
    {
        $teacher = User::factory()->create();
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($teacher);
        $this->assertTrue(UnloggedSessionsWidget::canView());

        $this->actingAs($admin);
        $this->assertFalse(UnloggedSessionsWidget::canView());
    }

    public function test_it_lists_missing_and_planned_sessions_from_sunday_until_now(): void
    {
        // Thursday 22 October 2026 at 12:00 in Algeria.
        Carbon::setTestNow(Carbon::create(2026, 10, 22, 12, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $missingSunday = $this->scheduleFor($teacher, DayOfWeek::Sunday, '08:00', '09:00');
        $plannedMonday = $this->scheduleFor($teacher, DayOfWeek::Monday, '09:00', '10:00');
        $completedTuesday = $this->scheduleFor($teacher, DayOfWeek::Tuesday, '10:00', '11:00');
        $cancelledWednesday = $this->scheduleFor($teacher, DayOfWeek::Wednesday, '11:00', '12:00');
        $overdueToday = $this->scheduleFor($teacher, DayOfWeek::Thursday, '08:00', '09:00');
        $futureToday = $this->scheduleFor($teacher, DayOfWeek::Thursday, '14:00', '15:00');

        DailyLog::factory()->for($plannedMonday)->create([
            'date' => '2026-10-19',
            'status' => DailyLogStatus::Planned->value,
        ]);
        DailyLog::factory()->for($completedTuesday)->create([
            'date' => '2026-10-20',
            'status' => DailyLogStatus::Completed->value,
        ]);
        DailyLog::factory()->for($cancelledWednesday)->create([
            'date' => '2026-10-21',
            'status' => DailyLogStatus::Cancelled->value,
        ]);

        // Another teacher's missing session must never appear.
        $this->scheduleFor($otherTeacher, DayOfWeek::Sunday, '08:00', '09:00');

        $component = Livewire::actingAs($teacher)
            ->test(UnloggedSessionsWidget::class)
            ->assertOk()
            ->assertSee('تنبيه: حصص غير موثقة')
            ->assertSee('إكمال السجل')
            ->assertSee('لم يُنشأ السجل')
            ->assertSee('بانتظار الإنجاز');

        $pending = $component->instance()->getPendingSessions();

        $this->assertSame(3, $pending->count());
        $this->assertSame(
            ['2026-10-18', '2026-10-19', '2026-10-22'],
            $pending->pluck('date')->map(fn (Carbon $date): string => $date->toDateString())->all(),
        );
        $this->assertSame(['missing', 'planned', 'missing'], $pending->pluck('state')->all());
        $this->assertSame($missingSunday->id, $pending[0]['schedule']->id);
        $this->assertSame($plannedMonday->id, $pending[1]['schedule']->id);
        $this->assertSame($overdueToday->id, $pending[2]['schedule']->id);
        $this->assertStringContainsString('schedule_id='.$missingSunday->id, $pending[0]['url']);
        $this->assertStringContainsString('schedule_id='.$overdueToday->id, $pending[2]['url']);
        $this->assertStringContainsString('date=2026-10-22', $pending[2]['url']);

        $this->assertFalse($pending->contains(
            fn (array $task): bool => $task['schedule']->is($futureToday),
        ));
    }

    public function test_it_renders_a_success_state_when_nothing_is_pending(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 22, 12, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $this->scheduleFor($teacher, DayOfWeek::Thursday, '14:00', '15:00');

        Livewire::actingAs($teacher)
            ->test(UnloggedSessionsWidget::class)
            ->assertOk()
            ->assertSee('كل الحصص موثقة')
            ->assertDontSee('تنبيه: حصص غير موثقة');
    }

    private function scheduleFor(
        User $teacher,
        DayOfWeek $day,
        string $startTime,
        string $endTime,
    ): Schedule {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'day_of_week' => $day->value,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
