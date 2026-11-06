<?php

namespace App\Enums;

enum InstitutionalReportStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة ديناميكية'),
            self::Validated => __('مصادق عليها ومجمدة'),
            self::Archived => __('مؤرشفة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Validated => 'emerald',
            self::Archived => 'indigo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Validated => 'heroicon-o-check-badge',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
