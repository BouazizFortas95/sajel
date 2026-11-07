<?php

namespace App\Enums;

enum MailStatus: string
{
    case Pending   = 'pending';
    case Processed = 'processed';
    case Archived  = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Pending   => __('قيد المعالجة'),
            self::Processed => __('تمت المعالجة'),
            self::Archived  => __('مؤرشف'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'warning',
            self::Processed => 'success',
            self::Archived  => 'gray',
        };
    }
}

