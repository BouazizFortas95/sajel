<?php

namespace App\Enums;

enum InventoryRecolementStatus: string
{
    case Conformed = 'conformed';
    case Discrepancy = 'discrepancy';
    case UnderInvestigation = 'under_investigation';

    public function label(): string
    {
        return match ($this) {
            self::Conformed => __('مطابق ومعتمد'),
            self::Discrepancy => __('توجد فروقات قيد التسوية'),
            self::UnderInvestigation => __('قيد التحقيق والتعويض'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Conformed => 'success',
            self::Discrepancy => 'warning',
            self::UnderInvestigation => 'danger',
        };
    }
}

