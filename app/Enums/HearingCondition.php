<?php

namespace App\Enums;

enum HearingCondition: string
{
    case Normal = 'normal';
    case MildImpairment = 'mild_impairment';
    case SevereImpairment = 'severe_impairment';
    case UsesHearingAid = 'uses_hearing_aid';

    public function label(): string
    {
        return match ($this) {
            self::Normal => __('سمع سليم وطبيعي (Audition normale)'),
            self::MildImpairment => __('نقص سمع خفيف (Baisse auditive légère)'),
            self::SevereImpairment => __('نقص سمع متوسط إلى شديد (Déficience auditive)'),
            self::UsesHearingAid => __('يستخدم سماعة طبية (Appareil auditif)'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::MildImpairment => 'warning',
            self::SevereImpairment => 'danger',
            self::UsesHearingAid => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}

