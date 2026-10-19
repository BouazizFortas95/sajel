<?php

namespace App\Enums;

enum LessonPhaseType: string
{
    case Warmup = 'warmup';
    case Discovery = 'discovery';
    case Institutionalization = 'institutionalization';
    case Application = 'application';
    case Evaluation = 'evaluation';

    public function label(): string
    {
        return match ($this) {
            self::Warmup => 'التمهيد / الانطلاق',
            self::Discovery => 'الاكتشاف',
            self::Institutionalization => 'الترسيخ',
            self::Application => 'التطبيق',
            self::Evaluation => 'التقويم',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Warmup => 'info',
            self::Discovery => 'primary',
            self::Institutionalization => 'warning',
            self::Application => 'gray',
            self::Evaluation => 'success',
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
