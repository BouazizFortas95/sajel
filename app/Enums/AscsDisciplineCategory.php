<?php

namespace App\Enums;

enum AscsDisciplineCategory: string
{
    case TeamSport = 'team_sport';
    case IndividualSport = 'individual_sport';
    case MindSport = 'mind_sport';
    case ArtisticCultural = 'artistic_cultural';

    public function label(): string
    {
        return match ($this) {
            self::TeamSport => __('رياضة جماعية (Sport collectif)'),
            self::IndividualSport => __('رياضة فردية وألعاب قوى (Sport individuel / Athlétisme)'),
            self::MindSport => __('رياضة فكرية وذهنية (Sport de l\'esprit / Échecs)'),
            self::ArtisticCultural => __('نشاط ثقافي وفني ومسرح (Activité culturelle et artistique)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::TeamSport => 'primary',
            self::IndividualSport => 'warning',
            self::MindSport => 'info',
            self::ArtisticCultural => 'success',
        };
    }
}
