<?php

namespace App\Filament\Resources\PedagogicalMemoResource\Pages;

use App\Filament\Resources\PedagogicalMemoResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPedagogicalMemo extends EditRecord
{
    protected static string $resource = PedagogicalMemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('طباعة المذكرة'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => PedagogicalMemoResource::getPrintUrl($this->getRecord()))
                ->openUrlInNewTab(),

            Actions\Action::make('downloadPdf')
                ->label(__('تحميل PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => PedagogicalMemoResource::getPdfUrl($this->getRecord()))
                ->openUrlInNewTab(),

            Actions\Action::make('duplicate')
                ->label(__('نسخ المذكرة'))
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('نسخ المذكرة'))
                ->modalDescription(__('سيتم إنشاء نسخة جديدة من المذكرة في بنكك، ويمكنك تعديلها قبل الطباعة.'))
                ->modalSubmitActionLabel(__('نسخ'))
                ->action(function (): void {
                    $user = Auth::user();

                    abort_unless($user instanceof User, 403);

                    $copy = $this->getRecord()->duplicateFor($user);

                    Notification::make()
                        ->success()
                        ->title(__('تم نسخ المذكرة'))
                        ->body($copy->title)
                        ->send();

                    $this->redirect(PedagogicalMemoResource::getUrl('edit', ['record' => $copy]));
                }),

            Actions\Action::make('archive')
                ->label(__('أرشفة'))
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('أرشفة المذكرة'))
                ->modalDescription(__('ستبقى المذكرة محفوظة ويمكن استعادتها لاحقاً.'))
                ->modalSubmitActionLabel(__('أرشفة'))
                ->visible(fn (): bool => ! $this->getRecord()->is_archived)
                ->action(function (): void {
                    $this->getRecord()->archive();

                    Notification::make()
                        ->success()
                        ->title(__('تمت أرشفة المذكرة'))
                        ->send();
                }),

            Actions\Action::make('restore')
                ->label(__('استعادة'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->is_archived)
                ->action(function (): void {
                    $this->getRecord()->restore();

                    Notification::make()
                        ->success()
                        ->title(__('تمت استعادة المذكرة'))
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
