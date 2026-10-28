<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClassroomToolbar extends Component
{
    public ?int $selectedScheduleId = null;

    public int $groupCount = 3;

    /**
     * @var list<array{id: int, name: string}>
     */
    #[Locked]
    public array $students = [];

    /**
     * @var list<int>
     */
    #[Locked]
    public array $picked = [];

    /**
     * @var list<list<int>>
     */
    #[Locked]
    public array $groups = [];

    public function mount(): void
    {
        $user = $this->user();
        $sessions = $this->availableSessions($user);

        $this->selectedScheduleId = $this->defaultScheduleId($sessions);
        $this->loadStudents();
    }

    public function updatedSelectedScheduleId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->selectedScheduleId = null;
            $this->resetInteractions();
            $this->loadStudents();

            return;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);
        abort_unless($id !== false, 403);

        $this->selectedScheduleId = $id;
        $this->resetInteractions();
        $this->loadStudents();
    }

    public function updatedGroupCount(mixed $value): void
    {
        $this->groupCount = max(2, min(6, (int) $value));
    }

    public function pickRandom(): void
    {
        if ($this->students === []) {
            return;
        }

        $studentIds = array_map('intval', array_column($this->students, 'id'));
        $remaining = array_values(array_diff($studentIds, $this->picked));

        // Every student has had a turn: start a fresh round automatically.
        if ($remaining === []) {
            $this->picked = [];
            $remaining = $studentIds;
        }

        $this->picked[] = $remaining[array_rand($remaining)];
    }

    public function resetPicked(): void
    {
        $this->picked = [];
    }

    public function generateGroups(): void
    {
        if ($this->students === []) {
            return;
        }

        $studentIds = array_map('intval', array_column($this->students, 'id'));
        shuffle($studentIds);

        $requested = max(2, min(6, $this->groupCount));
        $groupCount = min($requested, count($studentIds));
        $baseSize = intdiv(count($studentIds), $groupCount);
        $remainder = count($studentIds) % $groupCount;

        $groups = [];
        $offset = 0;

        for ($index = 0; $index < $groupCount; $index++) {
            $size = $baseSize + ($index < $remainder ? 1 : 0);
            $groups[] = array_slice($studentIds, $offset, $size);
            $offset += $size;
        }

        $this->groups = $groups;
    }

    public function render()
    {
        $user = $this->user();
        $sessions = $this->availableSessions($user);
        $selected = $this->selectedScheduleId === null
            ? null
            : $sessions->firstWhere('id', $this->selectedScheduleId);

        // A forged Livewire property must never load another teacher's roster.
        abort_if($this->selectedScheduleId !== null && $selected === null, 403);

        $studentNames = collect($this->students)->pluck('name', 'id')->all();
        $currentPickId = $this->picked === []
            ? null
            : (int) $this->picked[count($this->picked) - 1];

        return view('livewire.classroom-toolbar', [
            'isAdmin' => $user->isAdmin(),
            'selectedSchedule' => $selected,
            'scheduleOptions' => $sessions
                ->map(fn (Schedule $schedule): array => [
                    'id' => $schedule->id,
                    'label' => $this->scheduleLabel($schedule, $user),
                ])
                ->all(),
            'studentNames' => $studentNames,
            'currentPickName' => $currentPickId === null
                ? null
                : ($studentNames[$currentPickId] ?? null),
            'remaining' => max(0, count($this->students) - count($this->picked)),
        ]);
    }

    private function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Teachers can choose any lesson in their own timetable, with today's
     * lessons first. Administrators get today's school-wide lessons so the
     * selector stays short while still exposing a usable roster.
     *
     * @return Collection<int, Schedule>
     */
    private function availableSessions(User $user): Collection
    {
        $timezone = config('app.timezone', 'Africa/Algiers');
        $today = DayOfWeek::cases()[now($timezone)->dayOfWeek];

        $sessions = Schedule::query()
            ->with(['subject', 'classroom.level', 'group.classroom.level', 'user'])
            ->when($user->isTeacher(), fn ($query) => $query->forTeacher($user))
            ->when($user->isAdmin(), fn ($query) => $query->onDay($today))
            ->get();

        return $sessions
            ->sortBy(fn (Schedule $schedule): string => sprintf(
                '%d|%02d|%s|%s|%d',
                $schedule->day_of_week === $today ? 0 : 1,
                array_search($schedule->day_of_week, DayOfWeek::cases(), true) ?: 0,
                $schedule->start_time?->format('H:i') ?? '99:99',
                $user->isAdmin() ? ($schedule->user?->name ?? '') : '',
                $schedule->id,
            ))
            ->values();
    }

    /**
     * Prefer the lesson in progress, then the next lesson today, then the
     * first lesson of the day, then the first lesson in the timetable.
     *
     * @param  Collection<int, Schedule>  $sessions
     */
    private function defaultScheduleId(Collection $sessions): ?int
    {
        $timezone = config('app.timezone', 'Africa/Algiers');
        $now = now($timezone);
        $today = DayOfWeek::cases()[$now->dayOfWeek];
        $time = $now->format('H:i');

        $todaySessions = $sessions->filter(
            fn (Schedule $schedule): bool => $schedule->day_of_week === $today,
        );

        $current = $todaySessions->first(fn (Schedule $schedule): bool => $schedule->start_time !== null
            && $schedule->end_time !== null
            && $schedule->start_time->format('H:i') <= $time
            && $schedule->end_time->format('H:i') > $time);

        $next = $todaySessions->first(
            fn (Schedule $schedule): bool => $schedule->start_time !== null
                && $schedule->start_time->format('H:i') > $time,
        );

        return ($current ?? $next ?? $todaySessions->first() ?? $sessions->first())?->id;
    }

    private function loadStudents(): void
    {
        if ($this->selectedScheduleId === null) {
            $this->students = [];

            return;
        }

        $schedule = $this->resolveSchedule($this->selectedScheduleId);

        $this->students = Student::query()
            ->forSchedule($schedule)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->full_name,
            ])
            ->all();
    }

    private function resolveSchedule(int $scheduleId): Schedule
    {
        $user = $this->user();

        $query = Schedule::query();

        if ($user->isTeacher()) {
            $query->forTeacher($user);
        }

        $schedule = $query->find($scheduleId);

        abort_unless($schedule instanceof Schedule, 403);

        return $schedule;
    }

    private function resetInteractions(): void
    {
        $this->picked = [];
        $this->groups = [];
    }

    private function scheduleLabel(Schedule $schedule, User $user): string
    {
        $label = $schedule->label();

        if ($user->isAdmin() && $schedule->user) {
            $label .= ' | '.$schedule->user->name;
        }

        return $label;
    }
}
