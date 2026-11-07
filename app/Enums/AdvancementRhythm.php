<?php

namespace App\Enums;

enum AdvancementRhythm: string
{
    case Minimum = 'minimum';
    case Medium = 'medium';
    case Maximum = 'maximum';

    public function label(): string
    {
        return match ($this) {
            self::Minimum => __('الدرجة الدنيا (سريعة - سنتان ونصف)'),
            self::Medium => __('الدرجة المتوسطة (3 سنوات)'),
            self::Maximum => __('الدرجة القصوى (3 سنوات ونصف)'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Minimum => __('الدنيا (2.5 سنة)'),
            self::Medium => __('المتوسطة (3 سنوات)'),
            self::Maximum => __('القصوى (3.5 سنة)'),
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Minimum => 30, // 2.5 years
            self::Medium => 36,  // 3.0 years
            self::Maximum => 42, // 3.5 years
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Minimum => 'success',
            self::Medium => 'primary',
            self::Maximum => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Minimum->value => self::Minimum->label(),
            self::Medium->value => self::Medium->label(),
            self::Maximum->value => self::Maximum->label(),
        ];
    }
}
