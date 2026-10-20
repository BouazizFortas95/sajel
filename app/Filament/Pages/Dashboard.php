<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QuickLinks;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    /**
     * The view used to render the dashboard page.
     * Includes the Livewire dashboard-stats component for dark-mode-safe stat cards.
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
            QuickLinks::class,
            AccountWidget::class,
        ];
    }
}
