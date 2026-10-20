<?php

namespace App\Livewire;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\DailyLog;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherInteractiveSchedule extends Component
{
    use WithPagination;

    /**
     * Currently selected day of week (e.g. 'monday', 'tuesday').
     * Defaults to today.
     */
    public string $selectedDay = '';

    /**
     * Live search query bound with Livewire's debounced live model.
     */
    public string $search = '';

    /**
     * Layout mode: 'grid' or 'list'.
     */
    public string $viewMode = 'grid';

    /**
     * ID of the currently expanded schedule card (null when none).
     */
    public ?int $expandedCardId = null;

    public function mount(): void
    {
        // Carbon 3 returns an int (0 = Sunday) for `dayOfWeek`, so map it
        // through the enum's case order to get the matching day value.
        $this->selectedDay = DayOfWeek::cases()[now()->dayOfWeek]->value;
    }

    public function updatedSelectedDay(): void
    {
        $this->expandedCardId = null;
    }

    /**
     * Fetch the teacher's schedules for the selected day, optionally filtered by search.
     * Eager-loads related subject, classroom, group, and daily logs.
     */
    #[Computed]
    public function schedules()
    {
        $query = Schedule::query()
            ->with(['subject', 'classroom', 'group', 'dailyLogs'])
            ->forTeacher(auth()->user());

        if (filled($this->selectedDay)) {
            $day = DayOfWeek::tryFrom($this->selectedDay);
            if ($day) {
                $query->onDay($day);
            }
        }

        if (filled($this->search)) {
            $query->where(function ($q): void {
                $q->whereHas('subject', fn ($s) => $s->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('classroom', fn ($c) => $c->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('group', fn ($g) => $g->where('name', 'like', "%{$this->search}%"));
            });
        }

        return $query->orderBy('start_time', 'asc')->get();
    }

    /**
     * Today's daily logs keyed by schedule_id for quick lookup in the view.
     */
    #[Computed]
    public function todaysLogs()
    {
        return DailyLog::query()
            ->forTeacher(auth()->user())
            ->forDate(today())
            ->get()
            ->keyBy('schedule_id');
    }

    /**
     * Derived stats for the dashboard header stat cards.
     */
    #[Computed]
    public function scheduleStats(): array
    {
        $schedules = $this->schedules;
        $logs = $this->todaysLogs;

        return [
            'total' => $schedules->count(),
            'planned' => $schedules->filter(fn ($s): bool => ! $logs->has($s->id))->count(),
            'completed' => $logs->filter(fn (DailyLog $log): bool => $log->status === DailyLogStatus::Completed)->count(),
            'cancelled' => $logs->filter(fn (DailyLog $log): bool => $log->status === DailyLogStatus::Cancelled)->count(),
        ];
    }

    /**
     * Change the selected day and reset any expanded card.
     */
    public function setDay(string $day): void
    {
        $this->selectedDay = $day;
        $this->expandedCardId = null;
    }

    /**
     * Toggle expansion of a schedule card to reveal its full detail.
     */
    public function toggleCard(?int $scheduleId): void
    {
        $this->expandedCardId = $this->expandedCardId === $scheduleId ? null : $scheduleId;
    }

    /**
     * Mark today's daily log for a schedule as completed (create if missing).
     */
    public function markCompleted(int $scheduleId): void
    {
        $schedule = Schedule::with('subject')->find($scheduleId);

        if (! $schedule) {
            return;
        }

        $log = $schedule->dailyLogs()->forDate(today())->first();

        if (! $log) {
            $log = DailyLog::create([
                'schedule_id' => $schedule->id,
                'date' => today(),
                'title' => 'درس: '.($schedule->subject?->name ?? ''),
                'content_data' => [],
                'status' => DailyLogStatus::Planned->value,
            ]);
        }

        $log->markAsCompleted();

        $this->dispatch('schedule-updated');

        Notification::make()
            ->title('تم تمييز الحصة كمنجزة')
            ->success()
            ->send();
    }

    /**
     * Cancel today's daily log for a schedule (create if missing).
     */
    public function cancelLog(int $scheduleId): void
    {
        $schedule = Schedule::find($scheduleId);

        if (! $schedule) {
            return;
        }

        $log = $schedule->dailyLogs()->forDate(today())->first();

        if (! $log) {
            $log = DailyLog::create([
                'schedule_id' => $schedule->id,
                'date' => today(),
                'title' => 'درس: '.($schedule->subject?->name ?? ''),
                'content_data' => [],
                'status' => DailyLogStatus::Cancelled->value,
            ]);
        } else {
            $log->status = DailyLogStatus::Cancelled->value;
            $log->save();
        }

        $this->dispatch('schedule-updated');

        Notification::make()
            ->title('تم إلغاء الحصة')
            ->warning()
            ->send();
    }

    public function render()
    {
        $days = collect(DayOfWeek::cases())->mapWithKeys(
            fn (DayOfWeek $day): array => [$day->value => $day->label()]
        );

        return view('livewire.teacher-interactive-schedule', [
            'days' => $days,
        ]);
    }
}
