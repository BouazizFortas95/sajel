<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedagogicalRemediationResource\Pages;
use App\Models\PedagogicalRemediation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            TextInput::make('reason')->label(__('السبب'))->required(),
            Textarea::make('activities')->label(__('أنشطة المعالجة'))->rows(3),
            DatePicker::make('session_date')->label(__('تاريخ الحصة'))->default(today()),
            Toggle::make('attended')->label(__('حضر')),
            Textarea::make('follow_up')->label(__('متابعة')),
            Select::make('status')->label(__('الحالة'))->options(['planned' => 'مخطط', 'completed' => 'منجز', 'ongoing' => 'مستمر'])->default('planned'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('student.full_name')->label(__('التلميذ')),
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
