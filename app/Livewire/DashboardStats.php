<?php

namespace App\Livewire;

use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Livewire\Attributes\Url;
use Livewire\Component;

class DashboardStats extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    #[Url(as: 'period')]
    public string $period = 'today';

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'subject')]
    public ?int $subjectId = null;

    public function mount(): void
    {
        $user = $this->user();
        $this->activeTab = $this->normaliseTab($user, $this->activeTab);
        $this->period = $this->normalisePeriod($this->period);
        $this->validateFilters($user);
    }

    public function isAdmin(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Backwards-compatible shortcut used by earlier dashboard tests and any
     * external caller. The tabbed views read the service directly.
     */
    public function dailyLogsCount(): int
    {
        return $this->overviewData()['logs_today'];
    }

    public function completedCount(): int
    {
        return $this->overviewData()['completed_today'];
    }

    public function schedulesCount(): int
    {
        return $this->overviewData()['weekly_sessions'];
    }

    public function subjectsCount(): int
    {
        return $this->overviewData()['subjects_count'];
    }

    public function setTab(string $tab): void
    {
        $user = $this->user();

        abort_unless(
            in_array($tab, array_column($this->availableTabs($user), 'key'), true),
            403,
        );

        $this->activeTab = $tab;
    }

    public function updatedPeriod(string $period): void
    {
        $this->period = $this->normalisePeriod($period);
    }

    public function updatedClassroomId(mixed $value): void
    {
        $this->classroomId = $this->normaliseNullableId($value);
        $this->validateFilters($this->user());
    }

    public function updatedSubjectId(mixed $value): void
    {
        $this->subjectId = $this->normaliseNullableId($value);
        $this->validateFilters($this->user());
    }

    public function clearFilters(): void
    {
        $this->period = 'today';
        $this->classroomId = null;
        $this->subjectId = null;
    }

    public function render()
    {
        $user = $this->user();
        $service = app(DashboardMetricsService::class);
        $tabs = $this->availableTabs($user);

        // Re-check on every render so a forged Livewire property cannot expose
        // an administrative or unrelated dataset.
        abort_unless(
            in_array($this->activeTab, array_column($tabs, 'key'), true),
            403,
        );

        $this->period = $this->normalisePeriod($this->period);
        $options = $service->filterOptions($user);
        $this->validateFilters($user, $options);

        $settings = SchoolSetting::current();
        $data = match ($this->activeTab) {
            'schedule' => $service->schedule($user, $this->classroomId, $this->subjectId),
            'attendance' => $service->attendance($user, $this->period, $this->classroomId, $this->subjectId),
            'lessons' => $service->lessons($user, $this->period, $this->classroomId, $this->subjectId),
            'assessments' => $service->assessments($user, $this->period, $this->classroomId, $this->subjectId),
            'curriculum' => $service->curriculum($user, $this->classroomId, $this->subjectId),
            'support' => $service->support($user, $this->period, $this->classroomId, $this->subjectId),
            'institutional' => $service->institutional($user),
            default => $service->overview($user, $this->classroomId, $this->subjectId),
        };

        return view('livewire.dashboard-stats', [
            'user' => $user,
            'isAdmin' => $user->isAdmin(),
            'service' => $service,
            'tabs' => $tabs,
            'data' => $data,
            'options' => $options,
            'settings' => $settings,
            'today' => $service->today(),
            'todayLabel' => $service->today()
                ->locale(app()->getLocale())
                ->translatedFormat('l j F Y'),
            'periods' => $this->periodOptions(),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, icon: string}>
     */
    public function availableTabs(User $user): array
    {
        $tabs = [
            ['key' => 'overview', 'label' => __('نظرة عامة'), 'icon' => 'heroicon-o-squares-2x2'],
            ['key' => 'schedule', 'label' => __('الجدول الزمني'), 'icon' => 'heroicon-o-calendar-days'],
            ['key' => 'attendance', 'label' => __('الحضور والغياب'), 'icon' => 'heroicon-o-clipboard-document-check'],
            ['key' => 'lessons', 'label' => __('الدروس والسجل'), 'icon' => 'heroicon-o-book-open'],
            ['key' => 'assessments', 'label' => __('التقييم والتعلّم'), 'icon' => 'heroicon-o-clipboard-document-list'],
            ['key' => 'curriculum', 'label' => __('المنهاج'), 'icon' => 'heroicon-o-chart-bar'],
            ['key' => 'support', 'label' => __('التلاميذ والمتابعة'), 'icon' => 'heroicon-o-heart'],
        ];

        if ($user->isAdmin()) {
            $tabs[] = ['key' => 'institutional', 'label' => __('المؤسسة'), 'icon' => 'heroicon-o-building-library'];
        }

        return $tabs;
    }

    /**
     * @return array<string, string>
     */
    public function periodOptions(): array
    {
        return [
            'today' => __('اليوم الحالي'),
            'week' => __('هذا الأسبوع'),
            'month' => __('هذا الشهر'),
        ];
    }

    private function normaliseTab(User $user, string $tab): string
    {
        return in_array($tab, array_column($this->availableTabs($user), 'key'), true)
            ? $tab
            : 'overview';
    }

    private function normalisePeriod(string $period): string
    {
        return array_key_exists($period, $this->periodOptions()) ? $period : 'today';
    }

    /**
     * Filters can only narrow a user's own scope. A forged classroom or
     * subject ID receives a 403 rather than silently exposing another class.
     *
     * @param  array{classrooms: array<int, string>, subjects: array<int, string>}|null  $options
     */
    private function validateFilters(User $user, ?array $options = null): void
    {
        $options ??= app(DashboardMetricsService::class)->filterOptions($user);

        if ($this->classroomId !== null) {
            abort_unless(array_key_exists($this->classroomId, $options['classrooms']), 403);
        }

        if ($this->subjectId !== null) {
            abort_unless(array_key_exists($this->subjectId, $options['subjects']), 403);
        }
    }

    private function normaliseNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);

        return $id === false ? null : $id;
    }

    private function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Legacy values once exposed to the Blade view. Kept so existing callers
     * do not need to know about the tabbed implementation.
     *
     * @return array<string, mixed>
     */
    private function overviewData(): array
    {
        $user = $this->user();

        return app(DashboardMetricsService::class)->overview(
            $user,
            $this->classroomId,
            $this->subjectId,
        );
    }
}
