<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Resources\DailyLogResource\Pages\EditDailyLog;
use App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\DailyLogRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class DigitalVisaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_stamp_a_completed_log_with_notes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $log = $this->makeLog();
        $reviewedAt = Carbon::parse('2026-10-24 11:00:00');
        Carbon::setTestNow($reviewedAt);

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->callTableAction('digitalVisa', $log, data: [
                'admin_notes' => 'توجيه لمراجعة تخطيط التقويم.',
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified(__('تم التأشير الإداري'));

        $log->refresh();

        $this->assertSame($admin->id, $log->reviewed_by_id);
        $this->assertSame($reviewedAt->toDateTimeString(), $log->reviewed_at?->toDateTimeString());
        $this->assertSame('توجيه لمراجعة تخطيط التقويم.', $log->admin_notes);
        $this->assertTrue($log->isReviewed());

        Carbon::setTestNow();
    }

    public function test_admin_can_bulk_stamp_multiple_completed_logs(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $first = $this->makeLog();
        $second = $this->makeLog();

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->set('selectedTableRecords', [$first->id, $second->id])
            ->callTableBulkAction('digitalVisa', [$first, $second], data: [
                'admin_notes' => 'تمت المراجعة.',
            ])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified(__('تم تأشير :count سجلات بنجاح.', ['count' => 2]));

        foreach ([$first, $second] as $log) {
            $this->assertDatabaseHas('daily_logs', [
                'id' => $log->id,
                'reviewed_by_id' => $admin->id,
                'admin_notes' => 'تمت المراجعة.',
            ]);
            $this->assertNotNull($log->fresh()->reviewed_at);
        }
    }

    public function test_planned_and_cancelled_logs_cannot_be_stamped(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $planned = $this->makeLog(DailyLogStatus::Planned);
        $cancelled = $this->makeLog(DailyLogStatus::Cancelled);

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->assertTableActionHidden('digitalVisa', $planned)
            ->assertTableActionHidden('digitalVisa', $cancelled);

        foreach ([$planned, $cancelled] as $log) {
            try {
                $log->markAsReviewed($admin, 'لا يجب حفظ هذه الملاحظة');
                $this->fail('Incomplete logs must not accept an administrative stamp.');
            } catch (LogicException) {
                $this->assertDatabaseMissing('daily_logs', [
                    'id' => $log->id,
                    'reviewed_by_id' => $admin->id,
                ]);
            }
        }
    }

    public function test_teachers_cannot_invoke_or_spoof_the_administrative_visa_actions(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $log = $this->makeLog(teacher: $teacher);

        Livewire::actingAs($teacher)
            ->test(ListDailyLogs::class)
            ->assertTableActionHidden('digitalVisa', $log)
            ->assertTableBulkActionHidden('digitalVisa');

        $this->actingAs($teacher);

        Livewire::test(EditDailyLog::class, [
            'record' => $log->getRouteKey(),
        ])
            ->fillForm([
                'actual_content' => '<p>درس مكتمل.</p>',
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => now()->toDateTimeString(),
                'admin_notes' => 'Forged stamp',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('daily_logs', [
            'id' => $log->id,
            'reviewed_by_id' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ]);
    }

    public function test_print_and_pdf_register_html_include_the_administrative_visa(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'name' => 'مدير المؤسسة',
        ]);
        $log = $this->makeLog(attributes: [
            'date' => '2026-10-20',
            'homework' => 'تمرين المراجعة',
        ]);
        Carbon::setTestNow('2026-10-24 11:00:00');
        $log->markAsReviewed($admin, 'يرجى متابعة معالجة التعثرات.');

        $logs = app(DailyLogRegisterService::class)->completedLogs([$log->id], $admin);
        $this->assertCount(1, $logs);

        app()->setLocale('ar');

        $this->actingAs($admin)
            ->get(route('print.register', ['ids' => [$log->id]]))
            ->assertOk()
            ->assertSee('تأشيرة الإدارة')
            ->assertSee('مدير المؤسسة')
            ->assertSee('2026-10-24')
            ->assertSee('يرجى متابعة معالجة التعثرات.');

        $pdfHtml = app(DailyLogRegisterService::class)->pdfHtml($logs, $admin);

        $this->assertStringContainsString('تأشيرة الإدارة', $pdfHtml);
        $this->assertStringContainsString('مدير المؤسسة', $pdfHtml);
        $this->assertStringContainsString('يرجى متابعة معالجة التعثرات.', $pdfHtml);

        Carbon::setTestNow();
    }

    private function makeLog(
        DailyLogStatus $status = DailyLogStatus::Completed,
        ?User $teacher = null,
        array $attributes = [],
    ): DailyLog {
        $teacher ??= User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => Classroom::factory()->create()->id,
            'subject_id' => Subject::factory()->create()->id,
        ]);

        return DailyLog::factory()
            ->when($status === DailyLogStatus::Completed, fn ($factory) => $factory->completed())
            ->create([
                'schedule_id' => $schedule->id,
                'status' => $status->value,
                ...$attributes,
            ]);
    }
}
