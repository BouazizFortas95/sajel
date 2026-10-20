<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('مدير'),
            self::Teacher => __('أستاذ'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Teacher => 'info',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
