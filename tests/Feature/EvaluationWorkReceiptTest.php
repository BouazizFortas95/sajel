<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\EvaluationWorkReceiptsHub;
use App\Models\EvaluationWorkReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AdministrativeArchiveService;
use App\Services\EvaluationWorkReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class EvaluationWorkReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $principal;

    protected User $admin;

    protected User $teacher;

    protected Subject $arabic;

    protected Subject $math;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'directorate_name' => 'مديرية التربية لولاية المغير',
                'municipality_name' => 'المغير',
                'academic_year' => '2026-2027',
                'principal_name' => 'أحمد بوزقاق',
                'school_code' => '00-0000-0001',
            ]
        );

        $this->arabic = Subject::firstOrCreate(
            ['name' => 'اللغة العربية'],
            ['color' => '#dc2626']
        );

        $this->math = Subject::firstOrCreate(
            ['name' => 'الرياضيات'],
            ['color' => '#2563eb']
        );

        $this->principal = User::factory()->create([
            'name' => 'أحمد بوزقاق',
            'email' => 'principal.eval@sajel.test',
            'role' => Role::Principal->value,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin.eval@sajel.test',
            'role' => Role::Admin->value,
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'بالراشد فيروز',
            'email' => 'teacher.eval@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
            'weekly_hours_limit' => 22,
        ]);
    }

    public function test_hub_page_accessible_to_principal_and_admin_and_forbidden_to_teacher(): void
    {
        $url = EvaluationWorkReceiptsHub::getUrl();

        $this->actingAs($this->principal)
            ->get($url)
            ->assertSuccessful();

        $this->actingAs($this->admin)
            ->get($url)
            ->assertSuccessful();

        $this->actingAs($this->teacher)
            ->get($url)
            ->assertForbidden();
    }

    public function test_service_can_record_evaluation_work_deliverables_and_calculate_stats(): void
    {
        $service = app(EvaluationWorkReceiptService::class);

        // 1. Diagnostic test
        $receipt = $service->toggleItem($this->teacher->id, 'diagnostic_test', true, '2026-09-20');
        $this->assertTrue($receipt->diagnostic_test_submitted);
        $this->assertEquals('2026-09-20', $receipt->diagnostic_test_date->format('Y-m-d'));

        // 2. Homework 1, 2, 3
        $service->toggleItem($this->teacher->id, 'homework_1', true, '2026-10-05');
        $service->toggleItem($this->teacher->id, 'homework_2', true, '2026-11-05');
        $service->toggleItem($this->teacher->id, 'homework_3', false);

        // 3. Quizzes 1, 2, 3
        $service->toggleItem($this->teacher->id, 'quiz_1', true, '2026-10-15');

        // 4. Exams 1, 2, 3
        $service->toggleItem($this->teacher->id, 'exam_1', true, '2026-11-28');

        $receipt->refresh();
        $this->assertTrue($receipt->isItemSubmitted('homework_1'));
        $this->assertTrue($receipt->isItemSubmitted('homework_2'));
        $this->assertFalse($receipt->isItemSubmitted('homework_3'));
        $this->assertTrue($receipt->isItemSubmitted('quiz_1'));
        $this->assertTrue($receipt->isItemSubmitted('exam_1'));

        // 5. Update Notes
        $service->updateNotes($this->teacher->id, 'تم تسليم كافة النماذج وسلالم التنقيط في الآجال المحددة');
        $receipt->refresh();
        $this->assertEquals('تم تسليم كافة النماذج وسلالم التنقيط في الآجال المحددة', $receipt->notes);

        // 6. Summary Stats
        $stats = $service->getSummaryStats();
        $this->assertEquals(1, $stats['total_teachers']);
        $this->assertEquals(1, $stats['diagnostic_count']);
        $this->assertEquals(100.0, $stats['diagnostic_percentage']);
        $this->assertEquals(1, $stats['homework_1_count']);
        $this->assertEquals(1, $stats['quiz_1_count']);
        $this->assertEquals(1, $stats['exam_1_count']);
    }

    public function test_bulk_mark_deliverable(): void
    {
        $teacher2 = User::factory()->create([
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->math->id,
        ]);

        $service = app(EvaluationWorkReceiptService::class);
        $count = $service->bulkMarkItem([$this->teacher->id, $teacher2->id], 'diagnostic_test', true, '2026-09-22');

        $this->assertEquals(2, $count);
        $this->assertDatabaseHas('evaluation_work_receipts', [
            'user_id' => $this->teacher->id,
            'diagnostic_test_submitted' => true,
        ]);
        $this->assertDatabaseHas('evaluation_work_receipts', [
            'user_id' => $teacher2->id,
            'diagnostic_test_submitted' => true,
        ]);
    }

    public function test_livewire_hub_actions(): void
    {
        Livewire::actingAs($this->principal)
            ->test(EvaluationWorkReceiptsHub::class)
            ->call('toggleDeliverable', $this->teacher->id, 'diagnostic_test', true)
            ->assertHasNoErrors()
            ->call('updateDeliverableDate', $this->teacher->id, 'homework_1', '2026-10-10')
            ->assertHasNoErrors()
            ->call('saveNotes', $this->teacher->id, 'ملاحظة تجريبية')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evaluation_work_receipts', [
            'user_id' => $this->teacher->id,
            'diagnostic_test_submitted' => true,
            'homework_1_submitted' => true,
            'notes' => 'ملاحظة تجريبية',
        ]);
    }

    public function test_print_route_renders_cover_page_and_13_column_table(): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $service->toggleItem($this->teacher->id, 'diagnostic_test', true, '2026-09-20');
        $service->toggleItem($this->teacher->id, 'homework_1', true, '2026-10-05');

        $response = $this->actingAs($this->principal)
            ->get(route('print.evaluation-work-receipts', [
                'locale' => 'ar',
                'subject_id' => $this->arabic->id,
            ]));

        $response->assertSuccessful();

        $content = $response->getContent();

        // Check Page 1 Title
        $this->assertStringContainsString('وثيقة تسليم الأعمال التقويمية', $content);
        $this->assertStringNotContainsString('Procès-Verbal', $content);
        $this->assertStringContainsString('السنة الدراسية: 2026-2027', $content);

        // Check 13-Column Ledger Table Headers
        $this->assertStringContainsString('الرقم', $content);
        $this->assertStringContainsString('اسم ولقب الأستاذ', $content);
        $this->assertStringContainsString('اختبار كشف المستوى', $content);
        $this->assertStringContainsString('الواجب المنزلي', $content);
        $this->assertStringContainsString('الفروض المحروسة', $content);
        $this->assertStringContainsString('الاختبار', $content);
        $this->assertStringContainsString('الملاحظة', $content);

        // Check Clean Ministerial Footer
        $this->assertStringContainsString('المغير في:', $content);
        $this->assertStringContainsString('مدير المؤسسة', $content);
    }

    public function test_word_export_generates_valid_docx_with_interactive_controls_and_modern_compatibility(): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $service->toggleItem($this->teacher->id, 'diagnostic_test', true, '2026-09-20');
        $service->toggleItem($this->teacher->id, 'homework_1', true, '2026-10-05');

        $response = $this->actingAs($this->principal)
            ->get(route('print.evaluation-work-receipts.word', [
                'locale' => 'ar',
                'subject_id' => $this->arabic->id,
            ]));

        $response->assertSuccessful();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $response->headers->get('content-type')
        );

        // Test direct file generation to inspect internals via ZipArchive
        $tempDocx = $service->generateWordDocx($this->arabic->id, '2026-2027', true);
        $this->assertFileExists($tempDocx);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempDocx) === true);

        // 1. Verify document.xml exists and has native content controls
        $docXml = $zip->getFromName('word/document.xml');
        $this->assertNotEmpty($docXml);
        $this->assertStringContainsString('وثيقة تسليم الأعمال التقويمية', $docXml);
        $this->assertStringContainsString('<w14:checkbox>', $docXml);
        $this->assertStringContainsString('<w:date>', $docXml);
        $this->assertStringContainsString('مدير المؤسسة', $docXml);

        // 2. Verify settings.xml has compatibilityMode = 15 to suppress Word Compatibility alerts
        $settingsXml = $zip->getFromName('word/settings.xml');
        $this->assertNotEmpty($settingsXml);
        $this->assertStringContainsString('compatibilityMode', $settingsXml);
        $this->assertStringContainsString('w:val="15"', $settingsXml);

        $zip->close();
        @unlink($tempDocx);
    }

    public function test_livewire_bulk_evaluation_work_action(): void
    {
        Livewire::actingAs($this->principal)
            ->test(EvaluationWorkReceiptsHub::class)
            ->assertSuccessful()
            ->callAction('bulkEvaluationWork', [
                'itemKey' => 'quiz_1',
                'date' => '2026-10-25',
                'submitted' => true,
            ]);

        $this->assertDatabaseHas('evaluation_work_receipts', [
            'user_id' => $this->teacher->id,
            'quiz_1_submitted' => true,
        ]);

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertEquals('2026-10-25', $receipt->quiz_1_date->format('Y-m-d'));
    }

    public function test_archive_pedagogical_receipt_register_for_evaluations(): void
    {
        Storage::fake('public');

        $archiveService = app(AdministrativeArchiveService::class);
        $dummyPdf = '%PDF-1.4 mock evaluation content';

        $result = $archiveService->archivePedagogicalReceiptRegister(
            'evaluations',
            $dummyPdf,
            '2026-2027',
            'اللغة العربية'
        );

        $this->assertNotEmpty($result['mail_number']);
        $this->assertNotEmpty($result['relative_path']);
        Storage::disk('public')->assertExists($result['relative_path']);

        $this->assertDatabaseHas('official_mails', [
            'mail_number' => $result['mail_number'],
            'status' => 'archived',
            'attachment_path' => $result['relative_path'],
        ]);
    }
}
