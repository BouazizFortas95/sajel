<div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="space-y-6">
    <x-filament::section
        :heading="__('جدول الحصص الأسبوعي')"
        :description="__('من الأحد إلى الخميس — فترات صباحية ومسائية ثابتة.')"
        icon="heroicon-o-calendar-days"
    >
        <div class="overflow-x-auto pb-2">
            <div class="min-w-[60rem] space-y-2">
                {{-- Day headers ------------------------------------------------- --}}
                <div
                    wire:key="timetable-header"
                    class="grid grid-cols-[6.5rem_repeat(5,minmax(0,1fr))] gap-2"
                >
                    <div aria-hidden="true"></div>

                    @foreach ($days as $day)
                        @php
                            $isToday = $day === $today;
                        @endphp

                        <header
                            wire:key="timetable-day-{{ $day->value }}"
                            @class([
                                'rounded-xl border px-3 py-2 text-center',
                                'border-amber-300 bg-amber-50/70 dark:border-amber-500/40 dark:bg-amber-400/5' => $isToday,
                                'border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-900/40' => ! $isToday,
                            ])
                        >
                            <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
                                <h2 @class([
                                    'text-sm font-semibold',
                                    'text-amber-800 dark:text-amber-200' => $isToday,
                                    'text-gray-950 dark:text-white' => ! $isToday,
                                ])>
                                    {{ $day->label() }}
                                </h2>

                                @if ($isToday)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 ring-1 ring-inset ring-amber-500/30 dark:bg-amber-400/10 dark:text-amber-200">
                                        {{ __('اليوم الحالي') }}
                                    </span>
                                @endif
                            </div>
                        </header>
                    @endforeach
                </div>

                {{-- Slot rows --------------------------------------------------- --}}
                @foreach ($rows as $row)
                    @if ($row['type'] === 'slot')
                        <div
                            wire:key="timetable-row-{{ $row['start'] }}"
                            class="grid grid-cols-[6.5rem_repeat(5,minmax(0,1fr))] gap-2"
                        >
                            <div class="flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50/70 px-2 py-3 text-[11px] font-medium tabular-nums text-gray-500 dark:border-white/10 dark:bg-gray-900/40 dark:text-gray-400">
                                <span dir="ltr">{{ $row['start'] }} – {{ $row['end'] }}</span>
                            </div>

                            @foreach ($days as $day)
                                @php
                                    $isToday = $day === $today;
                                    $schedule = $grid[$day->value][$row['start']] ?? null;
                                    $date = $weekDates[$day->value];
                                @endphp

                                <div
                                    wire:key="timetable-cell-{{ $day->value }}-{{ $row['start'] }}"
                                    class="min-w-0"
                                >
                                    @if ($schedule)
                                        <button
                                            type="button"
                                            wire:click="openLog({{ $schedule->id }}, '{{ $date }}')"
                                            @class([
                                                'group flex h-full min-h-[5.25rem] w-full cursor-pointer flex-col justify-between gap-2 rounded-xl border border-gray-200 border-s-4 border-s-amber-500 bg-white p-3 text-start shadow-sm transition hover:-translate-y-0.5 hover:border-amber-400 hover:bg-amber-50 hover:shadow-md hover:ring-2 hover:ring-amber-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:border-white/10 dark:border-s-amber-400 dark:bg-gray-800 dark:hover:border-amber-300 dark:hover:bg-amber-400/10 dark:hover:ring-amber-400/40 dark:focus-visible:ring-offset-gray-900',
                                                'ring-1 ring-amber-300/70 dark:ring-amber-400/30' => $isToday,
                                            ])
                                            aria-label="{{ __('فتح سجل الحصة: :subject، :time', ['subject' => $schedule->subject?->name ?? __('بدون مادة'), 'time' => $row['start'].' - '.$row['end']]) }}"
                                        >
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="min-w-0 text-sm font-semibold text-gray-950 group-hover:text-amber-800 dark:text-white dark:group-hover:text-amber-200">
                                                    {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                                </span>

                                                <x-filament::icon
                                                    icon="heroicon-o-arrow-top-right-on-square"
                                                    class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 group-hover:text-amber-600 dark:text-gray-500 dark:group-hover:text-amber-300"
                                                />
                                            </div>

                                            @if ($schedule->classroom || $schedule->group)
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                    <x-filament::icon
                                                        icon="heroicon-o-map-pin"
                                                        class="h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400"
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
                                    @else
                                        <div @class([
                                            'flex h-full min-h-[5.25rem] flex-col items-center justify-center gap-1 rounded-xl border border-dashed px-2 py-3 text-center',
                                            'border-amber-300/80 bg-amber-50/20 dark:border-amber-500/25 dark:bg-amber-400/5' => $isToday,
                                            'border-gray-300 bg-transparent dark:border-gray-700' => ! $isToday,
                                        ])>
                                            <span @class([
                                                'text-[11px] font-medium',
                                                'text-amber-700/60 dark:text-amber-200/60' => $isToday,
                                                'text-gray-400 dark:text-gray-500' => ! $isToday,
                                            ])>
                                                {{ __('فراغ') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div
                            wire:key="timetable-{{ $row['type'] }}-{{ $row['range'] }}"
                            @class([
                                'flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold',
                                'border-amber-100 bg-amber-50/70 text-amber-800 dark:border-amber-500/20 dark:bg-amber-400/5 dark:text-amber-200' => $row['type'] === 'period',
                                'border-dashed border-gray-200 text-gray-400 dark:border-white/10 dark:text-gray-500' => $row['type'] === 'break',
                            ])
                        >
                            <x-filament::icon :icon="$row['icon']" class="h-4 w-4 shrink-0" />

                            <span>{{ $row['label'] }}</span>

                            <span class="font-normal opacity-70" dir="ltr">{{ $row['range'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </x-filament::section>
</div>
