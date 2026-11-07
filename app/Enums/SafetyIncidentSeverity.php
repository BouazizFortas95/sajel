<?php

namespace App\Enums;

enum SafetyIncidentSeverity: string
{
    case Minor = 'minor';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Minor => __('بسيط / طفيف (Léger)'),
            self::Moderate => __('متوسط / إسعاف أولي (Moyen)'),
            self::Severe => __('خطير / إخلاء مستشفى (Grave)'),
            self::Critical => __('حرج جداً (Critique)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Minor => 'success',
            self::Moderate => 'warning',
            self::Severe => 'danger',
            self::Critical => 'danger',
        };
    }
}
