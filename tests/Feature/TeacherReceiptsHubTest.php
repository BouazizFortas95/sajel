<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\EvaluationWorkReceipt;
use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\TeacherReceiptsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherReceiptsHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $principal;

    protected User $admin;

    protected User $teacher1;

    protected User $teacher2;

    protected Subject $arabic;

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

        $this->principal = User::factory()->create([
            'name' => 'أحمد بوزقاق',
            'email' => 'principal.teacherhub@sajel.test',
            'role' => Role::Principal->value,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin.teacherhub@sajel.test',
            'role' => Role::Admin->value,
        ]);

        $this->teacher1 = User::factory()->create([
            'name' => 'الأستاذ أحمد بلخير',
            'email' => 'teacher1.teacherhub@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
            'weekly_hours_limit' => 20,
        ]);

        $this->teacher2 = User::factory()->create([
            'name' => 'الأستاذة مريم علوش',
            'email' => 'teacher2.teacherhub@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
            'weekly_hours_limit' => 22,
        ]);
    }

    public function test_teacher_receipts_hub_accessible_to_teacher_and_principal_and_admin(): void
    {
        $url = TeacherReceiptsHub::getUrl();

        $this->actingAs($this->teacher1)
            ->get($url)
            ->assertSuccessful();

        $this->actingAs($this->principal)
            ->get($url)
            ->assertSuccessful();

        $this->actingAs($this->admin)
            ->get($url)
            ->assertSuccessful();
    }

    public function test_teacher_sees_their_own_name_and_cannot_switch_to_another_teacher(): void
    {
        Livewire::actingAs($this->teacher1)
            ->test(TeacherReceiptsHub::class, ['teacher' => $this->teacher2->id])
            ->assertSuccessful()
            ->assertSee('الأستاذ أحمد بلخير')
            ->assertSet('selectedTeacherId', $this->teacher1->id);
    }

    public function test_admin_can_select_and_inspect_different_teachers(): void
    {
        Livewire::actingAs($this->principal)
            ->test(TeacherReceiptsHub::class)
            ->assertSuccessful()
            ->set('selectedTeacherId', $this->teacher2->id)
            ->assertSee('الأستاذة مريم علوش');
    }

    public function test_service_computes_correct_percentages_and_breakdowns(): void
    {
        $service = app(TeacherReceiptsService::class);

        PedagogicalPlanReceipt::create([
            'user_id' => $this->teacher1->id,
            'academic_year' => '2026-2027',
            'annual_plan_submitted' => true,
            'annual_plan_date' => '2026-09-15',
            'september_submitted' => true,
            'september_date' => '2026-09-25',
            'october_submitted' => true,
            'october_date' => '2026-10-25',
            'director_visa' => true,
            'director_visa_at' => now(),
            'notes' => 'مخطط متناسق',
        ]);

        EvaluationWorkReceipt::create([
            'user_id' => $this->teacher1->id,
            'academic_year' => '2026-2027',
            'diagnostic_test_submitted' => true,
            'diagnostic_test_date' => '2026-09-20',
            'homework_1_submitted' => true,
            'homework_1_date' => '2026-10-10',
            'quiz_1_submitted' => true,
            'quiz_1_date' => '2026-10-28',
            'notes' => 'التزام بالمواعيد',
        ]);

        $data = $service->viewData($this->teacher1, '2026-2027');

        $this->assertTrue($data['annualPlan']['submitted']);
        $this->assertTrue($data['annualPlan']['director_visa']);
        $this->assertEquals(2, $data['submittedMonthsCount']);
        $this->assertEquals(3, $data['submittedEvalsCount']);

        // Total submitted = 1 (annual) + 2 (months) + 3 (evals) = 6 out of 20
        $this->assertEquals(6, $data['totalSubmitted']);
        $this->assertEquals(20, $data['totalItems']);
        $this->assertEquals(30.0, $data['overallPercentage']);
    }

    public function test_print_individual_receipt_route_renders_properly(): void
    {
        $response = $this->actingAs($this->teacher1)
            ->get(route('print.teacher-receipts', [
                'locale' => 'ar',
                'user' => $this->teacher1->id,
            ]));

        $response->assertSuccessful();
        $response->assertSee('كشف وضعية استلام وتأشير المخططات والأعمال التقويمية');
        $response->assertSee('الأستاذ أحمد بلخير');
        $response->assertSee('اللغة العربية');
        $response->assertSee('سجل استلام وتأشير المخططات والتدرجات البيداغوجية');
        $response->assertSee('سجل تسليم ومتابعة الأعمال والوثائق التقويمية');
        $response->assertSee('توقيع الأستاذ(ة) المعني(ة)');
        $response->assertSee('مدير المؤسسة');
    }

    public function test_teacher_cannot_view_another_teacher_receipts_print(): void
    {
        // When teacher1 requests teacher2's print URL, the controller locks it to teacher1
        $response = $this->actingAs($this->teacher1)
            ->get(route('print.teacher-receipts', [
                'locale' => 'ar',
                'user' => $this->teacher2->id,
            ]));

        $response->assertSuccessful();
        $response->assertSee('الأستاذ أحمد بلخير');
        $response->assertDontSee('الأستاذة مريم علوش');
    }

    public function test_principal_can_view_any_teacher_receipts_print(): void
    {
        $response = $this->actingAs($this->principal)
            ->get(route('print.teacher-receipts', [
                'locale' => 'ar',
                'user' => $this->teacher2->id,
            ]));

        $response->assertSuccessful();
        $response->assertSee('الأستاذة مريم علوش');
    }
}

