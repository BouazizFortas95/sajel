<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassroomResource\Pages;
use App\Models\Classroom;
use App\Models\Level;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationLabel(): string
    {
        return __('الأقسام');
    }

    public static function getModelLabel(): string
    {
        return __('قسم');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الأقسام');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 30;

    /**
     * The classroom catalog is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات القسم'))
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
                            ->required(),

                        TextInput::make('class_number')
                            ->label(__('رقم القسم'))
                            ->placeholder(__('1'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(99)
                            ->required()
                            ->helperText(__('يتم توليد اسم القسم تلقائياً بالشكل: رقم السنة + اختصار المرحلة + رقم القسم.')),

                        TextInput::make('academic_year')
                            ->label(__('السنة الدراسية'))
                            ->placeholder(now()->year.'-'.(now()->year + 1))
                            ->default(fn (): string => now()->year.'-'.(now()->year + 1))
                            ->maxLength(255),
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
                    ->label(__('القسم'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('academic_year')
                    ->label(__('السنة الدراسية'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('groups_count')
                    ->label(__('المجموعات'))
                    ->counts('groups')
                    ->badge()
                    ->color('info'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}
