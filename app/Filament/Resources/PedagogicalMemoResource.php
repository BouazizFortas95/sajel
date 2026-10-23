<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\PedagogicalMemoResource\Pages;
use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PedagogicalMemoResource extends Resource
{
    protected static ?string $model = PedagogicalMemo::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Keep the sidebar entry, page title, and breadcrumb in sync.
     *
     * The URL slug intentionally stays "pedagogical-memos" so localized panel
     * routes remain stable (/ar/auth/pedagogical-memos, ...).
     */
    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return __('مذكرة بيداغوجية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المذكرات البيداغوجية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    protected static ?int $navigationSort = 50;

    /**
     * Teachers see only their own memo bank; admins see every memo.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['subject', 'level.educationPhase', 'user']);

        return auth()->user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
    }

    /**
     * Build a locale-aware URL for the printable memo.
     */
    public static function getPrintUrl(PedagogicalMemo $memo): string
    {
        return route('print.pedagogical-memo', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);
    }

    /**
     * Build a locale-aware URL for the downloadable memo PDF.
     */
    public static function getPdfUrl(PedagogicalMemo $memo): string
    {
        return route('print.pedagogical-memo.pdf', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('بيانات المذكرة'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('عنوان المذكرة'))
                            ->placeholder(__('مثال: مذكرة الأعداد الطبيعية'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('subject_id')
                            ->label(__('المادة'))
                            ->options(fn (): array => Subject::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        Select::make('level_id')
                            ->label(__('المستوى'))
                            ->options(fn (): array => Level::query()
                                ->with('educationPhase')
                                ->get()
                                ->mapWithKeys(fn (Level $level): array => [
                                    $level->id => $level->display_name(),
                                ])
                                ->all())
                            ->searchable()
                            ->required(),

                        TextInput::make('phase')
                            ->label(__('المقطع التعلمي'))
                            ->placeholder(__('مثال: المقطع الأول'))
                            ->maxLength(255)
                            ->helperText(__('المرحلة أو المقطع ضمن البرنامج السنوي.')),

                        Toggle::make('is_template')
                            ->label(__('نموذج قابل لإعادة الاستخدام'))
                            ->helperText(__('يظهر في بنك المذكرات النموذجية لنسخه بسرعة.'))
                            ->default(false),

                        Select::make('user_id')
                            ->label(__('المعلم'))
                            ->options(fn (): array => User::query()
                                ->where('role', Role::Teacher->value)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->isAdmin() ? null : Auth::id())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->dehydratedWhenHidden()
                            ->helperText(__('عند إنشاء المذكرة بصفتك مديراً، يمكنك إسنادها لأستاذ.')),
                    ]),

                Section::make(__('المحتوى البيداغوجي'))
                    ->columns(2)
                    ->schema([
                        Textarea::make('competence')
                            ->label(__('الكفاءة المستهدفة'))
                            ->placeholder(__('مثال: يحلّ التلميذ وضعيات مشكلة باستعمال الموارد المكتسبة.'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('starting_situation')
                            ->label(__('الوضعية الانطلاقية'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Repeater::make('activities')
                            ->label(__('أنشطة الدرس'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('اسم النشاط'))
                                    ->required(),

                                TextInput::make('duration')
                                    ->label(__('المدة'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(600)
                                    ->suffix(__('دقيقة')),

                                TextInput::make('strategy')
                                    ->label(__('استراتيجية التدريس'))
                                    ->placeholder(__('حوار، عمل في أفواج، عمل فردي...')),

                                Textarea::make('content')
                                    ->label(__('محتوى النشاط'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('إضافة نشاط'))
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                                ? (string) $state['name']
                                : null)
                            ->columnSpanFull(),

                        Textarea::make('resources')
                            ->label(__('الموارد المعرفية'))
                            ->rows(3),

                        Textarea::make('evaluation')
                            ->label(__('التقويم'))
                            ->rows(3),

                        Textarea::make('notes')
                            ->label(__('ملاحظات المذكرة'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('عنوان المذكرة'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PedagogicalMemo $record): ?string => filled($record->competence)
                        ? Str::limit($record->competence, 70)
                        : null),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('المادة'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('level.name')
                    ->label(__('المستوى'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phase')
                    ->label(__('المقطع التعلمي'))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activity_count')
                    ->label(__('عدد الأنشطة'))
                    ->getStateUsing(fn (PedagogicalMemo $record): int => $record->activityCount())
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('is_template')
                    ->label(__('النماذج'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('نموذج') : __('عادية'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('is_archived')
                    ->label(__('الحالة'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('مؤرشفة') : __('نشطة'))
                    ->color(fn (bool $state): string => $state ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('آخر تحديث'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('المادة'))
                    ->options(fn (): array => Subject::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('level_id')
                    ->label(__('المستوى'))
                    ->options(fn (): array => Level::query()
                        ->with('educationPhase')
                        ->get()
                        ->mapWithKeys(fn (Level $level): array => [
                            $level->id => $level->display_name(),
                        ])
                        ->all())
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_template')
                    ->label(__('النماذج'))
                    ->trueLabel(__('النماذج فقط'))
                    ->falseLabel(__('المذكرات العادية'))
                    ->placeholder(__('الكل')),

                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label(__('الحالة'))
                    ->trueLabel(__('المؤرشفة'))
                    ->falseLabel(__('النشطة'))
                    ->placeholder(__('الكل')),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label(__('طباعة المذكرة'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (PedagogicalMemo $record): string => static::getPrintUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('print', $record) ?? false),

                Tables\Actions\Action::make('downloadPdf')
                    ->label(__('تحميل PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (PedagogicalMemo $record): string => static::getPdfUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('downloadPdf', $record) ?? false),

                static::duplicateTableAction(),

                Tables\Actions\Action::make('archive')
                    ->label(__('أرشفة'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('أرشفة المذكرة'))
                    ->modalDescription(__('ستبقى المذكرة محفوظة ويمكن استعادتها لاحقاً.'))
                    ->modalSubmitActionLabel(__('أرشفة'))
                    ->visible(fn (PedagogicalMemo $record): bool => ! $record->is_archived
                        && (auth()->user()?->can('archive', $record) ?? false))
                    ->action(function (PedagogicalMemo $record): void {
                        $record->archive();

                        Notification::make()
                            ->success()
                            ->title(__('تمت أرشفة المذكرة'))
                            ->send();
                    }),

                Tables\Actions\Action::make('restore')
                    ->label(__('استعادة'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (PedagogicalMemo $record): bool => $record->is_archived
                        && (auth()->user()?->can('archive', $record) ?? false))
                    ->action(function (PedagogicalMemo $record): void {
                        $record->restore();

                        Notification::make()
                            ->success()
                            ->title(__('تمت استعادة المذكرة'))
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
            ->defaultSort('updated_at', 'desc')
            ->emptyStateIcon('heroicon-o-document-duplicate')
            ->emptyStateHeading(__('لا توجد مذكرات بعد'))
            ->emptyStateDescription(__('أنشئ أول مذكرة بيداغوجية واجعلها نموذجاً قابلاً لإعادة الاستخدام.'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    /**
     * Duplicate a memo into the current user's bank.
     */
    public static function duplicateTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('duplicate')
            ->label(__('نسخ المذكرة'))
            ->icon('heroicon-o-document-duplicate')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__('نسخ المذكرة'))
            ->modalDescription(__('سيتم إنشاء نسخة جديدة من المذكرة في بنكك، ويمكنك تعديلها قبل الطباعة.'))
            ->modalSubmitActionLabel(__('نسخ'))
            ->visible(fn (PedagogicalMemo $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
            ->action(function (PedagogicalMemo $record): void {
                $user = Auth::user();

                abort_unless($user instanceof User, 403);

                $copy = $record->duplicateFor($user);

                Notification::make()
                    ->success()
                    ->title(__('تم نسخ المذكرة'))
                    ->body($copy->title)
                    ->actions([
                        NotificationAction::make('edit')
                            ->label(__('فتح النسخة'))
                            ->url(static::getUrl('edit', ['record' => $copy])),
                    ])
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedagogicalMemos::route('/'),
            'create' => Pages\CreatePedagogicalMemo::route('/create'),
            'edit' => Pages\EditPedagogicalMemo::route('/{record}/edit'),
        ];
    }
}
