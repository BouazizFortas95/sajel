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

    public function test_generate_rakmana_student_roster_csv_contains_all_ministerial_columns_and_utf8_bom(): void
    {
        $classroom = Classroom::factory()->create(['name' => '2م1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201116001234567890',
            'registration_number' => 'REG-2026-99',
            'first_name' => 'ياسين',
            'last_name' => 'بوضياف',
            'gender' => 'male',
            'place_of_birth' => 'الجزائر',
            'guardian_name' => 'محمد بوضياف',
            'guardian_phone' => '0555998877',
            'is_active' => true,
        ]);

        $service = app(RakmanaSyncService::class);
        $csv = $service->generateRakmanaStudentRosterCsv($classroom->id);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('رقم التعريف الوطني', $csv);
        $this->assertStringContainsString('اللقب بالعربية', $csv);
        $this->assertStringContainsString('الاسم بالعربية', $csv);
        $this->assertStringContainsString('المستوى التعليمي', $csv);
        $this->assertStringContainsString('القسم التربوي', $csv);
        $this->assertStringContainsString('صفة التمدرس', $csv);
        $this->assertStringContainsString('201116001234567890', $csv);
        $this->assertStringContainsString('بوضياف', $csv);
        $this->assertStringContainsString('ياسين', $csv);
        $this->assertStringContainsString('2م1', $csv);
    }

    public function test_export_roster_csv_route_streams_csv_for_admin_and_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get(route('rakmana.rosters.export-csv', [
            'locale' => 'ar',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('رقم التعريف الوطني', $response->streamedContent());
    }

    public function test_generate_blank_grade_template_csv_contains_students_and_empty_scores(): void
    {
        $classroom = Classroom::factory()->create(['name' => '3م2']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'العلوم الطبيعية']);

        Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201116004455667788',
            'first_name' => 'سارة',
            'last_name' => 'دريدي',
            'is_active' => true,
        ]);

        $service = app(RakmanaSyncService::class);
        $csv = $service->generateBlankGradeTemplateCsv($classroom, $subject, 1);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('التقويم المستمر', $csv);
        $this->assertStringContainsString('الفرض 1', $csv);
        $this->assertStringContainsString('الاختبار', $csv);
        $this->assertStringContainsString('201116004455667788', $csv);
        $this->assertStringContainsString('دريدي', $csv);
        $this->assertStringContainsString('سارة', $csv);
    }

    public function test_download_grade_template_csv_route_accessible_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->get(route('rakmana.grades.template-csv', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'subject' => $subject->id,
            'trimester' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('التقويم المستمر', $response->streamedContent());
    }

    public function test_parse_and_preview_grade_booking_file_matches_students_and_calculates_averages(): void
    {
        $classroom = Classroom::factory()->create(['name' => '4م1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'الفيزياء']);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201016001122334455',
            'registration_number' => 'REG-401',
            'first_name' => 'كمال',
            'last_name' => 'مصباحي',
            'is_active' => true,
        ]);

        $csv = <<<'CSV'
"الرقم","رقم التعريف الوطني","رقم التسجيل","اللقب","الاسم","التقويم المستمر","الفرض 1","الاختبار","الملاحظة"
1,"201016001122334455","REG-401","مصباحي","كمال","17,50","15,00","16,00","ممتاز واصل"
CSV;

        $service = app(RakmanaSyncService::class);
        $preview = $service->previewGradeImport($csv, $classroom, $subject, 1);

        $this->assertSame(1, $preview['total_parsed_rows']);
        $this->assertSame(1, $preview['matched_count']);
        $this->assertSame(0, $preview['unmatched_count']);

        $matched = $preview['matched_rows'][0];
        $this->assertSame($student->id, $matched['student_id']);
        $this->assertSame(17.5, (float) $matched['continuous']);
        $this->assertSame(15.0, (float) $matched['midterm']);
        $this->assertSame(16.0, (float) $matched['exam']);
        // (17.5 + 15 + (16 * 2)) / 4 = 64.5 / 4 = 16.13
        $this->assertSame(16.13, (float) $matched['projected_average']);
        $this->assertSame('ممتاز واصل', $matched['remarks']);
    }

    public function test_execute_grade_import_persists_assessments_and_grades_in_database(): void
    {
        $classroom = Classroom::factory()->create(['name' => '1م3']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'التاريخ والجغرافيا']);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201216005566778899',
            'first_name' => 'بلال',
            'last_name' => 'عمراني',
            'is_active' => true,
        ]);

        $matchedRows = [
            [
                'student_id' => $student->id,
                'continuous' => 18.0,
                'midterm' => 16.5,
                'exam' => 17.0,
                'remarks' => 'نتائج مشرفة',
            ],
        ];

        $service = app(RakmanaSyncService::class);
        $result = $service->executeGradeImport($matchedRows, $classroom, $subject, 1);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['students_processed']);
        $this->assertSame(3, $result['total_grades_saved']);

        // Verify Assessment & Grade records exist in DB
        $this->assertDatabaseHas('assessments', [
            'type' => AssessmentType::ContinuousAssessment->value,
        ]);
        $this->assertDatabaseHas('assessments', [
            'type' => AssessmentType::Midterm->value,
        ]);
        $this->assertDatabaseHas('assessments', [
            'type' => AssessmentType::Exam->value,
        ]);

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'score' => 18.0,
        ]);
        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'score' => 16.5,
        ]);
        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'score' => 17.0,
            'remarks' => 'نتائج مشرفة',
        ]);
    }

    public function test_filament_rakmana_hub_can_parse_and_execute_grade_import(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create(['name' => '2م3']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['name' => 'اللغة الإنجليزية']);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201116009900112233',
            'first_name' => 'هدى',
            'last_name' => 'بن عيسى',
            'is_active' => true,
        ]);

        $csvText = <<<'CSV'
