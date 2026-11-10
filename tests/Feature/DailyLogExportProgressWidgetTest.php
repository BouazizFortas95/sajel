<?php

namespace Tests\Feature;

use App\Filament\Exports\DailyLogExporter;
use App\Filament\Widgets\DailyLogExportProgressWidget;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyLogExportProgressWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_widget_is_hidden_when_no_active_export(): void
    {
        $this->actingAs($this->teacher);

        $this->assertFalse(DailyLogExportProgressWidget::canView());
    }

    public function test_widget_renders_and_shows_progress_when_export_is_active(): void
    {
        $this->actingAs($this->teacher);

        $export = Export::create([
            'completed_at' => null,
            'file_disk' => 'local',
            'file_name' => 'test-export',
            'exporter' => DailyLogExporter::class,
            'processed_rows' => 5,
            'total_rows' => 10,
            'successful_rows' => 5,
            'user_id' => $this->teacher->id,
        ]);

        $this->assertTrue(DailyLogExportProgressWidget::canView());

        Livewire::test(DailyLogExportProgressWidget::class)
            ->assertOk()
            ->assertSee('50%')
            ->assertSee(__('جارٍ تصدير دفتر النصوص'));
    }
}
