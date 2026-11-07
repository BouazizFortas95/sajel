<?php

namespace App\Enums;

enum WaterPotabilityStatus: string
{
    case CompliantPotable = 'compliant_potable';
    case ContaminatedNonPotable = 'contaminated_non_potable';
    case PendingAnalysis = 'pending_analysis';

    public function label(): string
    {
        return match ($this) {
            self::CompliantPotable => __('ماء شروب صالح ومطابق للتحاليل (Eau potable conforme)'),
            self::ContaminatedNonPotable => __('غير صالح للشرب / ملوث جرثومياً (Non potable / Contaminée)'),
            self::PendingAnalysis => __('قيد التحاليل المخبرية (Analyse bactériologique en cours)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::CompliantPotable => 'success',
            self::ContaminatedNonPotable => 'danger',
            self::PendingAnalysis => 'warning',
        };
    }
}

