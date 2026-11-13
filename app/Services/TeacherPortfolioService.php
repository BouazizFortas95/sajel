<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use App\Models\PedagogicalMeeting;
use App\Models\PedagogicalMemo;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Concerns\ConfiguresBrowsershot;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;

class TeacherPortfolioService
{
    use ConfiguresBrowsershot;

    public function viewData(User $teacher): array
    {
        $scheduleQuery = Schedule::query()->where('user_id', $teacher->id);

        $completedLogs = DailyLog::query()
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacher->id))
            ->where('status', DailyLogStatus::Completed->value)
            ->count();

        $reviewedLogs = DailyLog::query()
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacher->id))
            ->whereNotNull('reviewed_at')
            ->count();

        $memoCount = PedagogicalMemo::where('user_id', $teacher->id)->where('is_archived', false)->count();

        $meetings = PedagogicalMeeting::where('user_id', $teacher->id)
            ->orderByDesc('date')
            ->get();

        return [
            'teacher' => $teacher,
            'completedLogs' => $completedLogs,
            'reviewedLogs' => $reviewedLogs,
            'memoCount' => $memoCount,
            'meetings' => $meetings,
            'schedules' => $scheduleQuery->with(['subject', 'classroom', 'group.classroom'])->get(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
            'inlineCss' => false,
        ];
    }

    public function renderHtml(User $teacher): string
    {
        return view('print.teacher-portfolio', $this->viewData($teacher))->render();
    }

    public function generatePdf(User $teacher): string
    {
        $html = $this->renderHtml($teacher);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    private function inlineCss(string $html, string $css): string
    {
        $baseUrl = e(rtrim((string) config('app.url'), '/').'/');

        return str_replace(
            '</head>',
            '<base href="'.$baseUrl.'"><style>'.str_replace('</style', '<\/style', $css).'</style></head>',
            $html,
        );
    }

    private function compiledCss(): ?string
    {
        try {
            return Vite::content('resources/css/app.css');
        } catch (ViteException) {
            return null;
        }
    }
}
