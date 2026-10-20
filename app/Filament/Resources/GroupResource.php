<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Classroom;
use App\Models\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('المجموعات');
    }

    public static function getModelLabel(): string
    {
        return __('مجموعة');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المجموعات');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات المجموعة'))
                    ->columns(2)
                    ->schema([
                        Select::make('classroom_id')
                            ->label(__('القسم'))
                            ->options(fn (): array => Classroom::query()
                                ->with('level.educationPhase')
                                ->get()
                                ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => $classroom->display_name()])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('اسم المجموعة'))
                            ->placeholder(__('المجموعة أ'))
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classroom.level.name')
                    ->label(__('المستوى'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label(__('القسم'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('المجموعة'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label(__('الحصص'))
                    ->counts('schedules')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label(__('القسم'))
                    ->relationship('classroom', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
