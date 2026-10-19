<?php

namespace App\Filament\Widgets;

use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SajelStats extends StatsOverviewWidget
{
    /**
     * Render server-side so stats are present in the initial HTML.
     */
    protected static bool $isLazy = false;

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make(
                'سجلات اليوم',
                DailyLog::query()->forTeacher($userId)->forDate(today())->count(),
            )
                ->description('المسجلة في دفتر النصوص')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make(
                'مؤكدة اليوم',
                DailyLog::query()
                    ->forTeacher($userId)
                    ->forDate(today())
                    ->where('status', 'completed')
                    ->count(),
            )
                ->description('اعتمدت كسجل رسمي')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make(
                'الحصص الأسبوعية',
                Schedule::query()->forTeacher($userId)->count(),
            )
                ->description('في جدولك الزمني')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make(
                'المواد',
                Subject::query()->count(),
            )
                ->description('المواد المسجلة في المنصة')
                ->descriptionIcon('heroicon-m-swatch')
                ->color('primary'),
        ];
    }
}
