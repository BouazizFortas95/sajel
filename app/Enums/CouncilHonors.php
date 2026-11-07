<?php

namespace App\Enums;

enum CouncilHonors: string
{
    case None = 'none';
    case TableauHonneur = 'tableau_honneur';
    case Encouragement = 'encouragement';
    case Felicitations = 'felicitations';

    public function label(): string
    {
        return match ($this) {
            self::None => __('بدون تشريفات'),
            self::TableauHonneur => __('لوحة الشرف'),
            self::Encouragement => __('تشجيع'),
            self::Felicitations => __('تهنئة'),
        };
    }

    public function minAverage(): ?float
    {
        return match ($this) {
            self::None => null,
            self::TableauHonneur => 12.0,
            self::Encouragement => 14.0,
            self::Felicitations => 15.0,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::TableauHonneur => 'info',
            self::Encouragement => 'primary',
            self::Felicitations => 'success',
        };
    }

    public static function fromAverage(?float $average): self
    {
        if ($average === null) {
            return self::None;
        }

        return match (true) {
            $average >= 15.0 => self::Felicitations,
            $average >= 14.0 => self::Encouragement,
            $average >= 12.0 => self::TableauHonneur,
            default => self::None,
        };
    }
}

