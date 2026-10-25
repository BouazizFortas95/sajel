<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Livewire\QuickAttendance;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_access_quick_attendance_page(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful()
            ->assertSee(__('تسجيل الحضور السريع'));
    }

    public function test_admin_cannot_access_quick_attendance_page(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin)
            ->get('/ar/auth/quick-attendance')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_quick_attendance_page(): void
    {
        $this->get('/ar/auth/quick-attendance')
            ->assertRedirect();
    }

    public function test_quick_attendance_shows_teacher_schedules(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $todayDayOfWeek = DayOfWeek::cases()[today()->dayOfWeek];

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful()
            ->assertSee($subject->name)
            ->assertSee($classroom->name)
            ->assertSee($group->name);
    }

    public function test_group_scoped_schedule_shows_the_classroom_section_name(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $todayDayOfWeek = DayOfWeek::cases()[today()->dayOfWeek];

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => null,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $component = Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful()
            ->assertSee('1 م 1')
            ->assertSee('المجموعة أ');

        $component->set('selectedScheduleId', $schedule->id);
        $component->call('loadRoster');

        $component->assertSet('rosterSnapshot.classroom_label', '1 م 1');
    }

    public function test_teacher_can_mark_attendance_for_student(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $todayDayOfWeek = DayOfWeek::cases()[today()->dayOfWeek];

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'registration_number' => '001',
        ]);

        $component = Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful()
            ->assertSee($student->full_name);

        $component->selectedScheduleId = $schedule->id;
        $component->call('loadRoster');
        $component->call('markAttendance', $student->id, AttendanceStatus::Absent->value);

        $component->assertSet('attendanceStatuses', [
            $student->id => AttendanceStatus::Absent->value,
        ]);
    }

    public function test_teacher_can_save_attendance_for_students(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $todayDayOfWeek = DayOfWeek::cases()[today()->dayOfWeek];

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'سارة',
            'last_name' => 'أحمد',
            'registration_number' => '002',
        ]);

        $today = today()->toDateString();

        $component = Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful();

        $component->selectedScheduleId = $schedule->id;
        $component->selectedDate = $today;
        $component->call('loadRoster');
        $component->call('markAttendance', $student->id, AttendanceStatus::Absent->value);
        $component->call('saveAttendance');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => AttendanceStatus::Absent->value,
        ]);

        $this->assertDatabaseHas('daily_logs', [
            'schedule_id' => $schedule->id,
            'date' => $today.' 00:00:00',
            'status' => DailyLogStatus::Completed->value,
        ]);
    }

    public function test_quick_attendance_requires_schedule_and_date(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $component = Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful();

        $component->call('saveAttendance');

        $component->assertHasErrors(['selectedScheduleId']);
    }

    public function test_teacher_cannot_save_attendance_for_another_teachers_schedule(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $otherTeacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $todayDayOfWeek = DayOfWeek::cases()[today()->dayOfWeek];

        $schedule = Schedule::factory()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'يوسف',
            'last_name' => 'علي',
            'registration_number' => '003',
        ]);

        $today = today()->toDateString();

        $component = Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSuccessful();

        $component->selectedScheduleId = $schedule->id;
        $component->selectedDate = $today;
        $component->call('saveAttendance');

        $component->assertHasErrors(['selectedScheduleId']);
    }
}
