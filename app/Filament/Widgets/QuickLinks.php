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
                'label' => 'دفتر النصوص',
                'description' => 'سجلاتك اليومية والتحضير',
                'icon' => 'book',
                'url' => DailyLogResource::getUrl(),
            ],
            [
                'label' => 'سجل يومي جديد',
                'description' => 'ابدأ تحضير حصة اليوم',
                'icon' => 'plus',
                'url' => DailyLogResource::getUrl('create'),
            ],
            [
                'label' => 'الحصص',
                'description' => 'جدولك الأسبوعي',
                'icon' => 'calendar',
                'url' => ScheduleResource::getUrl(),
            ],
            [
                'label' => 'المواد',
                'description' => 'إدارة المواد وألوانها',
                'icon' => 'palette',
                'url' => SubjectResource::getUrl(),
            ],
            [
                'label' => 'الأقسام',
                'description' => 'الأقسام والسنوات الدراسية',
                'icon' => 'users',
                'url' => ClassroomResource::getUrl(),
            ],
            [
                'label' => 'المستويات',
                'description' => 'مستويات كل أساس تعليمي',
                'icon' => 'academic',
                'url' => LevelResource::getUrl(),
            ],
            [
                'label' => 'المجموعات',
                'description' => 'تقسيمات الأقسام الفرعية',
                'icon' => 'user-group',
                'url' => GroupResource::getUrl(),
            ],
            [
                'label' => 'الأساسات التعليمية',
                'description' => 'المراحل وإعداداتها',
                'icon' => 'building',
                'url' => EducationPhaseResource::getUrl(),
            ],
        ];
    }
}
