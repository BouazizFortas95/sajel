<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Exports\DailyLogExporter;
use App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_print_bulk_action_is_available_only_for_completed_logs(): void
    {
        $teacher = User::factory()->create();
        $completed = $this->createLog($teacher, title: 'سجل منجز');
        $planned = $this->createLog($teacher, title: 'سجل مخطط', status: DailyLogStatus::Planned);

        $this->actingAs($teacher);

        $component = Livewire::test(ListDailyLogs::class)
            ->assertTableBulkActionExists('printRegister')
            ->assertTableBulkActionHasIcon('printRegister', 'heroicon-o-printer')
            ->assertTableBulkActionHasLabel('printRegister', __('طباعة دفتر النصوص'));

        $component
            ->set('selectedTableRecords', [$completed->id])
            ->assertTableBulkActionVisible('printRegister');

        $component
            ->set('selectedTableRecords', [$planned->id])
            ->assertTableBulkActionHidden('printRegister');
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
                'schedule.classroom.name',
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
