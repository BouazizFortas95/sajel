<?php

namespace App\Enums;

enum CounselingSessionStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('مفتوحة'),
            self::InProgress => __('قيد المتابعة'),
            self::Resolved => __('تمت المعالجة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'rose',
            self::InProgress => 'amber',
            self::Resolved => 'emerald',
        };
    }
}
