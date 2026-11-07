<?php

namespace App\Enums;

enum CooperativeProjectType: string
{
    case SolidarityCharity = 'solidarity_charity';
    case HandicraftsExhibition = 'handicrafts_exhibition';
    case BotanicalGardening = 'botanical_gardening';
    case RecyclingEcoDrive = 'recycling_eco_drive';
    case SchoolCleanlinessDay = 'school_cleanliness_day';
    case CulturalBookSale = 'cultural_book_sale';

    public function label(): string
    {
        return match ($this) {
            self::SolidarityCharity => __('حملة تضامن وخيرية (Soutien solidaire et caritatif)'),
            self::HandicraftsExhibition => __('معرض الأشغال اليدوية والرسم (Exposition d\'artisanat et dessin)'),
            self::BotanicalGardening => __('بستنة وزراعة الحديقة البيداغوجية (Jardinage et botanique pédagogique)'),
            self::RecyclingEcoDrive => __('رسكلة وفرز النفايات المدرسية (Tri sélectif et recyclage scolaire)'),
            self::SchoolCleanlinessDay => __('يوم نظافة وتزيين المحيط المدرسي (Journée d\'embellissement et propreté)'),
            self::CulturalBookSale => __('سوق الكتاب المدرسي والوسائل (Bourse aux livres et fournitures)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::SolidarityCharity => 'danger',
            self::HandicraftsExhibition => 'warning',
            self::BotanicalGardening => 'success',
            self::RecyclingEcoDrive => 'info',
            self::SchoolCleanlinessDay => 'primary',
            self::CulturalBookSale => 'gray',
        };
    }
}

