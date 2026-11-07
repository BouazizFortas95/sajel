<?php

namespace App\Enums;

enum AscsTransactionType: string
{
    case IncomeSubscription = 'income_subscription';
    case IncomeSubsidy = 'income_subsidy';
    case IncomeDonation = 'income_donation';
    case ExpenseEquipment = 'expense_equipment';
    case ExpenseTransport = 'expense_transport';
    case ExpenseCatering = 'expense_catering';
    case ExpenseTrophiesAwards = 'expense_trophies_awards';
    case ExpenseRegistrationFee = 'expense_registration_fee';

    public function label(): string
    {
        return match ($this) {
            self::IncomeSubscription => __('اشتراكات التلاميذ السنوية (Cotisations des élèves)'),
            self::IncomeSubsidy => __('إعانة عمومية للرياضة المدرسية (Subvention publique)'),
            self::IncomeDonation => __('تبرعات ومساهمات شرفية (Dons et soutiens)'),
            self::ExpenseEquipment => __('اقتناء ألبسة وعتاد رياضي (Achat équipements et maillots)'),
            self::ExpenseTransport => __('تكاليف النقل للمنافسات (Transport compétitions)'),
            self::ExpenseCatering => __('إطعام ووجبات الوفد الرياضي (Restauration et collations)'),
            self::ExpenseTrophiesAwards => __('كؤوس وميداليات وجوائز (Trophées et récompenses)'),
            self::ExpenseRegistrationFee => __('حقوق الانخراط والتأمين (Frais d\'engagement et assurance)'),
        };
    }

    public function isIncome(): bool
    {
        return in_array($this, [self::IncomeSubscription, self::IncomeSubsidy, self::IncomeDonation], true);
    }

    public function badgeColor(): string
    {
        return $this->isIncome() ? 'success' : 'danger';
    }
}

