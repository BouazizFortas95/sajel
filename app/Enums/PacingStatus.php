<?php

namespace App\Enums;

enum PacingStatus: string
{
    case Ahead = 'ahead';
    case OnTrack = 'on_track';
    case MinorDelay = 'minor_delay';
    case CriticalDelay = 'critical_delay';

    public function label(): string
    {
        return match ($this) {
            self::Ahead => __('متقدم عن التدرج'),
            self::OnTrack => __('مطابق للتدرج السنوي'),
            self::MinorDelay => __('تأخر طفيف (أقل من حصتين)'),
            self::CriticalDelay => __('تأخر حرج يتطلب استدراكاً'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ahead => 'info',
            self::OnTrack => 'success',
            self::MinorDelay => 'warning',
            self::CriticalDelay => 'danger',
        };
    }

    public static function fromExecution(int $expected, int $completed): self
    {
        if ($expected <= 0) {
            return self::OnTrack;
        }

        $diff = $completed - $expected;

        if ($diff > 1) {
            return self::Ahead;
        }

        if ($diff >= -1) {
            return self::OnTrack;
        }

        if ($diff >= -3) {
            return self::MinorDelay;
        }

        return self::CriticalDelay;
    }
}

