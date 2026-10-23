<?php

namespace App\Filament\Resources\CurriculumDomainResource\Pages;

use App\Filament\Resources\CurriculumDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumDomain extends EditRecord
{
    protected static string $resource = CurriculumDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
