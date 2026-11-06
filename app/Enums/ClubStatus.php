<?php

namespace App\Enums;

enum ClubStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('نشط ومفعل'),
            self::Frozen => __('مجمد مؤقتاً'),
            self::Archived => __('مؤرشف'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Frozen => 'warning',
            self::Archived => 'gray',
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

