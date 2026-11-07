<?php

namespace App\Enums;

enum DormitoryAllocationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Transferred = 'transferred';
    case Vacated = 'vacated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('مقيم حالياً بالمرقد'),
            self::Suspended => __('موقوف مؤقتاً'),
            self::Transferred => __('محول لمرقد آخر'),
            self::Vacated => __('أخلى المرقد وسلّم الفراش'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Transferred => 'info',
            self::Vacated => 'gray',
        };
    }
}
