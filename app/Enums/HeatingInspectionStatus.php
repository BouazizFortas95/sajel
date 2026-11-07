<?php

namespace App\Enums;

enum HeatingInspectionStatus: string
{
    case CompliantApproved = 'compliant_approved';
    case ConditionalUse = 'conditional_use';
    case ShutDownEmergency = 'shut_down_emergency';

    public function label(): string
    {
        return match ($this) {
            self::CompliantApproved => __('مطابق ومعتمد للتشغيل (Conforme et autorisé)'),
            self::ConditionalUse => __('صالح بشروط وتحفظات (Autorisé sous réserves)'),
            self::ShutDownEmergency => __('توقيف إجباري لخطر وشيك (Arrêt d\'urgence / Non conforme)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::CompliantApproved => 'success',
            self::ConditionalUse => 'warning',
            self::ShutDownEmergency => 'danger',
        };
    }
}
