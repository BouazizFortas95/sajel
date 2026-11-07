<?php

namespace App\Enums;

enum BoilerFuelType: string
{
    case NaturalGas = 'natural_gas';
    case PropaneLpg = 'propane_lpg';
    case FuelOil = 'fuel_oil';

    public function label(): string
    {
        return match ($this) {
            self::NaturalGas => __('غاز طبيعي المدينة (Gaz naturel de ville)'),
            self::PropaneLpg => __('غاز البروبان المميع صهريج (Gaz propane citerne)'),
            self::FuelOil => __('مازوت / وقود التدفئة (Fioul / Mazout)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::NaturalGas => 'primary',
            self::PropaneLpg => 'warning',
            self::FuelOil => 'danger',
        };
    }
}

