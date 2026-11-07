<?php

namespace App\Enums;

enum SafetyInterventionTeamType: string
{
    case FirstAid = 'first_aid';
    case EvacuationGuide = 'evacuation_guide';
    case FireContainment = 'fire_containment';
    case SecurityPerimeter = 'security_perimeter';

    public function label(): string
    {
        return match ($this) {
            self::FirstAid => __('فوج الإسعافات الأولية والتضميد (Équipe de premiers secours)'),
            self::EvacuationGuide => __('فوج التوجيه وتأمين مسالك الإخلاء (Guides & Serre-files)'),
            self::FireContainment => __('فوج التدخل الأولي والإطفاء (Première intervention incendie)'),
            self::SecurityPerimeter => __('فوج تأمين المنافذ والمحيط الخارجي (Périmètre & Sécurité)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::FirstAid => 'danger',
            self::EvacuationGuide => 'info',
            self::FireContainment => 'warning',
            self::SecurityPerimeter => 'success',
        };
    }
}
