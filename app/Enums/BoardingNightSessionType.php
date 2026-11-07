<?php

namespace App\Enums;

enum BoardingNightSessionType: string
{
    case EveningStudy = 'evening_study';
    case NightSupervision = 'night_supervision';
    case WeekendDuty = 'weekend_duty';

    public function label(): string
    {
        return match ($this) {
            self::EveningStudy => __('مذاكرة مسائية محروسة (Étude du soir)'),
            self::NightSupervision => __('مداومة ليلية بالمراقد (Permanence de nuit)'),
            self::WeekendDuty => __('مداومة نهاية الأسبوع (Permanence de week-end)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::EveningStudy => 'primary',
            self::NightSupervision => 'info',
            self::WeekendDuty => 'warning',
        };
    }
}
