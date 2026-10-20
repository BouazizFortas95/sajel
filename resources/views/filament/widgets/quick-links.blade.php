{{--
    Quick navigation cards for the dashboard.

    Theme notes (Filament v3):
    * Colours come from Filament's CSS custom properties (`--c-50`,
      `--c-400`, `--c-600`) through `x-filament::icon` and
      `x-filament::link`, so they follow the panel palette and follow the
      light/dark theme.
    * Card surfaces use Filament's own card idiom
      (`bg-white ... dark:bg-gray-900`) so they invert with the theme
      instead of staying a solid white block.
--}}
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

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($links as $link)
        <x-filament::section compact>
            <div class="flex items-center gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-custom-50 text-custom-600 dark:bg-custom-400/10 dark:text-custom-400"
                    style="{{ \Filament\Support\get_color_css_variables('primary', shades: [50, 400, 600]) }}"
                >
                    <x-filament::icon
                        :icon="$iconMap[$link['icon']] ?? 'heroicon-o-book-open'"
                        class="h-5 w-5"
                    />
                </span>

                <span class="min-w-0 flex-1">
                    <x-filament::link
                        :href="$link['url']"
                        class="justify-start text-start"
                    >
                        {{ $link['label'] }}
                    </x-filament::link>

                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">
                        {{ $link['description'] }}
                    </span>
                </span>
            </div>
        </x-filament::section>
    @endforeach
</div>
