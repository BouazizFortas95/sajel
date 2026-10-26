<?php

namespace App\Filament\Resources\LabResource\RelationManagers;

use App\Enums\LabStatus;
use App\Models\Lab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LabStationsRelationManager extends RelationManager
{
    protected static string $relationship = 'stations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('محطات المختبر');
    }

    protected static function getModelLabel(): ?string
    {
        return __('محطة');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('المحطات');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! parent::canViewForRecord($ownerRecord, $pageClass)) {
            return false;
        }

        return $ownerRecord instanceof Lab && (auth()->user()?->isAdmin() ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('station_number')
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderBy('station_number');
            })
            ->columns([
                Tables\Columns\TextColumn::make('station_number')
                    ->label(__('رقم المحطة'))
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('device_type')
                    ->label(__('نوع الجهاز'))
                    ->badge()
                    ->searchable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label(__('الحالة'))
                    ->options(LabStatus::options())
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('ملاحظات'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('station_number')
            ->searchPlaceholder(__('بحث عن محطة...'))
            ->emptyStateIcon('heroicon-o-device-tablet')
            ->emptyStateHeading(__('لا توجد محطات في هذا المختبر'))
            ->emptyStateDescription(__('أضف محطات للمختبر لتحديد عدد الأجهزة وحالة كل منها.'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('station_number')
                    ->label(__('رقم المحطة'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->required(),

                Select::make('device_type')
                    ->label(__('نوع الجهاز'))
                    ->options([
                        'computer' => __('حاسوب'),
                        'tablet' => __('حاسوب لوحي'),
                        'device' => __('Device'),
                    ])
                    ->required(),

                Select::make('status')
                    ->label(__('الحالة'))
                    ->options(LabStatus::options())
                    ->required()
                    ->default(LabStatus::Working->value),

                TextInput::make('notes')
                    ->label(__('ملاحظات'))
                    ->maxLength(1000),
            ]);
    }
}
