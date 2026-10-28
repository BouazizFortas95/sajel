<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-dashboard.kpi-card
        :label="__('إجمالي الحصص الأسبوعية')"
        :value="number_format($data['total_sessions'])"
        :description="$isAdmin ? __('لكل المعلمين ضمن الفلتر الحالي') : __('في جدولك الزمني')"
        icon="heroicon-o-calendar-days"
        tone="info"
    />

    <x-dashboard.kpi-card
        :label="__('المواد')"
        :value="number_format($data['subjects_count'])"
        :description="__('المواد المرتبطة بالحصص المعروضة')"
        icon="heroicon-o-bookmark"
        tone="amber"
    />

    <x-dashboard.kpi-card
        :label="__('الحصة القادمة')"
        :value="$data['next_lesson']?->start_time?->format('H:i')"
        :description="$data['next_lesson']
            ? ($data['next_lesson']->subject?->name ?? __('بدون مادة')).' — '.($data['next_lesson']->placeLabel() ?? __('بدون قسم'))
            : __('لا توجد حصة قادمة اليوم')"
        icon="heroicon-o-clock"
        tone="success"
    />
</div>

<div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
    @foreach ($data['days'] as $day)
        @php($isToday = $day['day'] === \App\Enums\DayOfWeek::cases()[$today->dayOfWeek])

        <article @class([
            'rounded-2xl border p-4 shadow-sm',
            'border-amber-300 bg-amber-50 dark:border-amber-400/40 dark:bg-amber-400/10' => $isToday,
            'border-amber-100 bg-white dark:border-gray-700 dark:bg-gray-900' => ! $isToday,
        ])>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $day['day']->label() }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ number_format($day['sessions']) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('حصة') }}</p>

            @if ($isAdmin)
                <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400">{{ number_format($day['teachers']) }} {{ __('أستاذ') }}</p>
            @endif
        </article>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('اليوم الحالي') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('حصص اليوم') }}</h2>
            </div>

            <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 shrink-0 text-amber-500" />
        </header>

        <div class="p-5">
            @if (! $isAdmin && $data['today_sessions']->isNotEmpty())
                <ul role="list" class="space-y-3">
                    @foreach ($data['today_sessions'] as $session)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950 dark:text-white">{{ $session->subject?->name ?? __('بدون مادة') }}</p>
                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $session->placeLabel() ?? __('بدون قسم') }}</p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700">
                                <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
                                <span dir="ltr">{{ $session->start_time?->format('H:i') }} — {{ $session->end_time?->format('H:i') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد حصص لعرضها') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $isAdmin ? __('استخدم الفلترة لعرض جدول قسم أو مادة محددة.') : __('لا توجد حصص في جدولك لهذا اليوم.') }}</p>
                </div>
            @endif
        </div>

        <div class="border-t border-amber-100 px-5 py-4 dark:border-gray-700">
            <a
                href="{{ $isAdmin ? \App\Filament\Resources\ScheduleResource::getUrl() : \App\Filament\Pages\MySchedule::getUrl() }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
            >
                {{ $isAdmin ? __('فتح إدارة الحصص') : __('فتح جدولي الأسبوعي') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('نظرة سريعة') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('كيف تقرأ هذا الجدول؟') }}</h2>
            </div>

            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 shrink-0 text-amber-500" />
        </header>

        <div class="space-y-3 p-5 text-sm leading-6 text-gray-600 dark:text-gray-300">
            @if ($data['holiday'])
                <p class="rounded-xl bg-warning-50 px-4 py-3 text-warning-900 dark:bg-warning-400/10 dark:text-warning-100">
                    {{ __('اليوم عطلة مدرسية؛ لا تُحتسب الحصص غير المسجلة.') }}
                </p>
            @endif

            <p>{{ __('يعرض هذا التبويب الحصص المتكررة في الجدول الأسبوعي، وليس حالات التسجيل اليومية.') }}</p>
            <p>{{ $isAdmin ? __('البيانات مجمعة حسب اليوم داخل النطاق المفلتر؛ ولا تعرض أسماء التلاميذ.') : __('يمكنك فتح جدولك لاختيار اليوم أو الأسبوع، ثم الانتقال إلى سجل الحصة المناسب.') }}</p>

            @if ($data['next_lesson'])
                <div class="rounded-xl border border-amber-100 bg-amber-50/70 p-4 dark:border-amber-400/20 dark:bg-amber-400/10">
                    <p class="text-xs font-bold text-amber-700 dark:text-amber-300">{{ __('الحصة القادمة') }}</p>
                    <p class="mt-1 font-bold text-gray-950 dark:text-white">{{ $data['next_lesson']->subject?->name ?? __('بدون مادة') }}</p>
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $data['next_lesson']->placeLabel() ?? __('بدون قسم') }}</p>
                    <p class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-300" dir="ltr">
                        {{ $data['next_lesson']->start_time?->format('H:i') }} — {{ $data['next_lesson']->end_time?->format('H:i') }}
                    </p>
                </div>
            @endif
        </div>
    </section>
</div>
