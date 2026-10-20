<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClassroomResource;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\EducationPhaseResource;
use App\Filament\Resources\GroupResource;
use App\Filament\Resources\LevelResource;
use App\Filament\Resources\ScheduleResource;
use App\Filament\Resources\SubjectResource;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

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
     * @return list<array{label: string, description: string, icon: string, url: string}>
     */
    protected function getLinks(): array
    {
        return [
            [
                'label' => __('دفتر النصوص'),
                'description' => __('سجلاتك اليومية والتحضير'),
                'icon' => 'book',
                'url' => DailyLogResource::getUrl(),
            ],
            [
                'label' => __('سجل يومي جديد'),
                'description' => __('ابدأ تحضير حصة اليوم'),
                'icon' => 'plus',
                'url' => DailyLogResource::getUrl('create'),
            ],
            [
                'label' => __('الحصص'),
                'description' => __('جدولك الأسبوعي'),
                'icon' => 'calendar',
                'url' => ScheduleResource::getUrl(),
            ],
            [
                'label' => __('المواد'),
                'description' => __('إدارة المواد وألوانها'),
                'icon' => 'palette',
                'url' => SubjectResource::getUrl(),
            ],
            [
                'label' => __('الأقسام'),
                'description' => __('الأقسام والسنوات الدراسية'),
                'icon' => 'users',
                'url' => ClassroomResource::getUrl(),
            ],
            [
                'label' => __('المستويات'),
                'description' => __('مستويات كل أساس تعليمي'),
                'icon' => 'academic',
                'url' => LevelResource::getUrl(),
            ],
            [
                'label' => __('المجموعات'),
                'description' => __('تقسيمات الأقسام الفرعية'),
                'icon' => 'user-group',
                'url' => GroupResource::getUrl(),
            ],
            [
                'label' => __('الأساسات التعليمية'),
                'description' => __('المراحل وإعداداتها'),
                'icon' => 'building',
                'url' => EducationPhaseResource::getUrl(),
            ],
        ];
    }
}
