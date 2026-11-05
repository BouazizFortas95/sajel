<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrientationAppealStatus: string implements HasColor, HasLabel
{
    case None = 'none';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::None => __('لا يوجد طعن'),
            self::Submitted => __('طعن مودع قيد دراسة اللجنة الولائية'),
            self::Accepted => __('تم قبول الطعن وإعادة التوجيه'),
            self::Rejected => __('تم رفض الطعن وتثبيت القرار الأصلي'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Submitted => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getColor(): ?string
    {
        return $this->color();
    }
}
