<?php

namespace App\Filament\Widgets;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class UnloggedSessionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.unlogged-sessions-widget';

    /**
     * Render immediately so overdue sessions are visible in the first paint.
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * Only teachers need missed-session reminders.
     */
    public static function canView(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    /**
     * Sessions from Sunday through today whose end time has passed and whose
     * DailyLog is either missing or still in the "planned" state.
     *
     * @return Collection<int, array{
     *     date: Carbon,
     *     schedule: Schedule,
     *     state: 'missing'|'planned',
     *     log: DailyLog|null,
     *     url: string
     * }>
     */
    public function getPendingSessions(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isTeacher()) {
            return collect();
        }

        $timezone = config('app.timezone', 'Africa/Algiers');
        $now = now($timezone);
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();

        // Map each day that has occurred this week to its calendar date.
        $datesByDay = collect();

        for ($date = $weekStart->copy(); $date->lte($today); $date->addDay()) {
            $datesByDay->put(
                DayOfWeek::cases()[$date->dayOfWeek]->value,
                $date->copy(),
            );
        }

        if ($datesByDay->isEmpty()) {
            return collect();
        }

        $schedules = Schedule::query()
            ->with([
                'subject',
                'classroom',
                'group.classroom',
                'dailyLogs' => fn ($query) => $query->whereBetween('date', [
                    $weekStart->toDateString(),
                    $today->toDateString(),
                ]),
            ])
            ->forTeacher($user)
            ->whereIn('day_of_week', $datesByDay->keys()->all())
            ->orderBy('start_time')
            ->get();

        $pending = collect();

        foreach ($schedules as $schedule) {
            $day = $schedule->day_of_week;
            $date = $day ? $datesByDay->get($day->value) : null;
            $start = $schedule->start_time;
            $end = $schedule->end_time;

            if (! $date instanceof Carbon || ! $start || ! $end) {
                continue;
            }

            // Today's lessons are only overdue after their end time.
            $endsAt = $date->copy()->setTime($end->hour, $end->minute);

            if ($date->isSameDay($today) && $endsAt->gt($now)) {
                continue;
            }

            $log = $schedule->dailyLogs->first(
                fn (DailyLog $log): bool => $log->date->isSameDay($date),
            );

            if ($log && in_array($log->status, [
                DailyLogStatus::Completed,
                DailyLogStatus::Cancelled,
            ], true)) {
                continue;
            }

            $pending->push([
                'date' => $date->copy(),
                'schedule' => $schedule,
                'state' => $log ? 'planned' : 'missing',
                'log' => $log,
                'url' => $log
                    ? DailyLogResource::getUrl('edit', [
                        'record' => $log,
                        'locale' => app()->getLocale(),
                    ])
                    : DailyLogResource::getUrl('create', [
                        'schedule_id' => $schedule->id,
                        'date' => $date->toDateString(),
                        'locale' => app()->getLocale(),
                    ]),
            ]);
        }

        return $pending
            ->sortBy(fn (array $task): string => $task['date']->toDateString().' '.$task['schedule']->start_time->format('H:i'))
            ->values();
    }

    public function render(): View
    {
        return view(static::$view, [
            'pendingSessions' => $this->getPendingSessions(),
        ]);
    }
}
