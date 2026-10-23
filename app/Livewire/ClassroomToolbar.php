<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use App\Models\Student;
use Livewire\Component;

class ClassroomToolbar extends Component
{
    public int $timerSeconds = 0;

    public bool $timerRunning = false;

    public ?int $selectedScheduleId = null;

    public array $students = [];

    public array $picked = [];

    public array $groups = [];

    public int $groupCount = 3;

    public function mount(): void
    {
        $this->loadSchedule();
    }

    public function loadSchedule(): void
    {
        $sched = Schedule::query()
            ->forTeacher(auth()->id())
            ->onDay(DayOfWeek::cases()[now()->dayOfWeek])
            ->where('start_time', '<=', now()->format('H:i'))
            ->where('end_time', '>', now()->format('H:i'))
            ->first();

        $this->selectedScheduleId = $sched?->id;

        if ($sched) {
            $this->students = Student::query()
                ->forSchedule($sched)
                ->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name])
                ->toArray();
        }
    }

    public function startTimer(int $minutes): void
    {
        $this->timerSeconds = $minutes * 60;
        $this->timerRunning = true;
    }

    public function stopTimer(): void
    {
        $this->timerRunning = false;
    }

    public function tick(): void
    {
        if ($this->timerRunning && $this->timerSeconds > 0) {
            $this->timerSeconds--;
            if ($this->timerSeconds <= 0) {
                $this->timerRunning = false;
            }
        }
    }

    public function pickRandom(): void
    {
        $pool = array_diff_key(
            array_column($this->students, 'name', 'id'),
            array_flip($this->picked)
        );
        if (! $pool) {
            $this->picked = [];
            $pool = array_column($this->students, 'name', 'id');
        }
        $id = array_rand($pool);
        $this->picked[] = $id;
    }

    public function resetPicked(): void
    {
        $this->picked = [];
    }

    public function generateGroups(): void
    {
        $ids = array_column($this->students, 'id');
        shuffle($ids);
        $this->groups = array_chunk($ids, max(1, (int) ceil(count($ids) / max(1, $this->groupCount))));
    }

    public function render()
    {
        return view('livewire.classroom-toolbar', [
            'timerFormatted' => sprintf('%02d:%02d', (int) floor($this->timerSeconds / 60), $this->timerSeconds % 60),
        ]);
    }
}
