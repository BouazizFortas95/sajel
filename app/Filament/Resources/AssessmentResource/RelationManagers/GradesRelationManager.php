<?php

namespace App\Filament\Resources\AssessmentResource\RelationManagers;

use App\Models\Assessment;
use App\Models\Grade;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GradesRelationManager extends RelationManager
{
    protected static string $relationship = 'grades';

    /**
     * Materialize the roster only when the gradebook section is opened.
     */
    protected static bool $isLazy = true;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('سجل العلامات');
    }

    protected static function getModelLabel(): ?string
    {
        return __('علامة');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('العلامات');
    }

    /**
     * The child policy's viewAny ability cannot receive the owner record, so
     * explicitly gate this relation manager on ownership of the assessment.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! parent::canViewForRecord($ownerRecord, $pageClass)) {
            return false;
        }

        if (! $ownerRecord instanceof Assessment) {
            return false;
        }

        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function mount(): void
    {
        parent::mount();

        $ownerRecord = $this->getOwnerRecord();

        if ($ownerRecord instanceof Assessment) {
            $ownerRecord->ensureGradeRecords();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.full_name')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $schedule = $this->getOwnerRecord()->schedule;

                if (! $schedule) {
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->with('student')
                    ->whereHas(
                        'student',
                        fn (Builder $query): Builder => $query->forSchedule($schedule),
                    );
            })
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('الطالب'))
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function (Builder $query) use ($search): void {
                            $query->where(function (Builder $query) use ($search): void {
                                $query
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                        });
                    }),

                Tables\Columns\TextColumn::make('student.registration_number')
                    ->label(__('رقم التسجيل'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextInputColumn::make('score')
                    ->label(__('العلامة'))
                    ->type('number')
                    ->inputMode('decimal')
                    ->step(0.01)
                    ->rules(fn (Grade $record): array => [
                        'numeric',
                        'min:0',
                        'max:'.(float) $record->assessment->max_score,
                    ])
                    ->disabled(fn (Grade $record): bool => auth()->user()?->cannot('update', $record) ?? true)
                    ->validationAttribute(__('العلامة'))
                    ->width('130px'),

                Tables\Columns\TextInputColumn::make('remarks')
                    ->label(__('ملاحظات'))
                    ->placeholder(__('ملاحظة قصيرة'))
                    ->rules(['string', 'max:255'])
                    ->disabled(fn (Grade $record): bool => auth()->user()?->cannot('update', $record) ?? true)
                    ->width('280px'),
            ])
            ->filters([
                Tables\Filters\Filter::make('ungraded')
                    ->label(__('بدون علامة'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('score')),
            ])
            ->defaultSort('student_id')
            ->searchPlaceholder(__('بحث عن تلميذ...'))
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading(__('لا يوجد تلاميذ في هذه الحصة'))
            ->emptyStateDescription(__('أضف التلاميذ إلى المجموعة أو القسم المرتبط بالحصة أولاً.'));
    }
}
