<?php

namespace App\Enums;

enum MailConfidentiality: string
{
    case Normal = 'normal';
    case Confidential = 'confidential';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => __('عادي'),
            self::Confidential => __('سري'),
            self::Urgent => __('مستعجل'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'gray',
            self::Confidential => 'danger',
            self::Urgent => 'warning',
        };
    }
}
