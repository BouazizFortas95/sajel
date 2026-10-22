<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\AssessmentResource\Pages\EditAssessment;
use App\Filament\Resources\AssessmentResource\RelationManagers\GradesRelationManager;
use App\Filament\Resources\ClassroomResource;
use App\Filament\Resources\DailyLogResource\Pages\EditDailyLog;
use App\Filament\Resources\DailyLogResource\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\EducationPhaseResource;
use App\Filament\Resources\GroupResource;
use App\Filament\Resources\LevelResource;
use App\Filament\Resources\SubjectResource;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_the_teacher_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Teacher, $user->fresh()->role);
        $this->assertTrue($user->fresh()->isTeacher());
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertTrue($user->fresh()->canAccessPanel(config('filament.panel') ?? new Panel));
    }

    public function test_teacher_is_denied_the_user_resource(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)->get('/en/auth/users')->assertForbidden();
    }

    public function test_admin_can_access_the_user_resource(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin)->get('/en/auth/users')->assertOk();
    }

    public function test_teacher_cannot_open_another_teachers_daily_log(): void
    {
        $teacher = User::factory()->create();
        $otherLog = DailyLog::factory()->create(); // belongs to another teacher

        // The teacher-scoped query hides the record entirely (404, not 403),
        // so its existence is never revealed.
        $this->actingAs($teacher)
            ->get("/en/auth/daily-logs/{$otherLog->id}/edit")
            ->assertNotFound();
    }

    public function test_admin_can_open_any_daily_log(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $log = DailyLog::factory()->create(); // another teacher's log

        $this->actingAs($admin)
            ->get("/en/auth/daily-logs/{$log->id}/edit")
            ->assertOk();
    }

    public function test_teacher_schedule_list_shows_only_own_records(): void
    {
        $teacher = User::factory()->create();

        $ownClassroom = Classroom::factory()->create(['name' => 'قاعة الأمل']);
        $otherClassroom = Classroom::factory()->create(['name' => 'قاعة الغد']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $ownClassroom->id,
            'group_id' => null,
        ]);
        Schedule::factory()->create([
            'classroom_id' => $otherClassroom->id,
            'group_id' => null,
        ]);

        $response = $this->actingAs($teacher)->get('/en/auth/schedules');

        $response->assertOk();
        $response->assertSee('قاعة الأمل');
        $response->assertDontSee('قاعة الغد');
    }

    public function test_admin_schedule_list_shows_all_records(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $classroomA = Classroom::factory()->create(['name' => 'قاعة الشروق']);
        $classroomB = Classroom::factory()->create(['name' => 'قاعة النجاح']);

        Schedule::factory()->create(['classroom_id' => $classroomA->id, 'group_id' => null]);
        Schedule::factory()->create(['classroom_id' => $classroomB->id, 'group_id' => null]);

        $response = $this->actingAs($admin)->get('/en/auth/schedules');

        $response->assertOk();
        $response->assertSee('قاعة الشروق');
        $response->assertSee('قاعة النجاح');
    }

    public function test_policies_scope_teachers_and_bypass_for_admins(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $ownSchedule = Schedule::factory()->create(['user_id' => $teacher->id]);
        $otherSchedule = Schedule::factory()->create();

        // Schedules: ownership for teachers, full access for admins.
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownSchedule));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherSchedule));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherSchedule));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $otherSchedule));

        // Users: management is admin-only.
        $this->assertFalse(Gate::forUser($teacher)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherTeacher));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherTeacher));
    }

    public function test_student_policy_scopes_teachers_to_their_scheduled_group_or_classroom(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $otherGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب',
        ]);
        $otherClassroom = Classroom::factory()->create();
        $otherGroupInOtherClassroom = Group::factory()->create(['classroom_id' => $otherClassroom->id]);

        $student = Student::factory()->create(['group_id' => $group->id]);
        $classmate = Student::factory()->create(['group_id' => $otherGroup->id]);
        $outsider = Student::factory()->create(['group_id' => $otherGroupInOtherClassroom->id]);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
        ]);

        $this->assertTrue(Gate::forUser($teacher)->allows('view', $student));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $classmate));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $outsider));

        $this->assertFalse(Gate::forUser($teacher)->allows('viewAny', Student::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', Student::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $student));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $student));

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Student::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $outsider));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Student::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $outsider));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $outsider));
    }

    public function test_student_policy_classroom_schedule_covers_all_groups_in_that_classroom(): void
    {
        $teacher = User::factory()->create();

        $classroom = Classroom::factory()->create();
        $firstGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $secondGroup = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة ب',
        ]);

        $firstStudent = Student::factory()->create(['group_id' => $firstGroup->id]);
        $secondStudent = Student::factory()->create(['group_id' => $secondGroup->id]);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => null,
        ]);

        $this->assertTrue(Gate::forUser($teacher)->allows('view', $firstStudent));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $secondStudent));
    }

    public function test_assessment_policy_limits_teachers_to_their_own_schedules(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        $ownAssessment = Assessment::factory()->create([
            'schedule_id' => Schedule::factory()->create(['user_id' => $teacher->id]),
        ]);
        $otherAssessment = Assessment::factory()->create();

        $this->assertTrue(Gate::forUser($teacher)->allows('viewAny', Assessment::class));
        $this->assertTrue(Gate::forUser($teacher)->allows('create', Assessment::class));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $ownAssessment));
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownAssessment));
        $this->assertTrue(Gate::forUser($teacher)->allows('delete', $ownAssessment));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $otherAssessment));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherAssessment));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $otherAssessment));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $otherAssessment));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherAssessment));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $otherAssessment));
    }

    public function test_grade_policy_uses_the_owning_assessment(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        $ownGrade = Grade::factory()->create([
            'assessment_id' => Assessment::factory()->create([
                'schedule_id' => Schedule::factory()->create(['user_id' => $teacher->id]),
            ]),
        ]);
        $otherGrade = Grade::factory()->create();

        $this->assertTrue(Gate::forUser($teacher)->allows('viewAny', Grade::class));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $ownGrade));
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownGrade));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $otherGrade));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherGrade));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', Grade::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $ownGrade));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $otherGrade));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherGrade));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Grade::class));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $otherGrade));
    }

    public function test_attendance_policy_uses_the_owning_daily_log(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        $ownAttendance = Attendance::factory()->create([
            'daily_log_id' => DailyLog::factory()->create([
                'schedule_id' => Schedule::factory()->create(['user_id' => $teacher->id]),
            ]),
        ]);
        $otherAttendance = Attendance::factory()->create();

        $this->assertTrue(Gate::forUser($teacher)->allows('viewAny', Attendance::class));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $ownAttendance));
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownAttendance));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $otherAttendance));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherAttendance));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', Attendance::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $ownAttendance));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $otherAttendance));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherAttendance));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Attendance::class));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $otherAttendance));
    }

    public function test_relation_managers_require_ownership_of_the_parent_record(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        $ownAssessment = Assessment::factory()->create([
            'schedule_id' => Schedule::factory()->create(['user_id' => $teacher->id]),
        ]);
        $otherAssessment = Assessment::factory()->create();
        $ownDailyLog = DailyLog::factory()->create([
            'schedule_id' => Schedule::factory()->create(['user_id' => $teacher->id]),
        ]);
        $otherDailyLog = DailyLog::factory()->create();

        $this->actingAs($teacher);

        $this->assertTrue(GradesRelationManager::canViewForRecord($ownAssessment, EditAssessment::class));
        $this->assertFalse(GradesRelationManager::canViewForRecord($otherAssessment, EditAssessment::class));
        $this->assertTrue(AttendancesRelationManager::canViewForRecord($ownDailyLog, EditDailyLog::class));
        $this->assertFalse(AttendancesRelationManager::canViewForRecord($otherDailyLog, EditDailyLog::class));

        $this->actingAs($admin);

        $this->assertTrue(GradesRelationManager::canViewForRecord($otherAssessment, EditAssessment::class));
        $this->assertTrue(AttendancesRelationManager::canViewForRecord($otherDailyLog, EditDailyLog::class));
    }

    public function test_core_academic_structure_policies_are_admin_only(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create();

        foreach ($this->coreAcademicStructureRecords() as $model => $record) {
            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
                $argument = in_array($ability, ['viewAny', 'create'], true) ? $model : $record;

                $this->assertFalse(
                    Gate::forUser($teacher)->allows($ability, $argument),
                    "Teachers must not be allowed to {$ability} {$model}."
                );
            }

            $this->assertTrue(Gate::forUser($admin)->allows('viewAny', $model));
            $this->assertTrue(Gate::forUser($admin)->allows('view', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('create', $model));
            $this->assertTrue(Gate::forUser($admin)->allows('update', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('delete', $record));
        }
    }

    public function test_teacher_is_denied_every_core_academic_structure_screen(): void
    {
        $teacher = User::factory()->create();

        foreach ($this->coreAcademicStructureScreens() as $uri => $record) {
            $this->actingAs($teacher)->get("/en/auth/{$uri}")->assertForbidden();
            $this->actingAs($teacher)->get("/en/auth/{$uri}/create")->assertForbidden();
            $this->actingAs($teacher)->get("/en/auth/{$uri}/{$record->id}/edit")->assertForbidden();
        }
    }

    public function test_admin_can_access_every_core_academic_structure_screen(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        foreach ($this->coreAcademicStructureScreens() as $uri => $record) {
            $this->actingAs($admin)->get("/en/auth/{$uri}")->assertOk();
            $this->actingAs($admin)->get("/en/auth/{$uri}/create")->assertOk();
            $this->actingAs($admin)->get("/en/auth/{$uri}/{$record->id}/edit")->assertOk();
        }
    }

    public function test_core_academic_structure_resources_are_hidden_from_teacher_navigation(): void
    {
        $resources = [
            SubjectResource::class,
            EducationPhaseResource::class,
            LevelResource::class,
            ClassroomResource::class,
            GroupResource::class,
        ];

        foreach ($resources as $resource) {
            $this->assertFalse($resource::shouldRegisterNavigation());
        }

        $this->actingAs(User::factory()->create());

        foreach ($resources as $resource) {
            $this->assertFalse($resource::shouldRegisterNavigation());
        }

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        foreach ($resources as $resource) {
            $this->assertTrue($resource::shouldRegisterNavigation());
        }
    }

    /**
     * One record per admin-only core academic structure model.
     */
    private function coreAcademicStructureRecords(): array
    {
        $educationPhase = EducationPhase::factory()->create();
        $level = Level::factory()->create(['education_phase_id' => $educationPhase->id]);
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);

        return [
            Subject::class => Subject::factory()->create(),
            EducationPhase::class => $educationPhase,
            Level::class => $level,
            Classroom::class => $classroom,
            Group::class => Group::factory()->create(['classroom_id' => $classroom->id]),
        ];
    }

    /**
     * Panel resource slug mapped to a record, matching the registered routes.
     */
    private function coreAcademicStructureScreens(): array
    {
        $educationPhase = EducationPhase::factory()->create();
        $level = Level::factory()->create(['education_phase_id' => $educationPhase->id]);
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);

        return [
            'subjects' => Subject::factory()->create(),
            'education-phases' => $educationPhase,
            'levels' => $level,
            'classrooms' => $classroom,
            'groups' => Group::factory()->create(['classroom_id' => $classroom->id]),
        ];
    }
}
