<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Filament\Resources\ScheduleResource\Pages\CreateSchedule;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherSubjectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Subject, 2: Subject}
     */
    private function makeTeacher(
        ?Subject $primary = null,
        ?Subject $secondary = null,
        int $weeklyHoursLimit = 22,
    ): array {
        $primary ??= Subject::factory()->create(['name' => 'الرياضيات']);
        $secondary ??= Subject::factory()->create(['name' => 'الفيزياء']);

        $teacher = User::factory()->create([
            'primary_subject_id' => $primary->id,
            'secondary_subject_id' => $secondary->id,
            'weekly_hours_limit' => $weeklyHoursLimit,
        ]);

        return [$teacher, $primary, $secondary];
    }

    public function test_teachers_have_primary_and_secondary_subject_relationships_and_a_default_workload(): void
    {
        [$teacher, $primary, $secondary] = $this->makeTeacher(weeklyHoursLimit: 18);

        $this->assertTrue($teacher->primarySubject->is($primary));
        $this->assertTrue($teacher->secondarySubject->is($secondary));
        $this->assertSame(18, $teacher->weekly_hours_limit);

        $defaultTeacher = User::factory()->create();

        $this->assertSame(22, $defaultTeacher->weekly_hours_limit);
        $this->assertSame(22, $defaultTeacher->fresh()->weekly_hours_limit);
    }

    public function test_an_admin_can_assign_a_teachers_specialization_and_weekly_limit(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $primary = Subject::factory()->create(['name' => 'الرياضيات']);
        $secondary = Subject::factory()->create(['name' => 'الفيزياء']);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'أستاذة الرياضيات',
                'email' => 'math.teacher@sajel.test',
                'role' => Role::Teacher->value,
                'password' => 'password123',
                'primary_subject_id' => $primary->id,
                'secondary_subject_id' => $secondary->id,
                'weekly_hours_limit' => 18,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $teacher = User::query()
            ->where('email', 'math.teacher@sajel.test')
            ->firstOrFail();

        $this->assertSame($primary->id, $teacher->primary_subject_id);
        $this->assertSame($secondary->id, $teacher->secondary_subject_id);
        $this->assertSame(18, $teacher->weekly_hours_limit);
    }

    public function test_editing_an_existing_teacher_keeps_the_specialization_fields_available(): void
    {
        [$teacher, $primary, $secondary] = $this->makeTeacher(weeklyHoursLimit: 20);
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $teacher->getRouteKey()])
            ->assertFormFieldExists('primary_subject_id')
            ->assertFormFieldExists('secondary_subject_id')
            ->assertFormFieldExists('weekly_hours_limit')
            ->assertFormSet([
                'primary_subject_id' => $primary->id,
                'secondary_subject_id' => $secondary->id,
                'weekly_hours_limit' => 20,
            ])
            ->fillForm([
                'weekly_hours_limit' => 21,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(21, $teacher->fresh()->weekly_hours_limit);
    }

    public function test_a_teacher_subject_select_defaults_to_primary_and_accepts_the_secondary_subject(): void
    {
        [$teacher, $primary, $secondary] = $this->makeTeacher();

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->assertFormFieldIsHidden('user_id')
            ->assertFormFieldIsVisible('subject_id')
            ->assertFormSet([
                'user_id' => $teacher->id,
                'subject_id' => $primary->id,
            ])
            ->fillForm([
                'subject_id' => $secondary->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schedules', [
            'user_id' => $teacher->id,
            'subject_id' => $secondary->id,
        ]);
    }

    public function test_a_teacher_cannot_schedule_a_subject_outside_their_assignment(): void
    {
        [$teacher] = $this->makeTeacher();
        $unrelatedSubject = Subject::factory()->create(['name' => 'اللغة العربية']);

        $this->actingAs($teacher);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'subject_id' => $unrelatedSubject->id,
                'day_of_week' => DayOfWeek::Monday->value,
                'start_time' => '09:00',
                'end_time' => '10:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['subject_id']);

        $this->assertDatabaseMissing('schedules', [
            'user_id' => $teacher->id,
            'subject_id' => $unrelatedSubject->id,
        ]);
    }

    public function test_an_admin_can_only_schedule_the_selected_teachers_assigned_subjects(): void
    {
        [$teacher, $primary, $secondary] = $this->makeTeacher();
        $unrelatedSubject = Subject::factory()->create(['name' => 'اللغة العربية']);
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin);

        Livewire::test(CreateSchedule::class)
            ->assertFormFieldIsVisible('user_id')
            ->fillForm([
                'user_id' => $teacher->id,
            ])
            ->assertFormSet([
                'subject_id' => $primary->id,
            ])
            ->fillForm([
                'subject_id' => $secondary->id,
                'day_of_week' => DayOfWeek::Tuesday->value,
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schedules', [
            'user_id' => $teacher->id,
            'subject_id' => $secondary->id,
        ]);

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'user_id' => $teacher->id,
                'subject_id' => $unrelatedSubject->id,
                'day_of_week' => DayOfWeek::Wednesday->value,
                'start_time' => '11:00',
                'end_time' => '12:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['subject_id']);

        $this->assertDatabaseMissing('schedules', [
            'user_id' => $teacher->id,
            'subject_id' => $unrelatedSubject->id,
        ]);
    }
}
