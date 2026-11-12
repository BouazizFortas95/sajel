<?php

namespace App\Filament\Resources\BehaviorObservationResource\Pages;

use App\Filament\Resources\BehaviorObservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrashBehaviorObservations extends ListRecords
{
    protected static string $resource = BehaviorObservationResource::class;

    public function getTitle(): string
    {
        return __('سلة محذوفات تقارير السلوك وتسيير القسم');
    }

    public function getBreadcrumb(): string
    {
        return __('سلة المحذوفات');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $isTeacher = $user?->isTeacher() && ! $user?->isAdmin() && ! $user?->isLeadership() && ! $user?->isGeneralSupervisor();

        return parent::table($table)
            ->filters([])
            ->modifyQueryUsing(function (Builder $query) use ($user, $isTeacher): Builder {
                $query->onlyTrashed();

                if ($isTeacher) {
                    $query->where(function (Builder $q) use ($user) {
                        $q->where('deleted_by_id', $user?->id)
                            ->orWhere('recorded_by_id', $user?->id);
                    });
                }

                return $query;
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backToActive')
                ->label(__('العودة إلى التقارير النشطة'))
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }
}
