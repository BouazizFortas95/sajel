<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ClassCouncilHub;
use App\Filament\Pages\ClassroomToolbar;
use App\Filament\Pages\CurriculumProgress;
use App\Filament\Pages\DataShowMode;
use App\Filament\Pages\DeveloperSettingsHub;
use App\Filament\Pages\DisciplinaryCouncilHub;
use App\Filament\Pages\ExamTimetableHub;
use App\Filament\Pages\InstitutionalBilanHub;
use App\Filament\Pages\LabSeatingPlanPage;
use App\Filament\Pages\ManageSchoolSettings;
use App\Filament\Pages\MySchedule;
use App\Filament\Pages\OrientationHub;
use App\Filament\Pages\PedagogicalCouncilHub;
use App\Filament\Pages\PedagogicalInspectorHub;
use App\Filament\Pages\QuickAttendance;
use App\Filament\Pages\RakmanaSyncHub;
use App\Filament\Pages\SchoolArchiveHub;
use App\Filament\Pages\SchoolBoardGovernanceHub;
use App\Filament\Pages\SchoolBoardingHub;
use App\Filament\Pages\SchoolBudgetHub;
use App\Filament\Pages\SchoolCanteenHub;
use App\Filament\Pages\SchoolCensusHub;
use App\Filament\Pages\SchoolCooperativeHub;
use App\Filament\Pages\SchoolLifeHub;
use App\Filament\Pages\SchoolMaintenanceHub;
use App\Filament\Pages\SchoolSafetyHub;
use App\Filament\Pages\SchoolSolidarityHub;
use App\Filament\Pages\SchoolSportsHub;
use App\Filament\Pages\SchoolSupervisionHub;
use App\Filament\Pages\StaffCareerHub;
use App\Filament\Pages\StudentAwardsHub;
use App\Filament\Pages\StudentCounselingHub;
use App\Filament\Pages\StudentMobilityHub;
use App\Filament\Pages\TeacherAbsenceHub;
use App\Filament\Pages\TextbookInventoryHub;
use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\ClassroomResource;
use App\Filament\Resources\CurriculumDomainResource;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\EducationPhaseResource;
use App\Filament\Resources\PedagogicalMemoResource;
use App\Filament\Resources\ScheduleResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\SubjectResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleNavigationTailoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_navigation_is_streamlined_and_uncluttered(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($teacher);

        // Allowed teaching & pedagogical items
        $this->assertTrue(MySchedule::shouldRegisterNavigation());
        $this->assertTrue(DailyLogResource::shouldRegisterNavigation());
        $this->assertTrue(QuickAttendance::shouldRegisterNavigation());
        $this->assertTrue(ClassroomToolbar::shouldRegisterNavigation());
        $this->assertTrue(DataShowMode::shouldRegisterNavigation());
        $this->assertTrue(LabSeatingPlanPage::shouldRegisterNavigation());
        $this->assertTrue(CurriculumProgress::shouldRegisterNavigation());
        $this->assertTrue(PedagogicalMemoResource::shouldRegisterNavigation());
        $this->assertTrue(AssessmentResource::shouldRegisterNavigation());
        $this->assertTrue(ExamTimetableHub::shouldRegisterNavigation());
        $this->assertTrue(ClassCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(StudentAwardsHub::shouldRegisterNavigation());
        $this->assertTrue(PedagogicalCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCooperativeHub::shouldRegisterNavigation());

        // Strictly hidden administrative, structural, and guidance tools
        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertFalse(SchoolCensusHub::shouldRegisterNavigation());
        $this->assertFalse(RakmanaSyncHub::shouldRegisterNavigation());
        $this->assertFalse(TeacherAbsenceHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolArchiveHub::shouldRegisterNavigation());
        $this->assertFalse(InstitutionalBilanHub::shouldRegisterNavigation());
        $this->assertFalse(PedagogicalInspectorHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSupervisionHub::shouldRegisterNavigation());
        $this->assertFalse(StudentResource::shouldRegisterNavigation());
        $this->assertFalse(StudentCounselingHub::shouldRegisterNavigation());
        $this->assertFalse(OrientationHub::shouldRegisterNavigation());
        $this->assertFalse(EducationPhaseResource::shouldRegisterNavigation());
        $this->assertFalse(ClassroomResource::shouldRegisterNavigation());
        $this->assertFalse(SubjectResource::shouldRegisterNavigation());
        $this->assertFalse(CurriculumDomainResource::shouldRegisterNavigation());
        $this->assertFalse(ScheduleResource::shouldRegisterNavigation());
        $this->assertFalse(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSafetyHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBoardGovernanceHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertFalse(StaffCareerHub::shouldRegisterNavigation());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_counselor_navigation_is_tailored_to_psychopedagogical_guidance(): void
    {
        $counselor = User::factory()->create(['role' => Role::Counselor]);
        $this->actingAs($counselor);

        // Counseling specific items
        $this->assertTrue(StudentCounselingHub::shouldRegisterNavigation());
        $this->assertTrue(OrientationHub::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::shouldRegisterNavigation());

        // Hidden administration & teaching items
        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertFalse(SchoolCensusHub::shouldRegisterNavigation());
        $this->assertFalse(TeacherAbsenceHub::shouldRegisterNavigation());
        $this->assertFalse(PedagogicalCouncilHub::shouldRegisterNavigation());
        $this->assertFalse(ScheduleResource::shouldRegisterNavigation());
        $this->assertFalse(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSafetyHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolBoardGovernanceHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolCooperativeHub::shouldRegisterNavigation());
        $this->assertFalse(StaffCareerHub::shouldRegisterNavigation());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_general_supervisor_navigation_focuses_on_school_life_and_discipline(): void
    {
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $this->actingAs($supervisor);

        // Discipline, school life & supervision items
        $this->assertTrue(StudentResource::shouldRegisterNavigation());
        $this->assertTrue(DisciplinaryCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolLifeHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSafetyHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardGovernanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCooperativeHub::shouldRegisterNavigation());
        $this->assertTrue(StudentMobilityHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSupervisionHub::shouldRegisterNavigation());
        $this->assertTrue(TeacherAbsenceHub::shouldRegisterNavigation());
        $this->assertTrue(PedagogicalCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertFalse(StudentCounselingHub::shouldRegisterNavigation());
        $this->assertFalse(OrientationHub::shouldRegisterNavigation());
        $this->assertFalse(InstitutionalBilanHub::shouldRegisterNavigation());
        $this->assertFalse(ScheduleResource::shouldRegisterNavigation());
        $this->assertFalse(QuickAttendance::shouldRegisterNavigation());
        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertFalse(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertFalse(StaffCareerHub::shouldRegisterNavigation());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_finance_officer_navigation_focuses_on_textbooks_and_clearance(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $this->actingAs($bursar);

        // Textbook & clearance items
        $this->assertTrue(TextbookInventoryHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSafetyHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardGovernanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCooperativeHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::shouldRegisterNavigation());

        // Hidden tools
        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertFalse(SchoolCensusHub::shouldRegisterNavigation());
        $this->assertFalse(TeacherAbsenceHub::shouldRegisterNavigation());
        $this->assertFalse(PedagogicalCouncilHub::shouldRegisterNavigation());
        $this->assertFalse(StudentCounselingHub::shouldRegisterNavigation());
        $this->assertFalse(DisciplinaryCouncilHub::shouldRegisterNavigation());
        $this->assertFalse(ScheduleResource::shouldRegisterNavigation());
        $this->assertFalse(StaffCareerHub::shouldRegisterNavigation());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_censor_navigation_excludes_school_budget_hub(): void
    {
        $censor = User::factory()->create(['role' => Role::Censor]);
        $this->actingAs($censor);

        $this->assertFalse(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_principal_and_developer_have_comprehensive_visibility(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $this->assertTrue(InstitutionalBilanHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCooperativeHub::shouldRegisterNavigation());
        $this->assertTrue(PedagogicalInspectorHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCensusHub::shouldRegisterNavigation());
        $this->assertTrue(RakmanaSyncHub::shouldRegisterNavigation());
        $this->assertTrue(TeacherAbsenceHub::shouldRegisterNavigation());
        $this->assertTrue(PedagogicalCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolArchiveHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertTrue(StaffCareerHub::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::shouldRegisterNavigation());
        $this->assertTrue(DisciplinaryCouncilHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolLifeHub::shouldRegisterNavigation());
        $this->assertTrue(StudentMobilityHub::shouldRegisterNavigation());
        $this->assertTrue(StudentCounselingHub::shouldRegisterNavigation());
        $this->assertTrue(OrientationHub::shouldRegisterNavigation());
        $this->assertTrue(TextbookInventoryHub::shouldRegisterNavigation());
        $this->assertTrue(EducationPhaseResource::shouldRegisterNavigation());
        $this->assertTrue(ClassroomResource::shouldRegisterNavigation());
        $this->assertTrue(SubjectResource::shouldRegisterNavigation());
        $this->assertTrue(ScheduleResource::shouldRegisterNavigation());
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertTrue(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertTrue(DeveloperSettingsHub::shouldRegisterNavigation());

        $dev = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($dev);
        $this->assertTrue(InstitutionalBilanHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBudgetHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSolidarityHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardingHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSafetyHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolBoardGovernanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolMaintenanceHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolSportsHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCooperativeHub::shouldRegisterNavigation());
        $this->assertTrue(SchoolCanteenHub::shouldRegisterNavigation());
        $this->assertTrue(StaffCareerHub::shouldRegisterNavigation());
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertTrue(ManageSchoolSettings::shouldRegisterNavigation());
        $this->assertTrue(DeveloperSettingsHub::shouldRegisterNavigation());
    }
}
