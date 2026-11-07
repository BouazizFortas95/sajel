<?php

namespace App\Enums;

enum BusRouteStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('خط عامل بانتظام'),
            self::Suspended => __('معلق مؤقتاً'),
            self::Inactive => __('متوقف عن الخدمة'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Inactive => 'danger',
        };
    }
}
