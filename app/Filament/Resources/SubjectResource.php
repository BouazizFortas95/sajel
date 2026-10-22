<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    public static function getNavigationLabel(): string
    {
        return __('المواد');
    }

    public static function getModelLabel(): string
    {
        return __('مادة');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المواد');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 50;

    /**
     * The subject catalog is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات المادة'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('الاسم'))
                            ->required()
                            ->maxLength(255),

                        ColorPicker::make('color')
                            ->label(__('اللون'))
                            ->default('#4f46e5')
                            ->helperText(__('يُستخدم لتمييز المادة في الجداول والسجلات.')),
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

                Tables\Columns\TextColumn::make('color')
                    ->label(__('اللون'))
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        '<span class="inline-block h-5 w-5 rounded-full ring-1 ring-gray-900/10" style="background-color: '.e($state ?: '#9ca3af').'"></span>'
                    ))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label(__('الحصص'))
                    ->counts('schedules')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
