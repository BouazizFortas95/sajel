<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Livewire\TeacherTimetable;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
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

        $this->assertSame($sunday->id, $grid[DayOfWeek::Sunday->value]['08:00']['schedule']->id);
        $this->assertSame(1, $grid[DayOfWeek::Sunday->value]['08:00']['span']);
        $this->assertNull($grid[DayOfWeek::Sunday->value]['09:00']);
        $this->assertSame($monday->id, $grid[DayOfWeek::Monday->value]['14:00']['schedule']->id);
        $this->assertNull($grid[DayOfWeek::Monday->value]['08:00']);
    }

    public function test_a_multi_hour_schedule_spans_rows_and_marks_the_next_slots_covered(): void
    {
        $teacher = $this->makeTeacher();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Sunday, $subject, '08:00', '10:00');

        $this->actingAs($teacher);

        $component = Livewire::test(TeacherTimetable::class);

        $grid = $component->instance()->grid;

        $this->assertSame(2, $grid[DayOfWeek::Sunday->value]['08:00']['span']);
        $this->assertSame(['covered' => true], $grid[DayOfWeek::Sunday->value]['09:00']);
        $this->assertNull($grid[DayOfWeek::Sunday->value]['10:00']);

        $html = $component->html();

        // One card with an explicit two-row span and a taller minimum height.
        $this->assertSame(1, substr_count($html, "openLog({$schedule->id}"));
        $this->assertStringContainsString('row-span-2', $html);
        $this->assertStringContainsString('min-h-[11rem]', $html);
        $this->assertStringContainsString('/ span 2', $html);

        // 5 days × 7 slots = 35 cells: one card + one covered cell + 33 placeholders.
        $this->assertSame(33, substr_count($html, __('فراغ')));
    }

    public function test_back_to_back_multi_hour_schedules_each_span_their_own_rows(): void
    {
        $teacher = $this->makeTeacher();
        $subject = Subject::factory()->create();

        $first = $this->makeSchedule($teacher, DayOfWeek::Monday, $subject, '08:00', '10:00');
        $second = $this->makeSchedule($teacher, DayOfWeek::Monday, $subject, '10:00', '12:00');

        $this->actingAs($teacher);

        $component = Livewire::test(TeacherTimetable::class);
        $grid = $component->instance()->grid;

        $this->assertSame($first->id, $grid[DayOfWeek::Monday->value]['08:00']['schedule']->id);
        $this->assertSame(2, $grid[DayOfWeek::Monday->value]['08:00']['span']);
        $this->assertSame(['covered' => true], $grid[DayOfWeek::Monday->value]['09:00']);
        $this->assertSame($second->id, $grid[DayOfWeek::Monday->value]['10:00']['schedule']->id);
        $this->assertSame(2, $grid[DayOfWeek::Monday->value]['10:00']['span']);
        $this->assertSame(['covered' => true], $grid[DayOfWeek::Monday->value]['11:00']);

        $html = $component->html();

        $this->assertSame(2, substr_count($html, 'row-span-2'));
        $this->assertSame(31, substr_count($html, __('فراغ')));
    }

    public function test_a_three_hour_schedule_spans_three_rows(): void
    {
        $teacher = $this->makeTeacher();
        $this->makeSchedule($teacher, DayOfWeek::Sunday, Subject::factory()->create(), '08:00', '11:00');

        $this->actingAs($teacher);

        $component = Livewire::test(TeacherTimetable::class);
        $grid = $component->instance()->grid;

        $this->assertSame(3, $grid[DayOfWeek::Sunday->value]['08:00']['span']);
        $this->assertSame(['covered' => true], $grid[DayOfWeek::Sunday->value]['09:00']);
        $this->assertSame(['covered' => true], $grid[DayOfWeek::Sunday->value]['10:00']);
        $this->assertNull($grid[DayOfWeek::Sunday->value]['11:00']);

        $html = $component->html();

        $this->assertStringContainsString('row-span-3', $html);
        $this->assertStringContainsString('min-h-[16.75rem]', $html);
        $this->assertSame(32, substr_count($html, __('فراغ')));
    }

    public function test_a_schedule_never_spans_across_the_lunch_break(): void
    {
        $teacher = $this->makeTeacher();
        $this->makeSchedule($teacher, DayOfWeek::Sunday, Subject::factory()->create(), '11:00', '14:00');

        $this->actingAs($teacher);

        $grid = Livewire::test(TeacherTimetable::class)->instance()->grid;

        // 11:00 is the last morning row; 14:00 is not contiguous with it, so
        // the card stays on one row instead of painting over the break row.
        $this->assertSame(1, $grid[DayOfWeek::Sunday->value]['11:00']['span']);
        $this->assertNull($grid[DayOfWeek::Sunday->value]['14:00']);
        $this->assertArrayNotHasKey('covered', $grid[DayOfWeek::Sunday->value]['14:00'] ?? []);
    }

    public function test_it_calculates_the_weekly_workload_and_flags_an_overload(): void
    {
        $teacher = User::factory()->create(['weekly_hours_limit' => 2]);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);

        $this->makeSchedule($teacher, DayOfWeek::Sunday, $subject, '08:00', '10:00');
        $this->makeSchedule($teacher, DayOfWeek::Monday, $subject, '09:00', '10:30');

        $this->app->setLocale('ar');
        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertViewHas('weeklyHoursLimit', 2)
            ->assertViewHas('totalHours', fn (float $hours): bool => abs($hours - 3.5) < 0.001)
            ->assertSee('النصاب الزمني: 3.5 / 2 ساعة')
            ->assertSeeHtml('text-danger-700')
            ->assertDontSeeHtml('text-success-700');
    }

    public function test_it_does_not_flag_the_workload_when_it_matches_the_limit(): void
    {
        $teacher = User::factory()->create(['weekly_hours_limit' => 2]);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);

        $this->makeSchedule($teacher, DayOfWeek::Sunday, $subject, '08:00', '10:00');

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertViewHas('totalHours', 2.0)
            ->assertSeeHtml('text-success-700')
            ->assertDontSeeHtml('text-danger-700');
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

    public function test_it_resolves_the_section_name_for_group_scoped_schedules(): void
    {
        $teacher = $this->makeTeacher();
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => null,
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create(['name' => 'الإعلام الآلي'])->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('1 م 1')
            ->assertSee('المجموعة أ');
    }

    public function test_it_passes_the_current_week_date_to_each_schedule_action(): void
    {
        $teacher = $this->makeTeacher();
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Sunday);

        $this->actingAs($teacher);

        $date = DayOfWeek::Sunday->dateInCurrentWeek()->toDateString();

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSeeHtml("openLog({$schedule->id}, '{$date}')");
    }

    public function test_it_opens_the_existing_log_for_a_schedule(): void
    {
        $teacher = $this->makeTeacher();
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Monday);
        $date = DayOfWeek::Monday->dateInCurrentWeek();

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => $date,
        ]);

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->call('openLog', $schedule->id, $date->toDateString())
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

        $date = DayOfWeek::Monday->dateInCurrentWeek();

        Livewire::test(TeacherTimetable::class)
            ->call('openLog', $schedule->id, $date->toDateString())
            ->assertRedirect(DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
                'locale' => 'en',
            ]));
    }

    public function test_it_falls_back_to_the_current_week_when_a_stale_date_is_sent(): void
    {
        $teacher = $this->makeTeacher();
        $schedule = $this->makeSchedule($teacher, DayOfWeek::Monday);

        $this->actingAs($teacher);

        $date = DayOfWeek::Monday->dateInCurrentWeek();

        Livewire::test(TeacherTimetable::class)
            ->call('openLog', $schedule->id, '1999-01-01')
            ->assertRedirect(DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
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

    public function test_it_colours_each_schedule_card_using_its_subject_colour(): void
    {
        $teacher = $this->makeTeacher();

        // One subject per Filament colour, each occupying its own Sunday cell.
        $colors = ['primary', 'danger', 'success', 'warning', 'info', 'gray'];

        $slots = [
            ['08:00', '09:00'],
            ['09:00', '10:00'],
            ['10:00', '11:00'],
            ['11:00', '12:00'],
            ['14:00', '15:00'],
            ['15:00', '16:00'],
        ];

        foreach ($colors as $index => $color) {
            $this->makeSchedule(
                $teacher,
                DayOfWeek::Sunday,
                Subject::factory()->create(['name' => "مادة {$color}", 'color' => $color]),
                $slots[$index][0],
                $slots[$index][1],
            );
        }

        $this->actingAs($teacher);

        $html = Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->html();

        foreach ($colors as $color) {
            $this->assertSame(1, substr_count($html, "border-s-{$color}-500"), "card border for {$color}");
            $this->assertSame(1, substr_count($html, "hover:border-{$color}-400"), "hover border for {$color}");
            $this->assertSame(1, substr_count($html, "hover:bg-{$color}-50"), "hover background for {$color}");
            $this->assertSame(1, substr_count($html, "hover:ring-{$color}-400/60"), "hover ring for {$color}");
            $this->assertSame(1, substr_count($html, "focus-visible:ring-{$color}-500"), "focus ring for {$color}");
            $this->assertSame(1, substr_count($html, "dark:border-s-{$color}-400"), "dark card border for {$color}");
            $this->assertSame(1, substr_count($html, "group-hover:text-{$color}-800"), "title hover colour for {$color}");
            $this->assertSame(1, substr_count($html, "group-hover:text-{$color}-600"), "link hover colour for {$color}");
            $this->assertSame(1, substr_count($html, "text-{$color}-600 dark:text-{$color}-400"), "pin colour for {$color}");
        }
    }

    public function test_it_falls_back_to_the_primary_colour_for_missing_or_unknown_subject_colours(): void
    {
        $teacher = $this->makeTeacher();

        $this->makeSchedule($teacher, DayOfWeek::Sunday, Subject::factory()->create(['color' => null]), '08:00', '09:00');
        $this->makeSchedule($teacher, DayOfWeek::Sunday, Subject::factory()->create(['color' => '#4f46e5']), '09:00', '10:00');
        $this->makeSchedule($teacher, DayOfWeek::Sunday, Subject::factory()->create(['color' => 'magenta']), '10:00', '11:00');

        $this->actingAs($teacher);

        $html = Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->html();

        $this->assertSame(3, substr_count($html, 'border-s-primary-500'));
        $this->assertSame(3, substr_count($html, 'group-hover:text-primary-800'));
        $this->assertStringNotContainsString('border-s-magenta-500', $html);
        $this->assertStringNotContainsString('#4f46e5', $html);
    }
}
