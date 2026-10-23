<?php

namespace App\Filament\Resources\PedagogicalMemoResource\Pages;

use App\Filament\Resources\PedagogicalMemoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedagogicalMemos extends ListRecords
{
    protected static string $resource = PedagogicalMemoResource::class;

    /**
     * Use the same label as the sidebar and breadcrumb instead of Filament's
     * title-cased version ("Pedagogical Memos").
     */
    public function getTitle(): string
    {
        return PedagogicalMemoResource::getPluralModelLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
