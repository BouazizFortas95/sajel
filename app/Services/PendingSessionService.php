<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds recurring timetable sessions that are already over but still do not
 * have a confirmed register entry.
 *
 * The rules intentionally match the dashboard reminder widget:
 * - the window is Sunday through today in the school timezone;
 * - school holidays are skipped;
 * - today's session is overdue only after its end time;
 * - completed and cancelled daily logs are considered resolved;
 * - a planned log is still pending completion.
 */
class PendingSessionService
{
    /**
     * @return Collection<int, array{
     *     date: Carbon,
     *     schedule: Schedule,
     *     state: 'missing'|'planned',
     *     log: DailyLog|null,
     *     url: string
     * }>
     */
    public function forUser(
        User $user,
        ?int $classroomId = null,
        ?int $subjectId = null,
        ?Carbon $now = null,
    ): Collection {
        $timezone = config('app.timezone', 'Africa/Algiers');
        $now = ($now ?? now($timezone))->copy()->setTimezone($timezone);
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();

        // Map each day that has occurred this week to its calendar date.
        $datesByDay = collect();

        for ($date = $weekStart->copy(); $date->lte($today); $date->addDay()) {
            if (SchoolHoliday::isHoliday($date)) {
                continue;
            }

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
                'user',
                'subject',
                'classroom',
                'group.classroom',
                'dailyLogs' => fn ($query) => $query
                    ->whereDate('date', '>=', $weekStart->toDateString())
                    ->whereDate('date', '<=', $today->toDateString()),
            ])
            ->when($user->isTeacher(), fn (Builder $query): Builder => $query->forTeacher($user))
            ->when(
                $classroomId,
                fn (Builder $query): Builder => $this->whereClassroom($query, $classroomId),
            )
            ->when($subjectId, fn (Builder $query): Builder => $query->where('subject_id', $subjectId))
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

    public function countForUser(
        User $user,
        ?int $classroomId = null,
        ?int $subjectId = null,
        ?Carbon $now = null,
    ): int {
        return $this->forUser($user, $classroomId, $subjectId, $now)->count();
    }

    private function whereClassroom(Builder $query, int $classroomId): Builder
    {
        return $query->where(
            fn (Builder $query): Builder => $query
                ->where('classroom_id', $classroomId)
                ->orWhereHas(
                    'group',
                    fn (Builder $query): Builder => $query->where('classroom_id', $classroomId),
                ),
        );
    }
}
