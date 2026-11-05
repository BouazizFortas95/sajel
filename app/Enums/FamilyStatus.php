<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case BothParents = 'both_parents';
    case OrphanFather = 'orphan_father';
    case OrphanMother = 'orphan_mother';
    case OrphanBoth = 'orphan_both';
    case DivorcedSeparated = 'divorced_separated';
    case ParentsAbroad = 'parents_abroad';

    public function label(): string
    {
        return match ($this) {
            self::BothParents => __('أبوان معاً'),
            self::OrphanFather => __('يتيم الأب'),
            self::OrphanMother => __('يتيم الأم'),
            self::OrphanBoth => __('يتيم الأبوين'),
            self::DivorcedSeparated => __('طلاق / انفصال الوالدين'),
            self::ParentsAbroad => __('الوالدان بالمهجر / سفر دائم'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BothParents => 'emerald',
            self::OrphanFather, self::OrphanMother => 'amber',
            self::OrphanBoth => 'rose',
            self::DivorcedSeparated => 'purple',
            self::ParentsAbroad => 'blue',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BothParents => 'heroicon-o-home',
            self::OrphanFather, self::OrphanMother => 'heroicon-o-user-minus',
            self::OrphanBoth => 'heroicon-o-heart',
            self::DivorcedSeparated => 'heroicon-o-arrows-right-left',
            self::ParentsAbroad => 'heroicon-o-globe-americas',
        };
    }
}
