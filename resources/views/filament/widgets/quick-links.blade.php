@php
    $iconMap = [
        'book' => 'heroicon-o-book-open',
        'plus' => 'heroicon-o-plus',
        'calendar' => 'heroicon-o-calendar-days',
        'palette' => 'heroicon-o-swatch',
        'users' => 'heroicon-o-users',
        'academic' => 'heroicon-o-academic-cap',
        'user-group' => 'heroicon-o-user-group',
        'building' => 'heroicon-o-building-office-2',
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 lg:gap-6">
    @foreach ($links as $link)
        <a
            href="{{ $link['url'] }}"
            class="group block w-full rounded-2xl border border-amber-100/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-900/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-amber-700 dark:hover:shadow-black/20"
        >
            <div class="flex h-full flex-col items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white dark:bg-amber-400/10 dark:text-amber-400 dark:group-hover:bg-amber-500 dark:group-hover:text-white">
                    <x-filament::icon
                        :icon="$iconMap[$link['icon']] ?? 'heroicon-o-book-open'"
                        class="h-5 w-5"
                    />
                </span>

                <span class="w-full min-w-0 flex-1 break-words">
                    <span class="block font-bold leading-6 text-gray-900 group-hover:text-amber-700 dark:text-white dark:group-hover:text-amber-300">
                        {{ $link['label'] }}
                    </span>
                    <span class="mt-1 block break-words text-sm leading-5 text-gray-500 dark:text-gray-400">
                        {{ $link['description'] }}
                    </span>
                </span>
            </div>
        </a>
    @endforeach
</div>
