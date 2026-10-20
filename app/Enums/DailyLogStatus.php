<?php

namespace App\Enums;

enum DailyLogStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('مخطط'),
            self::Completed => __('منجز'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
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
