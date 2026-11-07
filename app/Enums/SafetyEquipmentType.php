<?php

namespace App\Enums;

enum SafetyEquipmentType: string
{
    case ExtinguisherPowder = 'extinguisher_powder';
    case ExtinguisherCo2 = 'extinguisher_co2';
    case ExtinguisherWater = 'extinguisher_water';
    case FireAlarm = 'fire_alarm';
    case EmergencyLighting = 'emergency_lighting';
    case CoDetector = 'co_detector';
    case GasShutoffValve = 'gas_shutoff_valve';

    public function label(): string
    {
        return match ($this) {
            self::ExtinguisherPowder => __('مطفأة بودرة جافة ABC (Extincteur poudre ABC)'),
            self::ExtinguisherCo2 => __('مطفأة ثاني أكسيد الكربون CO2 (Extincteur CO2)'),
            self::ExtinguisherWater => __('مطفأة ماء رغوي (Extincteur à eau pulvérisée)'),
            self::FireAlarm => __('جرس وإنذار حريق صوتي (Alarme incendie)'),
            self::EmergencyLighting => __('وحدات الإنارة الذاتية للطوارئ (Bloc autonome de sécurité)'),
            self::CoDetector => __('كاشف غاز أحادي أكسيد الكربون (Détecteur de monoxyde de carbone CO)'),
            self::GasShutoffValve => __('صمام أمان قطع الغاز المركزي (Vanne de barrage gaz)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ExtinguisherPowder, self::ExtinguisherCo2, self::ExtinguisherWater => 'danger',
            self::FireAlarm => 'warning',
            self::EmergencyLighting => 'primary',
            self::CoDetector => 'info',
            self::GasShutoffValve => 'gray',
        };
    }
}
