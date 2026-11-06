<?php

namespace App\Enums;

enum ClubActivityType: string
{
    case Workshop = 'workshop';
    case Exhibition = 'exhibition';
    case Competition = 'competition';
    case FieldTrip = 'field_trip';
    case Commemoration = 'commemoration';
    case AwarenessCampaign = 'awareness_campaign';

    public function label(): string
    {
        return match ($this) {
            self::Workshop => __('ورشة تدريبية وتطبيقية'),
            self::Exhibition => __('معرض مدرسي أو إنتاج فني'),
            self::Competition => __('مسابقة وتنافس تربوي'),
            self::FieldTrip => __('خرجة ميدانية / زيارة استكشافية'),
            self::Commemoration => __('إحياء مناسبة وطنية أو عالمية'),
            self::AwarenessCampaign => __('حملة تحسيسية وتوعوية'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Workshop => 'info',
            self::Exhibition => 'warning',
            self::Competition => 'danger',
            self::FieldTrip => 'success',
            self::Commemoration => 'purple',
            self::AwarenessCampaign => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Workshop => '🛠️',
            self::Exhibition => '🎨',
            self::Competition => '🏆',
            self::FieldTrip => '🚌',
            self::Commemoration => '🇩🇿',
            self::AwarenessCampaign => '📢',
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
