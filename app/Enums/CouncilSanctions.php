<?php

namespace App\Enums;

enum CouncilSanctions: string
{
    case None = 'none';
    case AvertissementTravail = 'avertissement_travail';
    case BlameTravail = 'blame_travail';
    case AvertissementConduite = 'avertissement_conduite';
    case BlameConduite = 'blame_conduite';

    public function label(): string
    {
        return match ($this) {
            self::None => __('بدون عقوبة'),
            self::AvertissementTravail => __('تنبيه في العمل'),
            self::BlameTravail => __('إنذار في العمل'),
            self::AvertissementConduite => __('تنبيه في السلوك'),
            self::BlameConduite => __('إنذار في السلوك'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::AvertissementTravail, self::AvertissementConduite => 'warning',
            self::BlameTravail, self::BlameConduite => 'danger',
        };
    }

    public static function fromAverage(?float $average): self
    {
        if ($average === null) {
            return self::None;
        }

        return match (true) {
            $average < 6.0 => self::BlameTravail,
            $average < 8.0 => self::AvertissementTravail,
            default => self::None,
        };
    }
}

