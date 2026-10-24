<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Group;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function getNavigationLabel(): string
    {
        return __('التلاميذ');
    }

    public static function getModelLabel(): string
    {
        return __('تلميذ');
    }

    public static function getPluralModelLabel(): string
    {
        return __('التلاميذ');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('شؤون التلاميذ');
    }

    protected static ?int $navigationSort = 45;

    /**
     * Student management is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * Eager-load the hierarchy used by the group labels and table rows.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('group.classroom.level');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات التلميذ'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('الاسم'))
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

                        TextInput::make('last_name')
                            ->label(__('اللقب'))
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

                        TextInput::make('registration_number')
                            ->label(__('رقم التسجيل'))
                            ->placeholder(__('مثال: 2026-0001'))
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

                        DatePicker::make('date_of_birth')
                            ->label(__('تاريخ الميلاد'))
                            ->maxDate(today()),

                        Select::make('group_id')
                            ->label(__('الفوج'))
                            ->relationship(
                                name: 'group',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->with('classroom.level'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Group $record): string => $record->display_name())
                            ->searchable(['name'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('اسم التلميذ'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name', 'first_name'])
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('registration_number')
                    ->label(__('رقم التسجيل'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('group.name')
                    ->label(__('الفوج'))
                    ->description(fn (Student $record): ?string => $record->group?->classroom?->display_name())
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label(__('تاريخ الميلاد'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_id')
                    ->label(__('الفوج'))
                    ->relationship(
                        name: 'group',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->with('classroom.level'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Group $record): string => $record->display_name())
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('printStudentReportCard')
                    ->label(__('طباعة بطاقة متابعة التلميذ'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Student $record): string => route('print.student-report-card', [
                        'locale' => app()->getLocale(),
                        'student' => $record,
                    ]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('openParentPortal')
                    ->label(__('نسخ رابط ولي الأمر'))
                    ->icon('heroicon-o-link')
                    ->color('amber')
                    ->url(fn (Student $record): string => URL::temporarySignedRoute('parent.student-portal', now()->addDays(90), ['locale' => app()->getLocale(), 'student' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('downloadStudentReportCard')
                    ->label(__('تحميل بطاقة المتابعة (PDF)'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Student $record): string => route('print.student-report-card.pdf', [
                        'locale' => app()->getLocale(),
                        'student' => $record,
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('لا يوجد تلاميذ'))
            ->emptyStateDescription(__('أضف أول تلميذ أو استورد قائمة من ملف CSV.'));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
