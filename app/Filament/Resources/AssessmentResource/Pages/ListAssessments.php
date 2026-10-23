<?php

namespace App\Filament\Resources\AssessmentResource\Pages;

use App\Filament\Resources\AssessmentResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAssessments extends ListRecords
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printGradebook')
                ->label(__('طباعة كشف النقاط'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->form([
                    Select::make('assessment_id')
                        ->label(__('التقييم'))
                        ->options(fn (): array => AssessmentResource::getAssessmentOptions())
                        ->searchable()
                        ->required(),
                ])
                ->modalHeading(__('طباعة كشف النقاط'))
                ->modalDescription(__('اختر التقييم الذي تريد طباعة كشف نقاطه.'))
                ->modalSubmitActionLabel(__('طباعة'))
                ->action(function (array $data): void {
                    $assessment = AssessmentResource::getEloquentQuery()
                        ->find($data['assessment_id']);

                    if (! $assessment) {
                        Notification::make()
                            ->title(__('التقييم غير متاح'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->redirect(AssessmentResource::getPrintUrl($assessment));
                }),
            Actions\CreateAction::make(),
        ];
    }
}
