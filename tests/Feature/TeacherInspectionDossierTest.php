<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\PedagogicalInspectorHub;
use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class TeacherInspectionDossierTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $otherTeacher;

    protected User $admin;

    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'academic_year' => '2026-2027',
                'directorate_name' => 'مديرية التربية لولاية المغير',
                'municipality_name' => 'المغير',
                'principal_name' => 'أحمد بوزقاق',
                'school_code' => '00-0000-0001',
            ]
        );

        $this->subject = Subject::firstOrCreate(
            ['name' => 'اللغة العربية'],
            ['color' => '#1e3a8a']
        );

        $this->teacher = User::factory()->create([
            'name' => 'الأستاذ عبد الحميد بن باديس',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->subject->id,
        ]);

        $this->otherTeacher = User::factory()->create([
            'name' => 'الأستاذ مفدي زكرياء',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->subject->id,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'المدير بوزقاق أحمد',
            'role' => Role::Admin->value,
        ]);

        Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    public function test_dossier_data_aggregates_receipts_scorecard_and_curriculum(): void
    {
        $service = app(PedagogicalInspectionService::class);
        $data = $service->getTeacherInspectionDossierData($this->teacher);

        $this->assertArrayHasKey('teacher', $data);
        $this->assertArrayHasKey('scorecard', $data);
        $this->assertArrayHasKey('annualPlan', $data);
        $this->assertArrayHasKey('monthlyPlans', $data);
        $this->assertArrayHasKey('evalItems', $data);
        $this->assertArrayHasKey('curriculumPercentage', $data);
        $this->assertArrayHasKey('inspectionHistory', $data);
        $this->assertSame($this->teacher->id, $data['teacher']->id);
    }

    public function test_print_dossier_authorized_for_owning_teacher_and_leadership(): void
    {
        // Owning teacher can view
        $this->actingAs($this->teacher)
            ->get(route('print.teacher-inspection-dossier', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('ملف المتابعة والتفتيش التربوي للأستاذ(ة)')
            ->assertSee('الأستاذ عبد الحميد بن باديس');

        // Admin can view for specific teacher
        $this->actingAs($this->admin)
            ->get(route('print.teacher-inspection-dossier', ['locale' => 'ar', 'user' => $this->teacher->id]))
            ->assertOk()
            ->assertSee('الأستاذ عبد الحميد بن باديس');
    }

    public function test_print_dossier_forbidden_for_other_teacher(): void
    {
        // Other teacher attempting to inspect colleague is forbidden
        $this->actingAs($this->otherTeacher)
            ->get(route('print.teacher-inspection-dossier', ['locale' => 'ar', 'user' => $this->teacher->id]))
            ->assertOk()
            // When user passes ID of someone else, resolveTeacher forces their own profile
            ->assertSee('الأستاذ مفدي زكرياء')
            ->assertDontSee('الأستاذ عبد الحميد بن باديس');
    }

    public function test_word_download_generates_valid_docx_with_interactive_controls(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('print.teacher-inspection-dossier.word', ['locale' => 'ar']));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('content-type'));

        // Validate zip structure and interactive controls
        $content = $response->streamedContent();
        $tempPath = tempnam(sys_get_temp_dir(), 'test_docx_').'.docx';
        file_put_contents($tempPath, $content);

        $zip = new ZipArchive;
        $opened = $zip->open($tempPath);
        $this->assertTrue($opened);

        $docXml = $zip->getFromName('word/document.xml');
        $this->assertNotEmpty($docXml);
        $this->assertStringContainsString('w14:checkbox', $docXml);
        $this->assertStringContainsString('w14:checked', $docXml);
        $this->assertStringContainsString('xmlns:w14=', $docXml);

        $settingsXml = $zip->getFromName('word/settings.xml');
        $this->assertNotEmpty($settingsXml);
        $this->assertStringContainsString('w:val="15"', $settingsXml);

        $zip->close();
        @unlink($tempPath);
    }

    public function test_pedagogical_inspector_hub_has_dossier_links(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(PedagogicalInspectorHub::class)
            ->set('activeTab', 'scorecard')
            ->assertSuccessful()
            ->assertSee('ملف التفتيش');
    }

    public function test_teacher_receipts_hub_has_dossier_actions(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(TeacherReceiptsHub::class)
            ->assertSuccessful()
            ->assertActionExists('dossierWord')
            ->assertActionExists('dossierPdf');
    }
}
