<?php

namespace App\Enums;

enum CooperativeProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('مبرمج قيد التحضير (Planifié)'),
            self::Active => __('نشط وقيد التنفيذ (En cours)'),
            self::Completed => __('منجز ومكتمل (Achevé)'),
            self::Cancelled => __('ملغى (Annulé)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Active => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
