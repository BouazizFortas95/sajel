<?php

namespace App\Filament\Resources;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Closure;
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
use Illuminate\Support\Carbon;
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
                            ->relationship(
                                'user',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('role', Role::Teacher->value),
                            )
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => auth()->user()?->isTeacher() ? Auth::id() : null)
                            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->dehydratedWhenHidden()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $teacher = filled($state)
                                    ? User::query()->find($state)
                                    : null;

                                $set('subject_id', $teacher?->primary_subject_id
                                    ?? $teacher?->secondary_subject_id);
                            })
                            ->required(),

                        Select::make('subject_id')
                            ->label(__('المادة'))
                            ->options(fn (Get $get): array => self::subjectOptions($get))
                            ->default(fn (): ?int => auth()->user()?->isTeacher()
                                ? (auth()->user()->primary_subject_id
                                    ?? auth()->user()->secondary_subject_id)
                                : null)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(fn (Get $get): string => self::subjectHelperText($get))
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, callable $fail) use ($get): void {
                                    $teacher = self::selectedTeacher($get);
                                    $allowedSubjectIds = array_map('intval', array_filter([
                                        $teacher?->primary_subject_id,
                                        $teacher?->secondary_subject_id,
                                    ]));

                                    if (! in_array((int) $value, $allowedSubjectIds, true)) {
                                        $fail(__('المادة المحددة غير مسندة لهذا المعلم.'));
                                    }
                                };
                            }),

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
                            ->required()
                            ->rule(function (): Closure {
                                return function (string $attribute, mixed $value, callable $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    if (! Classroom::query()->whereKey($value)->exists()) {
                                        $fail(__('القسم المحدد غير موجود.'));
                                    }
                                };
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                $groupId = $get('group_id');

                                if (blank($groupId)) {
                                    return;
                                }

                                $group = Group::query()->find($groupId);

                                // Keep the assigned group when the admin picks
                                // its own classroom; reset it only when the new
                                // classroom cannot own that group.
                                if (! $group || (filled($state) && (int) $group->classroom_id !== (int) $state)) {
                                    $set('group_id', null);
                                }
                            }),

                        Select::make('group_id')
                            ->label(__('المجموعة (اختياري)'))
                            ->options(function (Get $get): array {
                                $classroomId = $get('classroom_id');

                                if (filled($classroomId)) {
                                    return Group::query()
                                        ->where('classroom_id', $classroomId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                }

                                // Legacy group-only schedules: keep the assigned
                                // group visible while the classroom is required.
                                $groupId = $get('group_id');

                                return filled($groupId)
                                    ? Group::query()->whereKey($groupId)->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->searchable()
                            ->disabled(fn (Get $get) => blank($get('classroom_id')))
                            ->helperText(__('اتركه فارغاً لجدولة الحصة على القسم كاملاً.'))
                            ->rule(function (Get $get): Closure {
                                return function (string $attribute, mixed $value, callable $fail) use ($get): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $classroomId = $get('classroom_id');

                                    if (blank($classroomId)) {
                                        return;
                                    }

                                    $belongs = Group::query()
                                        ->whereKey($value)
                                        ->where('classroom_id', $classroomId)
                                        ->exists();

                                    if (! $belongs) {
                                        $fail(__('المجموعة المحددة لا تنتمي إلى القسم.'));
                                    }
                                };
                            }),

                        Select::make('day_of_week')
                            ->label(__('اليوم'))
                            ->options(DayOfWeek::options())
                            ->native(false)
                            ->required(),
                    ]),

                Section::make(__('توقيت الحصة'))
                    ->description(__('يمنع تجاوز وقت النهاية لوقت البداية، وتداخل الحصص لنفس المعلم في نفس اليوم.'))
                    ->columns(2)
                    ->schema([
                        TimePicker::make('start_time')
                            ->label(__('بداية الحصة'))
                            ->seconds(false)
                            ->required()
                            ->rule(fn (Get $get, ?Schedule $record): Closure => self::noOverlapRule($get, $record)),

                        TimePicker::make('end_time')
                            ->label(__('نهاية الحصة'))
                            ->seconds(false)
                            ->required()
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, callable $fail) use ($get): void {
                                    if (filled($value) && filled($get('start_time')) && $value <= $get('start_time')) {
                                        $fail(__('وقت النهاية يجب أن يكون بعد وقت البداية.'));
                                    }
                                };
                            })
                            ->rule(fn (Get $get, ?Schedule $record): Closure => self::noOverlapRule($get, $record)),
                    ]),
            ]);
    }

    /**
     * Reject a slot that overlaps (or is identical to) a slot already
     * scheduled for the same teacher on the same day.
     *
     * The rule is attached to both time fields so the pair is highlighted
     * together. The comparison happens in PHP over the teacher's few slots
     * for that day, which stays correct regardless of whether a stored time
     * is "H:i" or "H:i:s".
     */
    private static function noOverlapRule(Get $get, ?Schedule $record): Closure
    {
        return function (string $attribute, mixed $value, callable $fail) use ($get, $record): void {
            $teacherId = self::selectedTeacher($get)?->id;
            $start = $get('start_time');
            $end = $get('end_time');
            $day = $get('day_of_week');

            if (blank($teacherId) || blank($start) || blank($end) || blank($day)) {
                return;
            }

            $start = Carbon::parse($start)->format('H:i');
            $end = Carbon::parse($end)->format('H:i');

            // An inverted range is already reported by the end_time rule.
            if ($end <= $start) {
                return;
            }

            $overlaps = Schedule::query()
                ->where('user_id', $teacherId)
                ->where('day_of_week', $day instanceof DayOfWeek ? $day->value : $day)
                ->when($record?->exists, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                ->get(['id', 'start_time', 'end_time'])
                ->contains(function (Schedule $existing) use ($start, $end): bool {
                    $existingStart = $existing->start_time?->format('H:i');
                    $existingEnd = $existing->end_time?->format('H:i');

                    if ($existingStart === null || $existingEnd === null) {
                        return false;
                    }

                    // Half-open interval comparison: back-to-back slots
                    // (09:00-10:00 after 08:00-09:00) do not overlap.
                    return $existingStart < $end && $existingEnd > $start;
                });

            if ($overlaps) {
                $fail(__('هذا التوقيت يتقاطع مع حصة أخرى لنفس المعلم في نفس اليوم.'));
            }
        };
    }

    private static function selectedTeacher(Get $get): ?User
    {
        $teacherId = auth()->user()?->isAdmin()
            ? $get('user_id')
            : Auth::id();

        if (blank($teacherId)) {
            return null;
        }

        return User::query()
            ->with(['primarySubject', 'secondarySubject'])
            ->find($teacherId);
    }

    /**
     * @return array<int, string>
     */
    private static function subjectOptions(Get $get): array
    {
        $teacher = self::selectedTeacher($get);

        if (! $teacher) {
            return [];
        }

        return collect([$teacher->primarySubject, $teacher->secondarySubject])
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn (Subject $subject): array => [$subject->id => $subject->name])
            ->all();
    }

    private static function subjectHelperText(Get $get): string
    {
        $teacher = self::selectedTeacher($get);

        if (! $teacher) {
            return __('اختر المعلم أولاً.');
        }

        if (blank($teacher->primary_subject_id) && blank($teacher->secondary_subject_id)) {
            return __('لم يتم إسناد أي مادة لهذا المعلم.');
        }

        return __('يمكن اختيار المادة الأساسية أو المادة الثانوية فقط.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('day_of_week')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'classroom.level',
                'group.classroom.level',
            ]))
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

                Tables\Columns\TextColumn::make('section')
                    ->label(__('القسم'))
                    ->getStateUsing(fn (Schedule $record): ?string => $record->sectionName())
                    ->description(fn (Schedule $record): ?string => $record->resolvedClassroom()?->level?->name)
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
