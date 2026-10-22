<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_the_classroom_name_before_saving(): void
    {
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'م']);
        $level = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'grade_number' => 1,
        ]);

        $classroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'class_number' => 1,
            'name' => 'اسم قديم',
        ]);

        $this->assertSame('1 م 1', $classroom->name);
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'name' => '1 م 1',
        ]);
    }

    public function test_it_regenerates_the_name_when_the_class_number_changes(): void
    {
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'م']);
        $level = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'grade_number' => 2,
        ]);

        $classroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'class_number' => 1,
        ]);

        $classroom->update(['class_number' => 4]);

        $this->assertSame('2 م 4', $classroom->fresh()->name);
    }

    public function test_it_keeps_the_manual_name_when_the_convention_is_incomplete(): void
    {
        // Missing phase abbreviation.
        $phaseWithoutAbbreviation = EducationPhase::factory()->create(['phase_abbreviation' => null]);
        $levelWithoutAbbreviation = Level::factory()->create([
            'education_phase_id' => $phaseWithoutAbbreviation->id,
            'name' => 'السنة الأولى إعدادية',
            'grade_number' => 1,
        ]);

        $classroom = Classroom::factory()->create([
            'level_id' => $levelWithoutAbbreviation->id,
            'class_number' => 1,
            'name' => 'قاعة الأمل',
        ]);

        $this->assertSame('قاعة الأمل', $classroom->name);

        // Missing class number.
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'ث']);
        $level = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'name' => 'السنة الثالثة ثانوية',
            'grade_number' => 3,
        ]);

        $classroomWithoutNumber = Classroom::factory()->create([
            'level_id' => $level->id,
            'name' => 'قاعة الغد',
        ]);

        $this->assertSame('قاعة الغد', $classroomWithoutNumber->name);

        // Missing grade number.
        $levelWithoutGrade = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'name' => 'السنة الرابعة ثانوية',
            'grade_number' => null,
        ]);

        $classroomWithoutGrade = Classroom::factory()->create([
            'level_id' => $levelWithoutGrade->id,
            'class_number' => 2,
            'name' => 'قاعة الشروق',
        ]);

        $this->assertSame('قاعة الشروق', $classroomWithoutGrade->name);
    }
}
