@php
    $pendingItems = $data['pending']->take(5);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('سجلات مخططة')"
        :value="number_format($data['planned'])"
        :description="__('داخل الفترة المحددة')"
        icon="heroicon-o-pencil-square"
        tone="warning"
    />

    <x-dashboard.kpi-card
        :label="__('سجلات منجزة')"
        :value="number_format($data['completed'])"
        :description="__('سجلات مؤكدة رسمياً داخل الفترة')"
        icon="heroicon-o-check-circle"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('سجلات ملغاة')"
        :value="number_format($data['cancelled'])"
        :description="__('حصص ملغاة ولا تُحتسب ضمن المستحق')"
        icon="heroicon-o-x-circle"
        tone="gray"
    />

    <x-dashboard.kpi-card
        :label="__('بانتظار التوثيق')"
        :value="number_format($data['pending_count'])"
        :description="__('منذ بداية الأسبوع حتى الآن')"
        icon="heroicon-o-exclamation-triangle"
        tone="danger"
    />
</div>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <div>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('دفتر النصوص') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('حالة السجلات في الفترة') }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-danger-50 px-2.5 py-1 text-xs font-bold text-danger-800 dark:bg-danger-400/10 dark:text-danger-200">
                {{ number_format($data['missing_count']) }} {{ __('لم يُنشأ السجل') }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-bold text-warning-800 dark:bg-warning-400/10 dark:text-warning-200">
                {{ number_format($data['awaiting_completion_count']) }} {{ __('بانتظار الإنجاز') }}
            </span>
        </div>
    </header>

    <div class="space-y-4 p-5">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('مخططة') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($data['planned']) }}</p>
            </div>
            <div class="rounded-xl bg-success-50 p-3 text-center dark:bg-success-400/10">
                <p class="text-xs font-semibold text-success-700 dark:text-success-300">{{ __('منجزة') }}</p>
                <p class="mt-1 text-2xl font-bold text-success-800 dark:text-success-200">{{ number_format($data['completed']) }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('ملغاة') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($data['cancelled']) }}</p>
            </div>
            <div class="rounded-xl bg-danger-50 p-3 text-center dark:bg-danger-400/10">
                <p class="text-xs font-semibold text-danger-700 dark:text-danger-300">{{ __('معلقة') }}</p>
                <p class="mt-1 text-2xl font-bold text-danger-800 dark:text-danger-200">{{ number_format($data['pending_count']) }}</p>
            </div>
        </div>

        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
            {{ __('“مخطط” و“منجز” و“ملغى” حالات سجل موجود. “معلق” حصة انتهى وقتها دون سجل منجز أو ملغى؛ ويُحتسب بعد نهاية وقت الحصة فقط، مع استثناء العطل.') }}
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ \App\Filament\Resources\DailyLogResource::getUrl() }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
            >
                {{ __('فتح دفتر النصوص') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>

            <a
                href="{{ \App\Filament\Resources\DailyLogResource::getUrl('create') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300 dark:hover:bg-amber-400/20"
            >
                {{ __('إنشاء سجل يومي') }}
            </a>

            @if ($isAdmin)
                <a
                    href="{{ \App\Filament\Pages\SchoolSupervisionHub::getUrl() }}"
                    class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ __('فتح المراقبة العامة') }}
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                </a>
            @endif
        </div>
    </div>
</section>

@if ($isAdmin)
    <section class="rounded-2xl border border-danger-200/80 bg-white shadow-sm dark:border-danger-400/20 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-danger-100 px-5 py-4 dark:border-danger-400/20">
            <div>
                <p class="text-sm font-semibold text-danger-700 dark:text-danger-300">{{ __('المتابعة') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('الحصص المعلقة على مستوى المؤسسة') }}</h2>
            </div>

            <span class="inline-flex shrink-0 items-center justify-center rounded-full bg-danger-100 px-2.5 py-1 text-xs font-bold text-danger-800 dark:bg-danger-400/10 dark:text-danger-200">
                {{ number_format($data['pending_count']) }}
            </span>
        </header>

        <div class="p-5">
            @if ($pendingItems->isEmpty())
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-success-100 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('كل الحصص موثقة') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد حصص فائتة تحتاج إلى تسجيل هذا الأسبوع.') }}</p>
                </div>
            @else
                <ul role="list" class="space-y-3">
                    @foreach ($pendingItems as $task)
                        @php($schedule = $task['schedule'])

                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950 dark:text-white">
                                    {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                    <span class="font-normal text-gray-400 dark:text-gray-500">·</span>
                                    <span class="font-normal text-gray-600 dark:text-gray-300">{{ $schedule->placeLabel() ?? __('بدون قسم') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('الأستاذ') }}: {{ $schedule->user?->name ?? '—' }}
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    {{ $task['date']->format('Y/m/d') }}
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <span dir="ltr">{{ $schedule->start_time?->format('H:i') }} — {{ $schedule->end_time?->format('H:i') }}</span>
                                </p>
                            </div>

                            <a
                                href="{{ $task['url'] }}"
                                class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-danger-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-danger-700"
                            >
                                {{ __('إكمال السجل') }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($data['pending_count'] > $pendingItems->count())
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('يُعرض أول :shown من أصل :total حصة معلقة.', ['shown' => $pendingItems->count(), 'total' => $data['pending_count']]) }}
                    </p>
                @endif
            @endif
        </div>
    </section>
@else
    @livewire(\App\Filament\Widgets\UnloggedSessionsWidget::class)
@endif
