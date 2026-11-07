<?php

namespace App\Enums;

enum SportsLicenseType: string
{
    case Competitive = 'competitive';
    case RecreationalSchoolPractice = 'recreational_school_practice';

    public function label(): string
    {
        return match ($this) {
            self::Competitive => __('إجازة تنافسية للبطولات المدرسية (Licence compétitive FASS / LWSS)'),
            self::RecreationalSchoolPractice => __('إجازة تنشيطية وترفيهية داخلية (Pratique interne & animation)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Competitive => 'primary',
            self::RecreationalSchoolPractice => 'success',
        };
    }
}
