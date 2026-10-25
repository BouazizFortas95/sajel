<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSectionNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_name_prefers_the_direct_classroom(): void
    {
        $directClassroom = Classroom::factory()->create(['name' => '1 م 1']);
        $groupClassroom = Classroom::factory()->create(['name' => '2 م 2']);
        $group = Group::factory()->create([
            'classroom_id' => $groupClassroom->id,
            'name' => 'المجموعة أ',
        ]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => $directClassroom->id,
            'group_id' => $group->id,
        ]);

        $this->assertSame('1 م 1', $schedule->sectionName());
        $this->assertSame('1 م 1 — المجموعة أ', $schedule->placeLabel());
    }

    public function test_group_scoped_schedule_resolves_the_classroom_from_its_group(): void
    {
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $group->id,
        ]);

        $this->assertSame('1 م 1', $schedule->sectionName());
        $this->assertSame('1 م 1 — المجموعة أ', $schedule->placeLabel());
    }

    public function test_label_uses_the_classroom_section_instead_of_the_group_name(): void
    {
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $group->id,
        ]);

        $label = $schedule->label();

        $this->assertStringContainsString('1 م 1', $label);
        $this->assertStringContainsString('المجموعة أ', $label);
    }
}
