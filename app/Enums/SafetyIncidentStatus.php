<?php

namespace App\Enums;

enum SafetyIncidentStatus: string
{
    case Draft = 'draft';
    case Declared = 'declared';
    case InTreatment = 'in_treatment';
    case Settled = 'settled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة قيد التحرير (Brouillon)'),
            self::Declared => __('تم التصريح رسمياً (Déclaré)'),
            self::InTreatment => __('قيد المتابعة الطبية (En soins / suivi)'),
            self::Settled => __('تمت التسوية والشفاء (Clôturé / Guéri)'),
            self::Archived => __('مؤرشف (Archivé)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Declared => 'info',
            self::InTreatment => 'warning',
            self::Settled => 'success',
            self::Archived => 'gray',
        };
    }
}
