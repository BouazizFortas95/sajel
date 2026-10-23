<?php

namespace App\Filament\Resources\CurriculumSequenceResource\Pages;

use App\Filament\Resources\CurriculumSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumSequence extends EditRecord
{
    protected static string $resource = CurriculumSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
