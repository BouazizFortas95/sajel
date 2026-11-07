<?php

namespace App\Enums;

enum BoardingExitPassType: string
{
    case WeekendLeave = 'weekend_leave';
    case HolidayLeave = 'holiday_leave';
    case ExceptionalLeave = 'exceptional_leave';
    case MedicalLeave = 'medical_leave';

    public function label(): string
    {
        return match ($this) {
            self::WeekendLeave => __('خروج عطلة نهاية الأسبوع (Sortie de week-end)'),
            self::HolidayLeave => __('خروج العطلة المدرسية الرسمية (Sortie de vacances)'),
            self::ExceptionalLeave => __('رخصة خروج استثنائية (Sortie exceptionnelle)'),
            self::MedicalLeave => __('خروج صحي واستشفائي (Sortie médicale)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::WeekendLeave => 'primary',
            self::HolidayLeave => 'success',
            self::ExceptionalLeave => 'warning',
            self::MedicalLeave => 'danger',
        };
    }
}
