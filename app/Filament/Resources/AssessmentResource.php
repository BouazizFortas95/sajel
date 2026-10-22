<?php

namespace App\Filament\Resources;

use App\Enums\AssessmentType;
use App\Filament\Resources\AssessmentResource\Pages;
use App\Filament\Resources\AssessmentResource\RelationManagers\GradesRelationManager;
use App\Models\Assessment;
use App\Models\Schedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationLabel(): string
    {
        return __('دفتر العلامات');
    }

    public static function getModelLabel(): string
    {
        return __('تقييم');
    }

    public static function getPluralModelLabel(): string
    {
        return __('التقييمات');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    protected static ?int $navigationSort = 80;

    /**
     * Teachers see only assessments attached to their own schedules.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin()
            ? $query
            : $query->whereHas('schedule', fn (Builder $query) => $query->where('user_id', Auth::id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات التقييم'))
                    ->columns(2)
                    ->schema([
                        Select::make('schedule_id')
                            ->label(__('الحصة'))
                            ->options(function (): array {
                                $query = Schedule::query()
                                    ->with(['subject', 'classroom.level', 'group', 'user']);

                                if (! auth()->user()?->isAdmin()) {
                                    $query->forTeacher(Auth::id());
                                }

                                return $query
                                    ->get()
                                    ->mapWithKeys(function (Schedule $schedule): array {
                                        $label = $schedule->label();

                                        if (auth()->user()?->isAdmin()) {
                                            $label .= ' — '.($schedule->user?->name ?? '');
                                        }

                                        return [$schedule->id => $label];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabledOn('edit')
                            ->rules([
                                Rule::exists('schedules', 'id')->when(
                                    ! auth()->user()?->isAdmin(),
                                    fn (Exists $rule): Exists => $rule->where('user_id', Auth::id()),
                                ),
                            ])
                            ->validationMessages([
                                'exists' => __('الحصة المحددة غير متاحة.'),
                            ])
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? __('لا يمكن تغيير الحصة بعد إنشاء التقييم.')
                                : null),

                        TextInput::make('title')
                            ->label(__('العنوان'))
                            ->placeholder(__('مثال: الفرض الأول'))
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label(__('النوع'))
                            ->options(AssessmentType::options())
                            ->default(AssessmentType::Quiz->value)
                            ->required(),

                        DatePicker::make('date')
                            ->label(__('التاريخ'))
                            ->required()
                            ->default(today()),

                        TextInput::make('max_score')
                            ->label(__('العلامة القصوى'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(999.99)
                            ->step(0.01)
                            ->default(20),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'schedule.subject',
                'schedule.classroom',
                'schedule.group',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('العنوان'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('schedule.subject.name')
                    ->label(__('المادة'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('schedule.classroom.name')
                    ->label(__('القسم'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('schedule.group.name')
                    ->label(__('المجموعة'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('النوع'))
                    ->badge()
                    ->formatStateUsing(fn (AssessmentType $state): string => $state->label())
                    ->color(fn (AssessmentType $state): string => $state->color()),

                Tables\Columns\TextColumn::make('date')
                    ->label(__('التاريخ'))
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_score')
                    ->label(__('العلامة القصوى'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('النوع'))
                    ->options(AssessmentType::options()),
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
            ->defaultSort('date', 'desc')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading(__('لا توجد تقييمات بعد'));
    }

    public static function getRelations(): array
    {
        return [
            GradesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessments::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }
}
