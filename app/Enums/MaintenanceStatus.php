<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Reported = 'reported';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reported => __('مُبلغ عنه ومسجل (Signalé)'),
            self::Scheduled => __('مبرمج للتدخل (Programmé)'),
            self::InProgress => __('قيد الأشغال والإصلاح (En cours d\'intervention)'),
            self::Completed => __('تم الإنجاز والتشغيل (Achevé et clôturé)'),
            self::Cancelled => __('ملغى / غير قابل للتنفيذ (Annulé)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Reported => 'info',
            self::Scheduled => 'primary',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }
}

