<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Pages\SchoolCensusHub;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolCensusRecord;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolCensusService;
use App\Services\SchoolRestoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolCensusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'academic_year' => '2026/2027',
            'school_name' => 'متوسطة الشهيد زبانة',
            'directorate_name' => 'الجزائر وسط',
        ]);
    }

    public function test_census_record_model_factory_and_scopes(): void
    {
        $record = SchoolCensusRecord::factory()->create([
            'academic_year' => '2026/2027',
            'status' => 'draft',
            'ordinary_classrooms_count' => 14,
            'specialized_rooms_count' => 3,
            'science_labs_count' => 2,
            'computer_labs_count' => 1,
            'library_capacity' => 50,
            'canteen_capacity' => 300,
        ]);

        $this->assertInstanceOf(SchoolCensusRecord::class, $record);
        $this->assertFalse($record->isCertified());
        $this->assertSame(14, $record->ordinary_classrooms_count);
        $this->assertSame(50, $record->library_capacity);

        // Scope test with slash
        $this->assertSame(1, SchoolCensusRecord::forAcademicYear('2026/2027')->count());

        // Scope test with dash
        $this->assertSame(1, SchoolCensusRecord::forAcademicYear('2026-2027')->count());

        // Certified factory state
        $certifiedRecord = SchoolCensusRecord::factory()->certified()->create([
            'academic_year' => '2025/2026',
        ]);

        $this->assertTrue($certifiedRecord->isCertified());
        $this->assertNotNull($certifiedRecord->certified_at);
        $this->assertIsArray($certifiedRecord->snapshot_data);
    }

    public function test_service_computes_demographic_matrix_accurately(): void
    {
        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['order' => 1, 'code' => 'CEM']
        );

        $level1 = Level::create([
            'name' => 'السنة الأولى متوسط',
            'education_phase_id' => $phase->id,
            'order' => 1,
        ]);

        $level2 = Level::create([
            'name' => 'السنة الثانية متوسط',
            'education_phase_id' => $phase->id,
            'order' => 2,
        ]);

        $classroom1 = Classroom::create([
            'name' => '1م1',
            'level_id' => $level1->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $classroom2 = Classroom::create([
            'name' => '2م1',
            'level_id' => $level2->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $group1 = Group::create([
            'name' => 'فوج 1م1',
            'classroom_id' => $classroom1->id,
            'capacity' => 35,
        ]);

        $group2 = Group::create([
            'name' => 'فوج 2م1',
            'classroom_id' => $classroom2->id,
            'capacity' => 35,
        ]);

        // Create students for Level 1: 3 boys, 2 girls (all demi-pensionnaire)
        for ($i = 0; $i < 3; $i++) {
            Student::factory()->create([
                'group_id' => $group1->id,
                'gender' => 'male',
                'regime' => 'demi-pensionnaire',
                'is_active' => true,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Student::factory()->create([
                'group_id' => $group1->id,
                'gender' => 'female',
                'regime' => 'demi-pensionnaire',
                'is_active' => true,
            ]);
        }

        // Create students for Level 2: 1 boy, 3 girls (all externe)
        Student::factory()->create([
            'group_id' => $group2->id,
            'gender' => 'male',
            'regime' => 'externe',
            'is_active' => true,
        ]);
        for ($i = 0; $i < 3; $i++) {
            Student::factory()->create([
                'group_id' => $group2->id,
                'gender' => 'female',
                'regime' => 'externe',
                'is_active' => true,
            ]);
        }

        $service = app(SchoolCensusService::class);
        $demographics = $service->computeDemographics();

        $this->assertSame(9, $demographics['total_students']);
        $this->assertSame(4, $demographics['total_boys']);
        $this->assertSame(5, $demographics['total_girls']);
        $this->assertSame(2, $demographics['total_divisions']);
        $this->assertSame(5, $demographics['total_demi_internes']);
        $this->assertSame(4, $demographics['total_externes']);
        $this->assertSame(4.5, $demographics['average_per_division']);
        $this->assertSame(55.6, $demographics['girls_percentage']);
    }

    public function test_service_computes_age_pyramid_cross_tabulation(): void
    {
        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['order' => 1, 'code' => 'CEM']
        );

        $level = Level::create([
            'name' => 'السنة الأولى متوسط',
            'education_phase_id' => $phase->id,
            'order' => 1,
        ]);

        $classroom = Classroom::create([
            'name' => '1م1',
            'level_id' => $level->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $group = Group::create([
            'name' => 'فوج 1م1',
            'classroom_id' => $classroom->id,
            'capacity' => 35,
        ]);

        $currentYear = (int) Carbon::now()->format('Y');

        // Born in 2014 (approx 12 years old)
        Student::factory()->create([
            'group_id' => $group->id,
            'date_of_birth' => ($currentYear - 12).'-04-10',
            'gender' => 'male',
            'is_active' => true,
        ]);
        Student::factory()->create([
            'group_id' => $group->id,
            'date_of_birth' => ($currentYear - 12).'-08-20',
            'gender' => 'female',
            'is_active' => true,
        ]);

        $service = app(SchoolCensusService::class);
        $pyramid = $service->computeAgePyramid();

        $this->assertNotEmpty($pyramid['rows']);

        $row12 = collect($pyramid['rows'])->firstWhere('age', 12);
        $this->assertNotNull($row12);
        $this->assertSame(1, $row12['total_boys']);
        $this->assertSame(1, $row12['total_girls']);
        $this->assertSame(2, $row12['total']);
    }

    public function test_service_computes_teacher_staffing_and_pedagogical_ratios(): void
    {
        $subject = Subject::create([
            'name' => 'الرياضيات',
            'coefficient' => 3,
        ]);

        $teacher1 = User::factory()->teacher()->create();
        $teacher2 = User::factory()->teacher()->create();

        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['order' => 1, 'code' => 'CEM']
        );

        $level = Level::create([
            'name' => 'السنة الأولى متوسط',
            'education_phase_id' => $phase->id,
            'order' => 1,
        ]);

        $classroom = Classroom::create([
            'name' => '1م1',
            'level_id' => $level->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $group = Group::create([
            'name' => 'فوج 1م1',
            'classroom_id' => $classroom->id,
            'capacity' => 35,
        ]);

        Schedule::create([
            'user_id' => $teacher1->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Schedule::create([
            'user_id' => $teacher2->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // 20 students
        for ($i = 0; $i < 20; $i++) {
            Student::factory()->create([
                'group_id' => $group->id,
                'is_active' => true,
            ]);
        }

        $service = app(SchoolCensusService::class);
        $staffing = $service->computeStaffingAndSupervision();

        $this->assertGreaterThanOrEqual(2, $staffing['total_teachers']);
        $this->assertGreaterThanOrEqual(2, $staffing['total_weekly_hours']);

        $mathData = collect($staffing['subjects'])->firstWhere('subject_id', $subject->id);
        $this->assertNotNull($mathData);
        $this->assertSame(2, $mathData['teachers_count']);
        $this->assertSame(2, $mathData['weekly_hours']);
        $this->assertSame(10.0, $mathData['ratio']); // 20 students / 2 teachers
    }

    public function test_service_certify_census_freezes_snapshot(): void
    {
        $admin = User::factory()->admin()->create();

        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['order' => 1, 'code' => 'CEM']
        );

        $level = Level::create([
            'name' => 'السنة الأولى متوسط',
            'education_phase_id' => $phase->id,
            'order' => 1,
        ]);

        $classroom = Classroom::create([
            'name' => '1م1',
            'level_id' => $level->id,
            'education_phase_id' => $phase->id,
            'capacity' => 35,
        ]);

        $group = Group::create([
            'name' => 'فوج 1م1',
            'classroom_id' => $classroom->id,
            'capacity' => 35,
        ]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'gender' => 'male',
            'is_active' => true,
        ]);

        $service = app(SchoolCensusService::class);
        $record = $service->getOrCreateRecord('2026/2027');

        $this->assertSame('draft', $record->status);

        // Certify record
        $certifiedRecord = $service->certifyCensus(
            $record,
            $admin,
            'إحصاء-رسمي-2026/01',
            'تمت المصادقة والمطابقة الشاملة'
        );

        $this->assertSame('certified', $certifiedRecord->status);
        $this->assertSame($admin->id, $certifiedRecord->certified_by_id);
        $this->assertNotNull($certifiedRecord->snapshot_data);
        $this->assertSame(1, $certifiedRecord->snapshot_data['demographics']['total_students']);

        // Now modify/delete student from database
        $student->delete();

        // assembleCensusReport must preserve frozen snapshot
        $report = $service->assembleCensusReport('2026/2027', $certifiedRecord);
        $this->assertSame(1, $report['demographics']['total_students']);
        $this->assertTrue($report['is_certified']);
    }

    public function test_print_view_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get(route('print.school-census', [
            'locale' => 'ar',
            'academic_year' => '2026/2027',
        ]));

        $response->assertOk();
        $response->assertSee('كَشْفُ الإِحْصَاءِ الشَّامِل لِلدُّخُولِ المَدْرَسِيّ');
        $response->assertSee('2026/2027');
    }

    public function test_print_view_forbidden_for_non_admin(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $response = $this->get(route('print.school-census', [
            'locale' => 'ar',
            'academic_year' => '2026/2027',
        ]));

        $response->assertForbidden();
    }

    public function test_pdf_stream_download_route_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->mock(SchoolCensusService::class, function ($mock) {
            $mock->shouldReceive('resolveAcademicYear')->andReturn('2026/2027');
            $mock->shouldReceive('generateCensusPdf')
                ->once()
                ->with('2026/2027')
                ->andReturn('%PDF-1.4 mock census document');
        });

        $response = $this->get(route('print.school-census.pdf', [
            'locale' => 'ar',
            'academic_year' => '2026/2027',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_filament_school_census_hub_livewire_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(SchoolCensusHub::class)
            ->assertSuccessful()
            ->set('activeTab', 'facilities')
            ->set('ordinaryClassroomsCount', 16)
            ->set('specializedRoomsCount', 4)
            ->set('referenceNumber', 'مراسلة-رقم-99')
            ->call('saveFacilities')
            ->assertHasNoErrors()
            ->call('certifyRecord')
            ->assertHasNoErrors();

        $record = SchoolCensusRecord::first();
        $this->assertNotNull($record);
        $this->assertSame(16, $record->ordinary_classrooms_count);
        $this->assertSame(4, $record->specialized_rooms_count);
        $this->assertSame('certified', $record->status);

        // Test unlock action
        Livewire::test(SchoolCensusHub::class)
            ->call('unlockRecord')
            ->assertHasNoErrors();

        $this->assertSame('draft', $record->fresh()->status);
    }

    public function test_backup_and_restore_services_include_school_census_records(): void
    {
        $backupService = app(SchoolBackupService::class);
        $this->assertContains('school_census_records', $backupService->getTables());

        $restoreReflection = new \ReflectionClass(SchoolRestoreService::class);
        $prop = $restoreReflection->getProperty('tables');
        $prop->setAccessible(true);
        $tables = $prop->getValue(app(SchoolRestoreService::class));

        $this->assertContains('school_census_records', $tables);
    }
}
