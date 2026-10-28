@props([
    'label',
    'value' => null,
    'description' => null,
    'icon' => 'heroicon-o-chart-bar',
    'tone' => 'amber',
    'url' => null,
    'linkLabel' => null,
])

@php
    $tones = [
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400',
        'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400',
        'warning' => 'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300',
        'info' => 'bg-info-50 text-info-600 dark:bg-info-400/10 dark:text-info-400',
        'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    ];
@endphp

<article class="group flex h-full flex-col rounded-2xl border border-amber-100/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-900/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-amber-700 dark:hover:shadow-black/20">
    <div class="flex items-center gap-3">
        <span @class([
            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition group-hover:bg-amber-500 group-hover:text-white',
            $tones[$tone] ?? $tones['amber'],
        ])>
            <x-filament::icon :icon="$icon" class="h-5 w-5" />
        </span>

        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $label }}</span>
    </div>

    <div class="mt-4 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
        @if ($value === null || $value === '')
            <span aria-label="{{ __('غير متاح') }}">—</span>
        @else
            {{ $value }}
        @endif
    </div>

    @if ($description)
        <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @if ($url)
        <a
            href="{{ $url }}"
            class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
        >
            {{ $linkLabel ?? __('عرض التفاصيل') }}
            <x-filament::icon icon="heroicon-o-arrow-left" class="h-3.5 w-3.5 rtl:rotate-180" />
        </a>
    @endif
</article>
