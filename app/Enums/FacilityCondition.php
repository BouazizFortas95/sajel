<?php

namespace App\Enums;

enum FacilityCondition: string
{
    case Good = 'good';
    case Acceptable = 'acceptable';
    case NeedsRestoration = 'needs_restoration';
    case Dilapidated = 'dilapidated';

    public function label(): string
    {
        return match ($this) {
            self::Good => __('حالة جيدة وممتازة (Bon état)'),
            self::Acceptable => __('حالة مقبولة وصالحة (État passable / acceptable)'),
            self::NeedsRestoration => __('بحاجة لترميم وصيانة (Nécessite réhabilitation)'),
            self::Dilapidated => __('متدهور ويشكل خطورة (Vétuste / Dangereux)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Acceptable => 'info',
            self::NeedsRestoration => 'warning',
            self::Dilapidated => 'danger',
        };
    }
}

