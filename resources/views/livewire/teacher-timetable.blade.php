<div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="space-y-6">
    <x-filament::section
        :heading="__('جدول الحصص الأسبوعي')"
        :description="__('من الأحد إلى الخميس — فترات صباحية ومسائية ثابتة.')"
        icon="heroicon-o-calendar-days"
    >
        <x-slot name="headerEnd">
            @php
                $isOverloaded = $totalHours > $weeklyHoursLimit;
                $formatHours = static fn (float | int $hours): string => rtrim(
                    rtrim(number_format((float) $hours, 2, '.', ''), '0'),
                    '.',
                );
            @endphp

            <span
                @class([
                    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                    'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30' => $isOverloaded,
                    'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30' => ! $isOverloaded,
                ])
                :title="$isOverloaded ? __('تم تجاوز النصاب الأسبوعي المحدد.') : null"
            >
                <x-filament::icon
                    :icon="$isOverloaded ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'"
                    class="h-4 w-4 shrink-0"
                />

                <span>
                    {{ __('النصاب الزمني: :total / :limit ساعة', [
                        'total' => $formatHours($totalHours),
                        'limit' => $formatHours($weeklyHoursLimit),
                    ]) }}
                </span>
            </span>
        </x-slot>

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
                {{--
                    Every row lives in one CSS grid so a multi-hour lesson can be
                    a single item with `grid-row: <row> / span <n>`. The rows it
                    covers render no cell at all; otherwise auto-placement would
                    shift every following day column and misalign the grid.
                --}}
                <div class="grid grid-cols-[6.5rem_repeat(5,minmax(0,1fr))] gap-2">
                    @foreach ($rows as $row)
                        @php
                            $gridRow = $loop->iteration;
                        @endphp

                        @if ($row['type'] === 'slot')
                            <div
                                wire:key="timetable-time-{{ $row['start'] }}"
                                style="grid-row: {{ $gridRow }}; grid-column: 1"
                                class="flex min-h-[5.25rem] items-center justify-center rounded-lg border border-gray-200 bg-gray-50/70 px-2 py-3 text-[11px] font-medium tabular-nums text-gray-500 dark:border-white/10 dark:bg-gray-900/40 dark:text-gray-400"
                            >
                                <span dir="ltr">{{ $row['start'] }} – {{ $row['end'] }}</span>
                            </div>

                            @foreach ($days as $day)
                                @php
                                    $isToday = $day === $today;
                                    $column = $loop->iteration + 1;
                                    $cell = $grid[$day->value][$row['start']] ?? null;
                                    $schedule = $cell['schedule'] ?? null;
                                    $span = $cell['span'] ?? 1;
                                    $isCovered = ($cell['covered'] ?? false) === true;
                                    $date = $weekDates[$day->value];

                                    // Literal classes so Tailwind can compile them. A morning
                                    // period has at most four consecutive rows, so four spans
                                    // cover every possible duration.
                                    $spanClass = match (true) {
                                        $span <= 1 => 'row-span-1',
                                        $span === 2 => 'row-span-2',
                                        $span === 3 => 'row-span-3',
                                        default => 'row-span-4',
                                    };

                                    // 5.25rem per row plus the 0.5rem row gap.
                                    $spanMinHeightClass = match (true) {
                                        $span <= 1 => 'min-h-[5.25rem]',
                                        $span === 2 => 'min-h-[11rem]',
                                        $span === 3 => 'min-h-[16.75rem]',
                                        default => 'min-h-[22.5rem]',
                                    };

                                    // Tailwind only compiles class names it can read literally, so each
                                    // Filament subject colour maps to complete utility strings here.
                                    $subjectColorClasses = match ($schedule?->subject?->color) {
                                        'danger' => [
                                            'card' => 'border-s-danger-500 hover:border-danger-400 hover:bg-danger-50 hover:ring-danger-400/60 focus-visible:ring-danger-500 dark:border-s-danger-400 dark:hover:border-danger-300 dark:hover:bg-danger-400/10 dark:hover:ring-danger-400/40',
                                            'title' => 'group-hover:text-danger-800 dark:group-hover:text-danger-200',
                                            'link' => 'group-hover:text-danger-600 dark:group-hover:text-danger-300',
                                            'pin' => 'text-danger-600 dark:text-danger-400',
                                        ],
                                        'success' => [
                                            'card' => 'border-s-success-500 hover:border-success-400 hover:bg-success-50 hover:ring-success-400/60 focus-visible:ring-success-500 dark:border-s-success-400 dark:hover:border-success-300 dark:hover:bg-success-400/10 dark:hover:ring-success-400/40',
                                            'title' => 'group-hover:text-success-800 dark:group-hover:text-success-200',
                                            'link' => 'group-hover:text-success-600 dark:group-hover:text-success-300',
                                            'pin' => 'text-success-600 dark:text-success-400',
                                        ],
                                        'warning' => [
                                            'card' => 'border-s-warning-500 hover:border-warning-400 hover:bg-warning-50 hover:ring-warning-400/60 focus-visible:ring-warning-500 dark:border-s-warning-400 dark:hover:border-warning-300 dark:hover:bg-warning-400/10 dark:hover:ring-warning-400/40',
                                            'title' => 'group-hover:text-warning-800 dark:group-hover:text-warning-200',
                                            'link' => 'group-hover:text-warning-600 dark:group-hover:text-warning-300',
                                            'pin' => 'text-warning-600 dark:text-warning-400',
                                        ],
                                        'info' => [
                                            'card' => 'border-s-info-500 hover:border-info-400 hover:bg-info-50 hover:ring-info-400/60 focus-visible:ring-info-500 dark:border-s-info-400 dark:hover:border-info-300 dark:hover:bg-info-400/10 dark:hover:ring-info-400/40',
                                            'title' => 'group-hover:text-info-800 dark:group-hover:text-info-200',
                                            'link' => 'group-hover:text-info-600 dark:group-hover:text-info-300',
                                            'pin' => 'text-info-600 dark:text-info-400',
                                        ],
                                        'gray' => [
                                            'card' => 'border-s-gray-500 hover:border-gray-400 hover:bg-gray-50 hover:ring-gray-400/60 focus-visible:ring-gray-500 dark:border-s-gray-400 dark:hover:border-gray-300 dark:hover:bg-gray-400/10 dark:hover:ring-gray-400/40',
                                            'title' => 'group-hover:text-gray-800 dark:group-hover:text-gray-200',
                                            'link' => 'group-hover:text-gray-600 dark:group-hover:text-gray-300',
                                            'pin' => 'text-gray-600 dark:text-gray-400',
                                        ],
                                        // "primary", a missing colour, or anything unrecognised.
                                        default => [
                                            'card' => 'border-s-primary-500 hover:border-primary-400 hover:bg-primary-50 hover:ring-primary-400/60 focus-visible:ring-primary-500 dark:border-s-primary-400 dark:hover:border-primary-300 dark:hover:bg-primary-400/10 dark:hover:ring-primary-400/40',
                                            'title' => 'group-hover:text-primary-800 dark:group-hover:text-primary-200',
                                            'link' => 'group-hover:text-primary-600 dark:group-hover:text-primary-300',
                                            'pin' => 'text-primary-600 dark:text-primary-400',
                                        ],
                                    };
                                @endphp

                                @if ($isCovered)
                                    {{-- Owned by the multi-hour card that started in an earlier row. --}}
                                @elseif ($schedule)
                                    <button
                                        type="button"
                                        wire:key="timetable-cell-{{ $day->value }}-{{ $row['start'] }}"
                                        style="grid-row: {{ $gridRow }} / span {{ $span }}; grid-column: {{ $column }}"
                                        wire:click="openLog({{ $schedule->id }}, '{{ $date }}')"
                                        @class([
                                            'group flex h-full w-full cursor-pointer flex-col justify-between gap-2 rounded-xl border border-gray-200 border-s-4 bg-white p-3 text-start shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:border-white/10 dark:bg-gray-800 dark:focus-visible:ring-offset-gray-900',
                                            $spanClass,
                                            $spanMinHeightClass,
                                            $subjectColorClasses['card'],
                                            'ring-1 ring-amber-300/70 dark:ring-amber-400/30' => $isToday,
                                        ])
                                        aria-label="{{ __('فتح سجل الحصة: :subject، :time', [
                                            'subject' => $schedule->subject?->name ?? __('بدون مادة'),
                                            'time' => sprintf(
                                                '%s - %s',
                                                $schedule->start_time?->format('H:i') ?? $row['start'],
                                                $schedule->end_time?->format('H:i') ?? $row['end'],
                                            ),
                                        ]) }}"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="min-w-0 text-sm font-semibold text-gray-950 dark:text-white {{ $subjectColorClasses['title'] }}">
                                                {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                            </span>

                                            <x-filament::icon
                                                icon="heroicon-o-arrow-top-right-on-square"
                                                class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 {{ $subjectColorClasses['link'] }}"
                                            />
                                        </div>

                                        @if ($schedule->classroom || $schedule->group)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                <x-filament::icon
                                                    icon="heroicon-o-map-pin"
                                                    class="h-3.5 w-3.5 shrink-0 {{ $subjectColorClasses['pin'] }}"
                                                />

                                                <span class="truncate">
                                                    {{ $schedule->sectionName() }}

                                                    @if ($schedule->group)
                                                        <span class="text-gray-400 dark:text-gray-500">·</span>
                                                        {{ $schedule->group->name }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </button>
                                @else
                                    <div
                                        wire:key="timetable-cell-{{ $day->value }}-{{ $row['start'] }}"
                                        style="grid-row: {{ $gridRow }}; grid-column: {{ $column }}"
                                        @class([
                                            'flex h-full min-h-[5.25rem] flex-col items-center justify-center gap-1 rounded-xl border border-dashed px-2 py-3 text-center',
                                            'border-amber-300/80 bg-amber-50/20 dark:border-amber-500/25 dark:bg-amber-400/5' => $isToday,
                                            'border-gray-300 bg-transparent dark:border-gray-700' => ! $isToday,
                                        ])
                                    >
                                        <span @class([
                                            'text-[11px] font-medium',
                                            'text-amber-700/60 dark:text-amber-200/60' => $isToday,
                                            'text-gray-400 dark:text-gray-500' => ! $isToday,
                                        ])>
                                            {{ __('فراغ') }}
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div
                                wire:key="timetable-{{ $row['type'] }}-{{ $row['range'] }}"
                                style="grid-row: {{ $gridRow }}; grid-column: 1 / -1"
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
        </div>
    </x-filament::section>
</div>
