<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolHolidayResource\Pages;
use App\Models\SchoolHoliday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SchoolHolidayResource extends Resource
{
    protected static ?string $model = SchoolHoliday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 28;

    public static function getNavigationLabel(): string
    {
        return __('رزنامة العطل');
    }

    public static function getModelLabel(): string
    {
        return __('عطلة رسمية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('العطل الرسمية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('start_date');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->label(__('عنوان العطلة'))->required(),
            DatePicker::make('start_date')->label(__('تاريخ البداية'))->required(),
            DatePicker::make('end_date')->label(__('تاريخ النهاية'))->required(),
            Select::make('type')
                ->label(__('نوع العطلة'))
                ->options([
                    'national' => __('وطنية'),
                    'religious' => __('دينية'),
                    'seasonal' => __('فصلية'),
                    'exceptional' => __('استثنائية'),
                ])
                ->required(),
            Textarea::make('notes')->label(__('ملاحظات'))->nullable()->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('العنوان'))->searchable(),
                Tables\Columns\TextColumn::make('start_date')->label(__('البداية'))->date(),
                Tables\Columns\TextColumn::make('end_date')->label(__('النهاية'))->date(),
                Tables\Columns\TextColumn::make('type')->label(__('النوع')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label(__('النوع'))->options([
                    'national' => __('وطنية'),
                    'religious' => __('دينية'),
                    'seasonal' => __('فصلية'),
                    'exceptional' => __('استثنائية'),
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchoolHolidays::route('/'),
            'create' => Pages\CreateSchoolHoliday::route('/create'),
            'edit' => Pages\EditSchoolHoliday::route('/{record}/edit'),
        ];
    }
}