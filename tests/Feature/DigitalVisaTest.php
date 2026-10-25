<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Resources\DailyLogResource\Pages\EditDailyLog;
use App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
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

    public function test_string_selected_ids_still_reach_the_bulk_action(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $first = $this->makeLog();
        $second = $this->makeLog();

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->set('selectedTableRecords', [(string) $first->id, (string) $second->id])
            ->callTableBulkAction('digitalVisa', [$first, $second], data: [
                'admin_notes' => 'مراجعة جماعية.',
            ])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified(__('تم تأشير :count سجلات بنجاح.', ['count' => 2]));

        foreach ([$first, $second] as $log) {
            $this->assertDatabaseHas('daily_logs', [
                'id' => $log->id,
                'reviewed_by_id' => $admin->id,
            ]);
        }
    }

    public function test_bulk_action_modal_lists_the_selected_rows(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $first = $this->makeLog();
        $second = $this->makeLog();

        $component = Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->set('selectedTableRecords', [(string) $first->id, (string) $second->id])
            ->call('mountTableBulkAction', 'digitalVisa');

        $component
            ->assertSee(__('تم تحديد :count سجل.', ['count' => 2]))
            ->assertSee($first->title)
            ->assertSee($second->title);
    }

    public function test_daily_log_table_keeps_selection_when_searching_or_filtering(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin);

        $table = Livewire::test(ListDailyLogs::class)->instance()->getTable();

        $this->assertFalse($table->shouldDeselectAllRecordsWhenFiltered());
    }

    public function test_select_all_header_action_targets_every_filtered_record(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $completedLogs = DailyLog::factory()->completed()->count(3)->create();
        DailyLog::factory()->count(2)->create();

        $this->actingAs($admin);

        $component = Livewire::test(ListDailyLogs::class)
            ->assertSee(__('تحديد كل السجلات'));

        $selectAll = collect($component->instance()->getTable()->getHeaderActions())
            ->first(fn ($action): bool => $action->getName() === 'selectAllFiltered');

        $this->assertNotNull($selectAll);
        $this->assertSame('selectAllRecords', $selectAll->getAlpineClickHandler());

        $component->filterTable('status', DailyLogStatus::Completed->value);

        $this->assertEqualsCanonicalizing(
            $completedLogs->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            $component->instance()->getAllSelectableTableRecordKeys(),
        );
    }

    public function test_select_all_filtered_records_reach_the_bulk_visa_action(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        DailyLog::factory()->completed()->count(12)->create();

        $component = Livewire::actingAs($admin)->test(ListDailyLogs::class);
        $keys = $component->instance()->getAllSelectableTableRecordKeys();

        $this->assertCount(12, $keys);

        $component
            ->set('selectedTableRecords', $keys)
            ->call('mountTableBulkAction', 'digitalVisa')
            ->assertSet('mountedTableBulkAction', 'digitalVisa')
            ->set('mountedTableBulkActionData.admin_notes', 'تأشير جميع السجلات.')
            ->call('callMountedTableBulkAction')
            ->assertHasNoTableBulkActionErrors();

        $this->assertSame(12, DailyLog::query()->where('reviewed_by_id', $admin->id)->count());
        $this->assertSame(12, DailyLog::query()->whereNotNull('reviewed_at')->count());
    }

    public function test_bulk_visa_stamps_only_completed_logs_in_a_mixed_selection(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $completed = DailyLog::factory()->completed()->count(2)->create();
        $planned = $this->makeLog(DailyLogStatus::Planned);
        $cancelled = $this->makeLog(DailyLogStatus::Cancelled);

        $selectedIds = $completed
            ->pluck('id')
            ->push($planned->id, $cancelled->id)
            ->all();

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->set('selectedTableRecords', $selectedIds)
            ->call('mountTableBulkAction', 'digitalVisa')
            ->set('mountedTableBulkActionData.admin_notes', 'تأشير المنجز فقط.')
            ->call('callMountedTableBulkAction')
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified(__('تم تأشير :count سجلات بنجاح.', ['count' => 2]));

        foreach ($completed as $log) {
            $this->assertDatabaseHas('daily_logs', [
                'id' => $log->id,
                'reviewed_by_id' => $admin->id,
                'admin_notes' => 'تأشير المنجز فقط.',
            ]);
        }

        foreach ([$planned, $cancelled] as $log) {
            $this->assertDatabaseMissing('daily_logs', [
                'id' => $log->id,
                'reviewed_by_id' => $admin->id,
            ]);
        }
    }

    public function test_bulk_visa_reports_a_selection_without_completed_logs(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $planned = $this->makeLog(DailyLogStatus::Planned);
        $cancelled = $this->makeLog(DailyLogStatus::Cancelled);

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->set('selectedTableRecords', [$planned->id, $cancelled->id])
            ->call('mountTableBulkAction', 'digitalVisa')
            ->set('mountedTableBulkActionData.admin_notes', 'لا يجب حفظها.')
            ->call('callMountedTableBulkAction')
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified(__('لا توجد سجلات منجزة مطابقة للاختيار.'));

        foreach ([$planned, $cancelled] as $log) {
            $this->assertDatabaseMissing('daily_logs', [
                'id' => $log->id,
                'reviewed_by_id' => $admin->id,
            ]);
        }
    }

    public function test_group_scoped_logs_show_the_classroom_section_in_the_table(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create(['name' => '1 م 1']);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        $schedule = Schedule::factory()->create([
            'classroom_id' => null,
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create()->id,
        ]);

        DailyLog::factory()->completed()->create(['schedule_id' => $schedule->id]);

        Livewire::actingAs($admin)
            ->test(ListDailyLogs::class)
            ->assertSee('1 م 1')
            ->assertSee('المجموعة أ');
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
