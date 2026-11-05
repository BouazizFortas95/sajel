<?php

namespace App\Enums;

enum TeacherAbsenceStatus: string
{
    case Pending = 'pending';
    case Covered = 'covered';
    case PartiallyCovered = 'partially_covered';
    case Uncovered = 'uncovered';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('قيد المعالجة'),
            self::Covered => __('مغطى كلياً بالاستخلاف'),
            self::PartiallyCovered => __('مغطى جزئياً'),
            self::Uncovered => __('شاغر / غير مغطى'),
            self::Completed => __('منقضي'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Covered => 'success',
            self::PartiallyCovered => 'info',
            self::Uncovered => 'danger',
            self::Completed => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
