<?php

namespace App\Enums;

enum InventoryReformStatus: string
{
    case Proposed = 'proposed';
    case ApprovedByBoard = 'approved_by_board';
    case Discharged = 'discharged';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => __('مقترح للإسقاط من الجرد'),
            self::ApprovedByBoard => __('مصادق عليه من مجلس التوجيه'),
            self::Discharged => __('تمت التصفية والتسليم لأملاك الدولة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Proposed => 'warning',
            self::ApprovedByBoard => 'info',
            self::Discharged => 'success',
        };
    }
}
