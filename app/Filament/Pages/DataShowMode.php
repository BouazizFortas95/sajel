<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DataShowMode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.data-show-mode';

    protected ?string $maxWidth = 'full';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التعليم والتدريس اليومي');
    }

    public static function getNavigationLabel(): string
    {
        return __('عرض المسلاط');
    }

    public function getTitle(): string
    {
        return __('عرض المسلاط');
    }
}
