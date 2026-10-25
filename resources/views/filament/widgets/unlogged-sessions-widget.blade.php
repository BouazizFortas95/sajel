@php
    $pendingCount = $pendingSessions->total();
@endphp

<div class="w-full">
    @if ($pendingCount === 0)
        <div class="rounded-2xl border border-success-200/80 bg-success-50/80 px-4 py-3 shadow-sm dark:border-success-400/20 dark:bg-success-400/10">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-success-100 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-5 w-5"
                    />
                </span>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-success-800 dark:text-success-300">
                        {{ __('كل الحصص موثقة') }}
                    </p>

                    <p class="text-xs leading-5 text-success-700/80 dark:text-success-300/70">
                        {{ __('لا توجد حصص فائتة تحتاج إلى تسجيل هذا الأسبوع.') }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl bg-danger-50 shadow-lg ring-1 ring-danger-500 dark:bg-danger-500/10 dark:ring-danger-400/60">
            <div class="border-b border-danger-200/80 px-5 py-4 dark:border-danger-400/20">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="h-5 w-5"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold text-danger-900 dark:text-danger-100">
                            {{ __('تنبيه: حصص غير موثقة') }}
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-danger-700 dark:text-danger-200/80">
                            {{ __('لديك :count حصة فائتة تحتاج إلى توثيق.', ['count' => $pendingCount]) }}
                        </p>
                    </div>

                    <span class="inline-flex shrink-0 items-center justify-center rounded-full bg-danger-600 px-2.5 py-1 text-xs font-bold text-white dark:bg-danger-500">
                        {{ $pendingCount }}
                    </span>
                </div>
            </div>

            <ul role="list" class="divide-y divide-danger-200/70 dark:divide-danger-400/20">
                @foreach ($pendingSessions as $task)
                    @php
                        $schedule = $task['schedule'];
                        $classroom = $schedule->sectionName();
                    @endphp

                    <li
                        wire:key="pending-session-{{ $schedule->id }}-{{ $task['date']->format('Y-m-d') }}"
                        class="px-5 py-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-danger-800 ring-1 ring-inset ring-danger-300/70 dark:bg-danger-950/30 dark:text-danger-100 dark:ring-danger-400/30">
                                        <x-filament::icon
                                            icon="heroicon-o-calendar-days"
                                            class="h-3.5 w-3.5"
                                        />

                                        {{ $schedule->day_of_week?->label() }} — {{ $task['date']->format('Y/m/d') }}
                                    </span>

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/60 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-300/70 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                                        <x-filament::icon
                                            icon="heroicon-o-clock"
                                            class="h-3.5 w-3.5"
                                        />

                                        <span dir="ltr">
                                            {{ $schedule->start_time?->format('H:i') }} – {{ $schedule->end_time?->format('H:i') }}
                                        </span>
                                    </span>

                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                        'bg-danger-100 text-danger-800 ring-danger-300/70 dark:bg-danger-400/10 dark:text-danger-200 dark:ring-danger-400/30' => $task['state'] === 'missing',
                                        'bg-warning-100 text-warning-800 ring-warning-300/70 dark:bg-warning-400/10 dark:text-warning-200 dark:ring-warning-400/30' => $task['state'] === 'planned',
                                    ])>
                                        {{ $task['state'] === 'missing' ? __('لم يُنشأ السجل') : __('بانتظار الإنجاز') }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                    <span class="font-bold text-gray-950 dark:text-white">
                                        {{ $schedule->subject?->name ?? __('بدون مادة') }}
                                    </span>

                                    <span class="text-gray-400 dark:text-gray-500">·</span>

                                    <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                        <x-filament::icon
                                            icon="heroicon-o-map-pin"
                                            class="h-3.5 w-3.5 shrink-0 text-danger-500 dark:text-danger-300"
                                        />

                                        {{ $classroom ?? __('بدون قسم') }}

                                        @if ($schedule->group)
                                            <span class="text-gray-400 dark:text-gray-500">·</span>
                                            {{ $schedule->group->name }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <x-filament::button
                                :href="$task['url']"
                                tag="a"
                                color="danger"
                                size="sm"
                                icon="heroicon-o-pencil-square"
                                class="shrink-0"
                            >
                                {{ __('إكمال السجل') }}
                            </x-filament::button>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($pendingSessions->hasPages())
                <div class="border-t border-danger-200/80 px-5 py-3 dark:border-danger-400/20">
                    {{ $pendingSessions->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        </div>
    @endif
</div>
