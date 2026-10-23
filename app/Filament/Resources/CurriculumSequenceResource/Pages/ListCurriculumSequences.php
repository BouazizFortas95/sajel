<?php

namespace App\Filament\Resources\CurriculumSequenceResource\Pages;

use App\Filament\Resources\CurriculumSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumSequences extends ListRecords
{
    protected static string $resource = CurriculumSequenceResource::class;

    public function getTitle(): string
    {
        return CurriculumSequenceResource::getPluralModelLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
