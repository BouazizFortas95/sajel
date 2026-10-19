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

    protected static ?string $navigationLabel = 'الأساسات التعليمية';

    protected static ?string $modelLabel = 'أساس تعليمي';

    protected static ?string $pluralModelLabel = 'الأساسات التعليمية';

    protected static ?string $navigationGroup = 'البنية التربوية';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الأساس التعليمي')
                    ->description('الأساس التعليمي يمثل المرحلة (متوسطة، ابتدائية، ثانوية...) ويحتوي على مستوياته.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('رمز فريد يمثل المرحلة (مثال: middle).'),

                        KeyValue::make('settings')
                            ->label('إعدادات ديناميكية')
                            ->columnSpanFull()
                            ->helperText('إعدادات JSON تُستخدم لاحقاً (مدة الحصة الافتراضية، أيام الراحة...)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('levels_count')
                    ->label('المستويات')
                    ->counts('levels')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_levels')
                    ->label('مربوط بمستويات')
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
