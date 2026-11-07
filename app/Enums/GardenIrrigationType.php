<?php

namespace App\Enums;

enum GardenIrrigationType: string
{
    case DripIrrigation = 'drip_irrigation';
    case Sprinkler = 'sprinkler';
    case ManualWatering = 'manual_watering';
    case RainwaterHarvesting = 'rainwater_harvesting';

    public function label(): string
    {
        return match ($this) {
            self::DripIrrigation => __('السقي بالتقطير (Goutte-à-goutte)'),
            self::Sprinkler => __('الرش المحوري (Aspersion)'),
            self::ManualWatering => __('سقي يدوي تقليدي (Arrosage manuel)'),
            self::RainwaterHarvesting => __('مياه الأمطار المجمعة (Récupération des eaux pluviales)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DripIrrigation => 'success',
            self::Sprinkler => 'info',
            self::ManualWatering => 'warning',
            self::RainwaterHarvesting => 'primary',
        };
    }
}

