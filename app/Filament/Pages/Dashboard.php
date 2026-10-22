<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QuickLinks;
use App\Filament\Widgets\UnloggedSessionsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * The view keeps Filament's native page shell around the dashboard content.
     */
    public function getView(): string
    {
        return 'filament.pages.dashboard';
    }

    /**
     * Widgets shown on the Sajel dashboard (stats are now handled by the Livewire component).
     *
     * @return list<class-string>
     */
    public function getWidgets(): array
    {
        return [
            UnloggedSessionsWidget::class,
            QuickLinks::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
