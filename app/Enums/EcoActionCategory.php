<?php

namespace App\Enums;

enum EcoActionCategory: string
{
    case TreePlanting = 'tree_planting';
    case WasteRecycling = 'waste_recycling';
    case WaterConservation = 'water_conservation';
    case EnergySavingAudit = 'energy_saving_audit';
    case EnvironmentalAwareness = 'environmental_awareness';
    case OrganicComposting = 'organic_composting';

    public function label(): string
    {
        return match ($this) {
            self::TreePlanting => __('غرس وتشجير (Plantation d\'arbres)'),
            self::WasteRecycling => __('فرز ورسكلة النفايات (Tri et recyclage des déchets)'),
            self::WaterConservation => __('ترشيد استهلاك الماء (Économie et préservation de l\'eau)'),
            self::EnergySavingAudit => __('تدقيق وتوفير الطاقة (Audit et efficacité énergétique)'),
            self::EnvironmentalAwareness => __('توعية وتحسيس بيئي (Sensibilisation environnementale)'),
            self::OrganicComposting => __('إنتاج السماد العضوي / الكومبوست (Compostage organique)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::TreePlanting => 'success',
            self::WasteRecycling => 'primary',
            self::WaterConservation => 'info',
            self::EnergySavingAudit => 'warning',
            self::EnvironmentalAwareness => 'secondary',
            self::OrganicComposting => 'gray',
        };
    }
}

