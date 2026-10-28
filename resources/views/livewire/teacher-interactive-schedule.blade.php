{{--
    Teacher interactive schedule.

    Theme notes (Filament v3):
    * Colours come from Filament's own CSS custom properties (`--c-50`,
      `--c-400`, `--c-600`) via `x-filament::badge` / `x-filament::button`,
      so they follow the panel palette and the light/dark theme.
    * Surfaces use Filament's card idiom (`bg-white ... dark:bg-gray-900`)
      rather than a hard-coded background, so they invert with the theme.
    * Body copy uses `text-gray-950 dark:text-white` for headings and
      `text-gray-500 dark:text-gray-400` for secondary text, which keeps
      contrast in both themes.
--}}
@php
    use App\Enums\DailyLogStatus;
    use App\Enums\DayOfWeek;
    use Filament\Support\Colors\Color;

    $schedules = $this->schedules;
    $logs = $this->logsForSelectedDate;
    $stats = $this->scheduleStats;

    $isGrid = $viewMode === 'grid';

    // Turn a subject's stored hex into a Filament colour palette so the
    // component renders the shade variables (`--c-50`, `--c-400`, `--c-600`)
    // that Filament's own colour utilities consume.
    $subjectPalette = function (?string $hex): ?array {
        if (blank($hex)) {
            return null;
        }

        try {
            return Color::hex($hex);
        } catch (\Throwable) {
            return null;
        }
    };
@endphp

