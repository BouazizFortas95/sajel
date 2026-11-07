<?php

namespace App\Enums;

enum OfficialExamCenterStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planning => __('قيد التحضير والتنظيم'),
            self::Active => __('الامتحان جارٍ رسمياً'),
            self::Completed => __('مكتمل ومغلق'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Planning => 'warning',
            self::Active => 'success',
            self::Completed => 'gray',
        };
    }
}
