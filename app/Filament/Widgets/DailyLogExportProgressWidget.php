<?php

namespace App\Filament\Widgets;

use App\Filament\Exports\DailyLogExporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Widgets\Widget;

class DailyLogExportProgressWidget extends Widget
{
    protected static string $view = 'filament.widgets.daily-log-export-progress-widget';

    /**
     * Show the progress immediately after the export action starts instead
     * of waiting for the widget's lazy placeholder.
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && static::activeExport() !== null;
    }

    /**
     * Filament re-checks canView() on every Livewire hydrate. The active
     * export disappears as soon as it completes, so without this override
     * the final progress poll would abort with a 403. Keep the login check
     * here, but do not re-check the transient export state.
     */
    public function hydrateCanAuthorizeAccess(): void
    {
        abort_unless(auth()->check(), 403);
    }

    /**
     * @return array{export: Export|null, processed: int, total: int, percentage: int}
     */
    protected function getViewData(): array
    {
        $export = static::activeExport();

        if (! $export instanceof Export) {
            return [
                'export' => null,
                'processed' => 0,
                'total' => 0,
                'percentage' => 0,
            ];
        }

        $total = max(1, (int) $export->total_rows);
        $processed = max(0, min((int) $export->processed_rows, $total));

        return [
            'export' => $export,
            'processed' => $processed,
            'total' => $total,
            'percentage' => (int) round(($processed / $total) * 100),
        ];
    }

    /**
     * The current user's most recent export that is still being processed.
     */
    public static function activeExport(): ?Export
    {
        $userId = auth()->id();

        if ($userId === null) {
            return null;
        }

        return Export::query()
            ->where('user_id', $userId)
            ->where('exporter', DailyLogExporter::class)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();
    }
}
