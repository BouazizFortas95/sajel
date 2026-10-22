<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Filament\Resources\ScheduleResource\Pages\CreateSchedule;
use App\Filament\Resources\ScheduleResource\Pages\EditSchedule;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleOverlapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Subject, 2: Classroom}
     */
    private function makeTeacher(): array
    {
        $subject = Subject::factory()->create();

        $teacher = User::factory()->create([
            'primary_subject_id' => $subject->id,
        ]);

        return [$teacher, $subject, Classroom::factory()->create()];
    }

    private function scheduleFor(
        User $teacher,
        Subject $subject,
        Classroom $classroom,
        string $start,
        string $end,
        DayOfWeek $day = DayOfWeek::Sunday,
    ): Schedule {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => $day->value,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_overlapping_slots_for_the_same_teacher_are_rejected(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:30',
                'end_time' => '09:30',
            ])
            ->call('create')
            ->assertHasFormErrors(['start_time', 'end_time']);

        $this->assertSame(1, Schedule::query()->count());
    }

    public function test_identical_time_slots_are_rejected(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['start_time', 'end_time']);

        $this->assertSame(1, Schedule::query()->count());
    }

    public function test_back_to_back_slots_are_allowed(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');

        $this->actingAs($teacher);

        // Starts exactly when the previous slot ends.
        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '09:00',
                'end_time' => '10:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Ends exactly when the first slot starts.
        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '07:00',
                'end_time' => '08:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(3, Schedule::query()->count());
    }

    public function test_overlap_detection_is_scoped_to_the_teacher_and_the_day(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');

        // Same teacher, same time, another day: allowed.
        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Monday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Another teacher, same day and time: allowed.
        [$otherTeacher, $otherSubject, $otherClassroom] = $this->makeTeacher();

        $this->actingAs($otherTeacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $otherSubject->id,
                'classroom_id' => $otherClassroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_editing_a_schedule_ignores_itself_but_still_detects_other_overlaps(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $editable = $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');
        $later = $this->scheduleFor($teacher, $subject, $classroom, '10:00', '11:00');

        $this->actingAs($teacher);

        // Extending its own slot does not conflict with the old version.
        Livewire::test(EditSchedule::class, ['record' => $editable->getRouteKey()])
            ->fillForm(['end_time' => '09:30'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('09:30', $editable->fresh()?->end_time?->format('H:i'));

        // Growing into the later slot is still rejected.
        Livewire::test(EditSchedule::class, ['record' => $editable->getRouteKey()])
            ->fillForm(['end_time' => '10:30'])
            ->call('save')
            ->assertHasFormErrors(['start_time', 'end_time']);

        $this->assertSame('09:30', $editable->fresh()?->end_time?->format('H:i'));
        $this->assertSame('10:00', $later->fresh()?->start_time?->format('H:i'));
    }

    public function test_an_admin_cannot_double_book_a_teacher(): void
    {
        [$teacher, $subject, $classroom] = $this->makeTeacher();

        $this->scheduleFor($teacher, $subject, $classroom, '08:00', '09:00');

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'user_id' => $teacher->id,
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:15',
                'end_time' => '08:45',
            ])
            ->call('create')
            ->assertHasFormErrors(['start_time', 'end_time']);

        $this->assertSame(1, Schedule::query()->count());
    }
}
