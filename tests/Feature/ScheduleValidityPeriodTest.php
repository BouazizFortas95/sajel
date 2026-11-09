<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Resources\ScheduleResource\Pages\CreateSchedule;
use App\Livewire\TeacherInteractiveSchedule;
use App\Livewire\TeacherTimetable;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleValidityPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $subject;

    private Classroom $classroomA;

    private Classroom $classroomB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'name' => 'فرطاس بوعزيز',
            'role' => 'teacher',
        ]);

        $this->subject = Subject::factory()->create([
            'name' => 'الإعلام الآلي',
        ]);

        $this->teacher->update(['primary_subject_id' => $this->subject->id]);

        $this->classroomA = Classroom::factory()->create(['name' => '4 م 4']);
        $this->classroomB = Classroom::factory()->create(['name' => '1 م 1']);
    }

    public function test_schedules_can_have_valid_from_and_valid_until_dates(): void
    {
        $schedule = Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-10-31',
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'valid_from' => '2026-09-01 00:00:00',
            'valid_until' => '2026-10-31 00:00:00',
        ]);

        $this->assertTrue($schedule->isActiveOn('2026-09-15'));
        $this->assertTrue($schedule->isActiveOn('2026-10-31'));
        $this->assertFalse($schedule->isActiveOn('2026-11-01'));
        $this->assertFalse($schedule->isActiveOn('2026-08-30'));
    }

    public function test_active_on_scope_filters_by_specific_date(): void
    {
        // Old schedule: Sept 1 to Oct 31
        $old = Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-10-31',
        ]);

        // New schedule: Nov 1 onwards
        $new = Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomB->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-11-01',
            'valid_until' => null,
        ]);

        $octoberSchedules = Schedule::query()->activeOn('2026-10-15')->pluck('id');
        $this->assertTrue($octoberSchedules->contains($old->id));
        $this->assertFalse($octoberSchedules->contains($new->id));

        $novemberSchedules = Schedule::query()->activeOn('2026-11-15')->pluck('id');
        $this->assertFalse($novemberSchedules->contains($old->id));
        $this->assertTrue($novemberSchedules->contains($new->id));
    }

    public function test_overlapping_time_slots_with_different_validity_periods_are_allowed(): void
    {
        // Old schedule ends Oct 31
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-10-31',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Attempt to create a new schedule on Sunday at 08:00-09:00 starting Nov 01 (allowed!)
        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'user_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroomB->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'valid_from' => '2026-11-01',
                'valid_until' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Schedule::count());
    }

    public function test_overlapping_time_slots_with_conflicting_validity_periods_are_rejected(): void
    {
        // Active schedule valid from Sept 01 with no end date
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => null,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Attempting to create another schedule for the same slot with overlapping date range must fail
        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'user_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroomB->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'valid_from' => '2026-10-01',
                'valid_until' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['start_time', 'end_time']);

        $this->assertSame(1, Schedule::count());
    }

    public function test_teacher_interactive_schedule_only_shows_currently_active_session(): void
    {
        // Expired schedule
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-10-01',
        ]);

        // Active schedule
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomB->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-10-02',
            'valid_until' => null,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Sunday->value)
            ->assertOk()
            ->assertSee('1 م 1')
            ->assertDontSee('4 م 4');
    }

    public function test_teacher_timetable_week_view_respects_validity_dates(): void
    {
        // Schedule expired in the past
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomA->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2025-09-01',
            'valid_until' => '2025-10-01',
        ]);

        // Schedule currently active for current week
        Schedule::create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroomB->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-01',
            'valid_until' => null,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('1 م 1')
            ->assertDontSee('4 م 4');
    }
}
