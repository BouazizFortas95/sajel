<?php

namespace App\Enums;

enum HandoverRole: string
{
    case Principal = 'principal';
    case Censor = 'censor';
    case FinanceOfficer = 'finance_officer';

    public function label(): string
    {
        return match ($this) {
            self::Principal => __('مدير المؤسسة'),
            self::Censor => __('الناظر'),
            self::FinanceOfficer => __('المقتصد'),
        };
    }

    public function labelFr(): string
    {
        return match ($this) {
            self::Principal => 'Chef d\'établissement',
            self::Censor => 'Censeur',
            self::FinanceOfficer => 'Intendant / Gestionnaire',
        };
    }
}
