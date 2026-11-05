<?php

namespace App\Enums;

enum StudentAwardType: string
{
    case Excellence = 'excellence';
    case Encouragement = 'encouragement';
    case HonorRoll = 'honor_roll';
    case GoodConduct = 'good_conduct';

    public function label(): string
    {
        return match ($this) {
            self::Excellence => __('شهادة امتياز'),
            self::Encouragement => __('شهادة تشجيع'),
            self::HonorRoll => __('لوحة شرف'),
            self::GoodConduct => __('شهادة حسن سيرة وسلوك'),
        };
    }

    public function certificateTitle(): string
    {
        return match ($this) {
            self::Excellence => __('شَهَادَةُ امْتِيَازٍ وَتَفَوُّقٍ'),
            self::Encouragement => __('شَهَادَةُ تَشْجِيعٍ وَتَقْدِيرٍ'),
            self::HonorRoll => __('لَوْحَةُ شَرَفٍ وَتَقْدِيرٍ'),
            self::GoodConduct => __('شَهَادَةُ حُسْنِ سِيرَةٍ وَسُلُوكٍ'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Excellence => 'amber',
            self::Encouragement => 'sky',
            self::HonorRoll => 'indigo',
            self::GoodConduct => 'emerald',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Excellence => 'heroicon-o-trophy',
            self::Encouragement => 'heroicon-o-sparkles',
            self::HonorRoll => 'heroicon-o-academic-cap',
            self::GoodConduct => 'heroicon-o-heart',
        };
    }

    public function minimumAverage(): ?float
    {
        return match ($this) {
            self::Excellence => 16.0,
            self::Encouragement => 14.0,
            self::HonorRoll => 12.0,
            self::GoodConduct => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
