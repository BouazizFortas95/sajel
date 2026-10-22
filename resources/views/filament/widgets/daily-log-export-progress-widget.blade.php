<div @if ($export) wire:poll.2s @endif>
    @if ($export)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                        <x-filament::loading-indicator class="h-5 w-5" />
                    </span>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('جارٍ تصدير دفتر النصوص') }}
                        </p>

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            @if ($processed === 0)
                                {{ __('جارٍ بدء التصدير...') }}
                            @else
                                {{ __('تمت معالجة :processed من :total سجل.', [
                                    'processed' => number_format($processed),
                                    'total' => number_format($total),
                                ]) }}
                            @endif
                        </p>
                    </div>
                </div>

                <span class="shrink-0 text-sm font-bold text-primary-600 dark:text-primary-400">
                    {{ $percentage }}%
                </span>
            </div>

            <div
                class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="{{ $percentage }}"
            >
                @if ($processed === 0)
                    <div class="h-full w-1/3 animate-pulse rounded-full bg-primary-500"></div>
                @else
                    <div
                        class="h-full rounded-full bg-primary-600 transition-all duration-500"
                        style="width: {{ $percentage }}%"
                    ></div>
                @endif
            </div>
        </div>
    @endif
</div>
