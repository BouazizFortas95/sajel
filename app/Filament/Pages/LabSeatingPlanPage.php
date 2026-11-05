<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class LabSeatingPlanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.lab-seating-plan';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('مخطط جلوس المخبر');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التعليم والتدريس اليومي');
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
