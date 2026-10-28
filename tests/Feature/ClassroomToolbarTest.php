<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Livewire\ClassroomToolbar;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassroomToolbarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_page_loads_the_current_lesson_roster_and_usage_tips(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        [$schedule, $students] = $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00');
        $otherTeacher = User::factory()->teacher()->create();
        [$otherSchedule] = $this->lesson($otherTeacher, DayOfWeek::Wednesday, '09:00', '11:00', 'Other teacher subject');

        app()->setLocale('en');

        $response = $this->actingAs($teacher)->get('/en/auth/classroom-toolbar');

        $response
            ->assertOk()
            ->assertSee('Lesson timer')
            ->assertSee('Pick a student')
            ->assertSee('How to use the lesson tools')
            ->assertSee($students->first()->full_name)
            ->assertSee('role="timer"', false)
            ->assertSee('x-on:click="start(5)"', false)
            ->assertDontSee('wire:poll.1s', false)
            ->assertDontSee($otherSchedule->subject->name);
    }

    public function test_the_lesson_in_progress_is_selected_by_default_and_switching_swaps_the_roster(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        [$current, $currentStudents] = $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00');
        [$later, $laterStudents] = $this->lesson($teacher, DayOfWeek::Wednesday, '14:00', '15:00', 'Afternoon subject');

        $component = Livewire::actingAs($teacher)->test(ClassroomToolbar::class);

        $component
            ->assertSet('selectedScheduleId', $current->id)
            ->assertSee($currentStudents->first()->full_name)
            ->assertDontSee($laterStudents->first()->full_name)
            ->set('selectedScheduleId', $later->id)
            ->assertSet('selectedScheduleId', $later->id)
            ->assertSee($laterStudents->first()->full_name)
            ->assertDontSee($currentStudents->first()->full_name)
            ->assertSet('picked', [])
            ->assertSet('groups', []);
    }

    public function test_a_lesson_is_available_outside_lesson_hours(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 12, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        [$past, $pastStudents] = $this->lesson($teacher, DayOfWeek::Wednesday, '08:00', '09:00', 'Morning subject');
        [$next, $nextStudents] = $this->lesson($teacher, DayOfWeek::Wednesday, '14:00', '15:00', 'Afternoon subject');
        [$tomorrow, $tomorrowStudents] = $this->lesson($teacher, DayOfWeek::Thursday, '09:00', '10:00', 'Tomorrow subject');

        $component = Livewire::actingAs($teacher)->test(ClassroomToolbar::class);

        $component
            ->assertSet('selectedScheduleId', $next->id)
            ->assertSee($nextStudents->first()->full_name)
            ->assertSee($past->subject->name)
            ->assertSee($tomorrow->subject->name)
            ->assertDontSee($pastStudents->first()->full_name)
            ->assertDontSee($tomorrowStudents->first()->full_name);
    }

    public function test_a_teacher_cannot_select_another_teachers_lesson(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00');

        $otherTeacher = User::factory()->teacher()->create();
        [$otherSchedule] = $this->lesson($otherTeacher, DayOfWeek::Wednesday, '09:00', '11:00', 'Private subject');

        Livewire::actingAs($teacher)
            ->test(ClassroomToolbar::class)
            ->set('selectedScheduleId', $otherSchedule->id)
            ->assertForbidden();
    }

    public function test_random_picker_does_not_repeat_until_every_student_has_been_picked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        [, $students] = $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00', students: 5);

        $ids = $students->pluck('id')->all();
        $component = Livewire::actingAs($teacher)->test(ClassroomToolbar::class);

        for ($round = 1; $round <= count($ids); $round++) {
            $component->call('pickRandom');

            $picked = $component->get('picked');

            $this->assertCount($round, $picked);
            $this->assertSame($picked, array_values(array_unique($picked)));
            $this->assertEmpty(array_diff($picked, $ids));
        }

        // The next draw starts a new round automatically.
        $component->call('pickRandom');

        $picked = $component->get('picked');

        $this->assertCount(1, $picked);
        $this->assertContains($picked[0], $ids);

        $component->call('resetPicked')->assertSet('picked', []);
    }

    public function test_groups_are_balanced_and_include_every_student_once(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();
        [, $students] = $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00', students: 7);

        $component = Livewire::actingAs($teacher)
            ->test(ClassroomToolbar::class)
            ->set('groupCount', 3)
            ->call('generateGroups');

        $groups = $component->get('groups');
        $sizes = array_map('count', $groups);
        $flattened = collect($groups)->flatten()->sort()->values()->all();
        $expected = $students->pluck('id')->sort()->values()->all();

        $this->assertCount(3, $groups);
        $this->assertSame($expected, $flattened);
        $this->assertLessThanOrEqual(1, max($sizes) - min($sizes));

        $component
            ->set('groupCount', 99)
            ->assertSet('groupCount', 6)
            ->call('generateGroups');

        $this->assertCount(6, $component->get('groups'));

        $smallTeacher = User::factory()->teacher()->create();
        $this->lesson($smallTeacher, DayOfWeek::Wednesday, '09:00', '10:00', 'Small lesson subject', 2);

        $smallLesson = Livewire::actingAs($smallTeacher)->test(ClassroomToolbar::class);

        $smallLesson
            ->set('groupCount', 6)
            ->call('generateGroups');

        $this->assertCount(2, $smallLesson->get('groups'));
    }

    public function test_admin_can_use_today_school_sessions(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        [$schedule, $students] = $this->lesson($teacher, DayOfWeek::Wednesday, '09:00', '11:00', 'Admin visible subject');

        Livewire::actingAs($admin)
            ->test(ClassroomToolbar::class)
            ->assertSet('selectedScheduleId', $schedule->id)
            ->assertSee($students->first()->full_name)
            ->assertSee($teacher->name)
            ->assertSee('Admin visible subject');
    }

    public function test_empty_state_and_french_localization(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, config('app.timezone')));

        $teacher = User::factory()->teacher()->create();

        app()->setLocale('fr');

        $this->actingAs($teacher)
            ->get('/fr/auth/classroom-toolbar')
            ->assertOk()
            ->assertSee('Aucune séance n’est encore affectée.')
            ->assertSee('Ouvrir mon emploi du temps hebdomadaire')
            ->assertSee('Guide rapide')
            ->assertSee('Comment utiliser les outils de la séance ?');
    }

    /**
     * @return array{0: Schedule, 1: Collection<int, Student>, 2: Classroom, 3: Group, 4: Subject}
     */
    private function lesson(
        User $teacher,
        DayOfWeek $day,
        string $start,
        string $end,
        string $subjectName = 'Mathematics',
        int $students = 4,
    ): array {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create(['name' => $subjectName]);

        $schedule = Schedule::factory()->for($teacher)->create([
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $day->value,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $roster = Student::factory()->count($students)->for($group)->create();

        return [$schedule, $roster, $classroom, $group, $subject];
    }
}
