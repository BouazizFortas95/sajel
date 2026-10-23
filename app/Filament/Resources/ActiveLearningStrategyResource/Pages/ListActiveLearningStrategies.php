<?php

namespace App\Filament\Resources\ActiveLearningStrategyResource\Pages;

use App\Filament\Resources\ActiveLearningStrategyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActiveLearningStrategies extends ListRecords
{
    protected static string $resource = ActiveLearningStrategyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('إضافة استراتيجية')),
        ];
    }
}
