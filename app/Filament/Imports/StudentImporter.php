<?php

namespace App\Filament\Imports;

use App\Models\Group;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Throwable;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    /**
     * @return array<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->label(__('الاسم'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->castStateUsing(fn (mixed $state): string => trim((string) $state))
                ->example('أمين'),

            ImportColumn::make('last_name')
                ->label(__('اللقب'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->castStateUsing(fn (mixed $state): string => trim((string) $state))
                ->example('بن علي'),

            ImportColumn::make('registration_number')
                ->label(__('رقم التسجيل'))
                ->rules(['nullable', 'string', 'max:255', 'unique:students,registration_number'])
                ->castStateUsing(fn (mixed $state): ?string => filled($state) ? trim((string) $state) : null)
                ->example('2026-0001'),

            ImportColumn::make('date_of_birth')
                ->label(__('تاريخ الميلاد'))
                ->guess(['date_of_birth', 'birth_date', 'birthdate'])
                ->rules(['nullable', 'date'])
                ->castStateUsing(function (mixed $state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    try {
                        return Carbon::parse((string) $state)->toDateString();
                    } catch (Throwable) {
                        // Return the raw value so the date validation rule
                        // reports the failed row instead of aborting the job.
                        return (string) $state;
                    }
                })
                ->example('2011-09-15'),

            ImportColumn::make('group')
                ->label(__('الفوج'))
                ->guess(['group_id', 'group_name'])
                ->requiredMapping()
                ->rules(['required'])
                ->relationship(
                    name: 'group',
                    resolveUsing: fn (mixed $state): ?Group => static::resolveGroup($state),
                )
                ->example('السنة الأولى متوسطة — 1 م 1 (2025-2026) — المجموعة أ'),
        ];
    }

    public function resolveRecord(): ?Student
    {
        return new Student;
    }

    /**
     * Resolve a CSV group value by ID, exact display label, or a unique
     * group name. Ambiguous names are rejected instead of guessed.
     */
    private static function resolveGroup(mixed $state): ?Group
    {
        $value = trim((string) $state);

        if ($value === '') {
            return null;
        }

        $groups = Group::query()
            ->with('classroom.level')
            ->get();

        $exactMatches = $groups->filter(fn (Group $group): bool => (string) $group->getKey() === $value
            || $group->display_name() === $value);

        if ($exactMatches->count() === 1) {
            return $exactMatches->first();
        }

        if ($exactMatches->count() > 1) {
            return null;
        }

        $nameMatches = $groups->filter(fn (Group $group): bool => trim($group->name) === $value);

        return $nameMatches->count() === 1 ? $nameMatches->first() : null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('تم استيراد :count تلميذ بنجاح.', [
            'count' => number_format($import->successful_rows),
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__('تعذّر استيراد :count صف.', [
                'count' => number_format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
