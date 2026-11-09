<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\SchoolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddleSchoolClassroomsStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'academic_year' => '2026-2027',
                'directorate_name' => 'مديرية التربية لولاية المغير',
                'municipality_name' => 'المغير',
                'principal_name' => 'أحمد بوزقاق',
                'school_code' => '00-0000-0001',
            ]
        );

        $phase = EducationPhase::firstOrCreate(
            ['code' => 'CEM'],
            [
                'name' => 'التعليم المتوسط',
                'phase_abbreviation' => 'م',
            ]
        );

        Level::firstOrCreate(['id' => 6], ['education_phase_id' => $phase->id, 'name' => 'السنة الأولى متوسط', 'grade_number' => 1]);
        Level::firstOrCreate(['id' => 7], ['education_phase_id' => $phase->id, 'name' => 'السنة الثانية متوسط', 'grade_number' => 2]);
        Level::firstOrCreate(['id' => 8], ['education_phase_id' => $phase->id, 'name' => 'السنة الثالثة متوسط', 'grade_number' => 3]);
        Level::firstOrCreate(['id' => 9], ['education_phase_id' => $phase->id, 'name' => 'السنة الرابعة متوسط', 'grade_number' => 4]);
    }

    public function test_artisan_command_creates_exactly_twenty_classrooms_with_official_structure(): void
    {
        $this->artisan('sajel:seed-classrooms')
            ->assertSuccessful();

        $this->assertSame(20, Classroom::count());

        // 1st Year Middle School (7 classrooms)
        $level1 = Level::where('grade_number', 1)->first();
        $classrooms1 = Classroom::where('level_id', $level1->id)->orderBy('class_number')->get();
        $this->assertCount(7, $classrooms1);
        $this->assertSame(['1 م 1', '1 م 2', '1 م 3', '1 م 4', '1 م 5', '1 م 6', '1 م 7'], $classrooms1->pluck('name')->all());

        // 2nd Year Middle School (5 classrooms)
        $level2 = Level::where('grade_number', 2)->first();
        $classrooms2 = Classroom::where('level_id', $level2->id)->orderBy('class_number')->get();
        $this->assertCount(5, $classrooms2);
        $this->assertSame(['2 م 1', '2 م 2', '2 م 3', '2 م 4', '2 م 5'], $classrooms2->pluck('name')->all());

        // 3rd Year Middle School (4 classrooms)
        $level3 = Level::where('grade_number', 3)->first();
        $classrooms3 = Classroom::where('level_id', $level3->id)->orderBy('class_number')->get();
        $this->assertCount(4, $classrooms3);
        $this->assertSame(['3 م 1', '3 م 2', '3 م 3', '3 م 4'], $classrooms3->pluck('name')->all());

        // 4th Year Middle School (4 classrooms)
        $level4 = Level::where('grade_number', 4)->first();
        $classrooms4 = Classroom::where('level_id', $level4->id)->orderBy('class_number')->get();
        $this->assertCount(4, $classrooms4);
        $this->assertSame(['4 م 1', '4 م 2', '4 م 3', '4 م 4'], $classrooms4->pluck('name')->all());

        // Check subgroups: every classroom must have 2 groups (الفوج أ and الفوج ب)
        foreach (Classroom::with('groups')->get() as $classroom) {
            $this->assertCount(2, $classroom->groups);
            $this->assertContains('الفوج أ', $classroom->groups->pluck('name'));
            $this->assertContains('الفوج ب', $classroom->groups->pluck('name'));
        }
    }
}
