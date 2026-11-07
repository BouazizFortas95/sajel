<?php

namespace App\Enums;

enum MandateStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('قيد المراجعة والمطابقة'),
            self::Approved => __('مؤشر ومقبول للدفع'),
            self::Paid => __('تم التحويل والدفع'),
            self::Rejected => __('مرفوض من المحاسب'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }
}
