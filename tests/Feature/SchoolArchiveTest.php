<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Filament\Pages\SchoolArchiveHub;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_archive_hub_page_accessible_to_both_admin_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $this->actingAs($admin)
            ->get(SchoolArchiveHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('أرشيف المؤسسة ودفاتر النصوص');

        $this->actingAs($teacher)
            ->get(SchoolArchiveHub::getUrl(['locale' => 'ar', 'classroom' => $classroom->id]))
            ->assertOk()
            ->assertSee('أرشيف المؤسسة ودفاتر النصوص');

        auth()->logout();
        $this->get(SchoolArchiveHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_school_archive_service_assembles_complete_annual_register_book_with_curriculum_and_visas(): void
    {
        $service = app(SchoolArchiveService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 1']);
        $group = Group::factory()->for($classroom)->create();

        $subject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);
        $teacher = User::factory()->teacher()->create(['name' => 'أستاذ الفيزياء']);
        $adminReviewer = User::factory()->admin()->create(['name' => 'مدير المؤسسة']);

        $domain = CurriculumDomain::factory()->create([
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'name' => 'المادة وتحولاتها',
        ]);

        $sequence = CurriculumSequence::factory()->for($domain)->create([
            'name' => 'النموذج المجهري للتحول الكيميائي',
        ]);

        $resource = CurriculumResource::factory()->for($sequence)->create([
            'name' => 'انحفاظ الكتلة في التحول الكيميائي',
        ]);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:00',
            'end_time' => '10:00', // 2 hours
        ]);

        // Create completed daily log with curriculum resource and administrative visa
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-12',
            'title' => 'تجربة انحفاظ الكتلة مخبرياً',
            'actual_content' => 'تم تحقيق التجربة باستخدام كبريتات النحاس والصودا وميزان إلكتروني.',
            'homework' => 'حل التمرين 4 صفحة 28',
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $resource->id,
            'reviewed_by_id' => $adminReviewer->id,
            'reviewed_at' => '2026-10-15 14:00:00',
            'admin_notes' => 'حصة نموذجية واستغلال أمثل للتجهيزات المخبرية.',
        ]);

        // Student attendance
        $studentA = Student::factory()->for($group)->create();
        $studentB = Student::factory()->for($group)->create();

        Attendance::create(['daily_log_id' => $log->id, 'student_id' => $studentA->id, 'status' => AttendanceStatus::Present]);
        Attendance::create(['daily_log_id' => $log->id, 'student_id' => $studentB->id, 'status' => AttendanceStatus::Absent]);

        $bookData = $service->assembleAnnualRegisterBook($classroom, $subject->id);

        $this->assertSame(1, $bookData['stats']['total_sessions']);
        $this->assertSame(2.0, $bookData['stats']['total_hours']);
        $this->assertSame(1, $bookData['stats']['homework_count']);
        $this->assertSame(1, $bookData['stats']['visas_count']);
        $this->assertSame(50.0, $bookData['stats']['presence_rate']);

        $this->assertCount(1, $bookData['curriculumIndex']['domains']);
        $this->assertSame('المادة وتحولاتها', $bookData['curriculumIndex']['domains'][0]['name']);
        $this->assertSame(1, $bookData['curriculumIndex']['taught_resources_count']);

        $this->assertCount(1, $bookData['visas']);
        $this->assertSame('مدير المؤسسة', $bookData['visas'][0]['reviewer_name']);
        $this->assertStringContainsString('حصة نموذجية', $bookData['visas'][0]['notes']);
    }

    public function test_school_archive_service_scopes_to_trimester_and_teacher_filters(): void
    {
        $service = app(SchoolArchiveService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 2']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();

        $teacherA = User::factory()->teacher()->create(['name' => 'الأستاذ أ']);
        $teacherB = User::factory()->teacher()->create(['name' => 'الأستاذ ب']);

        $scheduleA = Schedule::factory()->create([
            'user_id' => $teacherA->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $scheduleB = Schedule::factory()->create([
            'user_id' => $teacherB->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        // Trimester 1 (Oct 2026) for Teacher A
        DailyLog::factory()->create([
            'schedule_id' => $scheduleA->id,
            'date' => '2026-10-10',
            'status' => DailyLogStatus::Completed,
            'title' => 'حصة الأستاذ أ في الفصل الأول',
        ]);

        // Trimester 2 (Feb 2027) for Teacher A
        DailyLog::factory()->create([
            'schedule_id' => $scheduleA->id,
            'date' => '2027-02-10',
            'status' => DailyLogStatus::Completed,
            'title' => 'حصة الأستاذ أ في الفصل الثاني',
        ]);

        // Trimester 1 (Oct 2026) for Teacher B
        DailyLog::factory()->create([
            'schedule_id' => $scheduleB->id,
            'date' => '2026-10-15',
            'status' => DailyLogStatus::Completed,
            'title' => 'حصة الأستاذ ب في الفصل الأول',
        ]);

        // Filter: Trimester 1 and Teacher A only
        $result = $service->assembleAnnualRegisterBook($classroom, null, 1, $teacherA->id);
        $this->assertCount(1, $result['logs']);
        $this->assertSame('حصة الأستاذ أ في الفصل الأول', $result['logs']->first()->title);

        // Filter: Trimester 2 all teachers
        $resultT2 = $service->assembleAnnualRegisterBook($classroom, null, 2);
        $this->assertCount(1, $resultT2['logs']);
        $this->assertSame('حصة الأستاذ أ في الفصل الثاني', $resultT2['logs']->first()->title);
    }

    public function test_school_archive_service_computes_curriculum_completion_percentage_for_subject(): void
    {
        $service = app(SchoolArchiveService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 2']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();
        $teacher = User::factory()->teacher()->create();

        $domain = CurriculumDomain::factory()->create(['level_id' => $level->id, 'subject_id' => $subject->id]);
        $sequence = CurriculumSequence::factory()->for($domain)->create();

        // 4 total resources in official syllabus
        $r1 = CurriculumResource::factory()->for($sequence)->create();
        $r2 = CurriculumResource::factory()->for($sequence)->create();
        $r3 = CurriculumResource::factory()->for($sequence)->create();
        $r4 = CurriculumResource::factory()->for($sequence)->create();

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Tuesday,
        ]);

        // Teach 2 of the 4 resources => 50%
        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-11-01',
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $r1->id,
        ]);
        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-11-08',
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $r2->id,
        ]);

        $bookData = $service->assembleAnnualRegisterBook($classroom, $subject->id);

        $this->assertSame(4, $bookData['curriculumIndex']['total_official_resources']);
        $this->assertSame(2, $bookData['curriculumIndex']['taught_resources_count']);
        $this->assertSame(50.0, $bookData['curriculumIndex']['completion_rate']);
    }

    public function test_print_annual_register_book_renders_cover_index_logs_visas_and_closing_attestation(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '4 م 3']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);
        $teacher = User::factory()->teacher()->create(['name' => 'الأستاذ أحمد']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-04',
            'title' => 'نص القراءة المشروحة: في سبيل الوطن',
            'status' => DailyLogStatus::Completed,
            'actual_content' => 'قراءة جهرية معبرة وتحديد الفكرة العامة والأفكار الأساسية.',
        ]);

        $this->get(route('print.school-archive.book', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'subject_id' => $subject->id,
        ]))
            ->assertOk()
            ->assertSee('الجمهورية الجزائرية الديمقراطية الشعبية')
            ->assertSee('وزارة التربية الوطنية')
            ->assertSee('دَفْتَرُ النُّصُوصِ السَّنَوِيُّ الرَّسْمِيُّ')
            ->assertSee('4 م 3')
            ->assertSee('اللغة العربية')
            ->assertSee('نص القراءة المشروحة: في سبيل الوطن')
            ->assertSee('محضر الإغلاق والإيداع في الأرشيف الرسمي للمؤسسة');
    }

    public function test_pdf_download_route_streams_annual_register_book_with_proper_filename_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 4']);
        $subject = Subject::factory()->create();

        $this->mock(SchoolArchiveService::class, function ($mock) use ($classroom, $subject): void {
            $mock->shouldReceive('generateAnnualBookPdf')
                ->once()
                ->withArgs(fn ($argClassroom, $subjectId, $trimester, $teacherId) => $argClassroom->id === $classroom->id && $subjectId === $subject->id)
                ->andReturn('%PDF-1.4 Mocked Annual Register Book');
        });

        $this->get(route('print.school-archive.book.pdf', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'subject_id' => $subject->id,
        ]))
            ->assertDownload('annual-register-class-'.$classroom->id.'-subj-'.$subject->id.'.pdf');
    }

    public function test_teacher_cannot_view_or_print_archive_book_for_unassigned_classroom(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $unassignedClassroom = Classroom::factory()->for($level)->create(['name' => 'خارجي 1']);

        $this->get(route('print.school-archive.book', [
            'locale' => 'ar',
            'classroom' => $unassignedClassroom,
        ]))
            ->assertForbidden();

        $this->get(route('print.school-archive.book.pdf', [
            'locale' => 'ar',
            'classroom' => $unassignedClassroom,
        ]))
            ->assertForbidden();
    }

    public function test_annual_register_book_renders_rich_html_actual_content_without_escaping_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);
        $teacher = User::factory()->teacher()->create(['name' => 'عبد الحميد بن علي']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
        ]);

        $richHtmlContent = '<p><strong>العنوان:</strong> نص: الأسرة والمدرسة</p><p><strong>الكفاءة المستهدفة:</strong> يتمكن المتعلم من فهم النص.</p><ul><li><strong>وضعية الانطلاق:</strong> طرح السؤال التوجيهي.</li></ul>';

        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-18',
            'title' => 'درس: نص: الأسرة والمدرسة',
            'status' => DailyLogStatus::Completed,
            'actual_content' => $richHtmlContent,
        ]);

        $response = $this->get(route('print.school-archive.book', [
            'locale' => 'ar',
            'classroom' => $classroom,
            'subject_id' => $subject->id,
        ]));

        $response->assertOk();
        $response->assertSee('<p><strong>العنوان:</strong> نص: الأسرة والمدرسة</p>', false);
        $response->assertSee('<li><strong>وضعية الانطلاق:</strong> طرح السؤال التوجيهي.</li>', false);
        $response->assertDontSee('&lt;p&gt;&lt;strong&gt;العنوان:&lt;/strong&gt;', false);
    }
}
