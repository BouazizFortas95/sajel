<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\MySchedule;
use App\Filament\Resources\ClassroomResource;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\EducationPhaseResource;
use App\Filament\Resources\GroupResource;
use App\Filament\Resources\LevelResource;
use App\Filament\Resources\ScheduleResource;
use App\Filament\Resources\SubjectResource;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class QuickLinks extends Widget
{
    protected static string $view = 'filament.widgets.quick-links';

    /**
     * Render server-side so the links are present in the initial HTML.
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function render(): View
    {
        return view(static::$view, [
            'links' => $this->getLinks(),
        ]);
    }

    /**
     * Quick navigation cards for the dashboard.
     *
     * Only links the current user is authorized to view are returned,
     * so teachers never see administrative-only screens they cannot open.
     *
     * @return list<array{label: string, description: string, icon: string, url: string}>
     */
    protected function getLinks(): array
    {
        $links = [];

        // My Schedule — page-level access check
        if (MySchedule::canAccess()) {
            $links[] = [
                'label' => __('جدولي الأسبوعي'),
                'description' => __('حصصك اليومية والوصول المباشر لسجلاتها'),
                'icon' => 'calendar',
                'url' => MySchedule::getUrl(),
            ];
        }

        // Daily Logs — list and create
        if (Gate::check('viewAny', DailyLog::class)) {
            $links[] = [
                'label' => __('دفتر النصوص'),
                'description' => __('سجلاتك اليومية والتحضير'),
                'icon' => 'book',
                'url' => DailyLogResource::getUrl(),
            ];
        }

        if (Gate::check('create', DailyLog::class)) {
            $links[] = [
                'label' => __('سجل يومي جديد'),
                'description' => __('ابدأ تحضير حصة اليوم'),
                'icon' => 'plus',
                'url' => DailyLogResource::getUrl('create'),
            ];
        }

        // Schedules
        if (Gate::check('viewAny', Schedule::class)) {
            $links[] = [
                'label' => __('الحصص'),
                'description' => __('جدولك الأسبوعي'),
                'icon' => 'calendar',
                'url' => ScheduleResource::getUrl(),
            ];
        }

        // Subjects
        if (Gate::check('viewAny', Subject::class)) {
            $links[] = [
                'label' => __('المواد'),
                'description' => __('إدارة المواد وألوانها'),
                'icon' => 'palette',
                'url' => SubjectResource::getUrl(),
            ];
        }

        // Classrooms
        if (Gate::check('viewAny', Classroom::class)) {
            $links[] = [
                'label' => __('الأقسام'),
                'description' => __('الأقسام والسنوات الدراسية'),
                'icon' => 'users',
                'url' => ClassroomResource::getUrl(),
            ];
        }

        // Levels
        if (Gate::check('viewAny', Level::class)) {
            $links[] = [
                'label' => __('المستويات'),
                'description' => __('مستويات كل أساس تعليمي'),
                'icon' => 'academic',
                'url' => LevelResource::getUrl(),
            ];
        }

        // Groups
        if (Gate::check('viewAny', Group::class)) {
            $links[] = [
                'label' => __('المجموعات'),
                'description' => __('تقسيمات الأقسام الفرعية'),
                'icon' => 'user-group',
                'url' => GroupResource::getUrl(),
            ];
        }

        // Education Phases
        if (Gate::check('viewAny', EducationPhase::class)) {
            $links[] = [
                'label' => __('الأساسات التعليمية'),
                'description' => __('المراحل وإعداداتها'),
                'icon' => 'building',
                'url' => EducationPhaseResource::getUrl(),
            ];
        }

        return $links;
    }
}
