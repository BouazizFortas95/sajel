<?php

namespace App\Enums;

enum SportsEquipmentCondition: string
{
    case New = 'new';
    case Good = 'good';
    case Acceptable = 'acceptable';
    case NeedsReplacement = 'needs_replacement';

    public function label(): string
    {
        return match ($this) {
            self::New => __('جديد وممتاز (Neuf)'),
            self::Good => __('حالة جيدة (Bon état)'),
            self::Acceptable => __('حالة مقبولة ومستعمل (État passable)'),
            self::NeedsReplacement => __('تالف وبحاجة للتجديد (Usé / À renouveler)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::New => 'success',
            self::Good => 'primary',
            self::Acceptable => 'warning',
            self::NeedsReplacement => 'danger',
        };
    }
}

