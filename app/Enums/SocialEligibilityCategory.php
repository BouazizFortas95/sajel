<?php

namespace App\Enums;

enum SocialEligibilityCategory: string
{
    case Orphan = 'orphan';
    case NeedyNoIncome = 'needy_no_income';
    case LowIncome = 'low_income';
    case SpecialNeeds = 'special_needs';
    case NationalTragedy = 'national_tragedy';

    public function label(): string
    {
        return match ($this) {
            self::Orphan => __('يتيم (فاقد الأب أو الوالدين معاً)'),
            self::NeedyNoIncome => __('معوز (أرباب الأسر بدون أي دخل)'),
            self::LowIncome => __('دخل ضعيف (أقل أو مساوٍ للأجر الأدنى SNMG)'),
            self::SpecialNeeds => __('ذوو الاحتياجات الخاصة والإعاقة'),
            self::NationalTragedy => __('ضحايا المأساة الوطنية والحالات الخاصة'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Orphan => 'info',
            self::NeedyNoIncome => 'danger',
            self::LowIncome => 'warning',
            self::SpecialNeeds => 'primary',
            self::NationalTragedy => 'gray',
        };
    }
}
