<?php

namespace App\Filament\Resources\DailyLogResource\Pages;

use App\Filament\Exports\DailyLogExporter;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Widgets\DailyLogExportProgressWidget;
use App\Models\DailyLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyLogs extends ListRecords
{
    protected static string $resource = DailyLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printRegister')
                ->label(__('دفتر النصوص الرسمي'))
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(function (): string {
                    $user = auth()->user();
                    $logs = DailyLog::query()
                        ->completed()
                        ->when(! $user?->isAdmin(), fn ($q) => $q->forTeacher($user))
                        ->latest('date')
                        ->limit(50)
                        ->pluck('id')
                        ->all();

                    return $logs ? route('print.register', ['ids' => $logs]) : '#';
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => DailyLog::query()
                    ->completed()
                    ->when(! auth()->user()?->isAdmin(), fn ($q) => $q->forTeacher(auth()->user()))
                    ->exists()
                ),
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['date'] ??= now()->toDateString();
                    $data['status'] ??= 'planned';

                    return $data;
                }),
            Actions\ExportAction::make()
                ->exporter(DailyLogExporter::class)
                ->label(__('تصدير دفتر النصوص'))
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DailyLogExportProgressWidget::class,
        ];
    }
}
