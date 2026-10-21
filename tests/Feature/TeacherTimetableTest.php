<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Livewire\TeacherTimetable;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherTimetableTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): User
    {
        return User::factory()->create();
    }

    private function makeSchedule(
        User $teacher,
        DayOfWeek $day,
        ?Subject $subject = null,
        string $start = '08:00',
        string $end = '09:00',
    ): Schedule {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => ($subject ?? Subject::factory()->create())->id,
            'day_of_week' => $day->value,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_it_renders_the_five_school_days_and_standard_time_slots(): void
    {
        $teacher = $this->makeTeacher();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $this->makeSchedule($teacher, DayOfWeek::Sunday, $subject);

        $this->actingAs($teacher);

        $html = Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('الرياضيات')
            ->assertSee(__('الفترة الصباحية'))
            ->assertSee(__('الفترة المسائية'))
            ->assertSee(__('استراحة الغداء'))
            ->assertDontSee(__('الجمعة'))
            ->assertDontSee(__('السبت'))
            ->html();

        foreach ([
            DayOfWeek::Sunday,
            DayOfWeek::Monday,
            DayOfWeek::Tuesday,
            DayOfWeek::Wednesday,
            DayOfWeek::Thursday,
        ] as $day) {
            $this->assertStringContainsString($day->label(), $html);
        }

        foreach (['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00'] as $time) {
            $this->assertStringContainsString($time, $html);
        }

        // 5 days × 7 slots = 35 cells; one is occupied by the schedule above.
        $this->assertSame(34, substr_count($html, __('فراغ')));
    }

    public function test_it_maps_schedules_to_the_correct_grid_cells(): void
    {
        $teacher = $this->makeTeacher();
        $subject = Subject::factory()->create();

        $sunday = $this->makeSchedule($teacher, DayOfWeek::Sunday, $subject, '08:00', '09:00');
        $monday = $this->makeSchedule($teacher, DayOfWeek::Monday, $subject, '14:00', '15:00');

        // Friday is outside the school week and must not appear in the grid.
        $this->makeSchedule($teacher, DayOfWeek::Friday, $subject, '09:00', '10:00');

        $this->actingAs($teacher);

        $grid = Livewire::test(TeacherTimetable::class)
            ->instance()
            ->grid;

        $this->assertCount(5, $grid);
        $this->assertArrayNotHasKey(DayOfWeek::Friday->value, $grid);
        $this->assertSame(
            ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00'],
            array_keys($grid[DayOfWeek::Sunday->value]),
        );

        $this->assertSame($sunday->id, $grid[DayOfWeek::Sunday->value]['08:00']?->id);
        $this->assertNull($grid[DayOfWeek::Sunday->value]['09:00']);
        $this->assertSame($monday->id, $grid[DayOfWeek::Monday->value]['14:00']?->id);
        $this->assertNull($grid[DayOfWeek::Monday->value]['08:00']);
    }

    public function test_it_only_shows_the_current_teachers_schedules(): void
    {
        $teacher = $this->makeTeacher();
        $other = $this->makeTeacher();

        $this->makeSchedule($teacher, DayOfWeek::Monday, Subject::factory()->create(['name' => 'الرياضيات']));
        $this->makeSchedule($other, DayOfWeek::Monday, Subject::factory()->create(['name' => 'الفيزياء']));

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('الرياضيات')
            ->assertDontSee('الفيزياء');
    }

    public function test_it_opens_the_existing_log_for_a_schedule(): void
    {
        $teacher = $this->makeTeacher();
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Monday);

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => DayOfWeek::Monday->dateInCurrentWeek(),
        ]);

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->call('openLogForSchedule', $schedule->id)
            ->assertRedirect(DailyLogResource::getUrl('edit', [
                'record' => $log,
                'locale' => 'en',
            ]));
    }

    public function test_it_opens_the_prefilled_create_page_when_no_log_exists(): void
    {
        $teacher = $this->makeTeacher();
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Monday);

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->call('openLogForSchedule', $schedule->id)
            ->assertRedirect(DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => DayOfWeek::Monday->dateInCurrentWeek()->toDateString(),
                'locale' => 'en',
            ]));
    }

    public function test_it_renders_empty_placeholders_without_schedules(): void
    {
        $this->actingAs($this->makeTeacher());

        $html = Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->html();

        // 5 school days × 7 one-hour slots.
        $this->assertSame(35, substr_count($html, __('فراغ')));
    }
}
