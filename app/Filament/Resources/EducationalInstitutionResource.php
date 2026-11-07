<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationalInstitutionResource\Pages;
use App\Models\EducationalInstitution;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EducationalInstitutionResource extends Resource
{
    protected static ?string $model = EducationalInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('سجل المؤسسات التربوية');
    }

    public static function getModelLabel(): string
    {
        return __('مؤسسة تربوية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المؤسسات التربوية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageInstitutions() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('الهوية الرسمية للمؤسسة التعليمية'))
                    ->description(__('البيانات الأساسية للمؤسسة المعتمدة لدى وزارة التربية الوطنية.'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('اسم المؤسسة التربوية'))
                                ->placeholder('مثال: متوسطة الشهيد العربي بن مهيدي')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('code')
                                ->label(__('الرقم الميكانيكي / الرمز الوطني'))
                                ->placeholder('مثال: 30-1102-0007')
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                        ]),

                        Grid::make(3)->schema([
                            Select::make('stage')
                                ->label(__('الطور التعليمي'))
                                ->options([
                                    'primary' => __('التعليم الابتدائي (Primaire)'),
                                    'middle' => __('التعليم المتوسط (Moyen)'),
                                    'secondary' => __('التعليم الثانوي (Secondaire)'),
                                    'complex' => __('مجمع مدرسي / قطب تعليمي'),
                                ])
                                ->default('middle')
                                ->required(),

                            TextInput::make('directorate_name')
                                ->label(__('مديرية التربية'))
                                ->placeholder('مثال: مديرية التربية لولاية الوادي')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('wilaya')
                                ->label(__('الولاية'))
                                ->placeholder('مثال: الوادي')
                                ->maxLength(100),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('municipality')
                                ->label(__('البلدية / الدائرة'))
                                ->placeholder('مثال: أم الطيور')
                                ->maxLength(100),

                            TextInput::make('address')
                                ->label(__('العنوان الجغرافي الكامل'))
                                ->maxLength(255),
                        ]),
                    ]),

                Section::make(__('الإدارة والتأطير والتواصل'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('principal_name')
                                ->label(__('اسم مدير(ة) المؤسسة'))
                                ->maxLength(255),

                            TextInput::make('counselor_name')
                                ->label(__('اسم مستشار(ة) التربية'))
                                ->maxLength(255),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('phone')
                                ->label(__('رقم الهاتف الرسمي'))
                                ->tel()
                                ->maxLength(50),

                            TextInput::make('email')
                                ->label(__('البريد الإلكتروني الرسمي'))
                                ->email()
                                ->maxLength(255),
                        ]),
                    ]),

                Section::make(__('المعطيات البيداغوجية والتشغيلية والترخيص'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('capacity')
                                ->label(__('طاقة الاستيعاب المقدرة (تلميذ)'))
                                ->numeric()
                                ->minValue(1),

                            Select::make('regime')
                                ->label(__('نظام التمدرس والدوام'))
                                ->options([
                                    'half_board' => __('نصف داخلي (Demi-pension)'),
                                    'externat' => __('خارجي (Externat)'),
                                    'internat' => __('داخلي (Internat)'),
                                ])
                                ->default('half_board')
                                ->required(),

                            Select::make('status')
                                ->label(__('حالة المؤسسة'))
                                ->options([
                                    'active' => __('نشطة ومفعلة (Active)'),
                                    'pending' => __('قيد التجهيز / التحضير (En préparation)'),
                                    'suspended' => __('معلقة مؤقتاً (Suspendue)'),
                                ])
                                ->default('active')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('academic_year')
                                ->label(__('السنة الدراسية المعتمدة'))
                                ->default('2026-2027')
                                ->required()
                                ->maxLength(20),

                            Toggle::make('is_current')
                                ->label(__('تعيين كمؤسسة نشطة للنظام'))
                                ->helperText(__('تفعيل هذه المؤسسة يجعل وثائق وتقارير المنصة تنطلق من هويتها الرسمية.'))
                                ->default(false),
                        ]),

                        Textarea::make('notes')
                            ->label(__('ملاحظات تقنية وإدارية للمطور'))
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('الرمز / الميكانيكي'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('اسم المؤسسة التربوية'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('stage')
                    ->label(__('الطور'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'primary' => __('ابتدائي'),
                        'middle' => __('متوسط'),
                        'secondary' => __('ثانوي'),
                        'complex' => __('مجمع'),
                        default => $state,
                    })
                    ->colors([
                        'success' => 'primary',
                        'info' => 'middle',
                        'warning' => 'secondary',
                        'danger' => 'complex',
                    ]),

                TextColumn::make('wilaya')
                    ->label(__('الولاية'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('directorate_name')
                    ->label(__('مديرية التربية'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('principal_name')
                    ->label(__('المدير(ة)'))
                    ->searchable()
                    ->placeholder(__('غير محدد')),

                TextColumn::make('status')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('نشطة'),
                        'pending' => __('قيد التجهيز'),
                        'suspended' => __('معلقة'),
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'suspended',
                    ]),

                IconColumn::make('is_current')
                    ->label(__('المؤسسة النشطة'))
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->label(__('الطور التعليمي'))
                    ->options([
                        'primary' => __('ابتدائي'),
                        'middle' => __('متوسط'),
                        'secondary' => __('ثانوي'),
                        'complex' => __('مجمع مدرسي'),
                    ]),

                SelectFilter::make('status')
                    ->label(__('الحالة'))
                    ->options([
                        'active' => __('نشطة'),
                        'pending' => __('قيد التجهيز'),
                        'suspended' => __('معلقة'),
                    ]),

                SelectFilter::make('is_current')
                    ->label(__('المؤسسة النشطة للنظام'))
                    ->options([
                        '1' => __('نعم (المؤسسة الحالية)'),
                        '0' => __('لا (مؤسسات أخرى)'),
                    ]),
            ])
            ->actions([
                Action::make('makeCurrent')
                    ->label(__('تعيين كنشطة'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (EducationalInstitution $record): bool => ! $record->is_current)
                    ->requiresConfirmation()
                    ->modalHeading(__('تعيين المؤسسة الحالية للنظام'))
                    ->modalDescription(__('هل أنت متأكد من تعيين هذه المؤسسة كمؤسسة نشطة للنظام ومزامنة بياناتها الرسمية؟'))
                    ->action(function (EducationalInstitution $record) {
                        $record->makeCurrent();

                        Notification::make()
                            ->title(__('تم تعيين المؤسسة بنجاح'))
                            ->body(__('تمت مزامنة بيانات المؤسسة مع هوية النظام والطباعة الرسمية.'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_current', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducationalInstitutions::route('/'),
            'create' => Pages\CreateEducationalInstitution::route('/create'),
            'edit' => Pages\EditEducationalInstitution::route('/{record}/edit'),
        ];
    }
}
