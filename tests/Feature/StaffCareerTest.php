<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StaffEvaluationAppreciation;
use App\Enums\StaffMovementType;
use App\Filament\Pages\StaffCareerHub;
use App\Models\CareerAdvancement;
use App\Models\StaffEvaluation;
use App\Models\User;
use App\Services\StaffCareerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffCareerTest extends TestCase
{
    use RefreshDatabase;

    protected StaffCareerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StaffCareerService::class);
    }

    public function test_user_career_attributes_and_seniority_calculations(): void
    {
        $user = User::factory()->create([
            'registration_number' => 'M98765',
            'national_id_number' => '10987654321',
            'recruitment_date' => Carbon::now()->subYears(5)->subMonths(3),
            'grade_appointment_date' => Carbon::now()->subYears(3)->subMonths(2),
            'current_step' => 2,
            'current_step_date' => Carbon::now()->subYears(2)->subMonths(6),
            'statutory_grade' => 'أستاذ التعليم المتوسط',
        ]);

        $seniority = $this->service->calculateSeniority($user);

        $this->assertEquals(5, $seniority['general_seniority']['years']);
        $this->assertEquals(3, $seniority['general_seniority']['months']);
        $this->assertEquals(3, $seniority['grade_seniority']['years']);
        $this->assertEquals(2, $seniority['grade_seniority']['months']);
        $this->assertEquals(2, $seniority['step_seniority']['years']);
        $this->assertEquals(6, $seniority['step_seniority']['months']);
    }

    public function test_project_advancement_eligibility_calculates_algerian_rhythms(): void
    {
        $stepDate = Carbon::parse('2023-01-01');
        $user = User::factory()->create([
            'current_step' => 3,
            'current_step_date' => $stepDate,
        ]);

        // Minimum rhythm = +30 months (2025-07-01)
        // Medium rhythm = +36 months (2026-01-01)
        // Maximum rhythm = +42 months (2026-07-01)
        $projection = $this->service->projectAdvancementEligibility($user, Carbon::parse('2026-02-01'));

        $this->assertEquals(3, $projection['current_step']);
        $this->assertEquals(4, $projection['next_step']);
        $this->assertEquals('2025-07-01', $projection['eligible_at_minimum']->format('Y-m-d'));
        $this->assertEquals('2026-01-01', $projection['eligible_at_medium']->format('Y-m-d'));
        $this->assertEquals('2026-07-01', $projection['eligible_at_maximum']->format('Y-m-d'));

        // As of 2026-02-01, both minimum and medium are reached
        $this->assertTrue($projection['is_eligible_minimum']);
        $this->assertTrue($projection['is_eligible_medium']);
        $this->assertFalse($projection['is_eligible_maximum']);
    }

    public function test_sync_advancement_projections_records_annual_proposals(): void
    {
        $user = User::factory()->create([
            'current_step' => 2,
            'current_step_date' => Carbon::parse('2023-01-01'),
        ]);

        $count = $this->service->syncAdvancementProjections(2026);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('career_advancements', [
            'user_id' => $user->id,
            'current_step' => 2,
            'next_step' => 3,
            'status' => 'draft',
        ]);
    }

    public function test_save_evaluation_calculates_administrative_and_composite_scores(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $principal = User::factory()->create(['role' => Role::Principal]);

        $evaluation = $this->service->saveEvaluation([
            'user_id' => $teacher->id,
            'academic_year' => '2025/2026',
            'punctuality_score' => 4.5,
            'performance_score' => 4.0,
            'conduct_score' => 4.5,
            'initiative_score' => 4.0,
            'pedagogical_score' => 17.00,
            'last_inspection_date' => '2025-11-10',
            'evaluator_notes' => 'مردود متميز وعطاء تربوي راقٍ.',
        ], $principal);

        $this->assertEquals(17.00, (float) $evaluation->administrative_score);
        $this->assertEquals(17.00, (float) $evaluation->pedagogical_score);
        $this->assertEquals(17.00, (float) $evaluation->composite_score);
        $this->assertEquals(StaffEvaluationAppreciation::VeryGood, $evaluation->appreciation);
        $this->assertEquals($principal->id, $evaluation->evaluator_id);

        $this->assertDatabaseHas('staff_evaluations', [
            'user_id' => $teacher->id,
            'academic_year' => '2025/2026',
            'administrative_score' => 17.00,
        ]);
    }

    public function test_promote_staff_updates_user_step_and_marks_advancement_approved(): void
    {
        $user = User::factory()->create([
            'current_step' => 4,
            'current_step_date' => Carbon::parse('2022-01-01'),
        ]);

        $advancement = CareerAdvancement::factory()->create([
            'user_id' => $user->id,
            'current_step' => 4,
            'next_step' => 5,
            'status' => 'submitted',
        ]);

        $promotedDate = Carbon::parse('2026-01-01');
        $success = $this->service->promoteStaff(
            $advancement,
            $promotedDate,
            'PV-CAP-04/2026',
            'تمت الترقية بالوتيرة المتوسطة بناء على موافقة اللجنة.'
        );

        $this->assertTrue($success);
        $advancement->refresh();
        $user->refresh();

        $this->assertEquals('approved_by_cap', $advancement->status);
        $this->assertEquals('PV-CAP-04/2026', $advancement->commission_reference);
        $this->assertEquals(5, $user->current_step);
        $this->assertEquals('2026-01-01', $user->current_step_date->format('Y-m-d'));
    }

    public function test_record_movement_persists_staff_movement_ledger(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $movement = $this->service->recordMovement([
            'user_id' => $teacher->id,
            'movement_type' => StaffMovementType::Transfer,
            'status' => 'approved',
            'effective_date' => '2026-09-01',
            'decision_number' => 'DEC-MUT-2026-102',
            'decision_date' => '2026-07-20',
            'source_institution' => 'متوسطة البشير الإبراهيمي',
            'target_institution' => 'متوسطة مالك بن نبي',
            'reason' => 'حركة التنقل السنوية للأساتذة',
        ]);

        $this->assertEquals(StaffMovementType::Transfer, $movement->movement_type);
        $this->assertDatabaseHas('staff_movements', [
            'user_id' => $teacher->id,
            'movement_type' => 'transfer',
            'decision_number' => 'DEC-MUT-2026-102',
        ]);
    }

    public function test_generate_advancement_ledger_csv_contains_utf8_bom_and_headers(): void
    {
        $user = User::factory()->create([
            'name' => 'فاطمة الزهراء بن علي',
            'registration_number' => 'M45612',
            'current_step' => 3,
            'current_step_date' => Carbon::parse('2023-01-01'),
        ]);

        $this->service->syncAdvancementProjections(2026);

        $csv = $this->service->generateAdvancementLedgerCsv(2026);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('اللقب والاسم', $csv);
        $this->assertStringContainsString('فاطمة الزهراء بن علي', $csv);
        $this->assertStringContainsString('الدرجة المقترحة', $csv);
    }

    public function test_print_and_pdf_routes_for_evaluations_and_advancements(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $teacher = User::factory()->create(['role' => Role::Teacher, 'name' => 'محمد العربي']);

        $evaluation = StaffEvaluation::factory()->create([
            'user_id' => $teacher->id,
            'evaluator_id' => $principal->id,
            'academic_year' => '2025/2026',
            'administrative_score' => 17.50,
        ]);

        $this->service->syncAdvancementProjections(2026);

        // 1. Preview Evaluation Bulletin
        $respEval = $this->actingAs($principal)->get(route('print.staff-career.evaluation-sheet', [
            'locale' => 'ar',
            'evaluation' => $evaluation->id,
        ]));
        $respEval->assertStatus(200);
        $respEval->assertSee('بطاقة التنقيط والتقييم الإداري والتربوي السنوي');
        $respEval->assertSee('محمد العربي');

        // 2. Stream PDF Evaluation Bulletin
        $respEvalPdf = $this->actingAs($principal)->get(route('print.staff-career.evaluation-sheet.pdf', [
            'locale' => 'ar',
            'evaluation' => $evaluation->id,
        ]));
        $respEvalPdf->assertStatus(200);

        // 3. Preview Advancement Ledger
        $respLedger = $this->actingAs($principal)->get(route('print.staff-career.advancement-ledger', [
            'locale' => 'ar',
            'year' => 2026,
        ]));
        $respLedger->assertStatus(200);
        $respLedger->assertSee('جدول الترقية في الدرجات لمستخدمي المؤسسة برسم سنة 2026');

        // 4. Stream PDF Advancement Ledger
        $respLedgerPdf = $this->actingAs($principal)->get(route('print.staff-career.advancement-ledger.pdf', [
            'locale' => 'ar',
            'year' => 2026,
        ]));
        $respLedgerPdf->assertStatus(200);

        // 5. Download Advancement CSV
        $respCsv = $this->actingAs($principal)->get(route('staff-career.advancements.export-csv', [
            'locale' => 'ar',
            'year' => 2026,
        ]));
        $respCsv->assertStatus(200);
        $respCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_unauthorized_user_cannot_access_career_routes(): void
    {
        $teacher1 = User::factory()->create(['role' => Role::Teacher]);
        $teacher2 = User::factory()->create(['role' => Role::Teacher]);

        $evaluation = StaffEvaluation::factory()->create(['user_id' => $teacher1->id]);

        // Teacher 2 cannot view Teacher 1's evaluation sheet
        $response = $this->actingAs($teacher2)->get(route('print.staff-career.evaluation-sheet', [
            'locale' => 'ar',
            'evaluation' => $evaluation->id,
        ]));
        $response->assertStatus(403);

        // Teacher 2 cannot access the Advancement Ledger
        $responseLedger = $this->actingAs($teacher2)->get(route('print.staff-career.advancement-ledger', [
            'locale' => 'ar',
            'year' => 2026,
        ]));
        $responseLedger->assertStatus(403);
    }

    public function test_filament_staff_career_hub_can_render_and_perform_actions(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);
        $teacher = User::factory()->create(['role' => Role::Teacher, 'name' => 'نور الهدى قاسم']);

        $this->actingAs($principal);

        Livewire::test(StaffCareerHub::class)
            ->assertSuccessful()
            ->call('setTab', 'evaluations')
            ->assertSet('activeTab', 'evaluations')
            ->call('openNewEvaluationModal')
            ->assertSet('isEvaluationModalOpen', true)
            ->set('evaluationUserId', $teacher->id)
            ->set('punctualityScore', 5.0)
            ->set('performanceScore', 4.5)
            ->set('conductScore', 5.0)
            ->set('initiativeScore', 4.5)
            ->set('pedagogicalScore', 18.0)
            ->set('evaluatorNotes', 'أستاذة قديرة ذات كفاءة عالية.')
            ->call('saveEvaluation')
            ->assertSet('isEvaluationModalOpen', false);

        $this->assertDatabaseHas('staff_evaluations', [
            'user_id' => $teacher->id,
            'administrative_score' => 19.00,
        ]);

        // Test Movement Modal through Livewire
        Livewire::test(StaffCareerHub::class)
            ->call('setTab', 'movements')
            ->call('openNewMovementModal')
            ->assertSet('isMovementModalOpen', true)
            ->set('movementUserId', $teacher->id)
            ->set('movementType', 'transfer')
            ->set('movementEffectiveDate', '2026-09-01')
            ->set('movementDecisionNumber', 'DEC/2026/555')
            ->call('saveMovement')
            ->assertSet('isMovementModalOpen', false);

        $this->assertDatabaseHas('staff_movements', [
            'user_id' => $teacher->id,
            'decision_number' => 'DEC/2026/555',
        ]);

        // Test Profile Update Modal through Livewire
        Livewire::test(StaffCareerHub::class)
            ->call('setTab', 'profiles')
            ->call('openEditProfileModal', $teacher->id)
            ->assertSet('isProfileModalOpen', true)
            ->set('profileRegistrationNumber', 'MAT-778899')
            ->set('profileCurrentStep', 5)
            ->call('saveProfile')
            ->assertSet('isProfileModalOpen', false);

        $teacher->refresh();
        $this->assertEquals('MAT-778899', $teacher->registration_number);
        $this->assertEquals(5, $teacher->current_step);
    }
}
