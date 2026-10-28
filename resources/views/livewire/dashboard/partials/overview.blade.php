@php
    $attendance = $data['attendance'];
    $nextLesson = $data['next_lesson'];
@endphp

@if ($data['holiday'])
    <div class="rounded-2xl border border-warning-200 bg-warning-50/80 px-4 py-3 shadow-sm dark:border-warning-400/20 dark:bg-warning-400/10">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-warning-100 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                <x-filament::icon icon="heroicon-o-sun" class="h-5 w-5" />
            </span>

            <div class="min-w-0">
                <p class="text-sm font-bold text-warning-900 dark:text-warning-100">{{ __('اليوم عطلة مدرسية') }}</p>
                <p class="mt-0.5 text-xs leading-5 text-warning-800/80 dark:text-warning-200/80">
                    {{ $data['holiday']->title }} — {{ __('لا تُحتسب الحصص غير المسجلة في أيام العطل.') }}
                </p>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('سجلات اليوم')"
        :value="number_format($data['logs_today'])"
        :description="__('المسجلة في دفتر النصوص')"
        icon="heroicon-o-book-open"
        tone="amber"
    />

    <x-dashboard.kpi-card
        :label="__('مؤكدة اليوم')"
        :value="number_format($data['completed_today'])"
        :description="__('اعتمدت كسجل رسمي')"
        icon="heroicon-o-check-circle"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('الحصص الأسبوعية')"
        :value="number_format($data['weekly_sessions'])"
        :description="$isAdmin ? __('لجميع المعلمين') : __('في جدولك الزمني')"
        icon="heroicon-o-calendar-days"
        tone="info"
    />

    @if ($isAdmin)
        <x-dashboard.kpi-card
            :label="__('حصص بلا حضور')"
            :value="number_format($attendance['not_recorded_sessions'])"
            :description="__('حصص انتهى وقتها دون سجل حضور')"
            icon="heroicon-o-clipboard-document-check"
            tone="danger"
        />
    @else
        <x-dashboard.kpi-card
            :label="__('بانتظار التوثيق')"
            :value="number_format($data['pending_count'])"
            :description="__('منذ بداية الأسبوع')"
            icon="heroicon-o-exclamation-triangle"
            tone="danger"
        />
    @endif
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="flex h-full flex-col rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ $isAdmin ? __('تغطية الحضور اليوم') : __('الحصة القادمة') }}</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                        {{ $isAdmin ? __('الحضور المسجل مقابل الحصص المستحقة') : __('استعد لحصتك القادمة') }}
                    </h2>
                </div>

                <x-filament::icon
                    :icon="$isAdmin ? 'heroicon-o-clipboard-document-check' : 'heroicon-o-clock'"
                    class="h-5 w-5 shrink-0 text-amber-500"
                />
            </div>
        </header>

        <div class="flex flex-1 flex-col p-5">
            @if ($isAdmin)
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('مستحقة') }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($attendance['due_sessions']) }}</p>
                    </div>

                    <div class="rounded-xl bg-success-50 p-3 text-center dark:bg-success-400/10">
                        <p class="text-xs font-semibold text-success-700 dark:text-success-300">{{ __('مسجلة') }}</p>
                        <p class="mt-1 text-2xl font-bold text-success-800 dark:text-success-200">{{ number_format($attendance['recorded_sessions']) }}</p>
                    </div>

                    <div class="rounded-xl bg-danger-50 p-3 text-center dark:bg-danger-400/10">
                        <p class="text-xs font-semibold text-danger-700 dark:text-danger-300">{{ __('غير مسجلة') }}</p>
                        <p class="mt-1 text-2xl font-bold text-danger-800 dark:text-danger-200">{{ number_format($attendance['not_recorded_sessions']) }}</p>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    {{ __('“غير مسجلة” تعني أن الحصة انتهى وقتها دون سجل حضور؛ ولا تعني أن التلاميذ غائبون.') }}
                </p>

                <div class="mt-auto pt-4">
                    <a
                        href="{{ \App\Filament\Pages\SchoolSupervisionHub::getUrl() }}"
                        class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                    >
                        {{ __('فتح المراقبة العامة') }}
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                    </a>
                </div>
            @elseif ($nextLesson)
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                            <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
                            <span dir="ltr">{{ $nextLesson->start_time?->format('H:i') }} — {{ $nextLesson->end_time?->format('H:i') }}</span>
                        </span>

                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $nextLesson->day_of_week?->label() }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xl font-bold text-gray-950 dark:text-white">{{ $nextLesson->subject?->name ?? __('بدون مادة') }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $nextLesson->placeLabel() ?? __('بدون قسم') }}</p>
                    </div>

                    @if ($nextLesson->group)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $nextLesson->group->name }}</p>
                    @endif
                </div>

                <div class="mt-auto pt-4">
                    <a
                        href="{{ \App\Filament\Pages\MySchedule::getUrl() }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
                    >
                        {{ __('فتح جدولي الأسبوعي') }}
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                    </a>
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد حصص متبقية اليوم') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('يمكنك مراجعة جدولك الأسبوعي أو سجلات اليوم.') }}</p>
                </div>
            @endif
        </div>
    </section>

    <section class="flex h-full flex-col rounded-2xl border border-danger-200/80 bg-white shadow-sm dark:border-danger-400/20 dark:bg-gray-900">
        <header class="border-b border-danger-100 px-5 py-4 dark:border-danger-400/20">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-danger-700 dark:text-danger-300">{{ __('المتابعة اليومية') }}</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('يحتاج إلى انتباهك') }}</h2>
                </div>

                <span @class([
                    'inline-flex shrink-0 items-center justify-center rounded-full px-2.5 py-1 text-xs font-bold',
                    'bg-success-100 text-success-800 dark:bg-success-400/10 dark:text-success-300' => $data['pending_count'] === 0,
                    'bg-danger-100 text-danger-800 dark:bg-danger-400/10 dark:text-danger-200' => $data['pending_count'] > 0,
                ])>
                    {{ number_format($data['pending_count']) }}
                </span>
            </div>
        </header>

        <div class="flex flex-1 flex-col p-5">
            @if ($data['pending_count'] === 0)
                <div class="flex flex-1 flex-col items-center justify-center py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-success-100 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('كل الحصص موثقة') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد حصص فائتة تحتاج إلى تسجيل هذا الأسبوع.') }}</p>
                </div>
            @else
                <ul role="list" class="space-y-3">
                    @foreach ($data['attention'] as $task)
                        @php($schedule = $task['schedule'])

                        <li class="rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700">
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-3.5 w-3.5" />
                                    {{ $task['date']->format('Y/m/d') }}
                                </span>

                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset',
                                    'bg-danger-100 text-danger-800 ring-danger-200 dark:bg-danger-400/10 dark:text-danger-200 dark:ring-danger-400/30' => $task['state'] === 'missing',
                                    'bg-warning-100 text-warning-800 ring-warning-200 dark:bg-warning-400/10 dark:text-warning-200 dark:ring-warning-400/30' => $task['state'] === 'planned',
                                ])>
                                    {{ $task['state'] === 'missing' ? __('لم يُنشأ السجل') : __('بانتظار الإنجاز') }}
                                </span>
                            </div>

                            <p class="mt-2 font-bold text-gray-950 dark:text-white">
                                {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                <span class="font-normal text-gray-400 dark:text-gray-500">·</span>
                                <span class="font-normal text-gray-600 dark:text-gray-300">{{ $schedule->placeLabel() ?? __('بدون قسم') }}</span>
                            </p>

                            @if ($isAdmin)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('الأستاذ') }}: {{ $schedule->user?->name ?? '—' }}</p>
                            @endif

                            <a
                                href="{{ $task['url'] }}"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                            >
                                {{ __('إكمال السجل') }}
                                <x-filament::icon icon="heroicon-o-arrow-left" class="h-3.5 w-3.5" />
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-auto pt-4">
                    <button
                        type="button"
                        wire:click="setTab('lessons')"
                        class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                    >
                        {{ __('عرض كل الحصص المعلقة') }}
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                    </button>
                </div>
            @endif
        </div>
    </section>
</div>

@include('livewire.dashboard.partials.smart-log', ['dailyLog' => $data['latest_log']])

<section class="space-y-3">
    <div class="flex items-end justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('الوصول السريع') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('اختصارات سريعة') }}</h2>
        </div>
    </div>

    @livewire(\App\Filament\Widgets\QuickLinks::class)
</section>
