<?php

namespace App\Enums;

enum SchoolBoardSessionTerm: string
{
    case Term1Opening = 'term_1_opening';
    case Term2Budget = 'term_2_budget';
    case Term3Bilan = 'term_3_bilan';
    case Extraordinary = 'extraordinary';

    public function label(): string
    {
        return match ($this) {
            self::Term1Opening => __('الدورة العادية الأولى: افتتاح السنة ومشروع المؤسسة (Session 1 - Rentrée)'),
            self::Term2Budget => __('الدورة العادية الثانية: مشروع الميزانية والوقاية (Session 2 - Budget)'),
            self::Term3Bilan => __('الدورة العادية الثالثة: الحصيلة السنوية والتحضير للدخول (Session 3 - Bilan)'),
            self::Extraordinary => __('دورة استثنائية طارئة (Session extraordinaire)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Term1Opening => 'info',
            self::Term2Budget => 'warning',
            self::Term3Bilan => 'success',
            self::Extraordinary => 'danger',
        };
    }
}

