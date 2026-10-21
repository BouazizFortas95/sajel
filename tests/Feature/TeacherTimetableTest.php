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

    private function makeSchedule(User $teacher, DayOfWeek $day, ?Subject $subject = null): Schedule
    {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => ($subject ?? Subject::factory()->create())->id,
            'day_of_week' => $day->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
    }

    public function test_it_renders_all_seven_days_for_a_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $this->makeSchedule($teacher, DayOfWeek::Monday, $subject);

        $this->actingAs($teacher);

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee('الرياضيات')
            ->assertSee(__('الأحد'))
            ->assertSee(__('السبت'));
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

    public function test_it_shows_the_empty_state_without_schedules(): void
    {
        $this->actingAs($this->makeTeacher());

        Livewire::test(TeacherTimetable::class)
            ->assertOk()
            ->assertSee(__('لا توجد حصص مجدولة'));
    }
}
