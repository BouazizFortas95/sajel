<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('قيد المعالجة والإجراء'),
            self::Approved => __('مصادق عليه ومكتمل'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'emerald',
            self::Cancelled => 'gray',
        };
    }
}

