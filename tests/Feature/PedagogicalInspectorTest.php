<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Filament\Pages\PedagogicalInspectorHub;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\InspectionReport;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PedagogicalInspectorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function curriculum_velocity_computes_parallel_section_progress_and_detects_lag(): void
    {
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'م']);
        $level = Level::factory()->for($phase)->create(['grade_number' => 1]);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);

        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
        ]);
        $sequence = CurriculumSequence::factory()->for($domain)->create();
        $r1 = CurriculumResource::factory()->for($sequence)->create();
        $r2 = CurriculumResource::factory()->for($sequence)->create();
        $r3 = CurriculumResource::factory()->for($sequence)->create();

        $c1 = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $c2 = Classroom::factory()->for($level)->create(['name' => '1 م 2']);

        $teacher = User::factory()->teacher()->create();

        $s1 = Schedule::factory()->for($teacher)->for($c1)->create(['subject_id' => $subject->id]);
        $s2 = Schedule::factory()->for($teacher)->for($c2)->create(['subject_id' => $subject->id]);

        // Complete 3 resources in 1 م 1
        DailyLog::factory()->for($s1)->create([
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $r1->id,
            'date' => now()->subDays(3)->toDateString(),
        ]);
        DailyLog::factory()->for($s1)->create([
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $r2->id,
            'date' => now()->subDays(2)->toDateString(),
        ]);
        DailyLog::factory()->for($s1)->create([
            'status' => DailyLogStatus::Completed,
            'curriculum_resource_id' => $r3->id,
            'date' => now()->subDays(1)->toDateString(),
        ]);

        // Complete 0 resources in 1 م 2

        $service = app(PedagogicalInspectionService::class);
        $result = $service->getCurriculumVelocity($subject->id, $level->id);

        $this->assertSame(3, $result['total_resources']);
        $this->assertSame(3, $result['max_completed']);
        $this->assertCount(2, $result['classrooms']);

        $c1Data = collect($result['classrooms'])->firstWhere('classroom.id', $c1->id);
        $c2Data = collect($result['classrooms'])->firstWhere('classroom.id', $c2->id);

        $this->assertSame('ahead', $c1Data['pacing_status']);
        $this->assertSame(0, $c1Data['resource_gap']);
        $this->assertSame(100.0, $c1Data['progress_percent']);

        $this->assertSame('lagging', $c2Data['pacing_status']);
        $this->assertSame(3, $c2Data['resource_gap']);
        $this->assertSame(0.0, $c2Data['progress_percent']);
    }

    #[Test]
    public function teacher_pedagogical_scorecard_calculates_logs_visas_and_inspections(): void
    {
        $teacher = User::factory()->teacher()->create();
        $schedule = Schedule::factory()->for($teacher)->create();

        // Create 4 completed logs, 3 with administrative visa
        for ($i = 0; $i < 4; $i++) {
            DailyLog::factory()->for($schedule)->create([
                'status' => DailyLogStatus::Completed,
                'date' => now()->subDays($i + 1)->toDateString(),
                'reviewed_at' => $i < 3 ? now() : null,
                'reviewed_by_id' => $i < 3 ? 1 : null,
            ]);
        }

        InspectionReport::factory()->create([
            'teacher_id' => $teacher->id,
            'pedagogical_score' => 17.50,
            'pedagogical_appreciation' => 'excellent',
        ]);

        $service = app(PedagogicalInspectionService::class);
        $scorecard = $service->getTeacherPedagogicalScorecard($teacher);

        $this->assertSame(4, $scorecard['completed_logs_count']);
        $this->assertSame(3, $scorecard['visa_count']);
        $this->assertSame(75.0, $scorecard['visa_rate']);
        $this->assertSame(1, $scorecard['inspections_count']);
        $this->assertSame(17.50, $scorecard['average_score']);
        $this->assertNotNull($scorecard['latest_inspection']);
    }

    #[Test]
    public function inspection_metrics_aggregates_global_kpis(): void
    {
        $t1 = User::factory()->teacher()->create();
        $t2 = User::factory()->teacher()->create();

        InspectionReport::factory()->create([
            'teacher_id' => $t1->id,
            'pedagogical_score' => 16.0,
            'pedagogical_appreciation' => 'very_good',
        ]);

        InspectionReport::factory()->create([
            'teacher_id' => $t2->id,
            'pedagogical_score' => 14.0,
            'pedagogical_appreciation' => 'good',
        ]);

        $service = app(PedagogicalInspectionService::class);
        $metrics = $service->getInspectionMetrics();

        $this->assertSame(2, $metrics['total_inspections']);
        $this->assertSame(2, $metrics['inspected_teachers_count']);
        $this->assertSame(15.0, $metrics['average_score']);
        $this->assertSame(1, $metrics['appreciations']['very_good']);
        $this->assertSame(1, $metrics['appreciations']['good']);
    }

    #[Test]
    public function pedagogical_inspector_hub_accessible_to_admin_and_forbidden_to_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(PedagogicalInspectorHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('فضاء المفتش التربوي ومتابعة المنهاج');

        $this->actingAs($teacher)
            ->get(PedagogicalInspectorHub::getUrl(['locale' => 'ar']))
            ->assertForbidden();
    }

    #[Test]
    public function print_inspection_report_authorized_for_admin_and_owning_teacher_and_forbidden_to_other_teachers(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher1 = User::factory()->teacher()->create();
        $teacher2 = User::factory()->teacher()->create();

        $report = InspectionReport::factory()->create([
            'teacher_id' => $teacher1->id,
            'pedagogical_score' => 16.50,
            'lesson_topic' => 'درس تفتيشي نموذجي في الرياضيات',
        ]);

        // Admin can view/print
        $this->actingAs($admin)
            ->get(route('print.inspection-report', ['locale' => 'ar', 'inspectionReport' => $report]))
            ->assertOk()
            ->assertSee('درس تفتيشي نموذجي في الرياضيات')
            ->assertSee('16.50');

        // Owning teacher can view/print
        $this->actingAs($teacher1)
            ->get(route('print.inspection-report', ['locale' => 'ar', 'inspectionReport' => $report]))
            ->assertOk()
            ->assertSee('درس تفتيشي نموذجي في الرياضيات');

        // Other teacher is forbidden (403)
        $this->actingAs($teacher2)
            ->get(route('print.inspection-report', ['locale' => 'ar', 'inspectionReport' => $report]))
            ->assertForbidden();
    }

    #[Test]
    public function print_inspection_report_renders_school_identity_and_criteria(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        SchoolSetting::create([
            'school_name' => 'متوسطة الإمام مالك النموذجية',
            'directorate_name' => 'مديرية التربية لولاية وهران',
            'academic_year' => '2026-2027',
            'principal_name' => 'أ. عبد القادر المنور',
        ]);

        $report = InspectionReport::factory()->create([
            'teacher_id' => $teacher->id,
            'inspector_name' => 'أ. البشير الإبراهيمي',
            'inspector_role' => 'inspector',
            'lesson_topic' => 'الأعداد النسبية والعمليات الحسابية',
            'pedagogical_score' => 18.00,
            'pedagogical_appreciation' => 'excellent',
        ]);

        $this->actingAs($admin)
            ->get(route('print.inspection-report', ['locale' => 'ar', 'inspectionReport' => $report]))
            ->assertOk()
            ->assertSee('متوسطة الإمام مالك النموذجية')
            ->assertSee('مديرية التربية لولاية وهران')
            ->assertSee('أ. البشير الإبراهيمي')
            ->assertSee('أ. عبد القادر المنور')
            ->assertSee('18.00');
    }

    #[Test]
    public function pdf_inspection_report_streams_download_when_authorized(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $report = InspectionReport::factory()->create([
            'teacher_id' => $teacher->id,
            'lesson_topic' => 'اختبار بصيغة بي دي اف',
        ]);

        $this->mock(PedagogicalInspectionService::class, function (MockInterface $mock) use ($report) {
            $mock->shouldReceive('generatePdf')
                ->with(\Mockery::on(fn ($arg) => $arg->id === $report->id))
                ->once()
                ->andReturn('%PDF-1.4 mock content');
        });

        $this->actingAs($admin)
            ->get(route('print.inspection-report.pdf', ['locale' => 'ar', 'inspectionReport' => $report]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
