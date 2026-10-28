@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $activeTabLabel = collect($tabs)->firstWhere('key', $activeTab)['label'] ?? __('نظرة عامة');
    $usesPeriod = in_array($activeTab, ['attendance', 'lessons', 'assessments', 'support'], true);
    $usesScopeFilters = $activeTab !== 'institutional';
@endphp

<div class="space-y-6" dir="{{ $dir }}">
    <section class="fi-section overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="space-y-6 p-5 sm:p-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('مرحباً بك في سجِّل') }}</p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        {{ __('لوحة التحكم') }}
                    </h1>

                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</span>
                        <span class="text-gray-400 dark:text-gray-500">·</span>
                        <span>{{ $user->role?->label() }}</span>
                    </p>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ $settings->school_name ?? '—' }}
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        {{ __('السنة الدراسية') }} {{ $settings->academic_year ?? '—' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $todayLabel }}</p>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('بتوقيت الجزائر') }}
                        <span dir="ltr" class="font-medium">Africa/Algiers</span>
                    </p>

                    <button
                        type="button"
                        wire:click="$refresh"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300 dark:hover:bg-amber-400/20"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-3.5 w-3.5" />
                        {{ __('تحديث') }}
                    </button>
                </div>
            </header>

            @if ($usesScopeFilters)
                <div class="grid grid-cols-1 gap-3 border-t border-amber-100 pt-5 dark:border-gray-700 sm:grid-cols-2 {{ $usesPeriod ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
                    @if ($usesPeriod)
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold text-gray-600 dark:text-gray-300">{{ __('الفترة') }}</span>

                            <select
                                wire:model.live="period"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >
                                @foreach ($periods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-gray-600 dark:text-gray-300">{{ __('القسم') }}</span>

                        <select
                            wire:model.live="classroomId"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('كل الأقسام') }}</option>
                            @foreach ($options['classrooms'] as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-gray-600 dark:text-gray-300">{{ __('المادة') }}</span>

                        <select
                            wire:model.live="subjectId"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('كل المواد') }}</option>
                            @foreach ($options['subjects'] as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-end">
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                            {{ __('مسح الفلاتر') }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="border-t border-amber-100 pt-5 dark:border-gray-700">
                <div
                    role="tablist"
                    aria-label="{{ __('أقسام لوحة التحكم') }}"
                    class="flex gap-2 overflow-x-auto pb-1"
                >
                    @foreach ($tabs as $tab)
                        @php($isActive = $activeTab === $tab['key'])

                        <button
                            type="button"
                            id="dashboard-tab-{{ $tab['key'] }}"
                            role="tab"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}"
                            aria-controls="dashboard-panel"
                            tabindex="{{ $isActive ? '0' : '-1' }}"
                            wire:click="setTab('{{ $tab['key'] }}')"
                            x-on:keydown.arrow-right.prevent="(document.documentElement.dir === 'rtl' ? $el.previousElementSibling : $el.nextElementSibling)?.focus()"
                            x-on:keydown.arrow-left.prevent="(document.documentElement.dir === 'rtl' ? $el.nextElementSibling : $el.previousElementSibling)?.focus()"
                            x-on:keydown.home.prevent="$el.parentElement.querySelector('[role=tab]')?.focus()"
                            x-on:keydown.end.prevent="[...$el.parentElement.querySelectorAll('[role=tab]')].at(-1)?.focus()"
                            @class([
                                'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2.5 text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-amber-500/40',
                                'bg-amber-500 text-white shadow-sm' => $isActive,
                                'text-gray-600 hover:bg-amber-50 dark:text-gray-300 dark:hover:bg-white/5' => ! $isActive,
                            ])
                        >
                            <x-filament::icon :icon="$tab['icon']" class="h-4 w-4 shrink-0" />
                            <span class="whitespace-nowrap">{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div wire:loading.delay class="overflow-hidden rounded-full bg-amber-100 dark:bg-amber-400/10">
        <div class="h-1 w-1/3 animate-pulse rounded-full bg-amber-500"></div>
    </div>

    <div
        id="dashboard-panel"
        role="tabpanel"
        aria-labelledby="dashboard-tab-{{ $activeTab }}"
        aria-label="{{ $activeTabLabel }}"
        class="space-y-6"
    >
        @include('livewire.dashboard.partials.' . $activeTab)
    </div>
</div>
