<?php

namespace App\Enums;

enum InventoryCondition: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Damaged = 'damaged';
    case Reformed = 'reformed';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Good => __('حالة جيدة ومستغلة'),
            self::Fair => __('حالة متوسطة صالحة'),
            self::Damaged => __('متدهور / بحاجة لإصلاح'),
            self::Reformed => __('مسقط من الجرد'),
            self::Lost => __('مفقود / في حكم الضائع'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Fair => 'info',
            self::Damaged => 'warning',
            self::Reformed => 'gray',
            self::Lost => 'danger',
        };
    }
}
