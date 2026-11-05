<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DisciplinaryStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case Deliberated = 'deliberated';
    case Executed = 'executed';
    case Appealed = 'appealed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('مجدولة وفي انتظار الانعقاد'),
            self::Deliberated => __('تمت المداولة وصدر القرار'),
            self::Executed => __('منفذة ومغلقة رسمياً'),
            self::Appealed => __('مطعون فيها أمام لجنة الطعن الولائية'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'amber',
            self::Deliberated => 'emerald',
            self::Executed => 'blue',
            self::Appealed => 'purple',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }
}
