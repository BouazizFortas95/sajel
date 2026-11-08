<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\PedagogicalPlanReceiptsHub;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalPlanReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedagogicalPlanReceiptTest extends TestCase
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
            'email' => 'principal.test@sajel.test',
            'role' => Role::Principal->value,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin.test@sajel.test',
            'role' => Role::Admin->value,
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'بالراشد فيروز',
            'email' => 'teacher.test@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
            'weekly_hours_limit' => 22,
        ]);
    }

    public function test_hub_page_accessible_to_principal_and_admin_and_forbidden_to_teacher(): void
    {
        $url = PedagogicalPlanReceiptsHub::getUrl();

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

    public function test_service_can_record_annual_and_monthly_plans_and_calculate_stats(): void
    {
        $service = app(PedagogicalPlanReceiptService::class);

        // 1. Toggle Annual Plan
        $receipt = $service->togglePlan($this->teacher->id, 'annual', true, '2026-09-18');
        $this->assertTrue($receipt->annual_plan_submitted);
        $this->assertEquals('2026-09-18', $receipt->annual_plan_date->format('Y-m-d'));

        // 2. Toggle Months
        $service->togglePlan($this->teacher->id, 'september', true, '2026-09-25');
        $service->togglePlan($this->teacher->id, 'october', true, '2026-10-25');

        $receipt->refresh();
        $this->assertTrue($receipt->september_submitted);
        $this->assertTrue($receipt->october_submitted);
        $this->assertFalse($receipt->november_submitted);
        $this->assertEquals(2, $receipt->getSubmittedMonthsCount());

        // 3. Update Notes
        $service->updateNotes($this->teacher->id, 'مخطط متناسق مع التدرج السنوي المعتمد');
        $receipt->refresh();
        $this->assertEquals('مخطط متناسق مع التدرج السنوي المعتمد', $receipt->notes);

        // 4. Summary Stats
        $stats = $service->getSummaryStats();
        $this->assertEquals(1, $stats['total_teachers']);
        $this->assertEquals(1, $stats['annual_submitted']);
        $this->assertEquals(100.0, $stats['annual_percentage']);
    }

    public function test_bulk_mark_annual_plan(): void
    {
        $teacher2 = User::factory()->create([
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->math->id,
        ]);

        $service = app(PedagogicalPlanReceiptService::class);
        $count = $service->bulkMarkAnnualPlan([$this->teacher->id, $teacher2->id], true, '2026-09-20');

        $this->assertEquals(2, $count);
        $this->assertDatabaseHas('pedagogical_plan_receipts', [
            'user_id' => $this->teacher->id,
            'annual_plan_submitted' => true,
        ]);
        $this->assertDatabaseHas('pedagogical_plan_receipts', [
            'user_id' => $teacher2->id,
            'annual_plan_submitted' => true,
        ]);
    }

    public function test_print_route_renders_cover_page_and_table_pages_with_correct_structure(): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $service->togglePlan($this->teacher->id, 'annual', true, '2026-09-18');
        $service->togglePlan($this->teacher->id, 'september', true, '2026-09-25');

        $response = $this->actingAs($this->principal)
            ->get('/ar/print/pedagogical-plan-receipts');

        $response->assertSuccessful();

        // Page 1 Elements: Cover
        $response->assertSee('الجمهورية الجزائرية الديمقراطية الشعبية');
        $response->assertSee('وزارة التربية الوطنية');
        $response->assertSee('مديرية التربية لولاية:');
        $response->assertSee('متوسطة حي 900 مسكن');
        $response->assertSee('وثيقة استلام المخططات السنوية والشهرية');
        $response->assertSee('السنة الدراسية');
        $response->assertSee('2026-2027');

        // Page 2 Elements: Tables
        $response->assertSee('المخطط السنوي');
        $response->assertSee('المخططات والتدرجات الشهرية');
        $response->assertSee('سبتمبر');
        $response->assertSee('أكتوبر');
        $response->assertSee('ماي');
        $response->assertSee('ملاحظات');
        $response->assertSee('بالراشد فيروز');
        $response->assertSee('تأشيرة ناظر الدروس');
        $response->assertSee('تأشيرة وختم مدير المؤسسة');
    }

    public function test_print_route_renders_single_subject_mode_on_two_pages(): void
    {
        $response = $this->actingAs($this->principal)
            ->get('/ar/print/pedagogical-plan-receipts?subject_id='.$this->arabic->id.'&layout=separate');

        $response->assertSuccessful();
        $response->assertSee('مادة: اللغة العربية');
        $response->assertSee('بالراشد فيروز');
        $response->assertDontSee('مادة: الرياضيات');
    }

    public function test_print_route_renders_continuous_layout_option(): void
    {
        $response = $this->actingAs($this->principal)
            ->get('/ar/print/pedagogical-plan-receipts?layout=continuous');

        $response->assertSuccessful();
        $response->assertSee('عرض مدمج');
    }

    public function test_print_route_forbidden_to_teacher(): void
    {
        $this->actingAs($this->teacher)
            ->get('/ar/print/pedagogical-plan-receipts')
            ->assertForbidden();
    }

    public function test_livewire_hub_actions_toggle_plans_and_save_notes(): void
    {
        Livewire::actingAs($this->principal)
            ->test(PedagogicalPlanReceiptsHub::class)
            ->assertSuccessful()
            ->assertSee('بالراشد فيروز')
            ->call('toggleAnnualPlan', $this->teacher->id, true)
            ->call('toggleMonthPlan', $this->teacher->id, 'september', true)
            ->call('saveNotes', $this->teacher->id, 'تمت المراجعة والمطابقة');

        $this->assertDatabaseHas('pedagogical_plan_receipts', [
            'user_id' => $this->teacher->id,
            'annual_plan_submitted' => true,
            'september_submitted' => true,
            'notes' => 'تمت المراجعة والمطابقة',
        ]);
    }
}
