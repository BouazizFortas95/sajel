<?php

namespace App\Enums;

enum CanteenSheetStatus: string
{
    case Draft = 'draft';
    case Certified = 'certified';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة يومية'),
            self::Certified => __('مؤشرة ومصادق عليها'),
            self::Archived => __('مؤرشفة نهائياً'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Certified => 'success',
            self::Archived => 'gray',
        };
    }
}
