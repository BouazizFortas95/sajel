<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleProfilesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_enum_covers_all_system_hierarchies(): void
    {
        app()->setLocale('ar');
        $cases = Role::cases();
        $this->assertCount(10, $cases);

        // Verify categories exist and are localized
        $categories = collect($cases)->map(fn (Role $r) => $r->category())->unique()->values();
        $this->assertContains('فريق التطوير والتقنية', $categories);
        $this->assertContains('إدارة المؤسسة', $categories);
        $this->assertContains('موظفو التربية والتسيير', $categories);
        $this->assertContains('هيئة التدريس', $categories);

        // Grouped options check
        $grouped = Role::groupedOptions();
        $this->assertArrayHasKey('فريق التطوير والتقنية', $grouped);
        $this->assertArrayHasKey('إدارة المؤسسة', $grouped);
        $this->assertArrayHasKey('موظفو التربية والتسيير', $grouped);
        $this->assertArrayHasKey('هيئة التدريس', $grouped);
    }

    public function test_developer_role_has_full_administrative_and_technical_authority(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);

        $this->assertTrue($dev->isDeveloper());
        $this->assertTrue($dev->isAdmin());
        $this->assertTrue($dev->isLeadership());
        $this->assertTrue($dev->canManageUsers());
        $this->assertTrue($dev->canManageSchoolSettings());
        $this->assertTrue($dev->canManageDiscipline());
        $this->assertTrue($dev->canManageCounseling());
        $this->assertTrue($dev->canManageTextbooks());
        $this->assertTrue($dev->canManageTeacherAbsences());
        $this->assertTrue($dev->canSuperviseSchoolLife());
        $this->assertFalse($dev->isTeacher());
        $this->assertFalse($dev->isEducationStaff());
    }

    public function test_principal_role_has_institutional_management_authority(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);

        $this->assertTrue($principal->isPrincipal());
        $this->assertTrue($principal->isAdmin());
        $this->assertTrue($principal->isLeadership());
        $this->assertTrue($principal->canManageUsers());
        $this->assertTrue($principal->canManageSchoolSettings());
        $this->assertTrue($principal->canManageDiscipline());
        $this->assertTrue($principal->canManageCounseling());
        $this->assertTrue($principal->canManageTextbooks());
        $this->assertTrue($principal->canManageTeacherAbsences());
        $this->assertTrue($principal->canSuperviseSchoolLife());
        $this->assertFalse($principal->isDeveloper());
        $this->assertFalse($principal->isTeacher());
    }

    public function test_censor_role_has_pedagogical_coordination_authority(): void
    {
        $censor = User::factory()->create(['role' => Role::Censor]);

        $this->assertTrue($censor->isCensor());
        $this->assertTrue($censor->isAdmin());
        $this->assertTrue($censor->isLeadership());
        $this->assertFalse($censor->isPrincipal());
        $this->assertFalse($censor->isDeveloper());
        $this->assertTrue($censor->canManageDiscipline());
        $this->assertTrue($censor->canManageTeacherAbsences());
        $this->assertTrue($censor->canSuperviseSchoolLife());
        $this->assertFalse($censor->canManageUsers());
        $this->assertFalse($censor->canManageSchoolSettings());
    }

    public function test_general_supervisor_role_manages_school_life_and_discipline(): void
    {
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);

        $this->assertTrue($supervisor->isGeneralSupervisor());
        $this->assertTrue($supervisor->isEducationStaff());
        $this->assertFalse($supervisor->isAdmin());
        $this->assertFalse($supervisor->isLeadership());
        $this->assertFalse($supervisor->isTeacher());
        $this->assertTrue($supervisor->canManageDiscipline());
        $this->assertTrue($supervisor->canSuperviseSchoolLife());
        $this->assertTrue($supervisor->canManageTeacherAbsences());
        $this->assertFalse($supervisor->canManageUsers());
        $this->assertFalse($supervisor->canManageTextbooks());
        $this->assertFalse($supervisor->canManageCounseling());
    }

    public function test_education_supervisor_assists_in_daily_discipline(): void
    {
        $assistant = User::factory()->create(['role' => Role::EducationSupervisor]);

        $this->assertTrue($assistant->isEducationSupervisor());
        $this->assertTrue($assistant->isEducationStaff());
        $this->assertFalse($assistant->isAdmin());
        $this->assertTrue($assistant->canManageDiscipline());
        $this->assertFalse($assistant->canManageUsers());
        $this->assertFalse($assistant->canManageTeacherAbsences());
    }

    public function test_counselor_role_manages_guidance_and_psychopedagogical_records(): void
    {
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($counselor->isCounselor());
        $this->assertTrue($counselor->isEducationStaff());
        $this->assertFalse($counselor->isAdmin());
        $this->assertFalse($counselor->isTeacher());
        $this->assertTrue($counselor->canManageCounseling());
        $this->assertFalse($counselor->canManageDiscipline());
        $this->assertFalse($counselor->canManageTextbooks());
        $this->assertFalse($counselor->canManageUsers());
    }

    public function test_finance_officer_role_manages_textbooks_and_clearance(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);

        $this->assertTrue($bursar->isFinanceOfficer());
        $this->assertTrue($bursar->isEducationStaff());
        $this->assertFalse($bursar->isAdmin());
        $this->assertFalse($bursar->isTeacher());
        $this->assertTrue($bursar->canManageTextbooks());
        $this->assertFalse($bursar->canManageDiscipline());
        $this->assertFalse($bursar->canManageCounseling());
        $this->assertFalse($bursar->canManageUsers());
    }

    public function test_head_teacher_and_teacher_roles(): void
    {
        $headTeacher = User::factory()->create(['role' => Role::HeadTeacher]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $this->assertTrue($headTeacher->isHeadTeacher());
        $this->assertTrue($headTeacher->isTeacher());
        $this->assertTrue($headTeacher->isTeachingStaff());
        $this->assertFalse($headTeacher->isAdmin());
        $this->assertFalse($headTeacher->isEducationStaff());

        $this->assertFalse($teacher->isHeadTeacher());
        $this->assertTrue($teacher->isTeacher());
        $this->assertTrue($teacher->isTeachingStaff());
        $this->assertFalse($teacher->isAdmin());
    }

    public function test_role_profiles_seeder_creates_all_archetypes(): void
    {
        $this->seed(RoleProfilesSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'dev@sajel.test', 'role' => Role::Developer->value]);
        $this->assertDatabaseHas('users', ['email' => 'principal@sajel.test', 'role' => Role::Principal->value]);
        $this->assertDatabaseHas('users', ['email' => 'censor@sajel.test', 'role' => Role::Censor->value]);
        $this->assertDatabaseHas('users', ['email' => 'supervisor@sajel.test', 'role' => Role::GeneralSupervisor->value]);
        $this->assertDatabaseHas('users', ['email' => 'assistant@sajel.test', 'role' => Role::EducationSupervisor->value]);
        $this->assertDatabaseHas('users', ['email' => 'counselor@sajel.test', 'role' => Role::Counselor->value]);
        $this->assertDatabaseHas('users', ['email' => 'bursar@sajel.test', 'role' => Role::FinanceOfficer->value]);
        $this->assertDatabaseHas('users', ['email' => 'headteacher@sajel.test', 'role' => Role::HeadTeacher->value]);
    }
}