<?php

namespace App\Enums;

enum PlantHealthStatus: string
{
    case ThrivingGood = 'thriving_good';
    case Moderate = 'moderate';
    case NeedsCare = 'needs_care';
    case DiseasedWilted = 'diseased_wilted';

    public function label(): string
    {
        return match ($this) {
            self::ThrivingGood => __('في حالة نمو ممتازة (Excellente vitalité)'),
            self::Moderate => __('نمو متوسط ومقبول (État satisfaisant)'),
            self::NeedsCare => __('بحاجة لعناية وتسميد (Nécessite soins / arrosage)'),
            self::DiseasedWilted => __('ذابل أو مصاب بآفات (Malade / Flétri)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ThrivingGood => 'success',
            self::Moderate => 'info',
            self::NeedsCare => 'warning',
            self::DiseasedWilted => 'danger',
        };
    }
}
