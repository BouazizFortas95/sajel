<?php

namespace Tests\Feature;

use App\Enums\InstitutionalReportStatus;
use App\Filament\Pages\InstitutionalBilanHub;
use App\Models\QuarterlyInstitutionalReport;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\InstitutionalBilanService;
use App\Services\SchoolBackupService;
use App\Services\SchoolRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstitutionalBilanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_institutional_bilan_hub_accessible_to_admin_and_forbidden_to_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin);
        Livewire::test(InstitutionalBilanHub::class)
            ->assertSuccessful();

        $this->actingAs($teacher);
        Livewire::test(InstitutionalBilanHub::class)
            ->assertForbidden();
    }

    public function test_service_computes_live_metrics_and_creates_draft_report(): void
    {
        $service = app(InstitutionalBilanService::class);
        $academicYear = '2026/2027';

        $report = $service->getOrCreateReport($academicYear, 1);

        $this->assertInstanceOf(QuarterlyInstitutionalReport::class, $report);
        $this->assertEquals($academicYear, $report->academic_year);
        $this->assertEquals(1, $report->trimester);
        $this->assertEquals(InstitutionalReportStatus::Draft, $report->status);
        $this->assertIsArray($report->level_breakdown);
        $this->assertNotEmpty($report->level_breakdown);
    }

    public function test_service_saves_draft_and_syncs_live_metrics(): void
    {
        $service = app(InstitutionalBilanService::class);
        $report = $service->getOrCreateReport('2026/2027', 1);

        $report->update([
            'executive_summary' => 'ملاحظات أولية إيجابية',
            'pedagogical_recommendations' => 'مواصلة المعالجة البيداغوجية',
        ]);

        $this->assertDatabaseHas('quarterly_institutional_reports', [
            'id' => $report->id,
            'executive_summary' => 'ملاحظات أولية إيجابية',
        ]);

        $synced = $service->syncLiveMetrics($report);
        $this->assertEquals('ملاحظات أولية إيجابية', $synced->executive_summary);
    }

    public function test_service_validates_and_freezes_report_and_allows_reopening(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(InstitutionalBilanService::class);
        $report = $service->getOrCreateReport('2026/2027', 1);

        $this->assertTrue($report->isDraft());
        $this->assertFalse($report->isValidated());

        // Validate
        $validated = $service->validateReport($report, $admin);
        $this->assertTrue($validated->isValidated());
        $this->assertEquals($admin->id, $validated->validated_by);
        $this->assertNotNull($validated->validated_at);

        // Reopen
        $reopened = $service->reopenReport($validated);
        $this->assertTrue($reopened->isDraft());
        $this->assertNull($reopened->validated_at);
    }

    public function test_print_institutional_bilan_accessible_to_admin_and_forbidden_to_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $report = QuarterlyInstitutionalReport::factory()->create();

        $this->actingAs($admin)
            ->get(route('print.institutional-bilan', ['locale' => 'ar', 'report' => $report->id]))
            ->assertOk()
            ->assertSee('الحصيلة البيداغوجية والتربوية الشاملة للمؤسسة');

        $this->actingAs($teacher)
            ->get(route('print.institutional-bilan', ['locale' => 'ar', 'report' => $report->id]))
            ->assertForbidden();
    }

    public function test_print_institutional_bilan_renders_school_identity_and_kpis(): void
    {
        $admin = User::factory()->admin()->create();
        $setting = SchoolSetting::first();
        if ($setting) {
            $setting->update([
                'school_name' => 'متوسطة ساجل المتميزة',
                'directorate_name' => 'مديرية التربية لولاية سطيف',
            ]);
        }

        $report = QuarterlyInstitutionalReport::factory()->create([
            'general_success_rate' => 88.50,
            'evaluated_students_count' => 300,
            'passed_students_count' => 265,
        ]);

        $this->actingAs($admin)
            ->get(route('print.institutional-bilan', ['locale' => 'ar', 'report' => $report->id]))
            ->assertOk()
            ->assertSee('متوسطة ساجل المتميزة')
            ->assertSee('مديرية التربية لولاية سطيف')
            ->assertSee('88.50%');
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $report = QuarterlyInstitutionalReport::factory()->create();

        $mock = $this->mock(InstitutionalBilanService::class);
        $mock->shouldReceive('getOrCreateReport')->andReturn($report);
        $mock->shouldReceive('generateBilanPdf')->once()->andReturn('%PDF-1.4 mock content');

        $response = $this->actingAs($admin)
            ->get(route('print.institutional-bilan.pdf', ['locale' => 'ar', 'report' => $report->id]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_livewire_hub_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(InstitutionalBilanService::class);
        $report = $service->getOrCreateReport('2026/2027', 1);

        $this->actingAs($admin);

        Livewire::test(InstitutionalBilanHub::class)
            ->set('executiveSummary', 'تم تسجيل تقدم ملحوظ في نتائج الرياضيات.')
            ->set('pedagogicalRecommendations', 'تعزيز الدعم والاستدراك.')
            ->call('saveDraftNotes')
            ->assertHasNoErrors()
            ->call('validateAndFreeze')
            ->assertHasNoErrors();

        $this->assertTrue($report->fresh()->isValidated());

        Livewire::test(InstitutionalBilanHub::class)
            ->call('reopenReport')
            ->assertHasNoErrors();

        $this->assertTrue($report->fresh()->isDraft());
    }

    public function test_backup_and_restore_services_include_quarterly_institutional_reports(): void
    {
        $backupService = app(SchoolBackupService::class);
        $this->assertContains('quarterly_institutional_reports', $backupService->getTables());

        $restoreService = app(SchoolRestoreService::class);
        $this->assertContains('quarterly_institutional_reports', $restoreService->getTables());
    }
}
