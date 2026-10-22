<?php

namespace App\Http\Controllers;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PrintDailyLogsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $user = $request->user();

        $logs = DailyLog::query()
            ->with([
                'schedule.classroom',
                'schedule.subject',
                'schedule.group',
                'schedule.user',
            ])
            ->whereIn('id', $validated['ids'])
            ->where('status', DailyLogStatus::Completed->value)
            ->when(
                ! $user->isAdmin(),
                fn (Builder $query): Builder => $query->forTeacher($user),
            )
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $teacherNames = $logs
            ->pluck('schedule.user.name')
            ->filter()
            ->unique()
            ->implode(' / ');

        $academicYears = $logs
            ->pluck('schedule.classroom.academic_year')
            ->filter()
            ->unique()
            ->implode(' / ');

        return view('print.register', [
            'logs' => $logs,
            'teacherName' => $teacherNames !== '' ? $teacherNames : $user->name,
            'academicYear' => $academicYears !== '' ? $academicYears : $this->currentAcademicYear(),
        ]);
    }

    private function currentAcademicYear(): string
    {
        $startYear = now()->month >= 9 ? now()->year : now()->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
