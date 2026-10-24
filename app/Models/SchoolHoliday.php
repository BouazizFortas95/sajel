<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function scopeCoversDate($query, Carbon|string $date): void
    {
        $date = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public static function isHoliday(Carbon|string $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        return self::whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->exists();
    }

    public static function holidayDatesBetween(Carbon|string $start, Carbon|string $end): array
    {
        $startStr = $start instanceof Carbon ? $start->toDateString() : (string) $start;
        $endStr = $end instanceof Carbon ? $end->toDateString() : (string) $end;
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
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
        }
        return array_unique($dates);
    }
}