<?php

namespace App\Enums;

enum ClubActivityStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('مبرمج'),
            self::Completed => __('منجز وموثق'),
            self::Postponed => __('مؤجل'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'warning',
            self::Completed => 'success',
            self::Postponed => 'amber',
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
