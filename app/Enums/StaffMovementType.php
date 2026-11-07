<?php

namespace App\Enums;

enum StaffMovementType: string
{
    case Transfer = 'transfer';
    case Secondment = 'secondment';
    case Availability = 'availability';
    case Retirement = 'retirement';
    case Integration = 'integration';
    case NewHire = 'new_hire';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => __('حركة نقل ولائية / وطنية'),
            self::Secondment => __('انتداب لدى مؤسسة أخرى'),
            self::Availability => __('إحالة على الاستيداع'),
            self::Retirement => __('إحالة على التقاعد'),
            self::Integration => __('إعادة إدماج بعد عطلة'),
            self::NewHire => __('تعيين جديد / توظيف'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Transfer => 'primary',
            self::Secondment => 'info',
            self::Availability => 'warning',
            self::Retirement => 'danger',
            self::Integration => 'success',
            self::NewHire => 'indigo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Transfer => 'heroicon-o-arrows-right-left',
            self::Secondment => 'heroicon-o-paper-airplane',
            self::Availability => 'heroicon-o-pause-circle',
            self::Retirement => 'heroicon-o-flag',
            self::Integration => 'heroicon-o-arrow-path',
            self::NewHire => 'heroicon-o-user-plus',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Transfer->value => self::Transfer->label(),
            self::Secondment->value => self::Secondment->label(),
            self::Availability->value => self::Availability->label(),
            self::Retirement->value => self::Retirement->label(),
            self::Integration->value => self::Integration->label(),
            self::NewHire->value => self::NewHire->label(),
        ];
    }
}
