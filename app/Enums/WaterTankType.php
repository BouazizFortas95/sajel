<?php

namespace App\Enums;

enum WaterTankType: string
{
    case UndergroundBache = 'underground_bache';
    case ElevatedRoofTank = 'elevated_roof_tank';
    case GroundCistern = 'ground_cistern';

    public function label(): string
    {
        return match ($this) {
            self::UndergroundBache => __('باشة ماء أرضية خرسانية (Bâche à eau souterraine)'),
            self::ElevatedRoofTank => __('خزان سطحي علوي (Réservoir surélevé en toiture)'),
            self::GroundCistern => __('صهريج أرضي مستقل (Citerne d\'eau au sol)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::UndergroundBache => 'primary',
            self::ElevatedRoofTank => 'info',
            self::GroundCistern => 'gray',
        };
    }
}
