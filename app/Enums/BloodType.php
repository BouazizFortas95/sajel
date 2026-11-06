<?php

namespace App\Enums;

enum BloodType: string
{
    case APositive = 'A+';
    case ANegative = 'A-';
    case BPositive = 'B+';
    case BNegative = 'B-';
    case ABPositive = 'AB+';
    case ABNegative = 'AB-';
    case OPositive = 'O+';
    case ONegative = 'O-';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::APositive => 'A+ (إيجابي)',
            self::ANegative => 'A- (سلبي)',
            self::BPositive => 'B+ (إيجابي)',
            self::BNegative => 'B- (سلبي)',
            self::ABPositive => 'AB+ (إيجابي)',
            self::ABNegative => 'AB- (سلبي)',
            self::OPositive => 'O+ (إيجابي)',
            self::ONegative => 'O- (سلبي)',
            self::Unknown => __('غير محدد'),
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
