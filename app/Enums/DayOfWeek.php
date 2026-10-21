<?php

namespace App\Enums;

use Carbon\Carbon;
use Carbon\CarbonInterface;

enum DayOfWeek: string
{
    case Sunday = 'sunday';
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';

    public function label(): string
    {
        return match ($this) {
            self::Sunday => __('الأحد'),
            self::Monday => __('الاثنين'),
            self::Tuesday => __('الثلاثاء'),
            self::Wednesday => __('الأربعاء'),
            self::Thursday => __('الخميس'),
            self::Friday => __('الجمعة'),
            self::Saturday => __('السبت'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sunday, self::Tuesday, self::Thursday => 'info',
            self::Monday, self::Wednesday => 'primary',
            self::Friday, self::Saturday => 'gray',
        };
    }

    /**
     * The date of this weekday in the reference week (Sunday-first).
     */
    public function dateInCurrentWeek(?CarbonInterface $reference = null): Carbon
    {
        $reference = $reference ?? today();

        $index = array_search($this, self::cases(), true);

        return Carbon::instance($reference)
            ->startOfWeek(Carbon::SUNDAY)
            ->addDays($index === false ? 0 : $index);
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
