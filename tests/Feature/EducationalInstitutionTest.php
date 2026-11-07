<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageSchoolSettings;
use App\Filament\Resources\EducationalInstitutionResource;
use App\Filament\Resources\EducationalInstitutionResource\Pages\CreateEducationalInstitution;
use App\Filament\Resources\EducationalInstitutionResource\Pages\ListEducationalInstitutions;
use App\Models\EducationalInstitution;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EducationalInstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_access_and_list_educational_institutions(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $school = EducationalInstitution::factory()->create([
            'name' => 'متوسطة الشهيد العربي بن مهيدي',
            'code' => '30-1102-0007',
            'is_current' => true,
        ]);

        $this->actingAs($dev);

        $this->assertTrue(EducationalInstitutionResource::canAccess());
        $this->assertTrue(EducationalInstitutionResource::shouldRegisterNavigation());

        Livewire::test(ListEducationalInstitutions::class)
            ->assertCanSeeTableRecords([$school])
            ->assertSee('متوسطة الشهيد العربي بن مهيدي')
            ->assertSee('30-1102-0007');
    }

    public function test_developer_can_create_new_educational_institution(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($dev);

        Livewire::test(CreateEducationalInstitution::class)
            ->fillForm([
                'name' => 'ثانوية مفدي زكريا',
                'code' => '47-0201-0015',
                'stage' => 'secondary',
                'directorate_name' => 'مديرية التربية لولاية غرداية',
                'wilaya' => 'غرداية',
                'municipality' => 'بني يزقن',
                'principal_name' => 'أ. إبراهيم باحمد',
                'counselor_name' => 'أ. مصطفى بلحاج',
                'capacity' => 950,
                'regime' => 'half_board',
                'status' => 'active',
                'academic_year' => '2026-2027',
                'is_current' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('educational_institutions', [
            'name' => 'ثانوية مفدي زكريا',
            'code' => '47-0201-0015',
            'stage' => 'secondary',
            'wilaya' => 'غرداية',
            'principal_name' => 'أ. إبراهيم باحمد',
        ]);
    }

    public function test_activating_an_institution_syncs_with_school_settings(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($dev);

        $school1 = EducationalInstitution::factory()->create([
            'name' => 'المدرسة الأولى',
            'code' => '11-0001',
            'is_current' => true,
        ]);

        $school2 = EducationalInstitution::factory()->create([
            'name' => 'ثانوية ابن خلدون الجديدة',
            'code' => '16-9999',
            'directorate_name' => 'مديرية التربية لولاية الجزائر وسط',
            'municipality' => 'الجزائر الوسطى',
            'principal_name' => 'د. عمار بوعلام',
            'is_current' => false,
        ]);

        // Activate school 2
        $school2->makeCurrent();

        $this->assertFalse($school1->fresh()->is_current);
        $this->assertTrue($school2->fresh()->is_current);

        // Check SchoolSetting singleton
        $currentSetting = SchoolSetting::current();
        $this->assertEquals('ثانوية ابن خلدون الجديدة', $currentSetting->school_name);
        $this->assertEquals('مديرية التربية لولاية الجزائر وسط', $currentSetting->directorate_name);
        $this->assertEquals('د. عمار بوعلام', $currentSetting->principal_name);
        $this->assertEquals('16-9999', $currentSetting->school_code);
    }

    public function test_principal_and_teachers_cannot_access_institutions_registry(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->actingAs($principal);
        $this->assertFalse(EducationalInstitutionResource::canAccess());
        $this->assertFalse(EducationalInstitutionResource::shouldRegisterNavigation());

        $this->actingAs($teacher);
        $this->assertFalse(EducationalInstitutionResource::canAccess());
        $this->assertFalse(EducationalInstitutionResource::shouldRegisterNavigation());

        $this->actingAs($counselor);
        $this->assertFalse(EducationalInstitutionResource::canAccess());
        $this->assertFalse(EducationalInstitutionResource::shouldRegisterNavigation());
    }

    public function test_developer_cannot_access_single_school_settings_while_principal_can(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $principal = User::factory()->create(['role' => Role::Principal]);

        // Developer cannot access single-school settings
        $this->actingAs($dev);
        $this->assertFalse(ManageSchoolSettings::canAccess());
        $this->assertFalse(ManageSchoolSettings::shouldRegisterNavigation());

        // Principal CAN access single-school settings
        $this->actingAs($principal);
        $this->assertTrue(ManageSchoolSettings::canAccess());
        $this->assertTrue(ManageSchoolSettings::shouldRegisterNavigation());
    }

    public function test_manage_school_settings_syncs_with_current_institution(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $institution = EducationalInstitution::factory()->create([
            'name' => 'متوسطة الشهيد العربي بن مهيدي',
            'code' => '30-1102-0007',
            'is_current' => true,
        ]);

        $this->actingAs($principal);

        Livewire::test(ManageSchoolSettings::class)
            ->set('data.school_name', 'متوسطة الشهيد العربي بن مهيدي المجددة')
            ->set('data.directorate_name', 'مديرية التربية لولاية الوادي')
            ->set('data.municipality_name', 'أم الطيور')
            ->set('data.principal_name', 'أ. عمر بن سالم المحترم')
            ->set('data.school_code', '30-1102-0007')
            ->set('data.academic_year', '2026-2027')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(
            'متوسطة الشهيد العربي بن مهيدي المجددة',
            $institution->fresh()->name
        );
        $this->assertEquals(
            'أ. عمر بن سالم المحترم',
            $institution->fresh()->principal_name
        );
    }
}
