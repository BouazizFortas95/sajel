<?php

namespace App\Filament\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Resources\EducationalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationalInstitutions extends ListRecords
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('إضافة مؤسسة تربوية جديدة'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
