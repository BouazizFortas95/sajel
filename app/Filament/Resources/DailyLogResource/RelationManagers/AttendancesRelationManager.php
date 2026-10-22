<?php

namespace App\Filament\Resources\DailyLogResource\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\Student;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    /**
     * Initialize the attendance roster only when the section loads.
     */
    protected static bool $isLazy = true;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('سجل الحضور والغياب');
    }

    protected static function getModelLabel(): ?string
    {
        return __('حضور');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('سجلات الحضور');
    }

    /**
     * The child policy's viewAny ability cannot receive the owner record, so
     * explicitly gate this relation manager on ownership of the daily log.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! parent::canViewForRecord($ownerRecord, $pageClass)) {
            return false;
        }

        if (! $ownerRecord instanceof DailyLog) {
            return false;
        }

        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function mount(): void
    {
        parent::mount();

        $ownerRecord = $this->getOwnerRecord();

        if ($ownerRecord instanceof DailyLog) {
            // Idempotently materialize the roster as "present" rows.
            $ownerRecord->ensureAttendanceRecords();
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

                Tables\Columns\SelectColumn::make('status')
                    ->label(__('الحالة'))
                    ->options(AttendanceStatus::options())
                    ->selectablePlaceholder(false)
                    ->rules(['required'])
                    ->disabled(fn (Attendance $record): bool => auth()->user()?->cannot('update', $record) ?? true)
                    ->width('150px'),

                Tables\Columns\TextInputColumn::make('remarks')
                    ->label(__('ملاحظات'))
                    ->placeholder(__('ملاحظة قصيرة'))
                    ->rules(['nullable', 'string', 'max:255'])
                    ->disabled(fn (Attendance $record): bool => auth()->user()?->cannot('update', $record) ?? true)
                    ->width('260px'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('الحالة'))
                    ->options(AttendanceStatus::options()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('markAllPresent')
                    ->label(__('تعيين الجميع حاضراً'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->getOwnerRecord()->attendances()->exists())
                    ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                    ->action(function (): void {
                        $dailyLog = $this->getOwnerRecord();

                        $dailyLog->ensureAttendanceRecords();

                        $schedule = $dailyLog->schedule;

                        if (! $schedule) {
                            return;
                        }

                        $studentIds = Student::query()
                            ->forSchedule($schedule)
                            ->pluck('id')
                            ->all();

                        $dailyLog->attendances()
                            ->whereIn('student_id', $studentIds)
                            ->update(['status' => AttendanceStatus::Present->value]);

                        Notification::make()
                            ->success()
                            ->title(__('تم تعيين جميع التلاميذ حاضرين'))
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleAbsence')
                    ->label(fn (Attendance $record): string => $record->status === AttendanceStatus::Absent
                        ? __('حاضر')
                        : __('غائب'))
                    ->icon(fn (Attendance $record): string => $record->status === AttendanceStatus::Absent
                        ? 'heroicon-o-check'
                        : 'heroicon-o-x-mark')
                    ->color(fn (Attendance $record): string => $record->status === AttendanceStatus::Absent
                        ? 'success'
                        : 'danger')
                    ->authorize(fn (Attendance $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (Attendance $record): void {
                        $record->update([
                            'status' => $record->status === AttendanceStatus::Absent
                                ? AttendanceStatus::Present
                                : AttendanceStatus::Absent,
                        ]);
                    }),
            ])
            ->defaultSort('student_id')
            ->searchPlaceholder(__('بحث عن تلميذ...'))
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading(__('لا يوجد تلاميذ في هذا القسم'))
            ->emptyStateDescription(__('أضف التلاميذ إلى المجموعة أو القسم المرتبط بالحصة أولاً.'));
    }
}
