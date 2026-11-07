<?php

namespace App\Enums;

enum MaintenancePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case Urgent = 'urgent';
    case EmergencySafety = 'emergency_safety';

    public function label(): string
    {
        return match ($this) {
            self::Low => __('عادي / غير مستعجل (Faible priorité)'),
            self::Normal => __('متوسط الأهمية (Priorité normale)'),
            self::Urgent => __('مستعجل (Urgent)'),
            self::EmergencySafety => __('طارئ جداً / خطر أمني وشيك (Urgence absolue / Sécurité)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::Urgent => 'warning',
            self::EmergencySafety => 'danger',
        };
    }
}
