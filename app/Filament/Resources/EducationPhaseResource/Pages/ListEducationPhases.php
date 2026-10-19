<?php

namespace App\Filament\Resources\EducationPhaseResource\Pages;

use App\Filament\Resources\EducationPhaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationPhases extends ListRecords
{
    protected static string $resource = EducationPhaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
