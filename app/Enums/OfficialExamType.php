<?php

namespace App\Enums;

enum OfficialExamType: string
{
    case Bem = 'bem';
    case Bac = 'bac';
    case PrimaryEval = 'primary_eval';

    public function label(): string
    {
        return match ($this) {
            self::Bem => __('امتحان شهادة التعليم المتوسط (BEM)'),
            self::Bac => __('امتحان شهادة البكالوريا (BAC)'),
            self::PrimaryEval => __('امتحان تقييم مكتسبات مرحلة التعليم الابتدائي'),
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::Bem => 'BEM',
            self::Bac => 'BAC',
            self::PrimaryEval => 'EVAL-PRIM',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Bem => 'info',
            self::Bac => 'primary',
            self::PrimaryEval => 'success',
        };
    }
}

