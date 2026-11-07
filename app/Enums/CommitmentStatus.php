<?php

namespace App\Enums;

enum CommitmentStatus: string
{
    case Committed = 'committed';
    case Liquidated = 'liquidated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Committed => __('ملتزم به قيد الإنجاز'),
            self::Liquidated => __('مصفى ومسدد'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Committed => 'warning',
            self::Liquidated => 'success',
            self::Cancelled => 'gray',
        };
    }
}
