<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LevelResource\Pages;
use App\Models\Level;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LevelResource extends Resource
{
    protected static ?string $model = Level::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function getNavigationLabel(): string
    {
        return __('المستويات');
    }

    public static function getModelLabel(): string
    {
        return __('مستوى');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المستويات');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 20;

    /**
     * The level catalog is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات المستوى'))
                    ->columns(2)
                    ->schema([
                        Select::make('education_phase_id')
                            ->label(__('الأساس التعليمي'))
                            ->relationship('educationPhase', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('الاسم'))
                            ->placeholder(__('السنة الأولى متوسطة'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('grade_number')
                            ->label(__('رقم السنة'))
                            ->placeholder('1')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required()
                            ->helperText(__('الرقم المستخدم في اسم القسم (مثال: 1 للسنة الأولى).')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('الاسم'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('educationPhase.name')
                    ->label(__('الأساس التعليمي'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('classrooms_count')
                    ->label(__('الأقسام'))
                    ->counts('classrooms')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('education_phase_id')
                    ->label(__('الأساس التعليمي'))
                    ->relationship('educationPhase', 'name'),
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
            'index' => Pages\ListLevels::route('/'),
            'create' => Pages\CreateLevel::route('/create'),
            'edit' => Pages\EditLevel::route('/{record}/edit'),
        ];
    }
}
