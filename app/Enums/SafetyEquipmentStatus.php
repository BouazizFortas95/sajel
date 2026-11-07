<?php

namespace App\Enums;

enum SafetyEquipmentStatus: string
{
    case Operational = 'operational';
    case NeedsRefill = 'needs_refill';
    case NeedsMaintenance = 'needs_maintenance';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Operational => __('صالح وجاهز للاستعمال (Opérationnel / Conforme)'),
            self::NeedsRefill => __('بحاجة لإعادة شحن وتعبئة (À recharger)'),
            self::NeedsMaintenance => __('بحاجة للصيانة والتصليح (Maintenance requise)'),
            self::OutOfService => __('معطل وخارج الخدمة (Hors service)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Operational => 'success',
            self::NeedsRefill => 'warning',
            self::NeedsMaintenance => 'danger',
            self::OutOfService => 'gray',
        };
    }
}
