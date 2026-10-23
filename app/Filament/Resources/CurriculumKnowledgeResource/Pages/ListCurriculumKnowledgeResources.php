<?php

namespace App\Filament\Resources\CurriculumKnowledgeResource\Pages;

use App\Filament\Resources\CurriculumKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumKnowledgeResources extends ListRecords
{
    protected static string $resource = CurriculumKnowledgeResource::class;

    public function getTitle(): string
    {
        return CurriculumKnowledgeResource::getPluralModelLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
