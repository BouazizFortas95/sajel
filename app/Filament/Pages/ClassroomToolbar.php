<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClassroomToolbar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 76;

    protected static string $view = 'filament.pages.classroom-toolbar';

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
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
