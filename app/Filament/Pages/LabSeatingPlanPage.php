<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LabSeatingPlanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?int $navigationSort = 60;

    protected static string $view = 'livewire.lab-seating-plan';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('مخطط جلوس المخبر');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    public function getTitle(): string
    {
        return __('مخطط جلوس المخبر');
    }

    public function getBreadcrumb(): string
    {
        return __('مخطط جلوس المخبر');
    }
}
