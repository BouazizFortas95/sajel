<?php

namespace App\Enums;

enum ClubType: string
{
    case Scientific = 'scientific';
    case Environmental = 'environmental';
    case Cultural = 'cultural';
    case Reading = 'reading';
    case Sports = 'sports';
    case Citizenship = 'citizenship';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Scientific => __('النادي العلمي والمعلوماتي'),
            self::Environmental => __('النادي الأخضر والبيئة المدرسية'),
            self::Cultural => __('النادي الثقافي والفنون والمسرح'),
            self::Reading => __('نادي القراءة والمطالعة والإعلام'),
            self::Sports => __('النادي الرياضي المدرسي'),
            self::Citizenship => __('نادي المواطنة وحقوق الطفل'),
            self::Other => __('نادي تربوي عام'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scientific => 'info',
            self::Environmental => 'success',
            self::Cultural => 'warning',
            self::Reading => 'purple',
            self::Sports => 'danger',
            self::Citizenship => 'amber',
            self::Other => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scientific => '🔬',
            self::Environmental => '🌱',
            self::Cultural => '🎭',
            self::Reading => '📚',
            self::Sports => '⚽',
            self::Citizenship => '🤝',
            self::Other => '✨',
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

