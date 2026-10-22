<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationPhaseResource\Pages;
use App\Models\EducationPhase;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EducationPhaseResource extends Resource
{
    protected static ?string $model = EducationPhase::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function getNavigationLabel(): string
    {
        return __('الأساسات التعليمية');
    }

    public static function getModelLabel(): string
    {
        return __('أساس تعليمي');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الأساسات التعليمية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('البنية التربوية');
    }

    protected static ?int $navigationSort = 10;

    /**
     * The education phase catalog is admin-only; teachers have no navigation entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('معلومات الأساس التعليمي'))
                    ->description(__('الأساس التعليمي يمثل المرحلة (متوسطة، ابتدائية، ثانوية...) ويحتوي على مستوياته.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('الاسم'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(__('الرمز'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('رمز فريد يمثل المرحلة (مثال: middle).')),

                        TextInput::make('phase_abbreviation')
                            ->label(__('الاختصار'))
                            ->placeholder(__('م'))
                            ->required()
                            ->maxLength(32)
                            ->helperText(__('يُستخدم في توليد اسم القسم (مثال: م للمرحلة المتوسطة).')),

                        KeyValue::make('settings')
                            ->label(__('إعدادات ديناميكية'))
                            ->columnSpanFull()
                            ->helperText(__('إعدادات JSON تُستخدم لاحقاً (مدة الحصة الافتراضية، أيام الراحة...)')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('الاسم'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label(__('الرمز'))
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('levels_count')
                    ->label(__('المستويات'))
                    ->counts('levels')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('تاريخ الإنشاء'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_levels')
                    ->label(__('مربوط بمستويات'))
                    ->query(fn (Builder $query) => $query->has('levels')),
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
            'index' => Pages\ListEducationPhases::route('/'),
            'create' => Pages\CreateEducationPhase::route('/create'),
            'edit' => Pages\EditEducationPhase::route('/{record}/edit'),
        ];
    }
}
