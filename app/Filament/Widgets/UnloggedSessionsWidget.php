<?php

namespace App\Filament\Widgets;

use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use App\Services\PendingSessionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class UnloggedSessionsWidget extends Widget
{
    use WithPagination;

    private const PER_PAGE = 5;

    private const PAGE_NAME = 'pendingPage';

    protected static string $view = 'filament.widgets.unlogged-sessions-widget';

    /**
     * Render immediately so overdue sessions are visible in the first paint.
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * Only teachers need missed-session reminders.
     */
    public static function canView(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    /**
     * Sessions from Sunday through today whose end time has passed and whose
     * DailyLog is either missing or still in the "planned" state.
     *
     * @return LengthAwarePaginator<int, array{
     *     date: Carbon,
     *     schedule: Schedule,
     *     state: 'missing'|'planned',
     *     log: DailyLog|null,
     *     url: string
     * }>
     */
    public function getPendingSessions(): LengthAwarePaginator
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isTeacher()) {
            return $this->paginatePendingSessions(collect());
        }

        return $this->paginatePendingSessions(
            app(PendingSessionService::class)->forUser($user),
        );
    }

    /**
     * @param  Collection<int, array{
     *     date: Carbon,
     *     schedule: Schedule,
     *     state: 'missing'|'planned',
     *     log: DailyLog|null,
     *     url: string
     * }>  $pending
     * @return LengthAwarePaginator<int, array{
     *     date: Carbon,
     *     schedule: Schedule,
     *     state: 'missing'|'planned',
     *     log: DailyLog|null,
     *     url: string
     * }>
     */
    private function paginatePendingSessions(Collection $pending): LengthAwarePaginator
    {
        $lastPage = max(1, (int) ceil($pending->count() / self::PER_PAGE));
        $page = min(max(1, Paginator::resolveCurrentPage(self::PAGE_NAME)), $lastPage);

        return new LengthAwarePaginator(
            $pending->forPage($page, self::PER_PAGE)->values(),
            $pending->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => self::PAGE_NAME,
            ],
        );
    }

    public function render(): View
    {
        return view(static::$view, [
            'pendingSessions' => $this->getPendingSessions(),
        ]);
    }
}
