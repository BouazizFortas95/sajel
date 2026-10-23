<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class QuickAttendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-check';

    protected static ?int $navigationSort = 75;

    protected static string $view = 'livewire.quick-attendance';

    public static function canAccess(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('تسجيل الحضور السريع');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public function getTitle(): string
    {
        return __('تسجيل الحضور السريع');
    }

    public function getBreadcrumb(): string
    {
        return __('تسجيل الحضور السريع');
    }
}
