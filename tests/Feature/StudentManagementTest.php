<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\CreateStudent;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_belongs_to_a_group_and_exposes_a_full_name(): void
    {
        $group = Group::factory()->create();
        $student = Student::factory()->create([
            'first_name' => 'أمين',
            'last_name' => 'بن علي',
            'group_id' => $group->id,
        ]);

        $this->assertTrue($student->group->is($group));
        $this->assertTrue($group->students->contains($student));
        $this->assertSame('أمين بن علي', $student->full_name);
    }

    public function test_deleting_a_group_deletes_its_students(): void
    {
        $group = Group::factory()->create();
        $student = Student::factory()->create(['group_id' => $group->id]);

        $group->delete();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_teachers_cannot_open_the_students_resource(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher);

        $this->assertFalse(StudentResource::shouldRegisterNavigation());

        $this->get('/en/auth/students')->assertForbidden();
    }

    public function test_admins_can_open_the_students_resource(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin);

        $this->assertTrue(StudentResource::shouldRegisterNavigation());

        $this->get('/en/auth/students')
            ->assertOk()
            ->assertSee('Students')
            ->assertSee('Import students');
    }

    public function test_a_student_can_be_created_from_the_filament_form(): void
    {
        $group = Group::factory()->create();

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        Livewire::test(CreateStudent::class)
            ->fillForm([
                'first_name' => 'أمين',
                'last_name' => 'بن علي',
                'registration_number' => '2026-0001',
                'date_of_birth' => '2011-09-15',
                'group_id' => $group->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = Student::query()
            ->where('registration_number', '2026-0001')
            ->firstOrFail();

        $this->assertSame('أمين', $student->first_name);
        $this->assertSame('بن علي', $student->last_name);
        $this->assertSame('2011-09-15', $student->date_of_birth->toDateString());
        $this->assertSame($group->id, $student->group_id);
    }

    public function test_registration_number_must_be_unique_when_present(): void
    {
        $group = Group::factory()->create();

        Student::factory()->create(['registration_number' => '2026-0001']);

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        Livewire::test(CreateStudent::class)
            ->fillForm([
                'first_name' => 'ياسمين',
                'last_name' => 'مرزوقي',
                'registration_number' => '2026-0001',
                'group_id' => $group->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['registration_number' => 'unique']);

        $this->assertDatabaseCount('students', 1);
    }

    public function test_students_can_be_filtered_by_group(): void
    {
        $firstGroup = Group::factory()->create();
        $secondGroup = Group::factory()->create();

        $firstStudent = Student::factory()->create(['group_id' => $firstGroup->id]);
        $secondStudent = Student::factory()->create(['group_id' => $secondGroup->id]);

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        Livewire::test(ListStudents::class)
            ->assertCanSeeTableRecords([$firstStudent, $secondStudent])
            ->filterTable('group_id', $firstGroup->id)
            ->assertCanSeeTableRecords([$firstStudent])
            ->assertCanNotSeeTableRecords([$secondStudent]);
    }

    public function test_the_importer_links_students_to_groups_and_allows_blank_registration_numbers(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $import = Import::create([
            'user_id' => $user->id,
            'file_name' => 'students.csv',
            'file_path' => '/tmp/students.csv',
            'importer' => StudentImporter::class,
            'total_rows' => 2,
        ]);

        $importer = new StudentImporter($import, [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'registration_number' => 'registration_number',
            'date_of_birth' => 'date_of_birth',
            'group' => 'group',
        ], []);

        $importer([
            'first_name' => 'أمين',
            'last_name' => 'بن علي',
            'registration_number' => '2026-1000',
            'date_of_birth' => '2010-05-14',
            'group' => $group->display_name(),
        ]);

        $importer([
            'first_name' => 'ياسمين',
            'last_name' => 'مرزوقي',
            'registration_number' => '',
            'date_of_birth' => '',
            'group' => $group->name,
        ]);

        $firstStudent = Student::query()
            ->where('registration_number', '2026-1000')
            ->firstOrFail();

        $this->assertSame('أمين', $firstStudent->first_name);
        $this->assertSame('بن علي', $firstStudent->last_name);
        $this->assertSame('2010-05-14', $firstStudent->date_of_birth->toDateString());
        $this->assertSame($group->id, $firstStudent->group_id);

        $secondStudent = Student::query()
            ->where('first_name', 'ياسمين')
            ->firstOrFail();

        $this->assertSame($group->id, $secondStudent->group_id);
        $this->assertNull($secondStudent->registration_number);
        $this->assertNull($secondStudent->date_of_birth);
    }
}
