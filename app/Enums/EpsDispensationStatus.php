<?php

namespace App\Enums;

enum EpsDispensationStatus: string
{
    case Fit = 'fit';
    case Annual = 'annual';
    case Trimester = 'trimester';
    case Temporary = 'temporary';

    public function label(): string
    {
        return match ($this) {
            self::Fit => __('لائق كلياً لممارسة التربية البدنية (Apte)'),
            self::Annual => __('معفى كلياً طيلة الموسم الدراسي (Dispensé Annuel)'),
            self::Trimester => __('معفى للفصل الدراسي الجاري (Dispensé Trimestriel)'),
            self::Temporary => __('إعفاء مؤقت ومحدود (Dispense Temporaire)'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Fit => __('لائق'),
            self::Annual => __('إعفاء سنوي'),
            self::Trimester => __('إعفاء فصلي'),
            self::Temporary => __('إعفاء مؤقت'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fit => 'success',
            self::Annual => 'danger',
            self::Trimester => 'warning',
            self::Temporary => 'amber',
        };
    }

    public function isExempt(): bool
    {
        return $this !== self::Fit;
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

