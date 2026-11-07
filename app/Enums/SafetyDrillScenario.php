<?php

namespace App\Enums;

enum SafetyDrillScenario: string
{
    case Earthquake = 'earthquake';
    case FireOutbreak = 'fire_outbreak';
    case GasLeak = 'gas_leak';
    case GeneralDisaster = 'general_disaster';

    public function label(): string
    {
        return match ($this) {
            self::Earthquake => __('تمرين وقاية وإخلاء ضد الزلازل (Simulation Séisme)'),
            self::FireOutbreak => __('تمرين إخلاء ضد نشوب حريق (Simulation Incendie)'),
            self::GasLeak => __('تمرين إخلاء ضد تسرب غازي (Simulation Fuite de gaz)'),
            self::GeneralDisaster => __('تمرين إخلاء شامل ضد الأخطار الكبرى (Évacuation générale)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Earthquake => 'danger',
            self::FireOutbreak => 'warning',
            self::GasLeak => 'info',
            self::GeneralDisaster => 'primary',
        };
    }
}
