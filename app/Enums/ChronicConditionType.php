<?php

namespace App\Enums;

enum ChronicConditionType: string
{
    case None = 'none';
    case Asthma = 'asthma';
    case Diabetes = 'diabetes';
    case Epilepsy = 'epilepsy';
    case Cardiac = 'cardiac';
    case SevereAllergy = 'severe_allergy';
    case VisualImpairment = 'visual_impairment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::None => __('سليم / لا توجد أمراض مزمنة'),
            self::Asthma => __('الربو وأمراض الجهاز التنفسي (Asthme)'),
            self::Diabetes => __('داء السكري (Diabète)'),
            self::Epilepsy => __('الصرع والتشنجات العصبية (Épilepsie)'),
            self::Cardiac => __('أمراض القلب والأوعية (Cardiopathie)'),
            self::SevereAllergy => __('الحساسية المفرطة والغذائية (Allergie)'),
            self::VisualImpairment => __('قصور حاد في البصر (Déficience visuelle)'),
            self::Other => __('حالة صحية خاصة أخرى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'success',
            self::Asthma => 'info',
            self::Diabetes => 'warning',
            self::Epilepsy => 'danger',
            self::Cardiac => 'danger',
            self::SevereAllergy => 'amber',
            self::VisualImpairment => 'purple',
            self::Other => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::None => '💚',
            self::Asthma => '🫁',
            self::Diabetes => '🩸',
            self::Epilepsy => '⚡',
            self::Cardiac => '❤️',
            self::SevereAllergy => '⚠️',
            self::VisualImpairment => '👓',
            self::Other => '🩺',
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
