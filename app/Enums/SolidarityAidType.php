<?php

namespace App\Enums;

enum SolidarityAidType: string
{
    case FreeTextbooks = 'free_textbooks';
    case SchoolBag = 'school_bag';
    case StationerySupplies = 'stationery_supplies';
    case ClothingAid = 'clothing_aid';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FreeTextbooks => __('مجانية طقم الكتب المدرسية'),
            self::SchoolBag => __('الحقيبة والمحفظة المدرسية المجهزة'),
            self::StationerySupplies => __('مستلزمات الأدوات والكراريس المدرسية'),
            self::ClothingAid => __('كسوة التضامن المدرسي (الملابس والأحذية)'),
            self::Other => __('إعانات تضامنية ومساعدات عينية أخرى'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::FreeTextbooks => 'primary',
            self::SchoolBag => 'success',
            self::StationerySupplies => 'info',
            self::ClothingAid => 'warning',
            self::Other => 'gray',
        };
    }
}
