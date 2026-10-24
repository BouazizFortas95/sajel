<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedagogicalMeetingResource\Pages;
use App\Models\PedagogicalMeeting;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PedagogicalMeetingResource extends Resource
{
    protected static ?string $model = PedagogicalMeeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 27;

    public static function getNavigationLabel(): string
    {
        return __('دفتر الندوات');
    }

    public static function getModelLabel(): string
    {
        return __('ندوة تربوية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الندوات التربوية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('user')
            ->orderByDesc('date');

        return Auth::user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('meeting_type')
                ->label(__('نوع الاجتماع'))
                ->options([
                    'inspector_seminar' => __('ندوة تكوينية مع المفتش'),
                    'coordination_meeting' => __('اجتماع تنسيقي للمادة'),
                    'class_council' => __('مجلس القسم'),
                    'pedagogical_council' => __('مجلس التعليم'),
                ])
                ->required(),

            TextInput::make('title')
                ->label(__('عنوان الندوة'))
                ->required()
                ->maxLength(255),

            DatePicker::make('date')
                ->label(__('التاريخ'))
                ->required(),

            TextInput::make('location')
                ->label(__('المكان'))
                ->nullable(),

            TextInput::make('supervisor_name')
                ->label(__('اسم المؤطر / المفتش'))
                ->nullable(),

            Textarea::make('agenda')
                ->label(__('جدول الأعمال والمحاور'))
                ->required()
                ->rows(3),

            Textarea::make('recommendations')
                ->label(__('التوصيات والتوجيهات التطبيقية'))
                ->nullable()
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('التاريخ'))
                    ->date('Y-m-d'),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('العنوان'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_type')
                    ->label(__('النوع'))
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('الأستاذ'))
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meeting_type')
                    ->label(__('النوع'))
                    ->options([
                        'inspector_seminar' => __('ندوة تكوينية مع المفتش'),
                        'coordination_meeting' => __('اجتماع تنسيقي للمادة'),
                        'class_council' => __('مجلس القسم'),
                        'pedagogical_council' => __('مجلس التعليم'),
                    ]),
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
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedagogicalMeetings::route('/'),
            'create' => Pages\CreatePedagogicalMeeting::route('/create'),
            'edit' => Pages\EditPedagogicalMeeting::route('/{record}/edit'),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Action::make('printPortfolio')
                ->label(__('طباعة ملف الإنجاز (PDF)'))
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('print.teacher-portfolio.pdf', ['locale' => app()->getLocale(), 'user' => Auth::id()]))
                ->openUrlInNewTab(),
        ];
    }
}
