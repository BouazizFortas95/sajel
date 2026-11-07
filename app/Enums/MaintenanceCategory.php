<?php

namespace App\Enums;

enum MaintenanceCategory: string
{
    case Electricity = 'electricity';
    case PlumbingSanitary = 'plumbing_sanitary';
    case CentralHeating = 'central_heating';
    case CarpentryLocksmith = 'carpentry_locksmith';
    case MasonryPainting = 'masonry_painting';
    case GlazingRoofing = 'glazing_roofing';

    public function label(): string
    {
        return match ($this) {
            self::Electricity => __('كهرباء وإنارة عامة (Électricité & Éclairage)'),
            self::PlumbingSanitary => __('ترصيص وصحي وشبكات المياه (Plomberie & Sanitaire)'),
            self::CentralHeating => __('تدفئة مركزية وغلايات وشبكة الغاز (Chauffage central & Gaz)'),
            self::CarpentryLocksmith => __('نجارة وأقفال وأبواب ونوافذ (Menuiserie & Serrurerie)'),
            self::MasonryPainting => __('بناء وتلييس ودهن وترميم (Maçonnerie & Peinture)'),
            self::GlazingRoofing => __('زجاج وأسقف وعزل مائي (Vitrerie, Toiture & Étanchéité)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Electricity => 'warning',
            self::PlumbingSanitary => 'info',
            self::CentralHeating => 'danger',
            self::CarpentryLocksmith => 'primary',
            self::MasonryPainting => 'success',
            self::GlazingRoofing => 'gray',
        };
    }
}

