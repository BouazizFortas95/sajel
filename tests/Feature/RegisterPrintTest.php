<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Exports\DailyLogExporter;
use App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs;
use App\Filament\Widgets\DailyLogExportProgressWidget;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\DailyLogRegisterService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class RegisterPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_print_own_completed_logs_in_date_order(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $classroom = Classroom::factory()->create([
            'name' => 'قسم الاختبار',
            'academic_year' => '2025/2026',
        ]);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);

        $laterLog = $this->createLog($teacher, $classroom, $subject, [
            'date' => '2026-10-20',
            'title' => 'درس لاحق',
            'actual_content' => '<p>محتوى <strong>منجز</strong></p><script>alert(1)</script>',
        ]);

        $earlierLog = $this->createLog($teacher, $classroom, $subject, [
            'date' => '2026-10-18',
            'title' => 'درس سابق',
        ]);

        $response = $this->actingAs($teacher)->get(route('print.register', [
            'ids' => [$laterLog->id, $earlierLog->id],
        ]));

        $response->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('دفتر النصوص الرسمي')
            ->assertSee('الأستاذة أمينة')
            ->assertSee('2025/2026')
            ->assertSee('قسم الاختبار')
            ->assertSee('الرياضيات')
            ->assertSee('محتوى')
            ->assertDontSee('<script>alert', false)
            ->assertSee('window.print()', false)
            ->assertSeeInOrder(['درس سابق', 'درس لاحق']);
    }

    public function test_print_view_excludes_other_teachers_and_non_completed_logs(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $ownCompleted = $this->createLog($teacher, title: 'سجل الأستاذ المنجز');
        $ownPlanned = $this->createLog($teacher, title: 'سجل مخطط', status: DailyLogStatus::Planned);
        $otherCompleted = $this->createLog($otherTeacher, title: 'سجل أستاذ آخر');

        $response = $this->actingAs($teacher)->get(route('print.register', [
            'ids' => [$ownCompleted->id, $ownPlanned->id, $otherCompleted->id],
        ]));

        $response->assertOk()
            ->assertSee('سجل الأستاذ المنجز')
            ->assertDontSee('سجل مخطط')
            ->assertDontSee('سجل أستاذ آخر');
    }

    public function test_admin_can_print_completed_logs_from_any_teacher(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['name' => 'الأستاذ محمد']);

        $log = $this->createLog($teacher, title: 'سجل إداري');

        $response = $this->actingAs($admin)->get(route('print.register', [
            'ids' => [$log->id],
        ]));

        $response->assertOk()
            ->assertSee('سجل إداري')
            ->assertSee('الأستاذ محمد');
    }

    public function test_print_route_validates_selected_ids(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('print.register'))
            ->assertRedirect()
            ->assertSessionHasErrors('ids');
    }

    public function test_guests_are_redirected_to_the_filament_login_page(): void
    {
        $this->get(route('print.register', ['ids' => [1]]))
            ->assertRedirect(route('filament.auth.auth.login', [
                'locale' => app()->getLocale(),
            ]));
    }

    public function test_pdf_bulk_action_is_always_visible_and_configured(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher);

        Livewire::test(ListDailyLogs::class)
            ->assertTableBulkActionExists('downloadRegisterPdf')
            ->assertTableBulkActionVisible('downloadRegisterPdf')
            ->assertTableBulkActionHasIcon('downloadRegisterPdf', 'heroicon-o-arrow-down-tray')
            ->assertTableBulkActionHasLabel('downloadRegisterPdf', __('تحميل دفتر النصوص (PDF)'));
    }

    public function test_pdf_bulk_action_refuses_a_selection_without_completed_logs(): void
    {
        $teacher = User::factory()->create();
        $planned = $this->createLog($teacher, title: 'سجل مخطط', status: DailyLogStatus::Planned);

        $this->actingAs($teacher);

        Livewire::test(ListDailyLogs::class)
            ->set('selectedTableRecords', [$planned->id])
            ->callTableBulkAction('downloadRegisterPdf', [$planned])
            ->assertNotified(__('لا توجد سجلات منجزة مطابقة للاختيار.'));
    }

    public function test_pdf_bulk_action_returns_a_streamed_pdf_download(): void
    {
        $teacher = User::factory()->create();
        $log = $this->createLog($teacher);

        $this->actingAs($teacher);

        $this->mock(DailyLogRegisterService::class, function (MockInterface $mock) use ($log): void {
            $mock->shouldReceive('completedLogs')
                ->once()
                ->andReturn(new EloquentCollection([$log]));

            $mock->shouldReceive('pdfResponse')
                ->once()
                ->andReturn(response()->streamDownload(
                    fn () => print 'pdf-bytes',
                    'register.pdf',
                    ['Content-Type' => 'application/pdf'],
                ));
        });

        Livewire::test(ListDailyLogs::class)
            ->set('selectedTableRecords', [$log->id])
            ->callTableBulkAction('downloadRegisterPdf', [$log])
            ->assertFileDownloaded('register.pdf', 'pdf-bytes', 'application/pdf');
    }

    public function test_export_progress_widget_shows_progress_for_the_current_user(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        Export::query()->create([
            'user_id' => $otherTeacher->id,
            'exporter' => DailyLogExporter::class,
            'file_disk' => 'local',
            'total_rows' => 80,
            'processed_rows' => 60,
            'successful_rows' => 60,
        ]);

        Export::query()->create([
            'user_id' => $teacher->id,
            'exporter' => DailyLogExporter::class,
            'file_disk' => 'local',
            'total_rows' => 40,
            'processed_rows' => 10,
            'successful_rows' => 10,
        ]);

        $this->actingAs($teacher);

        $this->assertTrue(DailyLogExportProgressWidget::canView());

        Livewire::test(DailyLogExportProgressWidget::class)
            ->assertOk()
            ->assertSee('جارٍ تصدير دفتر النصوص')
            ->assertSee('تمت معالجة 10 من 40 سجل.')
            ->assertSee('25%')
            ->assertDontSee('75%')
            ->assertSee('wire:poll.2s', false);

        Livewire::test(ListDailyLogs::class)
            ->assertOk()
            ->assertSee('جارٍ تصدير دفتر النصوص');
    }

    public function test_starting_an_export_shows_the_progress_widget_on_the_list_page(): void
    {
        Queue::fake();

        $teacher = User::factory()->create();
        $this->createLog($teacher);

        $this->actingAs($teacher);

        Livewire::test(ListDailyLogs::class)
            ->callAction('export')
            ->assertOk()
            ->assertSee('جارٍ تصدير دفتر النصوص');

        $this->assertDatabaseHas('exports', [
            'user_id' => $teacher->id,
            'exporter' => DailyLogExporter::class,
        ]);
    }

    public function test_export_progress_widget_is_hidden_without_an_active_export(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher);

        $this->assertFalse(DailyLogExportProgressWidget::canView());

        Export::query()->create([
            'user_id' => $teacher->id,
            'exporter' => DailyLogExporter::class,
            'file_disk' => 'local',
            'total_rows' => 40,
            'processed_rows' => 40,
            'successful_rows' => 40,
            'completed_at' => now(),
        ]);

        $this->assertFalse(DailyLogExportProgressWidget::canView());
    }

    public function test_export_progress_widget_polling_does_not_return_a_403_after_completion(): void
    {
        $teacher = User::factory()->create();

        $export = Export::query()->create([
            'user_id' => $teacher->id,
            'exporter' => DailyLogExporter::class,
            'file_disk' => 'local',
            'total_rows' => 40,
            'processed_rows' => 20,
            'successful_rows' => 20,
        ]);

        $this->actingAs($teacher);

        $widget = Livewire::test(DailyLogExportProgressWidget::class)
            ->assertOk()
            ->assertSee('جارٍ تصدير دفتر النصوص')
            ->assertSee('50%');

        $export->update([
            'processed_rows' => 40,
            'successful_rows' => 40,
            'completed_at' => now(),
        ]);

        $widget
            ->call('$refresh')
            ->assertOk()
            ->assertDontSee('جارٍ تصدير دفتر النصوص');
    }

    public function test_export_download_is_available_to_the_owner_and_admin_only(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $admin = User::factory()->create(['role' => Role::Admin]);

        $export = Export::query()->create([
            'user_id' => $teacher->id,
            'exporter' => DailyLogExporter::class,
            'file_disk' => 'local',
            'file_name' => 'daily-logs',
            'total_rows' => 1,
            'processed_rows' => 1,
            'successful_rows' => 1,
            'completed_at' => now(),
        ]);

        $directory = 'filament_exports/'.$export->getKey();

        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($directory.'/headers.csv', "date\n");
        Storage::disk('local')->put($directory.'/data.csv', "2026-10-22\n");

        $url = route('filament.exports.download', [
            'export' => $export,
            'format' => 'csv',
        ]);

        $this->actingAs($teacher)->get($url)->assertOk();
        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($otherTeacher)->get($url)->assertForbidden();
    }

    public function test_exporter_only_exports_completed_logs(): void
    {
        $completed = $this->createLog(User::factory()->create(), title: 'سجل منجز');
        $planned = $this->createLog(
            User::factory()->create(),
            title: 'سجل مخطط',
            status: DailyLogStatus::Planned,
        );

        $exportedIds = DailyLogExporter::modifyQuery(DailyLog::query())->pluck('id');

        $this->assertTrue($exportedIds->contains($completed->id));
        $this->assertFalse($exportedIds->contains($planned->id));

        $this->assertSame(
            [
                'date',
                'schedule',
                'schedule.subject.name',
                'title',
                'actual_content',
                'homework',
            ],
            collect(DailyLogExporter::getColumns())
                ->map(fn ($column): string => $column->getName())
                ->all(),
        );
    }

    public function test_register_service_normalizes_string_and_invalid_ids(): void
    {
        $teacher = User::factory()->create();
        $log = $this->createLog($teacher, title: 'سجل بمعرف نصي');

        $logs = app(DailyLogRegisterService::class)->completedLogs([
            ' '.$log->id.' ',
            (string) $log->id,
            'abc',
            '0',
            '1.5',
        ], $teacher);

        $this->assertCount(1, $logs);
        $this->assertSame($log->id, $logs->first()?->id);
    }

    public function test_group_scoped_register_resolves_the_classroom_section(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create([
            'name' => '1 م 1',
            'academic_year' => '2025/2026',
        ]);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => null,
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create(['name' => 'الإعلام الآلي'])->id,
        ]);

        $log = DailyLog::factory()->completed()->create([
            'schedule_id' => $schedule->id,
            'title' => 'درس الفوج',
            'date' => '2026-10-20',
        ]);

        $this->actingAs($teacher)
            ->get(route('print.register', ['ids' => [(string) $log->id]]))
            ->assertOk()
            ->assertSee('1 م 1')
            ->assertSee('المجموعة أ')
            ->assertSee('درس الفوج');
    }

    private function createLog(
        User $teacher,
        ?Classroom $classroom = null,
        ?Subject $subject = null,
        array $attributes = [],
        string $title = 'درس تجريبي',
        DailyLogStatus $status = DailyLogStatus::Completed,
    ): DailyLog {
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => ($classroom ?? Classroom::factory()->create())->id,
            'subject_id' => ($subject ?? Subject::factory()->create())->id,
            'group_id' => null,
        ]);

        return DailyLog::factory()
            ->when($status === DailyLogStatus::Completed, fn ($factory) => $factory->completed())
            ->create([
                'schedule_id' => $schedule->id,
                'status' => $status->value,
                'title' => $title,
                ...$attributes,
            ]);
    }
}