"الرقم","رقم التعريف الوطني","اللقب","الاسم","التقويم المستمر","الفرض 1","الاختبار","الملاحظة"
1,"201116009900112233","بن عيسى","هدى","19.00","18.00","18.50","Excellent"
CSV;

        Livewire::test(RakmanaSyncHub::class)
            ->assertSuccessful()
            ->set('activeTab', 'grade_import')
            ->set('importClassroomId', $classroom->id)
            ->set('importSubjectId', $subject->id)
            ->set('importTrimester', 1)
            ->set('gradeRawContent', $csvText)
            ->call('parseGradeFile')
            ->assertHasNoErrors()
            ->assertNotified()
            ->call('executeGradeImport')
            ->assertHasNoErrors()
            ->assertNotified();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'score' => 19.00,
        ]);
        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'score' => 18.50,
            'remarks' => 'Excellent',
        ]);
    }

    public function test_parse_and_preview_ministry_roster_with_latin_names_repeater_and_address(): void
    {
        $csv = <<<'CSV'
"رقم التعريف الوطني","رقم التسجيل","اللقب بالعربية","الاسم بالعربية","اللقب باللاتينية","الاسم باللاتينية","الجنس","تاريخ الميلاد","مكان الميلاد","القسم","صفة التمدرس","معيد","العنوان"
"201116001234567890","2026-0001","بن علي","أمين","Benali","Amine","ذكر","15/05/2011","الجزائر","1م1","نصف داخلي","نعم","حي السلام، الجزائر العاصمة"
CSV;

        $service = app(RakmanaSyncService::class);
        $preview = $service->previewImport($csv);

        $this->assertSame(1, $preview['total_count']);
        $row = $preview['sample_rows'][0];
        $this->assertSame('بن علي', $row['last_name']);
        $this->assertSame('أمين', $row['first_name']);
        $this->assertSame('Benali', $row['last_name_latin']);
        $this->assertSame('Amine', $row['first_name_latin']);
        $this->assertTrue($row['is_repeater']);
        $this->assertSame('حي السلام، الجزائر العاصمة', $row['address']);
    }

    public function test_execute_import_persists_latin_names_repeater_and_address(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->create(['education_phase_id' => $phase->id]);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1م2']);

        $rows = [
            [
                'national_id_number' => '201116008888777766',
                'registration_number' => '2026-201',
                'last_name' => 'سعيدي',
                'first_name' => 'فاطمة',
                'last_name_latin' => 'SAIDI',
                'first_name_latin' => 'Fatima',
                'gender' => 'female',
                'date_of_birth' => '2011-06-18',
                'place_of_birth' => 'وهران',
                'group_name' => '1م2',
                'regime' => 'external',
                'is_repeater' => true,
                'address' => 'نهج الأمير عبد القادر، وهران',
            ],
        ];

        $service = app(RakmanaSyncService::class);
        $result = $service->executeImport($rows);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('students', [
            'national_id_number' => '201116008888777766',
            'last_name' => 'سعيدي',
            'first_name' => 'فاطمة',
            'last_name_latin' => 'SAIDI',
            'first_name_latin' => 'Fatima',
            'is_repeater' => true,
            'address' => 'نهج الأمير عبد القادر، وهران',
        ]);

        $student = Student::where('national_id_number', '201116008888777766')->first();
        $this->assertTrue($student->isRepeater());
        $this->assertSame('SAIDI Fatima', $student->fullNameLatin);
    }

    public function test_generate_class_council_deliberation_matrix_calculates_points_coeffs_and_ranks(): void
    {
        $classroom = Classroom::factory()->create(['name' => '4م1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $student1 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'زينب',
            'last_name' => 'علوي',
            'is_active' => true,
        ]);

        $student2 = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'حمزة',
            'last_name' => 'عاشور',
            'is_active' => true,
        ]);

        $subjectArabic = Subject::factory()->create(['name' => 'اللغة العربية']);
        $subjectMath = Subject::factory()->create(['name' => 'الرياضيات']);

        $teacher = User::factory()->teacher()->create();

        $scheduleArabic = Schedule::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subjectArabic->id,
            'day_of_week' => 'sunday',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $scheduleMath = Schedule::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subjectMath->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        // Arabic assessment (Exam)
        $assessmentArabic = Assessment::create([
            'schedule_id' => $scheduleArabic->id,
            'title' => 'اختبار العربية - ف1',
            'type' => AssessmentType::Exam,
            'date' => '2026-11-20',
            'max_score' => 20,
        ]);

        // Math assessment (Exam)
        $assessmentMath = Assessment::create([
            'schedule_id' => $scheduleMath->id,
            'title' => 'اختبار الرياضيات - ف1',
            'type' => AssessmentType::Exam,
            'date' => '2026-11-25',
            'max_score' => 20,
        ]);

        // Student 1 grades: Arabic = 16.0, Math = 14.0
        Grade::create(['assessment_id' => $assessmentArabic->id, 'student_id' => $student1->id, 'score' => 16.0]);
        Grade::create(['assessment_id' => $assessmentMath->id, 'student_id' => $student1->id, 'score' => 14.0]);

        // Student 2 grades: Arabic = 8.0, Math = 7.0
        Grade::create(['assessment_id' => $assessmentArabic->id, 'student_id' => $student2->id, 'score' => 8.0]);
        Grade::create(['assessment_id' => $assessmentMath->id, 'student_id' => $student2->id, 'score' => 7.0]);

        $service = app(RakmanaSyncService::class);
        $matrix = $service->generateClassCouncilDeliberationMatrix($classroom, 1, '2026/2027');

        $this->assertSame(2, $matrix['students_count']);
        $this->assertSame(2, $matrix['graded_count']);
        $this->assertSame(1, $matrix['pass_count']);
        $this->assertSame(50.0, $matrix['pass_rate']);

        // Student 1 should be ranked 1st with average >= 14
        $row1 = $matrix['rows'][0];
        $this->assertSame($student1->id, $row1['student_id']);
        $this->assertSame(1, $row1['rank']);
        $this->assertTrue($row1['general_average'] >= 14.0);
        $this->assertStringContainsString('شرف', $row1['decision']);

        // Student 2 should be ranked 2nd
        $row2 = $matrix['rows'][1];
        $this->assertSame($student2->id, $row2['student_id']);
        $this->assertSame(2, $row2['rank']);
        $this->assertTrue($row2['general_average'] < 10.0);
    }

    public function test_generate_ministerial_deliberation_csv_contains_all_subjects_and_utf8_bom(): void
    {
        $classroom = Classroom::factory()->create(['name' => '3م2']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => '201116003322110099',
            'first_name' => 'سامي',
            'last_name' => 'بوعزيز',
            'is_active' => true,
        ]);

        $subject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);
        $teacher = User::factory()->teacher()->create();

        $schedule = Schedule::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'tuesday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'title' => 'اختبار الفيزياء',
            'type' => AssessmentType::Exam,
            'date' => '2026-11-20',
            'max_score' => 20,
        ]);

        Grade::create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 15.5]);

        $service = app(RakmanaSyncService::class);
        $csv = $service->generateMinisterialDeliberationCsv($classroom, 1, '2026/2027');

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('الرتبة', $csv);
        $this->assertStringContainsString('رقم التعريف الوطني', $csv);
        $this->assertStringContainsString('العلوم الفيزيائية', $csv);
        $this->assertStringContainsString('المعدل العام', $csv);
        $this->assertStringContainsString('قرار مجلس القسم', $csv);
        $this->assertStringContainsString('201116003322110099', $csv);
        $this->assertStringContainsString('بوعزيز', $csv);
    }

    public function test_print_and_pdf_routes_for_grade_sheets_and_deliberations(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create(['name' => '1م4']);
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);

        // 1. Grade sheet print route
        $respGradePrint = $this->get(route('print.rakmana.grade-sheet', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'subject' => $subject->id,
            'trimester' => 1,
        ]));
        $respGradePrint->assertOk();
        $respGradePrint->assertSee('بطاقة حجز النقاط الرسمية الموحدة للمادة');

        // 2. Grade sheet PDF download route (streaming fallback testable)
        $respGradePdf = $this->get(route('print.rakmana.grade-sheet.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'subject' => $subject->id,
            'trimester' => 1,
        ]));
        $respGradePdf->assertOk();

        // 3. Deliberation export CSV route
        $respDelibCsv = $this->get(route('rakmana.deliberations.export-csv', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'trimester' => 1,
        ]));
        $respDelibCsv->assertOk();

        // 4. Deliberation print route
        $respDelibPrint = $this->get(route('print.rakmana.deliberation-sheet', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'trimester' => 1,
        ]));
        $respDelibPrint->assertOk();
        $respDelibPrint->assertSee('محضر مداولات مجلس القسم الفصلي الرسمي');

        // 5. Deliberation PDF download route
        $respDelibPdf = $this->get(route('print.rakmana.deliberation-sheet.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom->id,
            'trimester' => 1,
        ]));
        $respDelibPdf->assertOk();
    }

    public function test_filament_rakmana_hub_deliberations_tab_and_latin_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $classroom = Classroom::factory()->create(['name' => '1م1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);

        Student::factory()->create([
            'group_id' => $group->id,
            'national_id_number' => null,
            'first_name_latin' => null,
            'last_name_latin' => null,
            'address' => null,
        ]);

        Livewire::test(RakmanaSyncHub::class)
            ->assertSuccessful()
            ->set('activeTab', 'deliberations')
            ->set('deliberationClassroomId', $classroom->id)
            ->set('deliberationTrimester', 1)
            ->assertSee(__('محاضر مجالس الأقسام والمداولات'))
            ->set('activeTab', 'audit')
            ->assertSee(__('نقص الأسماء اللاتينية'))
            ->assertSee(__('نقص عناوين الإقامة'));
    }
}
