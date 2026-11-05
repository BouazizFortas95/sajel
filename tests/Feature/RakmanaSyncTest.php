<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Filament\Pages\RakmanaSyncHub;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\RakmanaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RakmanaSyncTest extends TestCase
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

    public function test_parse_and_preview_ministry_roster_csv(): void
    {
        $csv = <<<'CSV'
"رقم التعريف الوطني","رقم التسجيل","اللقب","الاسم","الجنس","تاريخ الميلاد","مكان الميلاد","القسم","صفة التمدرس","اسم الولي","هاتف الولي"
"201116001234567890","2026-0001","بن علي","أمين","ذكر","15/05/2011","الجزائر","1م1","نصف داخلي","محمد بن علي","0555123456"
"201116009876543210","2026-0002","براهيمي","مريم","أنثى","22/09/2011","المدنية","1م1","خارجي","سعيد براهيمي","0661987654"
CSV;

        $service = app(RakmanaSyncService::class);
        $preview = $service->previewImport($csv);

        $this->assertSame(2, $preview['total_count']);
        $this->assertSame(2, $preview['new_count']);
        $this->assertSame(0, $preview['update_count']);
        $this->assertArrayHasKey('1م1', $preview['groups_detected']);
        $this->assertSame('بن علي', $preview['sample_rows'][0]['last_name']);
        $this->assertSame('أمين', $preview['sample_rows'][0]['first_name']);
        $this->assertSame('male', $preview['sample_rows'][0]['gender']);
        $this->assertSame('2011-05-15', $preview['sample_rows'][0]['date_of_birth']);
    }

    public function test_execute_import_creates_new_students_and_groups(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->create(['education_phase_id' => $phase->id]);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1م1']);

        $rows = [
            [
                'national_id_number' => '201116001234567890',
                'registration_number' => '2026-101',
                'last_name' => 'بلقاسم',
                'first_name' => 'فارس',
                'gender' => 'male',
                'date_of_birth' => '2011-04-12',
                'place_of_birth' => 'سطيف',
                'group_name' => '1م1',
                'regime' => 'half_board',
                'guardian_name' => 'رشيد بلقاسم',
                'guardian_phone' => '0550112233',
            ],
            [
                'national_id_number' => '201116009988776655',
                'registration_number' => '2026-102',
                'last_name' => 'منصوري',
                'first_name' => 'إيناس',
                'gender' => 'female',
                'date_of_birth' => '2011-10-25',
                'place_of_birth' => 'قسنطينة',
                'group_name' => '1م1',
                'regime' => 'external',
                'guardian_name' => 'كمال منصوري',
                'guardian_phone' => '0660445566',
            ],
        ];

        $service = app(RakmanaSyncService::class);
        $result = $service->executeImport($rows);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['created']);

        $this->assertDatabaseHas('students', [
            'national_id_number' => '201116001234567890',
            'last_name' => 'بلقاسم',
            'first_name' => 'فارس',
            'gender' => 'male',
            'regime' => 'half_board',
            'guardian_name' => 'رشيد بلقاسم',
        ]);

        $this->assertDatabaseHas('students', [
            'national_id_number' => '201116009988776655',
            'last_name' => 'منصوري',
            'first_name' => 'إيناس',
            'gender' => 'female',
        ]);
    }

    public function test_execute_import_updates_existing_students(): void
    {
        $group = Group::factory()->create();
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'كريم',
            'last_name' => 'سليماني',
            'national_id_number' => null,
            'guardian_phone' => null,
        ]);

        $rows = [
            [
                'national_id_number' => '201116007777888899',
                'registration_number' => $student->registration_number,
                'first_name' => 'كريم',
                'last_name' => 'سليماني',
                'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                'guardian_phone' => '0560778899',
            ],
        ];

        $service = app(RakmanaSyncService::class);
        $result = $service->executeImport($rows, ['update_existing' => true]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);

        $this->assertSame('201116007777888899', $student->fresh()->national_id_number);
        $this->assertSame('0560778899', $student->fresh()->guardian_phone);
    }

    public function test_generate_rakmana_grade_export_matrix_calculates_algerian_average(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);
        $teacher = User::factory()->teacher()->create();

        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'user_id' => $teacher->id,
        ]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'حمزة',
            'last_name' => 'شريف',
            'national_id_number' => '201116001122334455',
        ]);

        // Continuous = 16.00
        $assess1 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::ContinuousAssessment,
            'max_score' => 20,
        ]);
        Grade::create(['assessment_id' => $assess1->id, 'student_id' => $student->id, 'score' => 16.00]);

        // Midterm = 14.00
        $assess2 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Midterm,
            'max_score' => 20,
        ]);
        Grade::create(['assessment_id' => $assess2->id, 'student_id' => $student->id, 'score' => 14.00]);

        // Exam = 15.00
        $assess3 = Assessment::factory()->create([
            'schedule_id' => $schedule->id,
            'type' => AssessmentType::Exam,
            'max_score' => 20,
        ]);
        Grade::create(['assessment_id' => $assess3->id, 'student_id' => $student->id, 'score' => 15.00]);

        $service = app(RakmanaSyncService::class);
        $matrix = $service->generateGradeExportMatrix($classroom, $subject, 1);

        $this->assertSame(1, $matrix['students_count']);
        $row = $matrix['rows'][0];

        $this->assertSame('16.00', $row['continuous']);
        $this->assertSame('14.00', $row['midterm']);
        $this->assertSame('15.00', $row['exam']);

        // (16 + 14 + (15 * 2)) / 4 = 60 / 4 = 15.00
        $this->assertSame('15.00', $row['average']);
        $this->assertSame('جيد', $row['appreciation']);
    }

    public function test_generate_rakmana_grade_csv_has_utf8_bom_and_headers(): void
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();

        Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201116001234567890',
        ]);

        $service = app(RakmanaSyncService::class);
        $csv = $service->generateRakmanaGradeCsv($classroom, $subject, 1);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('رقم التعريف الوطني', $csv);
        $this->assertStringContainsString('201116001234567890', $csv);
    }

    public function test_download_grade_csv_route(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->get(route('rakmana.grades.export-csv', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'subject' => $subject->id,
            'trimester' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_download_sample_roster_route(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get(route('rakmana.sample-roster', [
            'locale' => 'ar',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('رقم التعريف الوطني', $response->streamedContent());
    }

    public function test_audit_reconciliation_detects_missing_nins(): void
    {
        $group = Group::factory()->create();

        // 2 with NIN
        Student::factory()->count(2)->create([
            'group_id' => $group->id,
            'national_id_number' => '201116001234567890',
            'is_active' => true,
        ]);

        // 1 without NIN
        Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => null,
            'is_active' => true,
        ]);

        $service = app(RakmanaSyncService::class);
        $audit = $service->auditReconciliation();

        $this->assertSame(3, $audit['total_students']);
        $this->assertSame(2, $audit['with_nin_count']);
        $this->assertSame(1, $audit['missing_nin_count']);
        $this->assertEquals(66.7, $audit['nin_completeness_rate']);
        $this->assertCount(1, $audit['deficient_students']);
    }

    public function test_filament_rakmana_hub_can_render_and_switch_tabs(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create(['name' => '1م1']);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => null,
        ]);

        Livewire::test(RakmanaSyncHub::class)
            ->assertSuccessful()
            ->set('activeTab', 'grades')
            ->assertSuccessful()
            ->set('activeTab', 'audit')
            ->assertSuccessful()
            ->call('openEditNinModal', $student->id, '201116009999888877')
            ->set('editNin', '201116009999888877')
            ->call('saveStudentNin')
            ->assertNotified();

        $this->assertSame('201116009999888877', $student->fresh()->national_id_number);
    }
}
