<?php

namespace App\Livewire;

use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardStats extends Component
{
    use WithPagination;

    public function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function dailyLogsCount(): int
    {
        $query = DailyLog::query();
        if (! $this->isAdmin()) {
            $query->forTeacher(auth()->user());
        }

        return $query->forDate(today())->count();
    }

    public function completedCount(): int
    {
        $query = DailyLog::query();
        if (! $this->isAdmin()) {
            $query->forTeacher(auth()->user());
        }

        return $query->forDate(today())->where('status', 'completed')->count();
    }

    public function schedulesCount(): int
    {
        $query = Schedule::query();
        if (! $this->isAdmin()) {
            $query->forTeacher(auth()->user());
        }

        return $query->count();
    }

    public function subjectsCount(): int
    {
        return Subject::query()->count();
    }

    public function render()
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $dailyLogQuery = DailyLog::query()
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group'])
            ->forDate(today());

        if (! $user->isAdmin()) {
            $dailyLogQuery->forTeacher($user);
        }

        return view('livewire.dashboard-stats', [
            'dailyLogsCount' => $this->dailyLogsCount(),
            'completedCount' => $this->completedCount(),
            'schedulesCount' => $this->schedulesCount(),
            'subjectsCount' => $this->subjectsCount(),
            'today' => Carbon::today()->locale(app()->getLocale())->translatedFormat('Y/m/d'),
            'isAdmin' => $this->isAdmin(),
            'dailyLog' => $dailyLogQuery->latest('updated_at')->first(),
        ]);
    }
}
