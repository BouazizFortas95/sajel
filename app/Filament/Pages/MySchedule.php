<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\Url;

class MySchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.my-schedule';

    /**
     * "day" shows the interactive daily cards; "week" shows the full grid.
     * Kept in the query string so the chosen view survives reloads.
     */
    #[Url(as: 'view')]
    public string $mode = 'day';

    public static function canAccess(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('جدولي الأسبوعي');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public function getTitle(): string
    {
        return __('جدولي الأسبوعي');
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['day', 'week'], true) ? $mode : 'day';
    }
}
