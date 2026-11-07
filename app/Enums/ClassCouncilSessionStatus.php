<?php

namespace App\Enums;

enum ClassCouncilSessionStatus: string
{
    case Draft = 'draft';
    case Deliberated = 'deliberated';
    case Certified = 'certified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة قيد الإعداد'),
            self::Deliberated => __('تمت المداولة'),
            self::Certified => __('محضر مصادق عليه رسميّاً'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Deliberated => 'warning',
            self::Certified => 'success',
        };
    }
}
