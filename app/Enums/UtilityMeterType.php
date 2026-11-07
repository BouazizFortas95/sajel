<?php

namespace App\Enums;

enum UtilityMeterType: string
{
    case Electricity = 'electricity';
    case NaturalGas = 'natural_gas';
    case PotableWater = 'potable_water';

    public function label(): string
    {
        return match ($this) {
            self::Electricity => __('عداد الكهرباء (Compteur d\'électricité)'),
            self::NaturalGas => __('عداد الغاز الطبيعي (Compteur de gaz naturel)'),
            self::PotableWater => __('عداد الماء الشروب (Compteur d\'eau potable)'),
        };
    }

    public function unitOfMeasure(): string
    {
        return match ($this) {
            self::Electricity => 'kWh',
            self::NaturalGas, self::PotableWater => 'm³',
        };
    }

    public function defaultProvider(): string
    {
        return match ($this) {
            self::Electricity, self::NaturalGas => 'Sonelgaz (سونلغاز)',
            self::PotableWater => 'Algérienne Des Eaux - ADE (الجزائرية للمياه)',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Electricity => 'warning',
            self::NaturalGas => 'danger',
            self::PotableWater => 'info',
        };
    }
}
