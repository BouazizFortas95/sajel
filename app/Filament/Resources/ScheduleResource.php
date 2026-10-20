<?php

namespace App\Filament\Resources;

use App\Enums\DayOfWeek;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function getNavigationLabel(): string
    {
        return __('الحصص');
    }

    public static function getModelLabel(): string
    {
        return __('حصة');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الحصص');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    protected static ?int $navigationSort = 60;

    /**
     * Teachers see only their own schedules; admins see everything.
     * Data isolation for other resources lives in their policies.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات الحصة'))
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label(__('المعلم'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            ->required(),

                        Select::make('subject_id')
                            ->label(__('المادة'))
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('classroom_id')
                            ->label(__('القسم'))
                            ->options(fn (): array => Classroom::query()
                                ->with('level.educationPhase')
                                ->get()
                                ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => $classroom->display_name()])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('group_id', null)),

                        Select::make('group_id')
                            ->label(__('المجموعة (اختياري)'))
                            ->options(fn (Get $get): array => filled($get('classroom_id'))
                                ? Group::query()
                                    ->where('classroom_id', $get('classroom_id'))
                                    ->pluck('name', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->disabled(fn (Get $get) => blank($get('classroom_id')))
                            ->helperText(__('اتركه فارغاً لجدولة الحصة على القسم كاملاً.')),

                        Select::make('day_of_week')
                            ->label(__('اليوم'))
                            ->options(DayOfWeek::options())
                            ->native(false)
                            ->required(),
                    ]),

                Section::make(__('توقيت الحصة'))
                    ->description(__('يمنع تجاوز وقت النهاية لوقت البداية.'))
                    ->columns(2)
                    ->schema([
                        TimePicker::make('start_time')
                            ->label(__('بداية الحصة'))
                            ->seconds(false)
                            ->required(),

                        TimePicker::make('end_time')
                            ->label(__('نهاية الحصة'))
                            ->seconds(false)
                            ->required()
                            ->rule(function (Get $get) {
                                return function (string $attribute, callable $fail, mixed $value) use ($get): void {
                                    if (filled($value) && filled($get('start_time')) && $value <= $get('start_time')) {
                                        $fail(__('وقت النهاية يجب أن يكون بعد وقت البداية.'));
                                    }
                                };
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('day_of_week')
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label(__('اليوم'))
                    ->badge()
                    ->formatStateUsing(fn (DayOfWeek $state): string => $state->label())
                    ->color(fn (DayOfWeek $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_range')
                    ->label(__('التوقيت'))
                    ->state(fn (Schedule $record): string => sprintf(
                        '%s - %s',
                        $record->start_time?->format('H:i'),
                        $record->end_time?->format('H:i'),
                    )),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('المادة'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label(__('القسم'))
                    ->description(fn (Schedule $record): ?string => $record->classroom?->level?->name)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('group.name')
                    ->label(__('المجموعة'))
                    ->placeholder(__('القسم كاملاً')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label(__('اليوم'))
                    ->options(DayOfWeek::options()),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('المادة'))
                    ->relationship('subject', 'name'),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
