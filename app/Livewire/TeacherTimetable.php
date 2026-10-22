<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeacherTimetable extends Component
{
    /**
     * Algerian middle-school week: Sunday through Thursday.
     *
     * @var list<string>
     */
    public const SCHOOL_DAYS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
    ];

    /**
     * Morning blocks: 08:00–12:00.
     *
     * @var list<array{start: string, end: string}>
     */
    public const MORNING_SLOTS = [
        ['start' => '08:00', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '11:00'],
        ['start' => '11:00', 'end' => '12:00'],
    ];

    /**
     * Afternoon blocks: 14:00–17:00 (12:00–14:00 is the lunch break).
     *
     * @var list<array{start: string, end: string}>
     */
    public const AFTERNOON_SLOTS = [
        ['start' => '14:00', 'end' => '15:00'],
        ['start' => '15:00', 'end' => '16:00'],
        ['start' => '16:00', 'end' => '17:00'],
    ];

    /**
     * Open the daily log for a schedule slot on the clicked date.
     *
     * The date is validated against the schedule's weekday in the current
     * week, so a stale page or a tampered request cannot target another
     * week. An existing log opens in edit mode; otherwise the create form
     * receives the schedule and date as query parameters.
     */
    public function openLog(int $scheduleId, string $date): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isTeacher(), 403);

        $schedule = Schedule::query()
            ->forTeacher($user)
            ->findOrFail($scheduleId);

        $timezone = config('app.timezone');
        $expectedDate = $schedule->day_of_week
            ->dateInCurrentWeek(now($timezone))
            ->startOfDay();

        $date = Carbon::hasFormat($date, 'Y-m-d')
            ? Carbon::createFromFormat('Y-m-d', $date, $timezone)->startOfDay()
            : $expectedDate;

        if (! $date->isSameDay($expectedDate)) {
            $date = $expectedDate;
        }

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

    /**
     * The actual calendar date of each school day in the current week.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function currentWeekDates(): array
    {
        $today = now(config('app.timezone'));

        return collect(self::SCHOOL_DAYS)
            ->mapWithKeys(fn (string $day): array => [
                $day => DayOfWeek::from($day)->dateInCurrentWeek($today)->toDateString(),
            ])
            ->all();
    }

    /**
     * The teacher's schedules for the current school week.
     *
     * @return Collection<int, Schedule>
     */
    #[Computed]
    public function weeklySchedules(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return new Collection;
        }

        return Schedule::query()
            ->with(['subject', 'classroom', 'group.classroom'])
            ->forTeacher($user)
            ->whereIn('day_of_week', self::SCHOOL_DAYS)
            ->get();
    }

    /**
     * Total duration of the teacher's scheduled lessons in hours.
     */
    #[Computed]
    public function totalHours(): float
    {
        return (float) $this->weeklySchedules->sum(function (Schedule $schedule): float {
            $start = $schedule->start_time;
            $end = $schedule->end_time;

            if ($start === null || $end === null) {
                return 0.0;
            }

            $startMinutes = ($start->hour * 60) + $start->minute;
            $endMinutes = ($end->hour * 60) + $end->minute;

            return max(0, $endMinutes - $startMinutes) / 60;
        });
    }

    /**
     * Build the fixed timetable matrix:
     * [day => [slot start => ['schedule' => Schedule, 'span' => n]
     *                         | ['covered' => true]
     *                         | null]].
     *
     * A schedule starting in a slot owns that row plus the next `span - 1`
     * rows. Those following rows are marked as covered so the view renders no
     * cell for them at all — omitting the cell (rather than rendering an empty
     * placeholder) is what keeps the other days aligned when a card spans
     * multiple grid rows.
     *
     * @return array<string, array<string, array{schedule: Schedule, span: int}|array{covered: true}|null>>
     */
    #[Computed]
    public function grid(): array
    {
        $slots = $this->timeSlots();
        $slotIndexes = array_flip(array_column($slots, 'start'));

        // Index once, keyed by day and start time. If a teacher somehow has
        // two schedules at the same time, the first one wins the cell.
        $mapped = [];

        foreach ($this->weeklySchedules as $schedule) {
            $day = $schedule->day_of_week?->value;
            $start = $schedule->start_time?->format('H:i');

            if ($day === null || $start === null || ! isset($slotIndexes[$start])) {
                continue;
            }

            $mapped[$day][$start] ??= $schedule;
        }

        $grid = [];

        foreach (self::SCHOOL_DAYS as $day) {
            /** @var array<string, true> $covered */
            $covered = [];

            foreach ($slots as $slot) {
                $start = $slot['start'];

                // A multi-hour lesson started in an earlier row already owns
                // this cell; the view must not render an empty placeholder.
                if (isset($covered[$start])) {
                    $grid[$day][$start] = ['covered' => true];

                    continue;
                }

                $schedule = $mapped[$day][$start] ?? null;

                if ($schedule === null) {
                    $grid[$day][$start] = null;

                    continue;
                }

                $span = $this->slotSpan($schedule, $slotIndexes[$start], $slots);

                $grid[$day][$start] = [
                    'schedule' => $schedule,
                    'span' => $span,
                ];

                for ($offset = 1; $offset < $span; $offset++) {
                    $covered[$slots[$slotIndexes[$start] + $offset]['start']] = true;
                }
            }
        }

        return $grid;
    }

    /**
     * The number of consecutive one-hour rows a schedule occupies.
     *
     * Rows are 08:00–09:00 … 11:00–12:00 and 14:00–15:00 … 16:00–17:00, so a
     * 08:00–10:00 lesson spans two rows. The walk stops when the end time is
     * reached and when the next row is not contiguous (the lunch break), which
     * guarantees a span can never cover the break row or a period heading.
     *
     * @param  list<array{start: string, end: string}>  $slots
     */
    private function slotSpan(Schedule $schedule, int $startIndex, array $slots): int
    {
        $end = $schedule->end_time;

        if ($end === null) {
            return 1;
        }

        $endMinutes = ($end->hour * 60) + $end->minute;
        $span = 1;

        for ($index = $startIndex + 1; $index < count($slots); $index++) {
            if ($slots[$index - 1]['end'] !== $slots[$index]['start']) {
                break;
            }

            if ($this->minutes($slots[$index]['start']) >= $endMinutes) {
                break;
            }

            $span++;
        }

        return $span;
    }

    /**
     * Convert an "H:i" slot boundary to minutes since midnight.
     */
    private function minutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }

    /**
     * The standard one-hour blocks, morning first.
     *
     * @return list<array{start: string, end: string}>
     */
    protected function timeSlots(): array
    {
        return array_merge(self::MORNING_SLOTS, self::AFTERNOON_SLOTS);
    }

    /**
     * Rows rendered by the view, including the period headings and lunch break.
     *
     * @return list<array{type: string, label?: string, range?: string, icon?: string, start?: string, end?: string}>
     */
    protected function slotRows(): array
    {
        return [
            ['type' => 'period', 'label' => __('الفترة الصباحية'), 'range' => '08:00 – 12:00', 'icon' => 'heroicon-o-sun'],
            ...array_map(fn (array $slot): array => ['type' => 'slot'] + $slot, self::MORNING_SLOTS),
            ['type' => 'break', 'label' => __('استراحة الغداء'), 'range' => '12:00 – 14:00', 'icon' => 'heroicon-o-clock'],
            ['type' => 'period', 'label' => __('الفترة المسائية'), 'range' => '14:00 – 17:00', 'icon' => 'heroicon-o-moon'],
            ...array_map(fn (array $slot): array => ['type' => 'slot'] + $slot, self::AFTERNOON_SLOTS),
        ];
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isTeacher(), 403);

        return view('livewire.teacher-timetable', [
            'grid' => $this->grid,
            'days' => collect(self::SCHOOL_DAYS)->map(
                fn (string $day): DayOfWeek => DayOfWeek::from($day),
            ),
            'today' => DayOfWeek::cases()[now(config('app.timezone'))->dayOfWeek],
            'weekDates' => $this->currentWeekDates,
            'rows' => $this->slotRows(),
            'totalHours' => $this->totalHours,
            'weeklyHoursLimit' => (int) ($user->weekly_hours_limit ?? 22),
        ]);
    }
}
