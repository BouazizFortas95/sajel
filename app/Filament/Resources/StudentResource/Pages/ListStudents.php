<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(StudentImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->label(__('استيراد التلاميذ')),
        ];
    }
}
