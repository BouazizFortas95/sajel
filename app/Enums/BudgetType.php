<?php

namespace App\Enums;

enum BudgetType: string
{
    case Expenditure = 'expenditure';
    case Revenue = 'revenue';

    public function label(): string
    {
        return match ($this) {
            self::Expenditure => __('نفقات التسيير والتجهيز'),
            self::Revenue => __('الإيرادات والمداخيل'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Expenditure => 'danger',
            self::Revenue => 'success',
        };
    }
}
