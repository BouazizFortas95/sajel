<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\DailyLogResource\Pages\EditDailyLog;
use App\Filament\Resources\DailyLogResource\RelationManagers\AttendancesRelationManager;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_defaults_to_present_and_has_both_relationships(): void
    {
        $student = Student::factory()->create();
        $dailyLog = DailyLog::factory()->create();

        $attendance = Attendance::create([
            'daily_log_id' => $dailyLog->id,
            'student_id' => $student->id,
        ]);

        $this->assertSame(AttendanceStatus::Present, $attendance->status);
        $this->assertTrue($attendance->dailyLog->is($dailyLog));
        $this->assertTrue($attendance->student->is($student));
        $this->assertTrue($dailyLog->attendances->contains($attendance));
        $this->assertTrue($student->attendances->contains($attendance));
    }

    public function test_a_student_can_only_have_one_attendance_per_daily_log(): void
    {
        $attendance = Attendance::factory()->create();

        $this->expectException(QueryException::class);

        Attendance::create([
            'daily_log_id' => $attendance->daily_log_id,
            'student_id' => $attendance->student_id,
        ]);
    }

    public function test_attendance_roster_uses_the_schedule_group_and_keeps_existing_state(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $otherGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب',
        ]);

        $student = Student::factory()->create(['group_id' => $group->id]);
        $anotherStudent = Student::factory()->create(['group_id' => $group->id]);
        $outsider = Student::factory()->create(['group_id' => $otherGroup->id]);

        $dailyLog = $this->createDailyLog($group, $classroom);

        $this->assertSame(2, $dailyLog->ensureAttendanceRecords());
        $this->assertSame(2, $dailyLog->attendances()->count());

        $this->assertDatabaseHas('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present->value,
        ]);

        $this->assertDatabaseMissing('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $outsider->id,
        ]);

        $databaseAttendance = $dailyLog->attendances()
            ->where('student_id', $anotherStudent->id)
            ->firstOrFail();

        $databaseAttendance->update([
            'status' => AttendanceStatus::Absent,
            'remarks' => 'موعد طبي',
        ]);

        $this->assertSame(0, $dailyLog->ensureAttendanceRecords());
        $this->assertSame(2, $dailyLog->attendances()->count());

        $this->assertDatabaseHas('attendances', [
            'id' => $databaseAttendance->id,
            'status' => AttendanceStatus::Absent->value,
            'remarks' => 'موعد طبي',
        ]);
    }

    public function test_attendance_roster_new_students_are_added_without_resetting_existing_rows(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $existingStudent = Student::factory()->create(['group_id' => $group->id]);

        $dailyLog = $this->createDailyLog($group, $classroom);

        $dailyLog->ensureAttendanceRecords();

        $existingAttendance = $dailyLog->attendances()->firstOrFail();
        $existingAttendance->update(['status' => AttendanceStatus::Late]);

        $newStudent = Student::factory()->create(['group_id' => $group->id]);

        $this->assertSame(1, $dailyLog->ensureAttendanceRecords());
        $this->assertSame(2, $dailyLog->attendances()->count());

        $this->assertDatabaseHas('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $existingStudent->id,
            'status' => AttendanceStatus::Late->value,
        ]);

        $this->assertDatabaseHas('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $newStudent->id,
            'status' => AttendanceStatus::Present->value,
        ]);
    }

    public function test_attendance_roster_falls_back_to_all_classroom_groups(): void
    {
        $classroom = Classroom::factory()->create();
        $firstGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $secondGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب',
        ]);
        $otherClassroom = Classroom::factory()->create();
        $otherGroup = Group::factory()->create(['classroom_id' => $otherClassroom->id]);

        $firstStudent = Student::factory()->create(['group_id' => $firstGroup->id]);
        $secondStudent = Student::factory()->create(['group_id' => $secondGroup->id]);
        $outsider = Student::factory()->create(['group_id' => $otherGroup->id]);

        $dailyLog = $this->createDailyLog(null, $classroom);

        $this->assertSame(2, $dailyLog->ensureAttendanceRecords());
        $this->assertSame(2, $dailyLog->attendances()->count());

        $this->assertDatabaseHas('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $firstStudent->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $secondStudent->id,
        ]);

        $this->assertDatabaseMissing('attendances', [
            'daily_log_id' => $dailyLog->id,
            'student_id' => $outsider->id,
        ]);
    }

    public function test_relation_manager_materializes_the_roster_and_updates_status_inline(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $dailyLog = $this->createDailyLog($group, $classroom, $teacher);

        $component = Livewire::actingAs($teacher)->test(AttendancesRelationManager::class, [
            'ownerRecord' => $dailyLog,
            'pageClass' => EditDailyLog::class,
        ]);

        $component->assertSuccessful();

        $attendance = Attendance::query()
            ->where('daily_log_id', $dailyLog->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertSame(AttendanceStatus::Present, $attendance->status);

        $component->assertCanSeeTableRecords([$attendance]);

        $component->call(
            'updateTableColumnState',
            'status',
            (string) $attendance->getKey(),
            AttendanceStatus::Absent->value,
        );

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => AttendanceStatus::Absent->value,
        ]);
    }

    public function test_attendance_relation_manager_is_registered_on_the_daily_log_resource(): void
    {
        $this->assertContains(
            AttendancesRelationManager::class,
            DailyLogResource::getRelations(),
        );
    }

    private function createDailyLog(
        ?Group $group,
        ?Classroom $classroom,
        ?User $teacher = null,
    ): DailyLog {
        $teacher ??= User::factory()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom?->id ?? $group?->classroom_id,
            'group_id' => $group?->id,
            'subject_id' => Subject::factory(),
        ]);

        return DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
        ]);
    }
}
