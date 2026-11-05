<?php

namespace App\Enums;

enum TextbookDistributionType: string
{
    case Solidarity = 'solidarity';
    case Purchased = 'purchased';
    case Loan = 'loan';

    public function label(): string
    {
        return match ($this) {
            self::Solidarity => __('مجاني (تضامن مدرسي)'),
            self::Purchased => __('مقتنى (شراء رسمي)'),
            self::Loan => __('إعارة بيداغوجية'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Solidarity => 'emerald',
            self::Purchased => 'primary',
            self::Loan => 'amber',
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
