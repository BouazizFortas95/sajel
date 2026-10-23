<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumSequenceResource\Pages;
use App\Models\CurriculumDomain;
use App\Models\CurriculumSequence;
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

class CurriculumSequenceResource extends Resource
{
    protected static ?string $model = CurriculumSequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 31;

    public static function getNavigationLabel(): string
    {
        return __('المقاطع التعلمية');
    }

    public static function getModelLabel(): string
    {
        return __('مقطع تعلمي');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المقاطع التعلمية');
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
        return parent::getEloquentQuery()->with(['domain.subject', 'domain.level']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات المقطع'))
                    ->columns(2)
                    ->schema([
                        Select::make('curriculum_domain_id')
                            ->label(__('الميدان'))
                            ->options(fn (): array => CurriculumDomain::query()
                                ->with(['subject', 'level'])
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (CurriculumDomain $domain): array => [
                                    $domain->id => $domain->display_name(),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('اسم المقطع'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('curriculum_domain_id', $get('curriculum_domain_id')),
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
                    ->label(__('اسم المقطع'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain.name')
                    ->label(__('الميدان'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain.subject.name')
                    ->label(__('المادة'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain.level.name')
                    ->label(__('المستوى'))
                    ->formatStateUsing(fn (string $state, CurriculumSequence $record): string => $record->domain?->level?->display_name() ?? $state)
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('curriculum_domain_id')
                    ->label(__('الميدان'))
                    ->relationship('domain', 'name')
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
            'index' => Pages\ListCurriculumSequences::route('/'),
            'create' => Pages\CreateCurriculumSequence::route('/create'),
            'edit' => Pages\EditCurriculumSequence::route('/{record}/edit'),
        ];
    }
}
