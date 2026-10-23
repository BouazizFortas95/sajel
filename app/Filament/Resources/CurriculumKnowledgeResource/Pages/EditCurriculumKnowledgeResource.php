<?php

namespace App\Filament\Resources\CurriculumKnowledgeResource\Pages;

use App\Filament\Resources\CurriculumKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumKnowledgeResource extends EditRecord
{
    protected static string $resource = CurriculumKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
