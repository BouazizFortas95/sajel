<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TreasuryTransfer = 'treasury_transfer';
    case Cheque = 'cheque';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::TreasuryTransfer => __('تحويل عبر الخزينة العمومية'),
            self::Cheque => __('شيك بريدي / بنكي'),
            self::Cash => __('نقداً من صندوق المؤسسة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TreasuryTransfer => 'primary',
            self::Cheque => 'info',
            self::Cash => 'warning',
        };
    }
}
