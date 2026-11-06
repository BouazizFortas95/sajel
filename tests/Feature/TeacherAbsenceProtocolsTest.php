<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\TeacherAbsenceReason;
use App\Enums\TeacherAbsenceStatus;
use App\Filament\Pages\TeacherAbsenceHub;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Services\TeacherAbsenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherAbsenceProtocolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_records_work_resumption_protocol(): void
    {
        $service = app(TeacherAbsenceService::class);
        $teacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-20'),
            'resumed_at' => null,
            'resumption_pv_number' => null,
        ]);

        $this->assertFalse($absence->hasResumed());

        $resumedAbsence = $service->recordWorkResumption($absence, [
            'resumed_at' => '2026-10-21 08:00:00',
            'resumption_pv_number' => 'PV-REP-2026-0042',
            'resumption_notes' => 'استأنف الأستاذ عمله في التوقيت الصباحي بصفة عادية.',
        ]);

        $this->assertTrue($resumedAbsence->hasResumed());
        $this->assertEquals('PV-REP-2026-0042', $resumedAbsence->resumption_pv_number);
        $this->assertEquals('2026-10-21 08:00:00', $resumedAbsence->resumed_at->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('استأنف الأستاذ عمله', $resumedAbsence->resumption_notes);

        // Verify model scopes
        $this->assertTrue(TeacherAbsence::query()->resumed()->where('id', $absence->id)->exists());
        $this->assertFalse(TeacherAbsence::query()->pendingResumption()->where('id', $absence->id)->exists());
    }

    public function test_service_issues_salary_deduction_notice(): void
    {
        $service = app(TeacherAbsenceService::class);
        $teacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-19'),
            'reason' => TeacherAbsenceReason::Unjustified,
            'is_deduction_notified' => false,
            'deduction_days' => null,
        ]);

        $this->assertFalse($absence->hasSalaryDeduction());
        $this->assertEquals(2, $absence->calculateDeductionDays());

        $deductedAbsence = $service->issueSalaryDeductionNotice($absence, [
            'deduction_days' => 2,
            'deduction_ref_number' => 'NOTIF-DED-2026-108',
        ]);

        $this->assertTrue($deductedAbsence->hasSalaryDeduction());
        $this->assertEquals(2, $deductedAbsence->deduction_days);
        $this->assertEquals('NOTIF-DED-2026-108', $deductedAbsence->deduction_ref_number);
        $this->assertNotNull($deductedAbsence->deduction_notified_at);

        // Verify model scope
        $this->assertTrue(TeacherAbsence::query()->deductionNotified()->where('id', $absence->id)->exists());
    }

    public function test_service_issues_administrative_requisition_mandate(): void
    {
        $service = app(TeacherAbsenceService::class);
        $teacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-18'),
            'is_requisition' => false,
        ]);

        $this->assertFalse($absence->hasRequisition());

        $requisitionedAbsence = $service->issueRequisitionMandate($absence, [
            'requisition_ref_number' => 'REQ-ADM-2026-05',
            'requisition_justification' => 'ضرورة المصلحة لتغطية أقسام الامتحانات الرسمية',
        ]);

        $this->assertTrue($requisitionedAbsence->hasRequisition());
        $this->assertEquals('REQ-ADM-2026-05', $requisitionedAbsence->requisition_ref_number);
        $this->assertEquals('ضرورة المصلحة لتغطية أقسام الامتحانات الرسمية', $requisitionedAbsence->requisition_justification);

        // Verify model scope
        $this->assertTrue(TeacherAbsence::query()->requisitions()->where('id', $absence->id)->exists());
    }

    public function test_print_resumption_pv_authorization_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-19'),
            'resumed_at' => Carbon::parse('2026-10-20 08:00:00'),
            'resumption_pv_number' => 'PV-2026-99',
        ]);

        // 1. Admin can access print view
        $this->actingAs($admin)
            ->get(route('print.teacher-absence.resumption-pv', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertOk()
            ->assertSee('محضر استئناف العمل')
            ->assertSee($teacher->name)
            ->assertSee('PV-2026-99');

        // 2. The teacher themself can access print view
        $this->actingAs($teacher)
            ->get(route('print.teacher-absence.resumption-pv', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertOk();

        // 3. Unrelated teacher is forbidden
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.teacher-absence.resumption-pv', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertForbidden();

        // 4. Mocked PDF download
        $this->mock(TeacherAbsenceService::class, function ($mock) use ($absence): void {
            $mock->shouldReceive('generateResumptionPvPdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $absence->id))
                ->andReturn('%PDF-1.4 Mocked Resumption PV');
        });

        $this->actingAs($admin)
            ->get(route('print.teacher-absence.resumption-pv.pdf', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertDownload('pv-reprise-travail-'.$absence->id.'.pdf');
    }

    public function test_print_salary_deduction_notice_authorization_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-19'),
            'is_deduction_notified' => true,
            'deduction_days' => 2,
            'deduction_ref_number' => 'NOTIF-2026-007',
        ]);

        // 1. Admin can view notice
        $this->actingAs($admin)
            ->get(route('print.teacher-absence.deduction-notice', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertOk()
            ->assertSee('إشعار رسمي بالخصم من الراتب')
            ->assertSee($teacher->name)
            ->assertSee('NOTIF-2026-007');

        // 2. Absent teacher can view their notice
        $this->actingAs($teacher)
            ->get(route('print.teacher-absence.deduction-notice', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertOk();

        // 3. Unrelated teacher is forbidden
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.teacher-absence.deduction-notice', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertForbidden();

        // 4. Mocked PDF download
        $this->mock(TeacherAbsenceService::class, function ($mock) use ($absence): void {
            $mock->shouldReceive('generateDeductionNoticePdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $absence->id))
                ->andReturn('%PDF-1.4 Mocked Deduction Notice');
        });

        $this->actingAs($admin)
            ->get(route('print.teacher-absence.deduction-notice.pdf', [
                'locale' => 'ar',
                'absence' => $absence,
            ]))
            ->assertDownload('avis-retenue-salaire-'.$absence->id.'.pdf');
    }

    public function test_print_administrative_requisition_order_authorization_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $substitute = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 1']);
        $subject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-19'),
            'ends_at' => Carbon::parse('2026-10-19'),
            'status' => TeacherAbsenceStatus::Covered,
        ]);

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-19',
            'substitute_teacher_id' => $substitute->id,
            'status' => DailyLogStatus::Planned,
        ]);

        // 1. Admin can view requisition order
        $this->actingAs($admin)
            ->get(route('print.teacher-absence.requisition-order', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertOk()
            ->assertSee('قرار تسخير إداري')
            ->assertSee($substitute->name)
            ->assertSee($teacher->name);

        // 2. Requisitioned substitute can view order
        $this->actingAs($substitute)
            ->get(route('print.teacher-absence.requisition-order', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertOk();

        // 3. Substituted teacher can view order
        $this->actingAs($teacher)
            ->get(route('print.teacher-absence.requisition-order', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertOk();

        // 4. Unrelated teacher is forbidden
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.teacher-absence.requisition-order', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertForbidden();

        // 5. Mocked PDF download
        $this->mock(TeacherAbsenceService::class, function ($mock) use ($absence, $log): void {
            $mock->shouldReceive('generateRequisitionOrderPdf')
                ->once()
                ->with(
                    \Mockery::on(fn ($arg) => $arg->id === $absence->id),
                    \Mockery::on(fn ($arg) => $arg->id === $log->id),
                    \Mockery::any(),
                )
                ->andReturn('%PDF-1.4 Mocked Requisition Order');
        });

        $this->actingAs($admin)
            ->get(route('print.teacher-absence.requisition-order.pdf', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertDownload('ordre-requisition-log-'.$log->id.'.pdf');
    }

    public function test_livewire_teacher_absence_hub_modal_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => Carbon::parse('2026-10-18'),
            'ends_at' => Carbon::parse('2026-10-20'),
            'reason' => TeacherAbsenceReason::Unjustified,
        ]);

        Livewire::actingAs($admin)
            ->test(TeacherAbsenceHub::class)
            // Test Resumption modal flow
            ->call('openResumptionModal', $absence->id)
            ->assertSet('isResumptionModalOpen', true)
            ->assertSet('resumingAbsenceId', $absence->id)
            ->set('resumptionDate', '2026-10-21T08:00')
            ->set('resumptionPvNumber', 'PV-MODAL-TEST')
            ->set('resumptionNotes', 'استأنف بنجاح في الموعد.')
            ->call('confirmResumption')
            ->assertSet('isResumptionModalOpen', false);

        $absence->refresh();
        $this->assertTrue($absence->hasResumed());
        $this->assertEquals('PV-MODAL-TEST', $absence->resumption_pv_number);

        // Test Deduction modal flow
        Livewire::actingAs($admin)
            ->test(TeacherAbsenceHub::class)
            ->call('openDeductionModal', $absence->id)
            ->assertSet('isDeductionModalOpen', true)
            ->assertSet('deductingAbsenceId', $absence->id)
            ->set('deductionDays', 3)
            ->set('deductionRefNumber', 'DED-MODAL-TEST')
            ->call('confirmDeduction')
            ->assertSet('isDeductionModalOpen', false);

        $absence->refresh();
        $this->assertTrue($absence->hasSalaryDeduction());
        $this->assertEquals(3, $absence->deduction_days);
        $this->assertEquals('DED-MODAL-TEST', $absence->deduction_ref_number);

        // Test toggleRequisition action
        Livewire::actingAs($admin)
            ->test(TeacherAbsenceHub::class)
            ->call('toggleRequisition', $absence->id);

        $absence->refresh();
        $this->assertTrue($absence->hasRequisition());
    }
}
