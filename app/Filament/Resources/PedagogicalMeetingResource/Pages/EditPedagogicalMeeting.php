<?php

namespace App\Filament\Resources\PedagogicalMeetingResource\Pages;

use App\Filament\Resources\PedagogicalMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedagogicalMeeting extends EditRecord
{
    protected static string $resource = PedagogicalMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
