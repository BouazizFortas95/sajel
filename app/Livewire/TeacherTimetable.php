<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class TeacherTimetable extends Component
{
    /**
     * Open the daily log for a schedule slot: edit today's log when it
     * exists, otherwise create it with the schedule prefilled.
     */
    public function openLogForSchedule(int $scheduleId): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isTeacher(), 403);

        $schedule = Schedule::query()
            ->forTeacher($user)
            ->findOrFail($scheduleId);

        $date = $schedule->day_of_week->dateInCurrentWeek();

        $log = $schedule->dailyLogs()->forDate($date)->first();

        $this->redirect($log
            ? DailyLogResource::getUrl('edit', [
                'record' => $log,
                'locale' => app()->getLocale(),
            ])
            : DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
                'locale' => app()->getLocale(),
            ]));
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isTeacher(), 403);

        $schedulesByDay = Schedule::query()
            ->with(['subject', 'classroom', 'group.classroom'])
            ->forTeacher($user)
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Schedule $schedule): string => $schedule->day_of_week->value);

        $today = DayOfWeek::cases()[now()->dayOfWeek];

        $days = collect(DayOfWeek::cases())
            ->map(fn (DayOfWeek $day): array => [
                'value' => $day->value,
                'label' => $day->label(),
                'isToday' => $day === $today,
                'schedules' => $schedulesByDay->get($day->value, new Collection),
            ]);

        return view('livewire.teacher-timetable', [
            'days' => $days,
        ]);
    }
}
