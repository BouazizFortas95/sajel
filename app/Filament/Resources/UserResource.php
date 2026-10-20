<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Password;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function getNavigationLabel(): string
    {
        return __('المستخدمون');
    }

    public static function getModelLabel(): string
    {
        return __('مستخدم');
    }

    public static function getPluralModelLabel(): string
    {
        return __('المستخدمون');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة');
    }

    protected static ?int $navigationSort = 90;

    /**
     * Visibility and record actions are enforced by UserPolicy
     * (admin-only); teachers receive a 403 on all user pages.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('حساب المستخدم'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('الاسم'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('البريد الإلكتروني'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label(__('الدور'))
                            ->options(Role::options())
                            ->default(Role::Teacher->value)
                            ->native(false)
                            ->required()
                            ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                            ->helperText(__('لا يمكنك تغيير دور حسابك الحالي.')),

                        Password::make('password')
                            ->label(__('كلمة المرور'))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(PasswordRule::min(8))
                            ->revealable()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(__('اتركها فارغة عند التعديل للإبقاء على كلمة المرور الحالية.')),
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

                Tables\Columns\TextColumn::make('email')
                    ->label(__('البريد الإلكتروني'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label(__('الدور'))
                    ->badge()
                    ->formatStateUsing(fn (Role $state): string => $state->label())
                    ->color(fn (Role $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label(__('الحصص'))
                    ->counts('schedules')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('تاريخ الإنشاء'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('الدور'))
                    ->options(Role::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->authorize('delete')
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
