<?php

namespace App\Enums;

enum CooperativeTransactionType: string
{
    case IncomeMembershipDues = 'income_membership_dues';
    case IncomeProceedsCrafts = 'income_proceeds_crafts';
    case IncomeProceedsGardenProduce = 'income_proceeds_garden_produce';
    case IncomeDonationsSponsorship = 'income_donations_sponsorship';
    case ExpenseSolidarityKits = 'expense_solidarity_kits';
    case ExpenseGardenToolsSeeds = 'expense_garden_tools_seeds';
    case ExpenseCraftMaterials = 'expense_craft_materials';
    case ExpenseExhibitionPrizes = 'expense_exhibition_prizes';

    public function label(): string
    {
        return match ($this) {
            self::IncomeMembershipDues => __('اشتراكات وتبرعات الأعضاء (Cotisations et adhésions)'),
            self::IncomeProceedsCrafts => __('مداخيل بيع المشغولات اليدوية (Recettes vente d\'artisanat)'),
            self::IncomeProceedsGardenProduce => __('مداخيل منتوج الحديقة والمشتلة (Recettes produits du jardin)'),
            self::IncomeDonationsSponsorship => __('هبات وإعانات تضامنية (Dons et parrainages)'),
            self::ExpenseSolidarityKits => __('اقتناء محافظ وكسوة للتلاميذ المعوزين (Aides et fournitures pour démunis)'),
            self::ExpenseGardenToolsSeeds => __('اقتناء بذور وعتاد البستنة (Semences et outillage de jardin)'),
            self::ExpenseCraftMaterials => __('اقتناء لوازم الورشات والأشغال (Fournitures d\'ateliers et dessin)'),
            self::ExpenseExhibitionPrizes => __('جوائز ومكافآت التلاميذ (Prix et récompenses)'),
        };
    }

    public function isIncome(): bool
    {
        return in_array($this, [
            self::IncomeMembershipDues,
            self::IncomeProceedsCrafts,
            self::IncomeProceedsGardenProduce,
            self::IncomeDonationsSponsorship,
        ], true);
    }

    public function badgeColor(): string
    {
        return $this->isIncome() ? 'success' : 'danger';
    }
}
