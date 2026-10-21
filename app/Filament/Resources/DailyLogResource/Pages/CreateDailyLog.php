<?php

namespace App\Filament\Resources\DailyLogResource\Pages;

use App\Enums\DailyLogStatus;
use App\Filament\Resources\DailyLogResource;
use App\Models\Schedule;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyLog extends CreateRecord
{
    protected static string $resource = DailyLogResource::class;

    /**
     * Allow the weekly schedule to deep-link into a prefilled log:
     * /daily-logs/create?schedule_id={id}
     */
    public function mount(): void
    {
        parent::mount();

        $this->prefillFromSchedule();
    }

    protected function prefillFromSchedule(): void
    {
        $scheduleId = (int) request()->query('schedule_id');

        if ($scheduleId <= 0) {
            return;
        }

        $user = auth()->user();

        if (! $user) {
            return;
        }

        $schedule = Schedule::query()
            ->with('subject')
            ->when(! $user->isAdmin(), fn ($query) => $query->forTeacher($user))
            ->find($scheduleId);

        if (! $schedule) {
            return;
        }

        $this->form->fill([
            'schedule_id' => $schedule->id,
            'date' => request()->date('date')?->toDateString() ?? today()->toDateString(),
            'title' => __('درس: ').($schedule->subject?->name ?? ''),
            'status' => DailyLogStatus::Planned->value,
        ]);
    }
}
