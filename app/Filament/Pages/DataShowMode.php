<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DataShowMode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 77;

    protected static string $view = 'livewire.data-show-mode';

    protected ?string $maxWidth = 'full';

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public static function getNavigationLabel(): string
    {
        return __('عرض المسلاط');
    }

    public function getTitle(): string
    {
        return __('عرض المسلاط');
    }

    public function mount(): void
    {
        parent::mount();
    }
}
