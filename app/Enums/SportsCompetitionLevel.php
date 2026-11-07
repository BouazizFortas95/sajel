<?php

namespace App\Enums;

enum SportsCompetitionLevel: string
{
    case InternalInterClass = 'internal_inter_class';
    case CommunalZone = 'communal_zone';
    case DairaChampionship = 'daira_championship';
    case WilayaFinals = 'wilaya_finals';
    case NationalChampionship = 'national_championship';

    public function label(): string
    {
        return match ($this) {
            self::InternalInterClass => __('بطولة مدرسية داخلية بين الأفواج (Tournoi inter-classes)'),
            self::CommunalZone => __('تصفيات بلدية ومقاطعة مدرسية (Éliminatoires communales)'),
            self::DairaChampionship => __('بطولة الدائرة المدرسية (Championnat de Daïra)'),
            self::WilayaFinals => __('نهائيات البطولة الولائية للرياضة المدرسية (Finales de Wilaya)'),
            self::NationalChampionship => __('البطولة الوطنية المدرسية (Championnat National Scolaire)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::InternalInterClass => 'gray',
            self::CommunalZone => 'info',
            self::DairaChampionship => 'primary',
            self::WilayaFinals => 'warning',
            self::NationalChampionship => 'danger',
        };
    }
}
