<?php

namespace App\Filament\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Resources\EducationalInstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationalInstitution extends CreateRecord
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->is_current) {
            $this->record->makeCurrent();
        }
    }
}
