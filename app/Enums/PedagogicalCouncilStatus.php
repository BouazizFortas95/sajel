<?php

namespace App\Enums;

enum PedagogicalCouncilStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('مبرمج'),
            self::InProgress => __('جارٍ الانعقاد'),
            self::Completed => __('منعقد وموثق'),
            self::Postponed => __('مؤجل'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Postponed => 'danger',
        };
    }
}
