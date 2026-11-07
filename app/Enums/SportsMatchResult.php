<?php

namespace App\Enums;

enum SportsMatchResult: string
{
    case Upcoming = 'upcoming';
    case Win = 'win';
    case Loss = 'loss';
    case Draw = 'draw';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => __('مباراة مبرمجة قادمة (Rencontre à venir)'),
            self::Win => __('فوز وانتصار (Victoire)'),
            self::Loss => __('هزيمة (Défaite)'),
            self::Draw => __('تعادل (Match nul)'),
            self::Cancelled => __('مباراة ملغاة / مؤجلة (Annulé / Reporté)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Upcoming => 'info',
            self::Win => 'success',
            self::Loss => 'danger',
            self::Draw => 'warning',
            self::Cancelled => 'gray',
        };
    }
}
