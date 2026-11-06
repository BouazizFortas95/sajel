<?php

namespace App\Enums;

enum SeminarStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('مبرمجة'),
            self::Completed => __('منجزة'),
            self::Postponed => __('مؤجلة'),
            self::Cancelled => __('ملغاة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Completed => 'success',
            self::Postponed => 'warning',
            self::Cancelled => 'danger',
        };
    }
}

