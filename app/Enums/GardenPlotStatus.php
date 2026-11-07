<?php

namespace App\Enums;

enum GardenPlotStatus: string
{
    case ActivePlanted = 'active_planted';
    case FallowResting = 'fallow_resting';
    case PreparationTillage = 'preparation_tillage';
    case UnderMaintenance = 'under_maintenance';

    public function label(): string
    {
        return match ($this) {
            self::ActivePlanted => __('مزروع ونامي (Planté et en production)'),
            self::FallowResting => __('في راحة بيولوجية (En jachère)'),
            self::PreparationTillage => __('قيد الحرث والتهيئة (En préparation et labour)'),
            self::UnderMaintenance => __('قيد الصيانة والتقليم (En entretien)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ActivePlanted => 'success',
            self::FallowResting => 'gray',
            self::PreparationTillage => 'warning',
            self::UnderMaintenance => 'info',
        };
    }
}

