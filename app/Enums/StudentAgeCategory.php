<?php

namespace App\Enums;

enum StudentAgeCategory: string
{
    case Benjamins = 'benjamins';
    case Minimes = 'minimes';
    case Cadets = 'cadets';
    case Juniors = 'juniors';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Benjamins => __('أصاغر (Benjamins - U13)'),
            self::Minimes => __('أشبال (Minimes - U15)'),
            self::Cadets => __('أواسط (Cadets - U17)'),
            self::Juniors => __('أكابر وشبيبة (Juniors - U19)'),
            self::All => __('جميع الفئات العمرية (Toutes catégories)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Benjamins => 'info',
            self::Minimes => 'primary',
            self::Cadets => 'warning',
            self::Juniors => 'danger',
            self::All => 'gray',
        };
    }
}

