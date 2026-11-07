<?php

namespace App\Enums;

enum SchoolBoardType: string
{
    case Ceg = 'ceg';
    case Cog = 'cog';

    public function label(): string
    {
        return match ($this) {
            self::Ceg => __('مجلس التربية والتسيير (Conseil d\'éducation et de gestion - CEM)'),
            self::Cog => __('مجلس التوجيه والتسيير (Conseil d\'orientation et de gestion - Lycée)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Ceg => 'primary',
            self::Cog => 'success',
        };
    }
}
