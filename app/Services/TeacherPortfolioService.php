<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use App\Models\PedagogicalMeeting;
use App\Models\PedagogicalMemo;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class TeacherPortfolioService
{
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

        $memoCount = PedagogicalMemo::where('recorded_by_id', $teacher->id)->count();

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

    private function browsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle()
            ->timeout(120);

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        foreach ([
            'node_binary' => 'setNodeBinary',
            'npm_binary' => 'setNpmBinary',
            'chrome_path' => 'setChromePath',
        ] as $configKey => $method) {
            $value = config("services.browsershot.{$configKey}");

            if (filled($value)) {
                $browsershot->{$method}($value);
            }
        }

        return $browsershot;
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
