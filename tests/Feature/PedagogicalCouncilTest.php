<?php

namespace Tests\Feature;

use App\Filament\Pages\PedagogicalCouncilHub;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Level;
use App\Models\PedagogicalCouncil;
use App\Models\PedagogicalSeminar;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalCouncilService;
use App\Services\SchoolBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedagogicalCouncilTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_council_and_attaches_teacher_attendances(): void
    {
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $coordinator = User::factory()->create([
            'role' => 'teacher',
            'primary_subject_id' => $subject->id,
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'primary_subject_id' => $subject->id,
        ]);

        $service = app(PedagogicalCouncilService::class);
        $council = $service->createCouncil([
            'academic_year' => '2026/2027',
            'subject_id' => $subject->id,
            'council_type' => 'subject_council',
            'session_term' => 1,
            'title' => 'مجلس مادة الرياضيات للفصل الأول',
            'held_at' => now(),
            'location' => 'المخبر 1',
            'coordinator_id' => $coordinator->id,
            'agenda' => 'جدول الأعمال البيداغوجي وتوحيد الفروض.',
            'curriculum_pacing_notes' => 'سير الدروس طبيعي.',
            'unified_assessments_agreement' => 'فرض موحد في الأسبوع 7.',
            'recommendations' => 'استغلال برمجيات الهندسة الديناميكية.',
            'status' => 'completed',
        ], [$coordinator->id, $teacher->id]);

        $this->assertDatabaseHas('pedagogical_councils', [
            'id' => $council->id,
            'subject_id' => $subject->id,
            'title' => 'مجلس مادة الرياضيات للفصل الأول',
        ]);

        $this->assertNotNull($council->pv_reference);
        $this->assertCount(2, $council->attendances);
        $this->assertTrue($council->attendances->where('user_id', $coordinator->id)->first()->is_present);
        $this->assertSame('منسق المادة', $council->attendances->where('user_id', $coordinator->id)->first()->role_in_council);
    }

    public function test_service_computes_curriculum_pacing_metrics(): void
    {
        $subject = Subject::factory()->create(['name' => 'الفيزياء']);
        $level = Level::factory()->create(['grade_number' => 1]);
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $schedule = Schedule::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'user_id' => $teacher->id,
        ]);

        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => now()->subDays(2),
            'status' => 'completed',
        ]);
        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => now()->addDays(2),
            'status' => 'planned',
        ]);

        $service = app(PedagogicalCouncilService::class);
        $stats = $service->computeCurriculumPacingStats('2026/2027', $subject->id);

        $row = $stats->firstWhere('level.id', $level->id);
        $this->assertNotNull($row);
        $this->assertSame(2, $row['totalLessons']);
        $this->assertSame(1, $row['completedLessons']);
        $this->assertSame(50.0, $row['completionRate']);
    }

    public function test_service_records_seminar_and_participants(): void
    {
        $subject = Subject::factory()->create(['name' => 'اللغة الإنجليزية']);
        $animator = User::factory()->create(['role' => 'teacher']);
        $attendee = User::factory()->create(['role' => 'teacher']);

        $service = app(PedagogicalCouncilService::class);
        $seminar = $service->recordSeminar([
            'academic_year' => '2026/2027',
            'subject_id' => $subject->id,
            'title' => 'تدريس مهارة التعبير الشفوي بالإنجليزية',
            'inspector_name' => 'أ. رابح بلقاسم',
            'host_school' => 'متوسطة الإخوة الشهداء',
            'scheduled_at' => now()->addDays(10)->format('Y-m-d'),
            'seminar_type' => 'model_lesson',
            'target_teachers_description' => 'أساتذة السنة الأولى والثانية',
            'animator_teacher_id' => $animator->id,
            'objectives' => 'تطوير الطلاقة اللغوية واستخدام التسجيلات الصوتية.',
            'status' => 'planned',
        ], [$attendee->id]);

        $this->assertDatabaseHas('pedagogical_seminars', [
            'id' => $seminar->id,
            'title' => 'تدريس مهارة التعبير الشفوي بالإنجليزية',
        ]);

        $this->assertCount(1, $seminar->attendances);
        $this->assertTrue($seminar->attendances->first()->attended);
    }

    public function test_pedagogical_council_controller_routes_accessible_to_authorized_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $council = PedagogicalCouncil::factory()->create(['subject_id' => $subject->id]);

        // 1. Council PV Preview
        $response = $this->actingAs($admin)->get(route('print.pedagogical-council.pv', [
            'locale' => 'ar',
            'council' => $council->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee($council->title);

        // 2. Curriculum Pacing Ledger
        $pacingResponse = $this->actingAs($admin)->get(route('print.pedagogical-council.curriculum-pacing', [
            'locale' => 'ar',
        ]));
        $pacingResponse->assertStatus(200);
        $pacingResponse->assertSee('كشف متابعة وتيرة تنفيذ المنهاج');

        // 3. Seminars Calendar
        $seminarResponse = $this->actingAs($admin)->get(route('print.pedagogical-council.seminar-calendar', [
            'locale' => 'ar',
        ]));
        $seminarResponse->assertStatus(200);
        $seminarResponse->assertSee('رزنامة الندوات التربوية');
    }

    public function test_pedagogical_council_hub_renders_and_switches_tabs(): void
    {
        app()->setLocale('ar');
        $admin = User::factory()->create(['role' => 'admin']);
        $council = PedagogicalCouncil::factory()->create();
        $seminar = PedagogicalSeminar::factory()->create();

        Livewire::actingAs($admin)
            ->test(PedagogicalCouncilHub::class)
            ->assertOk()
            ->assertSee($council->title)
            ->call('setTab', 'pacing')
            ->assertSet('activeTab', 'pacing')
            ->assertSee('مؤشرات تدرج التعلمات')
            ->call('setTab', 'seminars')
            ->assertSet('activeTab', 'seminars')
            ->assertSee($seminar->title);
    }

    public function test_pedagogical_council_hub_actions_save_council_and_seminar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create(['name' => 'العلوم الطبيعية']);

        Livewire::actingAs($admin)
            ->test(PedagogicalCouncilHub::class)
            ->call('openCouncilModal')
            ->assertSet('isCouncilModalOpen', true)
            ->set('councilSubjectId', $subject->id)
            ->set('councilTitle', 'مجلس العلوم الطبيعية للفصل الأول')
            ->set('councilHeldAt', '2026-11-15T10:00')
            ->call('saveCouncil')
            ->assertSet('isCouncilModalOpen', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pedagogical_councils', [
            'title' => 'مجلس العلوم الطبيعية للفصل الأول',
            'subject_id' => $subject->id,
        ]);

        Livewire::actingAs($admin)
            ->test(PedagogicalCouncilHub::class)
            ->call('openSeminarModal')
            ->assertSet('isSeminarModalOpen', true)
            ->set('seminarSubjectId', $subject->id)
            ->set('seminarTitle', 'التجريب المخبري في العلوم الطبيعية')
            ->set('seminarDate', '2026-11-20')
            ->call('saveSeminar')
            ->assertSet('isSeminarModalOpen', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pedagogical_seminars', [
            'title' => 'التجريب المخبري في العلوم الطبيعية',
            'subject_id' => $subject->id,
        ]);
    }

    public function test_pdf_download_routes_stream(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $council = PedagogicalCouncil::factory()->create();

        $response = $this->actingAs($admin)->get(route('print.pedagogical-council.pv.pdf', [
            'locale' => 'ar',
            'council' => $council->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_sajel_health_check_and_backup_include_pedagogical_tables(): void
    {
        $backupTables = app(SchoolBackupService::class)->getTables();

        $this->assertContains('pedagogical_councils', $backupTables);
        $this->assertContains('pedagogical_council_attendances', $backupTables);
        $this->assertContains('pedagogical_seminars', $backupTables);
        $this->assertContains('pedagogical_seminar_attendances', $backupTables);
    }
}
