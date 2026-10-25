<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SchoolHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'type',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeCoversDate($query, CarbonInterface|string $date): void
    {
        $date = self::normalizeDate($date);

        $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public static function isHoliday(CarbonInterface|string $date): bool
    {
        $dateStr = self::normalizeDate($date);

        return self::whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public static function holidayDatesBetween(CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $startStr = self::normalizeDate($start);
        $endStr = self::normalizeDate($end);

        $rows = self::whereDate('start_date', '<=', $endStr)
            ->whereDate('end_date', '>=', $startStr)
            ->get();

        $dates = [];

        foreach ($rows as $row) {
            $current = max($startStr, $row->start_date->toDateString());
            $last = min($endStr, $row->end_date->toDateString());
            $cursor = $current;

            while ($cursor <= $last) {
                $dates[] = $cursor;
                $cursor = date('Y-m-d', strtotime($cursor.' +1 day'));
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * Normalise a date or datetime value to a plain Y-m-d string. Both
     * Carbon\Carbon and Illuminate\Support\Carbon implement CarbonInterface.
     */
    private static function normalizeDate(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();
    }
}
