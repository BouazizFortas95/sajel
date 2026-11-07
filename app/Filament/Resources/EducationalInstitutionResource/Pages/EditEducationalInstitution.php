<?php

namespace App\Filament\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Resources\EducationalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationalInstitution extends EditRecord
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->is_current) {
            $this->record->makeCurrent();
        }
    }
}
