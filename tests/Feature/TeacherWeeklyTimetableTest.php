<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Livewire\TeacherInteractiveSchedule;
use App\Livewire\TeacherTimetable;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherWeeklyTimetableTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'name' => 'فرطاس بوعزيز',
            'role' => 'teacher',
        ]);

        $this->subject = Subject::factory()->create([
            'name' => 'الإعلام الآلي',
            'color' => '#0284c7',
        ]);

        $this->teacher->update(['primary_subject_id' => $this->subject->id]);

        // Create the required classrooms
        $classNames = [
            '1 م 1', '1 م 2', '1 م 3', '1 م 4', '1 م 5', '1 م 6', '1 م 7',
            '3 م 1', '3 م 2', '3 م 4',
            '4 م 1', '4 م 2', '4 م 3', '4 م 4',
        ];

        foreach ($classNames as $name) {
            Classroom::factory()->create(['name' => $name]);
        }
    }

    public function test_artisan_command_seeds_fifteen_weekly_sessions(): void
    {
        $this->artisan('sajel:seed-teacher-timetable', ['--clear' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('schedules', 15);

        // Check teacher's schedules count
        $schedules = Schedule::where('user_id', $this->teacher->id)->get();
        $this->assertCount(15, $schedules);

        // Check Sunday sessions (5 sessions)
        $sunday = Schedule::where('user_id', $this->teacher->id)
            ->where('day_of_week', DayOfWeek::Sunday->value)
            ->with('classroom')
            ->orderBy('start_time')
            ->get();
        $this->assertCount(5, $sunday);
        $this->assertSame('08:00', $sunday[0]->start_time->format('H:i'));
        $this->assertSame('4 م 4', $sunday[0]->classroom->name);
        $this->assertSame('10:00', $sunday[1]->start_time->format('H:i'));
        $this->assertSame('4 م 1', $sunday[1]->classroom->name);
        $this->assertSame('11:00', $sunday[2]->start_time->format('H:i'));
        $this->assertSame('1 م 1', $sunday[2]->classroom->name);
        $this->assertSame('14:00', $sunday[3]->start_time->format('H:i'));
        $this->assertSame('3 م 4', $sunday[3]->classroom->name);
        $this->assertSame('15:00', $sunday[4]->start_time->format('H:i'));
        $this->assertSame('1 م 4', $sunday[4]->classroom->name);

        // Check Monday sessions (3 sessions)
        $monday = Schedule::where('user_id', $this->teacher->id)
            ->where('day_of_week', DayOfWeek::Monday->value)
            ->with('classroom')
            ->orderBy('start_time')
            ->get();
        $this->assertCount(3, $monday);
        $this->assertSame('09:00', $monday[0]->start_time->format('H:i'));
        $this->assertSame('4 م 3', $monday[0]->classroom->name);
        $this->assertSame('10:00', $monday[1]->start_time->format('H:i'));
        $this->assertSame('1 م 7', $monday[1]->classroom->name);
        $this->assertSame('11:00', $monday[2]->start_time->format('H:i'));
        $this->assertSame('1 م 3', $monday[2]->classroom->name);

        // Check Tuesday sessions (3 sessions)
        $tuesday = Schedule::where('user_id', $this->teacher->id)
            ->where('day_of_week', DayOfWeek::Tuesday->value)
            ->with('classroom')
            ->orderBy('start_time')
            ->get();
        $this->assertCount(3, $tuesday);
        $this->assertSame('08:00', $tuesday[0]->start_time->format('H:i'));
        $this->assertSame('1 م 6', $tuesday[0]->classroom->name);
        $this->assertSame('09:00', $tuesday[1]->start_time->format('H:i'));
        $this->assertSame('3 م 2', $tuesday[1]->classroom->name);
        $this->assertSame('10:00', $tuesday[2]->start_time->format('H:i'));
        $this->assertSame('1 م 2', $tuesday[2]->classroom->name);

        // Check Wednesday sessions (2 sessions)
        $wednesday = Schedule::where('user_id', $this->teacher->id)
            ->where('day_of_week', DayOfWeek::Wednesday->value)
            ->with('classroom')
            ->orderBy('start_time')
            ->get();
        $this->assertCount(2, $wednesday);
        $this->assertSame('09:00', $wednesday[0]->start_time->format('H:i'));
        $this->assertSame('3 م 4', $wednesday[0]->classroom->name);
        $this->assertSame('11:00', $wednesday[1]->start_time->format('H:i'));
        $this->assertSame('1 م 5', $wednesday[1]->classroom->name);

        // Check Thursday sessions (2 sessions)
        $thursday = Schedule::where('user_id', $this->teacher->id)
            ->where('day_of_week', DayOfWeek::Thursday->value)
            ->with('classroom')
            ->orderBy('start_time')
            ->get();
        $this->assertCount(2, $thursday);
        $this->assertSame('08:00', $thursday[0]->start_time->format('H:i'));
        $this->assertSame('3 م 1', $thursday[0]->classroom->name);
        $this->assertSame('09:00', $thursday[1]->start_time->format('H:i'));
        $this->assertSame('4 م 2', $thursday[1]->classroom->name);
    }

    public function test_teacher_timetable_livewire_component_displays_all_sessions(): void
    {
        $this->artisan('sajel:seed-teacher-timetable', ['--clear' => true]);

        $this->actingAs($this->teacher);

        $test = Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('الإعلام الآلي')
            ->assertSee('4 م 4')
            ->assertSee('4 م 1')
            ->assertSee('1 م 1')
            ->assertSee('3 م 4')
            ->assertSee('1 م 4')
            ->assertSee('4 م 3')
            ->assertSee('1 م 7')
            ->assertSee('1 م 3')
            ->assertSee('1 م 6')
            ->assertSee('3 م 2')
            ->assertSee('1 م 2')
            ->assertSee('1 م 5')
            ->assertSee('3 م 1')
            ->assertSee('4 م 2');

        $component = $test->instance();
        $this->assertEquals(15.0, $component->totalHours);
    }

    public function test_teacher_interactive_schedule_component_renders_daily_sessions(): void
    {
        $this->artisan('sajel:seed-teacher-timetable', ['--clear' => true]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Sunday->value)
            ->assertOk()
            ->assertSee('4 م 4')
            ->assertSee('4 م 1')
            ->assertSee('1 م 1')
            ->assertSee('3 م 4')
            ->assertSee('1 م 4');
    }
}
