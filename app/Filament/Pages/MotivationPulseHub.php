<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

class MotivationPulseHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 78;

    protected static string $view = 'filament.pages.motivation-pulse-hub';

    protected ?string $maxWidth = 'full';

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public static function getNavigationLabel(): string
    {
        return __('نبض التميز والتحفيز');
    }

    public function getTitle(): string
    {
        return __('لوحة التميز والتحفيز');
    }

    public function getHeaderFilters(): array
    {
        return [
            Select::make('classroom_id')
                ->label(__('القسم'))
                ->options(fn () => Classroom::query()->with('level')->get()->mapWithKeys(fn ($c) => [$c->id => $c->display_name()])->all())
                ->searchable()
                ->preload(),
        ];
    }
}
