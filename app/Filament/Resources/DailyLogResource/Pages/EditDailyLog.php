<?php

namespace App\Filament\Resources\DailyLogResource\Pages;

use App\Enums\DailyLogStatus;
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
            Actions\Action::make('printRegister')
                ->label(__('دفتر النصوص'))
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(function (): string {
                    $classroomId = $this->record->schedule?->classroom_id
                        ?? $this->record->schedule?->group?->classroom_id;

                    return route('print.register', array_filter([
                        'classroom_id' => $classroomId,
                        'ids' => [$this->record->id],
                    ]));
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->isCompleted()),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === DailyLogStatus::Completed->value && blank($data['actual_content'] ?? null)) {
            $data['actual_content'] = DailyLogResource::generateActualContentFromData(
                title: (string) ($data['title'] ?? ''),
                contentData: is_array($data['content_data'] ?? null) ? $data['content_data'] : [],
            );
        }

        return $data;
    }
}
