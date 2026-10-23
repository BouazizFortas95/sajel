<?php

namespace App\Filament\Resources\AssessmentResource\Pages;

use App\Filament\Resources\AssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessment extends EditRecord
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printGradebook')
                ->label(__('طباعة كشف النقاط'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => AssessmentResource::getPrintUrl($this->getRecord()))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
