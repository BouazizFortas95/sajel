<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QuickLinks;
use App\Filament\Widgets\SajelStats;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    /**
     * Widgets shown on the Sajel dashboard: overview stats,
     * quick navigation links, then the account widget.
     *
     * @return list<class-string>
     */
    public function getWidgets(): array
    {
        return [
            SajelStats::class,
            QuickLinks::class,
            AccountWidget::class,
        ];
    }
}
