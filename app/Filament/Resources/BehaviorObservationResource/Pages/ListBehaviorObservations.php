<?php

namespace App\Filament\Resources\BehaviorObservationResource\Pages;

use App\Filament\Resources\BehaviorObservationResource;
use App\Models\BehaviorObservation;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBehaviorObservations extends ListRecords
{
    protected static string $resource = BehaviorObservationResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isTeacher = $user?->isTeacher() && ! $user?->isAdmin() && ! $user?->isLeadership() && ! $user?->isGeneralSupervisor();

        $trashedCount = BehaviorObservation::onlyTrashed()
            ->when($isTeacher, function (Builder $q) use ($user) {
                $q->where(function (Builder $sub) use ($user) {
                    $sub->where('deleted_by_id', $user?->id)
                        ->orWhere('recorded_by_id', $user?->id);
                });
            })
            ->count();

        return [
            Actions\Action::make('viewTrash')
                ->label(__('سلة المحذوفات'))
                ->icon('heroicon-o-trash')
                ->color($trashedCount > 0 ? 'warning' : 'gray')
                ->badge($trashedCount ?: null)
                ->badgeColor('danger')
                ->tooltip(__('الانتقال إلى صفحة سلة المحذوفات ورؤية التقارير المحذوفة مع إمكانية التراجع عن الحذف'))
                ->url(fn (): string => static::getResource()::getUrl('trash')),

            Actions\CreateAction::make()
                ->label(__('إنشاء تقرير جديد')),
        ];
    }
}
