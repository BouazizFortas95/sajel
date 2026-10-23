<?php

namespace App\Filament\Resources\CurriculumDomainResource\Pages;

use App\Filament\Resources\CurriculumDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumDomains extends ListRecords
{
    protected static string $resource = CurriculumDomainResource::class;

    public function getTitle(): string
    {
        return CurriculumDomainResource::getPluralModelLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
