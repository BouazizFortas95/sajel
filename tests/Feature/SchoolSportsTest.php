<?php

namespace Tests\Feature;

use App\Enums\AscsDisciplineCategory;
use App\Enums\AscsTransactionType;
use App\Enums\Role;
use App\Enums\SportsCompetitionLevel;
use App\Enums\SportsEquipmentCondition;
use App\Enums\SportsGenderTarget;
use App\Enums\SportsLicenseType;
use App\Enums\SportsMatchResult;
use App\Enums\SportsMedicalAptitude;
use App\Enums\StudentAgeCategory;
use App\Filament\Pages\SchoolSportsHub;
use App\Models\AscsFinancialTransaction;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\SchoolSportsAssociation;
use App\Models\SportsCulturalDiscipline;
use App\Models\SportsEquipmentInventory;
use App\Models\Student;
use App\Models\StudentSportsLicense;
use App\Models\User;
use App\Services\SchoolSportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sports_tables_exist(): void
    {
        $tables = [
            'school_sports_associations',
            'sports_cultural_disciplines',
            'student_sports_licenses',
            'sports_competitions_events',
            'ascs_financial_transactions',
            'sports_equipment_inventories',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist.");
        }
    }

    public function test_sports_enums_and_labels(): void
    {
        $this->assertNotEmpty(AscsDisciplineCategory::TeamSport->label());
        $this->assertNotEmpty(SportsGenderTarget::Mixed->label());
        $this->assertNotEmpty(StudentAgeCategory::Minimes->label());
        $this->assertNotEmpty(SportsLicenseType::Competitive->label());
        $this->assertNotEmpty(SportsMedicalAptitude::FitApt->label());
        $this->assertNotEmpty(SportsCompetitionLevel::WilayaFinals->label());
        $this->assertNotEmpty(SportsMatchResult::Win->label());
        $this->assertNotEmpty(AscsTransactionType::IncomeSubscription->label());
        $this->assertTrue(AscsTransactionType::IncomeSubscription->isIncome());
        $this->assertFalse(AscsTransactionType::ExpenseEquipment->isIncome());
        $this->assertNotEmpty(SportsEquipmentCondition::Good->label());
    }

    public function test_user_can_manage_school_sports_permissions(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);
        $supervisor = User::factory()->create(['role' => Role::GeneralSupervisor]);
        $censor = User::factory()->create(['role' => Role::Censor]);
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->assertTrue($principal->canManageSchoolSports());
        $this->assertTrue($teacher->canManageSchoolSports());
        $this->assertTrue($bursar->canManageSchoolSports());
        $this->assertTrue($supervisor->canManageSchoolSports());
        $this->assertTrue($censor->canManageSchoolSports());
        $this->assertFalse($counselor->canManageSchoolSports());
    }

    public function test_service_generates_sequential_codes(): void
    {
        $service = app(SchoolSportsService::class);

        $licNum = $service->generateLicenseNumber('2026/2027');
        $this->assertStringStartsWith('LIC-2026-2027-', $licNum);

        $discCode = $service->generateDisciplineCode();
        $this->assertEquals('DISC-01', $discCode);

        $evtCode = $service->generateEventCode('2026/2027');
        $this->assertStringStartsWith('EVT-2026-2027-', $evtCode);

        $finRef = $service->generateTransactionReference('2026/2027');
        $this->assertStringStartsWith('FIN-2026-2027-', $finRef);

        $eqCode = $service->generateEquipmentCode();
        $this->assertEquals('SPT-EQ-01', $eqCode);
    }

    public function test_service_gets_or_creates_association(): void
    {
        $service = app(SchoolSportsService::class);
        $ascs = $service->getOrCreateAssociation('2026/2027');

        $this->assertInstanceOf(SchoolSportsAssociation::class, $ascs);
        $this->assertEquals('2026/2027', $ascs->academic_year);
        $this->assertNotEmpty($ascs->affiliation_number);
        $this->assertNotEmpty($ascs->executive_bureau_members);

        // Fetching again returns the existing record
        $sameAscs = $service->getOrCreateAssociation('2026/2027');
        $this->assertEquals($ascs->id, $sameAscs->id);
    }

    public function test_service_bootstraps_default_disciplines(): void
    {
        $service = app(SchoolSportsService::class);
        $count = $service->bootstrapDefaultDisciplines();

        $this->assertGreaterThanOrEqual(6, $count);
        $this->assertDatabaseHas('sports_cultural_disciplines', [
            'name_ar' => 'كرة اليد المدرسية',
        ]);
    }

    public function test_service_issues_student_sports_license(): void
    {
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $license = $service->issueStudentLicense([
            'academic_year' => '2026/2027',
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
            'license_type' => SportsLicenseType::Competitive,
            'medical_aptitude_verdict' => SportsMedicalAptitude::FitApt,
            'parental_consent_signed' => true,
            'insurance_covered' => true,
        ]);

        $this->assertInstanceOf(StudentSportsLicense::class, $license);
        $this->assertTrue($license->isFullyEligible());
        $this->assertDatabaseHas('student_sports_licenses', [
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
        ]);
    }

    public function test_service_records_competition_event_and_evaluates_result(): void
    {
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $event = $service->recordCompetitionEvent([
            'academic_year' => '2026/2027',
            'discipline_id' => $discipline->id,
            'event_name' => 'نهائي بطولة الدائرة لكرة اليد',
            'competition_level' => SportsCompetitionLevel::DairaChampionship,
            'location_venue' => 'القاعة متعددة الرياضات',
            'opponent_team_name' => 'متوسطة الإخوة الشهداء',
            'our_score' => 18,
            'opponent_score' => 14,
            'ranking_achieved' => 'المرتبة الأولى — تأهل للبطولة الولائية',
            'fair_play_score' => 10,
        ]);

        $this->assertEquals(SportsMatchResult::Win, $event->match_result);
        $this->assertEquals(18, $event->our_score);
        $this->assertEquals(14, $event->opponent_score);
    }

    public function test_service_records_financial_transactions(): void
    {
        $service = app(SchoolSportsService::class);

        $income = $service->recordFinancialTransaction([
            'academic_year' => '2026/2027',
            'transaction_type' => AscsTransactionType::IncomeSubscription,
            'amount_dzd' => 12000,
            'description' => 'اشتراكات سنوية للتلاميذ المنخرطين',
            'recorded_by_name' => 'المقتصد',
        ]);

        $expense = $service->recordFinancialTransaction([
            'academic_year' => '2026/2027',
            'transaction_type' => AscsTransactionType::ExpenseEquipment,
            'amount_dzd' => 5000,
            'description' => 'شراء كرات يد وألبسة رياضية',
            'recorded_by_name' => 'المقتصد',
        ]);

        $this->assertInstanceOf(AscsFinancialTransaction::class, $income);
        $this->assertInstanceOf(AscsFinancialTransaction::class, $expense);

        $telemetry = $service->getSportsTelemetry('2026/2027');
        $this->assertEquals(12000, $telemetry['treasury_income_dzd']);
        $this->assertEquals(5000, $telemetry['treasury_expense_dzd']);
        $this->assertEquals(7000, $telemetry['treasury_balance_dzd']);
    }

    public function test_service_adds_sports_equipment(): void
    {
        $service = app(SchoolSportsService::class);

        $equipment = $service->addOrUpdateSportsEquipment([
            'item_name' => 'كرات يد قياس 02',
            'quantity_total' => 12,
            'quantity_operational' => 10,
            'condition_rating' => SportsEquipmentCondition::Good,
            'storage_location' => 'مخزن التربية البدنية',
        ]);

        $this->assertInstanceOf(SportsEquipmentInventory::class, $equipment);
        $this->assertEquals(12, $equipment->quantity_total);
    }

    public function test_service_computes_telemetry(): void
    {
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();

        $telemetry = $service->getSportsTelemetry('2026/2027');

        $this->assertArrayHasKey('total_licenses', $telemetry);
        $this->assertArrayHasKey('disciplines_count', $telemetry);
        $this->assertArrayHasKey('total_competitions', $telemetry);
        $this->assertArrayHasKey('treasury_balance_dzd', $telemetry);
    }

    public function test_service_assembles_printable_data(): void
    {
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $license = $service->issueStudentLicense([
            'academic_year' => '2026/2027',
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
        ]);

        $licenseData = $service->assembleStudentLicenseData($license);
        $this->assertArrayHasKey('license', $licenseData);
        $this->assertArrayHasKey('student', $licenseData);
        $this->assertArrayHasKey('association', $licenseData);

        $medicalData = $service->assembleParentalConsentAndMedicalData($license);
        $this->assertArrayHasKey('license', $medicalData);

        $event = $service->recordCompetitionEvent([
            'academic_year' => '2026/2027',
            'discipline_id' => $discipline->id,
            'event_name' => 'مباراة ودية',
            'competition_level' => SportsCompetitionLevel::InternalInterClass,
            'location_venue' => 'ملعب المؤسسة',
        ]);

        $eventData = $service->assembleCompetitionRosterPvData($event);
        $this->assertArrayHasKey('event', $eventData);
        $this->assertArrayHasKey('licensed_students', $eventData);

        $bilanData = $service->assembleAscsAnnualBilanData('2026/2027');
        $this->assertArrayHasKey('association', $bilanData);
        $this->assertArrayHasKey('telemetry', $bilanData);
    }

    public function test_print_student_license_routes(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $license = $service->issueStudentLicense([
            'academic_year' => '2026/2027',
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
        ]);

        $responseHtml = $this->actingAs($teacher)->get(route('print.sports.student-license', [
            'locale' => 'ar',
            'license' => $license->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($teacher)->get(route('print.sports.student-license.pdf', [
            'locale' => 'ar',
            'license' => $license->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_parental_medical_fiche_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $license = $service->issueStudentLicense([
            'academic_year' => '2026/2027',
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
        ]);

        $responseHtml = $this->actingAs($principal)->get(route('print.sports.medical-fiche', [
            'locale' => 'ar',
            'license' => $license->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($principal)->get(route('print.sports.medical-fiche.pdf', [
            'locale' => 'ar',
            'license' => $license->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_competition_roster_pv_routes(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $event = $service->recordCompetitionEvent([
            'academic_year' => '2026/2027',
            'discipline_id' => $discipline->id,
            'event_name' => 'نصف نهائي الدائرة',
            'competition_level' => SportsCompetitionLevel::DairaChampionship,
            'location_venue' => 'القاعة الرياضية',
        ]);

        $responseHtml = $this->actingAs($principal)->get(route('print.sports.competition-pv', [
            'locale' => 'ar',
            'event' => $event->id,
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($principal)->get(route('print.sports.competition-pv.pdf', [
            'locale' => 'ar',
            'event' => $event->id,
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_print_ascs_bilan_routes(): void
    {
        $bursar = User::factory()->create(['role' => Role::FinanceOfficer]);

        $responseHtml = $this->actingAs($bursar)->get(route('print.sports.ascs-bilan', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $responseHtml->assertStatus(200);

        $responsePdf = $this->actingAs($bursar)->get(route('print.sports.ascs-bilan.pdf', [
            'locale' => 'ar',
            'academicYear' => '2026-2027',
        ]));
        $this->assertContains($responsePdf->status(), [200, 302]);
    }

    public function test_sports_hub_renders_for_authorized_users(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $this->actingAs($teacher);
        Livewire::test(SchoolSportsHub::class)
            ->assertStatus(200)
            ->assertSee(__('الجمعية الثقافية والرياضية المدرسية (ASCS) والمنافسات'));
    }

    public function test_sports_hub_forbids_unauthorized_users(): void
    {
        $counselor = User::factory()->create(['role' => Role::Counselor]);

        $this->actingAs($counselor);
        Livewire::test(SchoolSportsHub::class)
            ->assertForbidden();
    }

    public function test_sports_hub_can_issue_license_and_record_event(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $service = app(SchoolSportsService::class);
        $service->bootstrapDefaultDisciplines();
        $discipline = SportsCulturalDiscipline::first();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['group_id' => $group->id]);

        $this->actingAs($teacher);

        Livewire::test(SchoolSportsHub::class)
            ->set('selectedStudentId', $student->id)
            ->set('selectedDisciplineId', $discipline->id)
            ->set('licenseType', 'competitive')
            ->set('medicalAptitude', 'fit_apt')
            ->call('saveLicense')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_sports_licenses', [
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
        ]);

        Livewire::test(SchoolSportsHub::class)
            ->set('eventName', 'مباراة تدريبية افتتاحية')
            ->set('eventDisciplineId', $discipline->id)
            ->set('competitionLevel', 'internal_inter_class')
            ->set('locationVenue', 'ملعب المؤسسة')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sports_competitions_events', [
            'event_name' => 'مباراة تدريبية افتتاحية',
        ]);
    }

    public function test_health_check_verifies_schema(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
