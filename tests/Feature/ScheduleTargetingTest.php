<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Resources\ScheduleResource\Pages\CreateSchedule;
use App\Filament\Resources\ScheduleResource\Pages\EditSchedule;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleTargetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_schedule_requires_a_classroom(): void
    {
        [$teacher, $subject] = $this->makeTeacher();

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['classroom_id']);

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_a_group_must_belong_to_the_selected_classroom(): void
    {
        [$teacher, $subject] = $this->makeTeacher();
        $classroom = Classroom::factory()->create();
        $otherGroup = Group::factory()->create();

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->fillForm(['group_id' => $otherGroup->id])
            ->call('create')
            ->assertHasFormErrors(['group_id']);

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_editing_a_group_only_schedule_can_keep_its_group_when_the_classroom_is_confirmed(): void
    {
        [$teacher, $subject] = $this->makeTeacher();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => null,
            'group_id' => $group->id,
            'day_of_week' => DayOfWeek::Sunday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($teacher);

        Livewire::test(EditSchedule::class, ['record' => $schedule->getRouteKey()])
            ->fillForm(['classroom_id' => $classroom->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $schedule->refresh();

        $this->assertSame($classroom->id, $schedule->classroom_id);
        $this->assertSame($group->id, $schedule->group_id);
    }

    /**
     * @return array{0: User, 1: Subject}
     */
    private function makeTeacher(): array
    {
        $subject = Subject::factory()->create();

        $teacher = User::factory()->create([
            'primary_subject_id' => $subject->id,
        ]);

        return [$teacher, $subject];
    }
}
