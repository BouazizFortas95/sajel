<?php

namespace App\Enums;

enum SportsGenderTarget: string
{
    case Boys = 'boys';
    case Girls = 'girls';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Boys => __('ذكور فقط (Garçons)'),
            self::Girls => __('إناث فقط (Filles)'),
            self::Mixed => __('مختلط (Mixte)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Boys => 'info',
            self::Girls => 'danger',
            self::Mixed => 'success',
        };
    }
}
