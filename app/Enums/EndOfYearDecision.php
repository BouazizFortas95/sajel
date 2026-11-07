<?php

namespace App\Enums;

enum EndOfYearDecision: string
{
    case None = 'none';
    case Admis = 'admis';
    case Redouble = 'redouble';
    case Reoriente = 'reoriente';
    case Exclu = 'exclu';

    public function label(): string
    {
        return match ($this) {
            self::None => __('قيد الدراسة'),
            self::Admis => __('ينتقل إلى المستوى الأعلى'),
            self::Redouble => __('يعيد السنة'),
            self::Reoriente => __('يوجه إلى مسار بديل'),
            self::Exclu => __('يفصل لبلوغ السن القانونية'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Admis => 'success',
            self::Redouble => 'warning',
            self::Reoriente => 'info',
            self::Exclu => 'danger',
        };
    }
}
