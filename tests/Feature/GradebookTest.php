<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\AssessmentResource\Pages\CreateAssessment;
use App\Filament\Resources\AssessmentResource\Pages\EditAssessment;
use App\Filament\Resources\AssessmentResource\RelationManagers\GradesRelationManager;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_and_grade_relationships_casts_and_nullable_scores(): void
    {
        $assessment = Assessment::factory()->create([
            'type' => AssessmentType::Midterm,
            'max_score' => 20,
        ]);
        $student = Student::factory()->create();

        $grade = Grade::create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $this->assertSame(AssessmentType::Midterm, $assessment->type);
        $this->assertSame('20.00', $assessment->max_score);
        $this->assertTrue($assessment->schedule->assessments->contains($assessment));
        $this->assertTrue($assessment->grades->contains($grade));
        $this->assertTrue($grade->assessment->is($assessment));
        $this->assertTrue($grade->student->is($student));
        $this->assertTrue($student->grades->contains($grade));
        $this->assertNull($grade->score);
    }

    public function test_a_student_can_only_have_one_grade_per_assessment(): void
    {
        $grade = Grade::factory()->create();

        $this->expectException(QueryException::class);

        Grade::create([
            'assessment_id' => $grade->assessment_id,
            'student_id' => $grade->student_id,
        ]);
    }

    public function test_grade_roster_uses_the_schedule_group_and_preserves_entered_scores(): void
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

        $assessment = $this->createAssessment($group, $classroom);

        $this->assertSame(2, $assessment->ensureGradeRecords());
        $this->assertSame(2, $assessment->grades()->count());

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => null,
        ]);

        $this->assertDatabaseMissing('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $outsider->id,
        ]);

        $databaseGrade = $assessment->grades()
            ->where('student_id', $anotherStudent->id)
            ->firstOrFail();

        $databaseGrade->update([
            'score' => 14.5,
            'remarks' => 'تحسن ملحوظ',
        ]);

        $this->assertSame(0, $assessment->ensureGradeRecords());
        $this->assertSame(2, $assessment->grades()->count());

        $this->assertDatabaseHas('grades', [
            'id' => $databaseGrade->id,
            'score' => 14.5,
            'remarks' => 'تحسن ملحوظ',
        ]);
    }

    public function test_new_students_are_added_to_the_grade_roster_without_resetting_existing_grades(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $existingStudent = Student::factory()->create(['group_id' => $group->id]);

        $assessment = $this->createAssessment($group, $classroom);

        $assessment->ensureGradeRecords();

        $existingGrade = $assessment->grades()->firstOrFail();
        $existingGrade->update([
            'score' => 18,
            'remarks' => 'ممتاز',
        ]);

        $newStudent = Student::factory()->create(['group_id' => $group->id]);

        $this->assertSame(1, $assessment->ensureGradeRecords());
        $this->assertSame(2, $assessment->grades()->count());

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $existingStudent->id,
            'score' => 18,
            'remarks' => 'ممتاز',
        ]);

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $newStudent->id,
            'score' => null,
        ]);
    }

    public function test_grade_roster_falls_back_to_all_classroom_groups(): void
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

        $assessment = $this->createAssessment(null, $classroom);

        $this->assertSame(2, $assessment->ensureGradeRecords());
        $this->assertSame(2, $assessment->grades()->count());

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $firstStudent->id,
        ]);

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $secondStudent->id,
        ]);

        $this->assertDatabaseMissing('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $outsider->id,
        ]);
    }

    public function test_grades_relation_manager_materializes_the_roster_and_updates_score_inline(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);
        $outsider = Student::factory()->create();

        $assessment = $this->createAssessment($group, $classroom, $teacher);

        $component = Livewire::actingAs($teacher)->test(GradesRelationManager::class, [
            'ownerRecord' => $assessment,
            'pageClass' => EditAssessment::class,
        ]);

        $component->assertSuccessful();

        $grade = $assessment->grades()
            ->where('student_id', $student->id)
            ->firstOrFail();

        $outsiderGrade = Grade::create([
            'assessment_id' => $assessment->id,
            'student_id' => $outsider->id,
        ]);

        $this->assertSame(2, $assessment->grades()->count());

        $component
            ->assertCanSeeTableRecords([$grade])
            ->assertCanNotSeeTableRecords([$outsiderGrade]);

        $component->call(
            'updateTableColumnState',
            'score',
            (string) $grade->getKey(),
            '15.50',
        );

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'score' => 15.5,
        ]);

        $component->call(
            'updateTableColumnState',
            'score',
            (string) $grade->getKey(),
            '',
        );

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'score' => null,
        ]);
    }

    public function test_inline_score_is_validated_against_the_assessment_maximum(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $assessment = $this->createAssessment($group, $classroom, $teacher, [
            'max_score' => 20,
        ]);

        $component = Livewire::actingAs($teacher)->test(GradesRelationManager::class, [
            'ownerRecord' => $assessment,
            'pageClass' => EditAssessment::class,
        ])->assertSuccessful();

        $grade = $assessment->grades()
            ->where('student_id', $student->id)
            ->firstOrFail();

        $result = $component->instance()->updateTableColumnState(
            'score',
            (string) $grade->getKey(),
            '25',
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'score' => null,
        ]);
    }

    public function test_a_teacher_can_only_create_assessments_for_their_own_schedules(): void
    {
        $teacher = User::factory()->create();
        $ownSchedule = Schedule::factory()->create(['user_id' => $teacher->id]);
        $otherSchedule = Schedule::factory()->create();

        $this->actingAs($teacher);

        Livewire::test(CreateAssessment::class)
            ->fillForm([
                'schedule_id' => $otherSchedule->id,
                'title' => 'تقييم غير مصرح',
                'type' => AssessmentType::Quiz->value,
                'date' => '2026-10-21',
                'max_score' => 20,
            ])
            ->call('create')
            ->assertHasFormErrors(['schedule_id']);

        $this->assertDatabaseMissing('assessments', ['title' => 'تقييم غير مصرح']);

        Livewire::test(CreateAssessment::class)
            ->fillForm([
                'schedule_id' => $ownSchedule->id,
                'title' => 'تقييم تلميذي',
                'type' => AssessmentType::Exam->value,
                'date' => '2026-10-21',
                'max_score' => 20,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $assessment = Assessment::query()
            ->where('title', 'تقييم تلميذي')
            ->firstOrFail();

        $this->assertSame($ownSchedule->id, $assessment->schedule_id);
        $this->assertSame(AssessmentType::Exam, $assessment->type);
        $this->assertSame('2026-10-21', $assessment->date->toDateString());
        $this->assertSame('20.00', $assessment->max_score);
    }

    public function test_a_teacher_can_update_an_assessment_without_moving_it_to_another_schedule(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $assessment = $this->createAssessment($group, $classroom, $teacher, [
            'title' => 'الفرض الأول',
        ]);
        $originalScheduleId = $assessment->schedule_id;

        $otherSchedule = Schedule::factory()->create(['user_id' => $teacher->id]);

        Livewire::actingAs($teacher)
            ->test(EditAssessment::class, ['record' => $assessment->getRouteKey()])
            ->fillForm([
                'schedule_id' => $otherSchedule->id,
                'title' => 'الفرض الثاني',
                'type' => AssessmentType::Midterm->value,
                'date' => '2026-11-05',
                'max_score' => 40,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $assessment->refresh();

        $this->assertSame($originalScheduleId, $assessment->schedule_id);
        $this->assertSame('الفرض الثاني', $assessment->title);
        $this->assertSame(AssessmentType::Midterm, $assessment->type);
        $this->assertSame('2026-11-05', $assessment->date->toDateString());
        $this->assertSame('40.00', $assessment->max_score);
    }

    public function test_deleting_an_assessment_or_its_schedule_cascades_to_grades(): void
    {
        $grade = Grade::factory()->create();
        $assessment = $grade->assessment;

        $assessment->delete();

        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);

        $otherAssessment = Assessment::factory()->create();
        $otherGrade = Grade::factory()->create([
            'assessment_id' => $otherAssessment->id,
        ]);
        $schedule = $otherAssessment->schedule;

        $schedule->delete();

        $this->assertDatabaseMissing('assessments', ['id' => $otherAssessment->id]);
        $this->assertDatabaseMissing('grades', ['id' => $otherGrade->id]);
    }

    public function test_teachers_see_only_their_assessments_and_cannot_open_another_teachers_record(): void
    {
        $teacher = User::factory()->create();
        $ownAssessment = $this->createAssessment(null, null, $teacher, [
            'title' => 'تقييم تلميذي',
        ]);
        $otherAssessment = Assessment::factory()->create([
            'title' => 'تقييم أستاذ آخر',
        ]);

        $this->actingAs($teacher)
            ->get('/en/auth/assessments')
            ->assertOk()
            ->assertSee('تقييم تلميذي')
            ->assertDontSee('تقييم أستاذ آخر');

        $this->actingAs($teacher)
            ->get("/en/auth/assessments/{$otherAssessment->id}/edit")
            ->assertNotFound();

        $this->actingAs($teacher)
            ->get("/en/auth/assessments/{$ownAssessment->id}/edit")
            ->assertOk();
    }

    public function test_assessment_relation_manager_is_registered_on_the_resource(): void
    {
        $this->assertContains(
            GradesRelationManager::class,
            AssessmentResource::getRelations(),
        );
    }

    private function createAssessment(
        ?Group $group,
        ?Classroom $classroom,
        ?User $teacher = null,
        array $attributes = [],
    ): Assessment {
        $teacher ??= User::factory()->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom?->id ?? $group?->classroom_id,
            'group_id' => $group?->id,
            'subject_id' => Subject::factory(),
        ]);

        return Assessment::factory()->create([
            ...$attributes,
            'schedule_id' => $schedule->id,
        ]);
    }
}
