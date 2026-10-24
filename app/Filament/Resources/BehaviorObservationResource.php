<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BehaviorObservationResource\Pages;
use App\Models\BehaviorObservation;
use App\Models\Schedule;
use App\Models\Student;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BehaviorObservationResource extends Resource
{
    protected static ?string $model = BehaviorObservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-face-smile';

    protected static ?int $navigationSort = 78;

    protected static ?string $navigationLabel = 'الملاحظات والسلوك';

    protected static ?string $navigationGroup = 'التشغيل اليومي';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('student_id')
                ->label(__('التلميذ'))
                ->relationship('student', 'full_name')
                ->required(),
            Select::make('schedule_id')
                ->label(__('الحصة'))
                ->options(fn (): array => Schedule::query()
                    ->when(auth()->user()?->isTeacher(), fn ($query) => $query->forTeacher(auth()->id()))
                    ->with(['user', 'subject', 'classroom', 'group'])
                    ->get()
                    ->mapWithKeys(fn (Schedule $schedule): array => [$schedule->id => $schedule->label()])
                    ->all())
                ->searchable()
                ->preload()
                ->rules(fn (Get $get): array => [
                    function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (blank($value) || blank($get('student_id'))) {
                            return;
                        }

                        $student = Student::query()->with('group')->find($get('student_id'));
                        $schedule = Schedule::query()->find($value);

                        if (
                            ! $student
                            || ! $schedule
                            || (auth()->user()?->isTeacher() && $schedule->user_id !== auth()->id())
                            || (
                                $schedule->group_id !== $student->group_id
                                && ! (
                                    $schedule->group_id === null
                                    && $schedule->classroom_id === $student->group?->classroom_id
                                )
                            )
                        ) {
                            $fail(__('الحصة المحددة لا تخص قسم التلميذ.'));
                        }
                    },
                ]),
            Select::make('category')
                ->label(__('الفئة'))
                ->options([
                    'positive' => __('إيجابي'),
                    'negative' => __('سلبي'),
                    'disciplinary' => __('انضباطي'),
                    'observation' => __('ملاحظة'),
                ])
                ->required(),
            DatePicker::make('date')
                ->label(__('التاريخ'))
                ->default(today()),
            Textarea::make('notes')->label(__('الملاحظة'))->rows(3),
            Toggle::make('is_followed_up')->label(__('تم المتابعة'))->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')->label(__('التلميذ')),
                TextColumn::make('category')->label(__('الفئة')),
                TextColumn::make('date')->label(__('التاريخ')),
                TextColumn::make('is_followed_up')->label(__('متابعة'))->badge()
                    ->formatStateUsing(fn ($s) => $s ? __('نعم') : __('لا')),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('الفئة'))
                    ->options([
                        'positive' => __('إيجابي'),
                        'negative' => __('سلبي'),
                        'disciplinary' => __('انضباطي'),
                        'observation' => __('ملاحظة'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBehaviorObservations::route('/'),
            'create' => Pages\CreateBehaviorObservation::route('/create'),
            'edit' => Pages\EditBehaviorObservation::route('/{record}/edit'),
        ];
    }
}
