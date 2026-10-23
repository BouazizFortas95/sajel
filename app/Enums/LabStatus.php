<?php

namespace App\Enums;

enum LabStatus: string
{
    case Working = 'working';
    case Broken = 'broken';
    case UnderMaintenance = 'under_maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Working => __('يعمل'),
            self::Broken => __('معطل'),
            self::UnderMaintenance => __('قيد الصيانة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Working => 'success',
            self::Broken => 'danger',
            self::UnderMaintenance => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
