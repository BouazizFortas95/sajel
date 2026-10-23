<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumDomainResource\Pages;
use App\Models\CurriculumDomain;
use App\Models\Level;
use App\Models\Subject;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CurriculumDomainResource extends Resource
{
    protected static ?string $model = CurriculumDomain::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('الميادين');
    }

    public static function getModelLabel(): string
    {
        return __('ميدان');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الميادين');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    /**
     * The curriculum catalog is admin-only; teachers map lessons to it.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['subject', 'level']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات الميدان'))
                    ->columns(2)
                    ->schema([
                        Select::make('subject_id')
                            ->label(__('المادة'))
                            ->options(fn (): array => Subject::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('level_id')
                            ->label(__('المستوى'))
                            ->options(fn (): array => Level::query()
                                ->with('educationPhase')
                                ->get()
                                ->mapWithKeys(fn (Level $level): array => [
                                    $level->id => $level->display_name(),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('اسم الميدان'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('subject_id', $get('subject_id'))
                                    ->where('level_id', $get('level_id')),
                            ),

                        TextInput::make('code')
                            ->label(__('الرمز'))
                            ->maxLength(50),

                        Textarea::make('description')
                            ->label(__('الوصف'))
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('sort_order')
                            ->label(__('الترتيب'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('اسم الميدان'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('المادة'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('level.name')
                    ->label(__('المستوى'))
                    ->formatStateUsing(fn (string $state, CurriculumDomain $record): string => $record->level?->display_name() ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('sequences_count')
                    ->label(__('المقاطع'))
                    ->counts('sequences')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('resources_count')
                    ->label(__('الموارد'))
                    ->counts('resources')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('الترتيب'))
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('المادة'))
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('level_id')
                    ->label(__('المستوى'))
                    ->options(fn (): array => Level::query()
                        ->with('educationPhase')
                        ->get()
                        ->mapWithKeys(fn (Level $level): array => [
                            $level->id => $level->display_name(),
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurriculumDomains::route('/'),
            'create' => Pages\CreateCurriculumDomain::route('/create'),
            'edit' => Pages\EditCurriculumDomain::route('/{record}/edit'),
        ];
    }
}
