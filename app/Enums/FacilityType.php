<?php

namespace App\Enums;

enum FacilityType: string
{
    case PedagogicalWing = 'pedagogical_wing';
    case AdministrativeWing = 'administrative_wing';
    case BoardingDormitory = 'boarding_dormitory';
    case RefectoryKitchen = 'refectory_kitchen';
    case SportsHall = 'sports_hall';
    case WorkshopLab = 'workshop_lab';
    case StaffHousing = 'staff_housing';
    case ExteriorYard = 'exterior_yard';

    public function label(): string
    {
        return match ($this) {
            self::PedagogicalWing => __('جناح تربوي وبيداغوجي (Aile pédagogique)'),
            self::AdministrativeWing => __('جناح الإدارة العامة (Bloc administratif)'),
            self::BoardingDormitory => __('مرقد الجناح الداخلي (Pavillon d\'internat / Dortoir)'),
            self::RefectoryKitchen => __('المطعم ونصف الداخلي والمطبخ (Demi-pension / Réfectoire & Cuisine)'),
            self::SportsHall => __('قاعة الرياضة والملاعب (Salle omnisports & Terrains)'),
            self::WorkshopLab => __('مخابر العلوم والتكنولوجيا والورشات (Laboratoires & Ateliers)'),
            self::StaffHousing => __('سكن وظيفي إلزامي (Logement d\'astreinte)'),
            self::ExteriorYard => __('الفناء والساحات والمحيط الخارجي (Cour & Espaces extérieurs)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PedagogicalWing => 'primary',
            self::AdministrativeWing => 'info',
            self::BoardingDormitory => 'warning',
            self::RefectoryKitchen => 'success',
            self::SportsHall => 'danger',
            self::WorkshopLab => 'primary',
            self::StaffHousing => 'gray',
            self::ExteriorYard => 'gray',
        };
    }
}

