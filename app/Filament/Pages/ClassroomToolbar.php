<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClassroomToolbar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.classroom-toolbar';

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
        return __('أدوات الحصة التفاعلية');
    }

    public function getTitle(): string
    {
        return __('أدوات الحصة التفاعلية');
    }
}
