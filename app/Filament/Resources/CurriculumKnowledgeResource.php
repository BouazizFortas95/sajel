<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumKnowledgeResource\Pages;
use App\Models\CurriculumResource;
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

class CurriculumKnowledgeResource extends Resource
{
    protected static ?string $model = CurriculumResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 32;

    public static function getNavigationLabel(): string
    {
        return __('الموارد المعرفية');
    }

    public static function getModelLabel(): string
    {
        return __('مورد معرفي');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الموارد المعرفية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public static function getSlug(): string
    {
        return 'curriculum-knowledge-resources';
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
        return parent::getEloquentQuery()->with(['sequence.domain.subject', 'sequence.domain.level']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات المورد المعرفي'))
                    ->columns(2)
                    ->schema([
                        Select::make('curriculum_sequence_id')
                            ->label(__('المقطع التعلمي'))
                            ->options(fn (): array => CurriculumSequence::query()
                                ->with(['domain.subject', 'domain.level'])
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (CurriculumSequence $sequence): array => [
                                    $sequence->id => implode(' — ', array_filter([
                                        $sequence->domain?->display_name(),
                                        $sequence->name,
                                    ])),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('اسم المورد المعرفي'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('curriculum_sequence_id', $get('curriculum_sequence_id')),
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
                    ->label(__('اسم المورد المعرفي'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sequence.name')
                    ->label(__('المقطع التعلمي'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sequence.domain.name')
                    ->label(__('الميدان'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sequence.domain.subject.name')
                    ->label(__('المادة'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sequence.domain.level.name')
                    ->label(__('المستوى'))
                    ->formatStateUsing(fn (string $state, CurriculumResource $record): string => $record->sequence?->domain?->level?->display_name() ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('الترتيب'))
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('curriculum_sequence_id')
                    ->label(__('المقطع التعلمي'))
                    ->relationship('sequence', 'name')
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
            'index' => Pages\ListCurriculumKnowledgeResources::route('/'),
            'create' => Pages\CreateCurriculumKnowledgeResource::route('/create'),
            'edit' => Pages\EditCurriculumKnowledgeResource::route('/{record}/edit'),
        ];
    }
}
