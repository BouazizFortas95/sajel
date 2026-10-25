<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_only_schedule_targets_every_group_in_the_classroom(): void
    {
        $classroom = Classroom::factory()->create();
        $firstGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ - '.uniqid(),
        ]);
        $secondGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب - '.uniqid(),
        ]);
        $otherGroup = Group::factory()->create();

        $first = Student::factory()->create(['group_id' => $firstGroup->id]);
        $second = Student::factory()->create(['group_id' => $secondGroup->id]);
        $outsider = Student::factory()->create(['group_id' => $otherGroup->id]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => null,
        ]);

        $rosterIds = Student::query()->forSchedule($schedule)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $rosterIds);
        $this->assertNotContains($outsider->id, $rosterIds);
    }

    public function test_group_scoped_schedule_targets_only_its_own_group(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ - '.uniqid(),
        ]);
        $classmateGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب - '.uniqid(),
        ]);

        $member = Student::factory()->create(['group_id' => $group->id]);
        $classmate = Student::factory()->create(['group_id' => $classmateGroup->id]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
        ]);

        $rosterIds = Student::query()->forSchedule($schedule)->pluck('id')->all();

        $this->assertSame([$member->id], $rosterIds);
        $this->assertNotContains($classmate->id, $rosterIds);
    }

    public function test_group_only_schedule_resolves_its_roster_from_the_group(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ - '.uniqid(),
        ]);
        $otherGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب - '.uniqid(),
        ]);

        $member = Student::factory()->create(['group_id' => $group->id]);
        $outsider = Student::factory()->create(['group_id' => $otherGroup->id]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $group->id,
        ]);

        $rosterIds = Student::query()->forSchedule($schedule)->pluck('id')->all();

        $this->assertSame([$member->id], $rosterIds);
        $this->assertNotContains($outsider->id, $rosterIds);
    }

    public function test_schedule_without_a_classroom_or_group_targets_no_students(): void
    {
        Student::factory()->count(3)->create();

        $schedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => null,
        ]);

        $this->assertCount(0, Student::query()->forSchedule($schedule)->get());
    }
}
