<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActiveLearningStrategyResource\Pages;
use App\Models\ActiveLearningStrategy;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActiveLearningStrategyResource extends Resource
{
    protected static ?string $model = ActiveLearningStrategy::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'استراتيجيات التعلم النشط';

    protected static ?string $navigationGroup = 'التشغيل اليومي';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('اسم الاستراتيجية'))
                    ->required()
                    ->maxLength(200),

                TextInput::make('slug')
                    ->label(__('المفتاح (Slug)'))
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label(__('الوصف'))
                    ->rows(3),

                TextInput::make('icon')
                    ->label(__('أيقونة Heroicon'))
                    ->default('heroicon-o-light-bulb')
                    ->maxLength(100),

                ColorPicker::make('color')
                    ->label(__('اللون'))
                    ->default('info'),

                TagsInput::make('tags')
                    ->label(__('وسوم'))
                    ->suggestions([
                        'collaborative',
                        'individual',
                        'discussion',
                        'assessment',
                        'visual',
                        'peer-teaching',
                        'simulation',
                    ]),

                Toggle::make('is_active')
                    ->label(__('نشط'))
                    ->default(true),

                TextInput::make('sort_order')
                    ->label(__('ترتيب'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label(__('أيقونة'))
                    ->icon(fn (string $state): string => $state ?: 'heroicon-o-light-bulb')
                    ->color('primary'),

                ColorColumn::make('color')
                    ->label(__('اللون')),

                TextColumn::make('name')
                    ->label(__('الاسم'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('المفتاح'))
                    ->searchable(),

                TextColumn::make('tags')
                    ->label(__('الوسوم'))
                    ->badge()
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->label(__('ترتيب'))
                    ->sortable()
                    ->numeric(),

                TextColumn::make('is_active')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('نشط') : __('غير نشط'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('الحالة'))
                    ->options([
                        true => __('نشط'),
                        false => __('غير نشط'),
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActiveLearningStrategies::route('/'),
            'create' => Pages\CreateActiveLearningStrategy::route('/create'),
            'edit' => Pages\EditActiveLearningStrategy::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('استراتيجيةتعلمنشط');
    }

    public static function getPluralModelLabel(): string
    {
        return __('استراتيجيات التعلم النشط');
    }
}