<div class="space-y-6">
    {{-- Stat summary ---------------------------------------------------- --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => __('إجمالي الحصص'), 'value' => $stats['total'], 'icon' => 'heroicon-o-calendar-days'],
            ['label' => __('مخططة'), 'value' => $stats['planned'], 'icon' => 'heroicon-o-pencil-square'],
            ['label' => __('منجزة'), 'value' => $stats['completed'], 'icon' => 'heroicon-o-check-circle'],
            ['label' => __('ملغاة'), 'value' => $stats['cancelled'], 'icon' => 'heroicon-o-x-circle'],
        ] as $stat)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <x-filament::icon
                            :icon="$stat['icon']"
                            class="h-5 w-5 text-gray-400 dark:text-gray-500"
                        />

                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </span>
                    </div>

                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($stat['value']) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Schedule --------------------------------------------------------- --}}
    <x-filament::section
        :heading="__('جدول الحصص — :day', ['day' => DayOfWeek::tryFrom($selectedDay)?->label() ?? ''])"
        :description="__('اختر اليوم لعرض حصصك، واستخدم البحث للوصول السريع.')"
        icon="heroicon-o-calendar-days"
    >
        <x-slot name="headerEnd">
            <x-filament::icon-button
                :icon="$isGrid ? 'heroicon-o-squares-2x2' : 'heroicon-o-list-bullet'"
                :color="$isGrid ? 'primary' : 'gray'"
                wire:click="$set('viewMode', $isGrid ? 'list' : 'grid')"
                :label="$isGrid ? __('عرض كقائمة') : __('عرض كشبكة')"
            />
        </x-slot>

        {{-- Day picker --}}
        <x-filament::tabs
            class="mb-4"
            :label="__('اختر اليوم')"
        >
            @foreach ($days as $dayValue => $dayLabel)
                <x-filament::tabs.item
                    :active="$selectedDay === $dayValue"
                    wire:click="setDay('{{ $dayValue }}')"
                    :icon="$selectedDay === $dayValue ? 'heroicon-m-calendar' : 'heroicon-o-calendar'"
                >
                    {{ $dayLabel }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        {{-- Search --}}
        <div class="mb-4 max-w-md">
            <x-filament::input.wrapper
                prefix-icon="heroicon-m-magnifying-glass"
            >
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('ابحث باسم المادة أو القسم أو المجموعة')"
                />
            </x-filament::input.wrapper>
        </div>

        {{-- Cards --}}
        @if ($schedules->isEmpty())
            <x-filament-tables::empty-state
                icon="heroicon-o-calendar-days"
                :heading="__('لا توجد حصص')"
                :description="__('لا توجد حصص مسجّلة لهذا اليوم. جرّب اختيار يوم آخر أو أضف حصة جديدة.')"
            />
        @else
            <div
                @class([
                    'grid gap-4',
                    'md:grid-cols-3' => $isGrid,
                    'space-y-4' => ! $isGrid,
                ])
            >
                @foreach ($schedules as $schedule)
                    @php
                        $subject = $schedule->subject;
                        $palette = $subjectPalette($subject?->color);
                        $log = $logs->get($schedule->id);
                        $status = $log?->status ?? DailyLogStatus::Planned;
                        $isExpanded = $expandedCardId === $schedule->id;
                    @endphp

                    <div
                        wire:key="schedule-{{ $schedule->id }}"
                        class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                    >
                        <div class="grid gap-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-x-2">
                                    <x-filament::icon
                                        icon="heroicon-o-book-open"
                                        class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500"
                                    />

                                    <span class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $subject?->name ?? __('بدون مادة') }}
                                    </span>
                                </div>

                                <x-filament::badge
                                    :color="$status->color()"
                                    size="sm"
                                >
                                    {{ $status->label() }}
                                </x-filament::badge>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::badge
                                    :color="$palette ?? 'gray'"
                                    size="sm"
                                    icon="heroicon-o-clock"
                                >
                                    {{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}
                                </x-filament::badge>

                                @if ($place = $schedule->placeLabel())
                                    <x-filament::badge
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-o-map-pin"
                                    >
                                        {{ $place }}
                                    </x-filament::badge>
                                @endif
                            </div>

                            {{-- Expandable detail --}}
                            <div
                                x-data="{ expanded: @js($isExpanded) }"
                                x-show="expanded"
                                x-transition
                                x-cloak
                            >
                                @if (filled($log?->actual_content) || filled($log?->homework) || filled($log?->observations))
                                    <div class="grid gap-y-2 border-t border-gray-200 pt-3 dark:border-white/10">
                                        @if (filled($log?->actual_content))
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    {{ __('نص الحصة') }}
                                                </div>
                                                <div class="text-sm text-gray-950 dark:text-white">
                                                    {{ \Illuminate\Support\Str::of(strip_tags($log->actual_content))->squish()->limit(300) }}
                                                </div>
                                            </div>
                                        @endif

                                        @if (filled($log?->homework))
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    {{ __('الواجب') }}
                                                </div>
                                                <div class="text-sm text-gray-950 dark:text-white">
                                                    {{ $log->homework }}
                                                </div>
                                            </div>
                                        @endif

                                        @if (filled($log?->observations))
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    {{ __('ملاحظات') }}
                                                </div>
                                                <div class="text-sm text-gray-950 dark:text-white">
                                                    {{ $log->observations }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-2">
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    outlined
                                    icon="heroicon-m-arrow-top-right-on-square"
                                    wire:click="openLog({{ $schedule->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="openLog({{ $schedule->id }})"
                                >
                                    {{ $log ? __('فتح السجل') : __('إنشاء سجل') }}
                                </x-filament::button>

                                <x-filament::button
                                    size="sm"
                                    color="success"
                                    icon="heroicon-m-check"
                                    wire:click="markCompleted({{ $schedule->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markCompleted({{ $schedule->id }})"
                                >
                                    {{ __('تمييز كمنجز') }}
                                </x-filament::button>

                                <x-filament::button
                                    size="sm"
                                    color="danger"
                                    icon="heroicon-m-x-mark"
                                    outlined
                                    wire:click="cancelLog({{ $schedule->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="cancelLog({{ $schedule->id }})"
                                >
                                    {{ __('إلغاء') }}
                                </x-filament::button>

                                <x-filament::icon-button
                                    size="sm"
                                    color="gray"
                                    :icon="$isExpanded ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down'"
                                    wire:click="toggleCard({{ $schedule->id }})"
                                    :label="__('عرض التفاصيل')"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</div>
</</new_text>