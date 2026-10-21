<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
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
     * Build the fixed timetable matrix:
     * [day => [slot start => Schedule|null]].
     *
     * @return array<string, array<string, Schedule|null>>
     */
    #[Computed]
    public function grid(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $schedules = Schedule::query()
            ->with(['subject', 'classroom', 'group.classroom'])
            ->forTeacher($user)
            ->get();

        // Index once, keyed by day and start time. If a teacher somehow has
        // two schedules at the same time, the first one wins the cell.
        $mapped = [];

        foreach ($schedules as $schedule) {
            $day = $schedule->day_of_week?->value;
            $start = $schedule->start_time?->format('H:i');

            if ($day === null || $start === null) {
                continue;
            }

            $mapped[$day][$start] ??= $schedule;
        }

        $grid = [];

        foreach (self::SCHOOL_DAYS as $day) {
            foreach ($this->timeSlots() as $slot) {
                $grid[$day][$slot['start']] = $mapped[$day][$slot['start']] ?? null;
            }
        }

        return $grid;
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
        ]);
    }
}
