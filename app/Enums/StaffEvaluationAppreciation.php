<?php

namespace App\Enums;

enum StaffEvaluationAppreciation: string
{
    case Excellent = 'excellent';
    case VeryGood = 'very_good';
    case Good = 'good';
    case Acceptable = 'acceptable';
    case Poor = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => __('ممتاز'),
            self::VeryGood => __('جيد جداً'),
            self::Good => __('جيد'),
            self::Acceptable => __('مقبول'),
            self::Poor => __('ضعيف / غير كافٍ'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Excellent => 'success',
            self::VeryGood => 'primary',
            self::Good => 'info',
            self::Acceptable => 'warning',
            self::Poor => 'danger',
        };
    }

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 18.0 => self::Excellent,
            $score >= 15.0 => self::VeryGood,
            $score >= 12.0 => self::Good,
            $score >= 10.0 => self::Acceptable,
            default => self::Poor,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Excellent->value => self::Excellent->label(),
            self::VeryGood->value => self::VeryGood->label(),
            self::Good->value => self::Good->label(),
            self::Acceptable->value => self::Acceptable->label(),
            self::Poor->value => self::Poor->label(),
        ];
    }
}

