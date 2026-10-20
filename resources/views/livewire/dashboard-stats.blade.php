{{--
    Dashboard stat cards.

    Theme notes (Filament v3):
    * Card surfaces use Filament's own stat-card idiom
      (`bg-white ... dark:bg-gray-900`) so they invert with the theme.
    * Icon tiles use Filament's `custom` colour utilities, which are driven
      by the panel palette, instead of hard-coded Tailwind palette colours.
    * Copy uses `text-gray-950 dark:text-white` for values and
      `text-gray-500 dark:text-gray-400` for labels, keeping contrast in
      both themes.
--}}
@php
    $stats = [
        [
            'label' => 'سجلات اليوم',
            'description' => 'المسجلة في دفتر النصوص',
            'value' => $dailyLogsCount,
            'icon' => 'heroicon-o-book-open',
        ],
        [
            'label' => 'مؤكدة اليوم',
            'description' => 'اعتمدت كسجل رسمي',
            'value' => $completedCount,
            'icon' => 'heroicon-o-check-circle',
        ],
        [
            'label' => 'الحصص الأسبوعية',
            'description' => $isAdmin ? 'لجميع المعلمين' : 'في جدولك الزمني',
            'value' => $schedulesCount,
            'icon' => 'heroicon-o-calendar-days',
        ],
        [
            'label' => 'المواد',
            'description' => 'المواد المسجلة في المنصة',
            'value' => $subjectsCount,
            'icon' => 'heroicon-o-bookmark',
        ],
    ];
@endphp

<div class="space-y-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="mb-4 text-2xl font-bold text-gray-950 dark:text-white">
            لوحة التحكم — {{ $today }}
        </h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-custom-50 text-custom-600 dark:bg-custom-400/10 dark:text-custom-400"
                                style="{{ \Filament\Support\get_color_css_variables('primary', shades: [50, 400, 600]) }}"
                            >
                                <x-filament::icon
                                    :icon="$stat['icon']"
                                    class="h-5 w-5"
                                />
                            </span>

                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $stat['label'] }}
                            </span>
                        </div>

                        <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stat['value']) }}
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $stat['description'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
