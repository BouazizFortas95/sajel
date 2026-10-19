<?php

namespace App\Filament\Resources\DailyLogResource\Pages;

use App\Filament\Resources\DailyLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyLog extends EditRecord
{
    protected static string $resource = DailyLogResource::class;

    /**
     * The save/cancel buttons are provided automatically by EditRecord as
     * form-footer actions (getFormActions()). Only extra header actions
     * belong here.
     */
    protected function getHeaderActions(): array
    {
        return [
            DailyLogResource::confirmCompletionAction(),
            Actions\DeleteAction::make(),
        ];
    }
}
