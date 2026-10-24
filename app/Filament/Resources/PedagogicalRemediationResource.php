<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedagogicalRemediationResource\Pages;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\Student;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PedagogicalRemediationResource extends Resource
{
    protected static ?string $model = PedagogicalRemediation::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 79;

    protected static ?string $navigationLabel = 'المعالجة البيداغوجية';

    protected static ?string $navigationGroup = 'التشغيل اليومي';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('student_id')->relationship('student', 'full_name')->required()->label(__('التلميذ')),
            Select::make('schedule_id')
                ->label(__('المادة / الحصة'))
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
            TextInput::make('reason')->label(__('السبب'))->required(),
            Textarea::make('activities')->label(__('أنشطة المعالجة'))->rows(3),
            DatePicker::make('session_date')->label(__('تاريخ الحصة'))->default(today()),
            Toggle::make('attended')->label(__('حضر')),
            Textarea::make('follow_up')->label(__('متابعة')),
            Select::make('status')->label(__('الحالة'))->options([
                'planned' => __('مخطط'),
                'completed' => __('منجز'),
                'ongoing' => __('مستمر'),
            ])->default('planned'),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student', 'schedule.subject']);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('student.full_name')->label(__('التلميذ')),
            TextColumn::make('schedule.subject.name')->label(__('المادة')),
            TextColumn::make('reason')->label(__('السبب'))->limit(30),
            TextColumn::make('session_date')->label(__('التاريخ')),
            TextColumn::make('status')->label(__('الحالة')),
        ])->filters([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPedagogicalRemediations::route('/'), 'create' => Pages\CreatePedagogicalRemediation::route('/create'), 'edit' => Pages\EditPedagogicalRemediation::route('/{record}/edit')];
    }
}
