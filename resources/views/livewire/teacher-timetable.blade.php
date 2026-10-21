<div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="space-y-6">
    <x-filament::section
        :heading="__('جدول الحصص الأسبوعي')"
        :description="__('عرض أسبوعي لحصصك. اختر أي حصة للانتقال إلى سجلها اليومي.')"
        icon="heroicon-o-calendar-days"
    >
        @if ($days->every(fn (array $day): bool => $day['schedules']->isEmpty()))
            <x-filament-tables::empty-state
                icon="heroicon-o-calendar-days"
                :heading="__('لا توجد حصص مجدولة')"
                :description="__('ستظهر حصصك الأسبوعية هنا بعد إضافتها إلى جدولك.')"
            />
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-7">
                @foreach ($days as $day)
                    <section
                        wire:key="timetable-day-{{ $day['value'] }}"
                        @class([
                            'min-w-0 rounded-2xl border p-3 transition-colors',
                            'border-amber-300 bg-amber-50/70 dark:border-amber-500/40 dark:bg-amber-400/5' => $day['isToday'],
                            'border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-900/40' => ! $day['isToday'],
                        ])
                    >
                        <header class="mb-3 flex items-center justify-between gap-2 px-1">
                            <h2 @class([
                                'text-sm font-semibold',
                                'text-amber-800 dark:text-amber-200' => $day['isToday'],
                                'text-gray-950 dark:text-white' => ! $day['isToday'],
                            ])>
                                {{ $day['label'] }}
                            </h2>

                            @if ($day['isToday'])
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-500/30 dark:bg-amber-400/10 dark:text-amber-200">
                                    {{ __('اليوم الحالي') }}
                                </span>
                            @endif
                        </header>

                        <div class="space-y-3">
                            @forelse ($day['schedules'] as $schedule)
                                <button
                                    type="button"
                                    wire:key="timetable-schedule-{{ $schedule->id }}"
                                    wire:click="openLogForSchedule({{ $schedule->id }})"
                                    class="group block w-full rounded-xl border border-gray-200 border-s-4 border-s-amber-500 bg-white p-3 text-start shadow-sm transition hover:-translate-y-0.5 hover:border-amber-400 hover:bg-amber-50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:border-white/10 dark:border-s-amber-400 dark:bg-gray-800 dark:hover:border-amber-300 dark:hover:bg-amber-400/10 dark:focus-visible:ring-offset-gray-900"
                                    aria-label="{{ __('فتح سجل الحصة: :subject، :time', ['subject' => $schedule->subject?->name ?? __('بدون مادة'), 'time' => $schedule->start_time?->format('H:i').' - '.$schedule->end_time?->format('H:i')]) }}"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-2">
                                        <span class="min-w-0 text-sm font-semibold text-gray-950 group-hover:text-amber-800 dark:text-white dark:group-hover:text-amber-200">
                                            {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                        </span>

                                        <x-filament::icon
                                            icon="heroicon-o-arrow-top-right-on-square"
                                            class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 group-hover:text-amber-600 dark:text-gray-500 dark:group-hover:text-amber-300"
                                        />
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <x-filament::icon
                                            icon="heroicon-o-clock"
                                            class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                        />
                                        <span dir="ltr" class="font-medium tabular-nums">
                                            {{ $schedule->start_time?->format('H:i') }} – {{ $schedule->end_time?->format('H:i') }}
                                        </span>
                                    </div>

                                    @if ($schedule->classroom || $schedule->group)
                                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <x-filament::icon
                                                icon="heroicon-o-map-pin"
                                                class="h-4 w-4 shrink-0"
                                            />
                                            <span class="truncate">
                                                {{ $schedule->classroom?->name ?? $schedule->group?->classroom?->name }}
                                                @if ($schedule->group)
                                                    <span class="text-gray-400 dark:text-gray-500">·</span>
                                                    {{ $schedule->group->name }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </button>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center text-xs text-gray-400 dark:border-white/15 dark:text-gray-500">
                                    {{ __('لا توجد حصص') }}
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</div>
