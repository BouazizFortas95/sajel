<?php

namespace App\Enums;

enum DormitoryWing: string
{
    case Boys = 'boys';
    case Girls = 'girls';

    public function label(): string
    {
        return match ($this) {
            self::Boys => __('جناح الذكور (Pavillon Garçons)'),
            self::Girls => __('جناح الإناث (Pavillon Filles)'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Boys => __('ذكور'),
            self::Girls => __('إناث'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Boys => 'info',
            self::Girls => 'danger',
        };
    }
}
