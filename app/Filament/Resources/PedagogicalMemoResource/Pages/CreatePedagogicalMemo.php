<?php

namespace App\Filament\Resources\PedagogicalMemoResource\Pages;

use App\Filament\Resources\PedagogicalMemoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePedagogicalMemo extends CreateRecord
{
    protected static string $resource = PedagogicalMemoResource::class;

    /**
     * Teachers always own the memos they create, regardless of any submitted
     * user_id. Admins may assign a memo to a specific teacher.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
