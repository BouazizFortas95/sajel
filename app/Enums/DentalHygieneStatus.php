<?php

namespace App\Enums;

enum DentalHygieneStatus: string
{
    case Good = 'good';
    case Moderate = 'moderate';
    case Poor = 'poor';
    case NeedsOrthodontics = 'needs_orthodontics';

    public function label(): string
    {
        return match ($this) {
            self::Good => __('صحة فموية جيدة وأسنان سليمة'),
            self::Moderate => __('متوسطة / تسوس سطحي بحاجة لعلاج'),
            self::Poor => __('سيئة / تسوس متعدد والتهاب لثة'),
            self::NeedsOrthodontics => __('بحاجة لتقويم الأسنان (Orthodontie)'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Moderate => 'warning',
            self::Poor => 'danger',
            self::NeedsOrthodontics => 'info',
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

