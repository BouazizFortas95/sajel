<?php

namespace App\Enums;

enum TransferType: string
{
    case Inward = 'inward';
    case Outward = 'outward';

    public function label(): string
    {
        return match ($this) {
            self::Inward => __('وافد (تحويل إلى المؤسسة)'),
            self::Outward => __('مغادر (تحويل من المؤسسة)'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Inward => __('وافد'),
            self::Outward => __('مغادر'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Inward => 'emerald',
            self::Outward => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Inward => 'heroicon-o-arrow-left-end-on-rectangle',
            self::Outward => 'heroicon-o-arrow-right-start-on-rectangle',
        };
    }
}
