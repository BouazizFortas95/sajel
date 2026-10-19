<?php

namespace App\Filament\Resources\EducationPhaseResource\Pages;

use App\Filament\Resources\EducationPhaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationPhase extends EditRecord
{
    protected static string $resource = EducationPhaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
