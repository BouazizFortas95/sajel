<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BehaviorObservationResource\Pages;
use App\Models\BehaviorObservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
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
            Select::make('category')
                ->label(__('الفئة'))
                ->options([
                    'positive' => __('إيجابي'),
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
