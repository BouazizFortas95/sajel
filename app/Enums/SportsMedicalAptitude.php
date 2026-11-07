<?php

namespace App\Enums;

enum SportsMedicalAptitude: string
{
    case FitApt = 'fit_apt';
    case UnfitTemporarily = 'unfit_temporarily';
    case UnfitPermanently = 'unfit_permanently';

    public function label(): string
    {
        return match ($this) {
            self::FitApt => __('مؤهل صحياً وقادر بدنياً (Apte médicalement)'),
            self::UnfitTemporarily => __('عجز صحي مؤقت ومعفى مؤقتاً (Inapte temporaire)'),
            self::UnfitPermanently => __('غير مؤهل صحياً ومعفى كلياً (Inapte définitif)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::FitApt => 'success',
            self::UnfitTemporarily => 'warning',
            self::UnfitPermanently => 'danger',
        };
    }
}
