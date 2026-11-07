<?php

namespace App\Enums;

enum BoardingExitPassStatus: string
{
    case Authorized = 'authorized';
    case Departed = 'departed';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Authorized => __('مرخص ومقبول'),
            self::Departed => __('غادر المؤسسة فعلياً'),
            self::Returned => __('عاد والتحق بالمرقد'),
            self::Overdue => __('متأخر عن موعد العودة'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Authorized => 'info',
            self::Departed => 'warning',
            self::Returned => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
