<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabResource\Pages;
use App\Filament\Resources\LabResource\RelationManagers\LabStationsRelationManager;
use App\Models\Lab;
use App\Models\Level;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LabResource extends Resource
{
    protected static ?string $model = Lab::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationLabel(): string
    {
        return __('المختبرات');
    }

    public static function getModelLabel(): string
    {
        return __('مختبر');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المختبرات');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 50;

    /**
     * The lab catalog is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات المختبر'))
                    ->columns(2)
                    ->schema([
                        Select::make('level_id')
                            ->label(__('المستوى'))
                            ->options(fn (): array => Level::query()
                                ->with('educationPhase')
                                ->get()
                                ->mapWithKeys(fn (Level $level): array => [$level->id => $level->display_name()])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('name')
                            ->label(__('اسم المختبر'))
                            ->placeholder(__('مختبر الحاسوب 1'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('capacity')
                            ->label(__('السعة (عدد المناصب)'))
                            ->placeholder('20')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1000)
                            ->required()
                            ->default(20),

                        TextInput::make('academic_year')
                            ->label(__('السنة الدراسية'))
                            ->placeholder(now()->year.'-'.(now()->year + 1))
                            ->default(fn (): string => now()->year.'-'.(now()->year + 1))
                            ->maxLength(255),
                    ]),

                Section::make(__('ملاحظات'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('ملاحظات'))
                            ->maxLength(1000)
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level.educationPhase.name')
                    ->label(__('الأساس التعليمي'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('level.name')
                    ->label(__('المستوى'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('المختبر'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label(__('السعة'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academic_year')
                    ->label(__('السنة الدراسية'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('stations_count')
                    ->label(__('المناصب المسجلة'))
                    ->counts('stations')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('working_stations_count')
                    ->label(__('عاملة'))
                    ->counts('workingStations')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('broken_stations_count')
                    ->label(__('معطلة'))
                    ->counts('brokenStations')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('under_maintenance_stations_count')
                    ->label(__('قيد الصيانة'))
                    ->counts('underMaintenanceStations')
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level_id')
                    ->label(__('المستوى'))
                    ->relationship('level', 'name'),
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
        return [
            LabStationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabs::route('/'),
            'create' => Pages\CreateLab::route('/create'),
            'edit' => Pages\EditLab::route('/{record}/edit'),
        ];
    }
}
