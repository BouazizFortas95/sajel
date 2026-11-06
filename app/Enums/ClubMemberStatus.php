<?php

namespace App\Enums;

enum ClubMemberStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('منخرط فعلي'),
            self::Withdrawn => __('منسحب'),
            self::Graduated => __('متخرج / منتقل'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Withdrawn => 'danger',
            self::Graduated => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
