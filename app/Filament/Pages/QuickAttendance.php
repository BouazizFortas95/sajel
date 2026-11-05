<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class QuickAttendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'livewire.quick-attendance';

    public static function canAccess(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('تسجيل الحضور السريع');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التعليم والتدريس اليومي');
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
