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
     * Widget classes kept for the dashboard contract and widget tests.
     *
     * The tabbed DashboardStats component embeds QuickLinks in Overview and
     * UnloggedSessionsWidget in Lessons, so the dashboard view does not render
     * this list a second time.
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
