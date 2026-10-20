@php
    $stats = [
        [
            'label' => __('سجلات اليوم'),
            'description' => __('المسجلة في دفتر النصوص'),
            'value' => $dailyLogsCount,
            'icon' => 'heroicon-o-book-open',
        ],
        [
            'label' => __('مؤكدة اليوم'),
            'description' => __('اعتمدت كسجل رسمي'),
            'value' => $completedCount,
            'icon' => 'heroicon-o-check-circle',
        ],
        [
            'label' => __('الحصص الأسبوعية'),
            'description' => $isAdmin ? __('لجميع المعلمين') : __('في جدولك الزمني'),
            'value' => $schedulesCount,
            'icon' => 'heroicon-o-calendar-days',
        ],
        [
            'label' => __('المواد'),
            'description' => __('المواد المسجلة في المنصة'),
            'value' => $subjectsCount,
            'icon' => 'heroicon-o-bookmark',
        ],
    ];
@endphp

<section
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="fi-section rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    <div class="fi-section-content space-y-6 p-5 sm:p-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('مرحباً بك في سجِّل') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ __('لوحة التحكم') }}
                </h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $today }}</p>
        </header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <article class="group rounded-2xl border border-amber-100/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-900/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-amber-700 dark:hover:shadow-black/20">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white dark:bg-amber-400/10 dark:text-amber-400 dark:group-hover:bg-amber-500 dark:group-hover:text-white">
                            <x-filament::icon :icon="$stat['icon']" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $stat['label'] }}</span>
                    </div>

                    <div class="mt-4 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($stat['value']) }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['description'] }}</p>
                </article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-md shadow-amber-900/5 dark:border-gray-700 dark:bg-gray-800 dark:shadow-black/20">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 bg-amber-50/50 px-5 py-4 sm:px-6 dark:border-gray-700 dark:bg-gray-800/80">
                <div>
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('دفتر التحضير وسجل القسم') }}</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('السجل الذكي') }}</h3>
                </div>

                @if ($dailyLog)
                    <span @class([
                        'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold',
                        'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' => $dailyLog->isCompleted(),
                        'bg-amber-100 text-amber-800 dark:bg-amber-400/10 dark:text-amber-300' => $dailyLog->isPlanned(),
                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $dailyLog->isCompleted() && ! $dailyLog->isPlanned(),
                    ])>
                        <span @class([
                            'h-2 w-2 rounded-full',
                            'bg-green-500' => $dailyLog->isCompleted(),
                            'bg-amber-500' => $dailyLog->isPlanned(),
                            'bg-gray-400' => ! $dailyLog->isCompleted() && ! $dailyLog->isPlanned(),
                        ])></span>
                        {{ $dailyLog->status->label() }}
                    </span>
                @endif
            </header>

            @if ($dailyLog)
                <div class="p-5 sm:p-6" x-data="{ activeTab: 'preparation' }">
                    <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-100/80 p-1.5 dark:bg-gray-900" role="tablist" aria-label="{{ __('مراحل السجل اليومي') }}">
                        <button
                            type="button"
                            role="tab"
                            x-on:click="activeTab = 'preparation'"
                            x-bind:aria-selected="activeTab === 'preparation'"
                            x-bind:class="activeTab === 'preparation' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800'"
                            class="rounded-lg px-3 py-2.5 text-sm font-bold transition"
                        >
                            {{ __('التحضير المسبق') }}
                        </button>
                        <button
                            type="button"
                            role="tab"
                            x-on:click="activeTab = 'execution'"
                            x-bind:aria-selected="activeTab === 'execution'"
                            x-bind:class="activeTab === 'execution' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800'"
                            class="rounded-lg px-3 py-2.5 text-sm font-bold transition"
                        >
                            {{ __('مرحلة الإنجاز') }}
                        </button>
                    </div>

                    <div class="mt-5">
                        <div
                            role="tabpanel"
                            x-show="activeTab === 'preparation'"
                            x-cloak
                            class="space-y-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-base font-bold text-gray-950 dark:text-white">{{ $dailyLog->title }}</h4>
                                    @if ($dailyLog->schedule)
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $dailyLog->schedule->label() }}</p>
                                    @endif
                                </div>
                                <time class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                                    {{ $dailyLog->date->format('Y/m/d') }}
                                </time>
                            </div>

                            @forelse ($dailyLog->content_data ?? [] as $block)
                                @php($data = $block['data'] ?? [])
                                @switch($block['type'] ?? '')
                                    @case('middle_school_lesson')
                                        @if (filled($data['competence'] ?? null))
                                            <p class="rounded-xl bg-amber-50/70 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-amber-400/10 dark:text-gray-200">
                                                <span class="font-bold text-amber-800 dark:text-amber-300">{{ __('الكفاءة المستهدفة:') }}</span>
                                                {{ $data['competence'] }}
                                            </p>
                                        @endif

                                        @if (filled($data['pedagogical_situation'] ?? null))
                                            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $data['pedagogical_situation'] }}</p>
                                        @endif

                                        @if (filled($data['phases'] ?? []))
                                            <ol class="space-y-2">
                                                @foreach ($data['phases'] as $phase)
                                                    @php($phaseLabel = \App\Enums\LessonPhaseType::tryFrom($phase['type'] ?? '')?->label() ?? __('مرحلة'))
                                                    <li class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/70">
                                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                                                        <div class="min-w-0 flex-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                            <span class="font-bold text-gray-900 dark:text-white">{{ $phaseLabel }}</span>
                                                            @if (filled($phase['duration'] ?? null))
                                                                <span class="text-gray-500 dark:text-gray-400">({{ $phase['duration'] }} {{ __('د') }})</span>
                                                            @endif
                                                            @if (filled($phase['description'] ?? null))
                                                                <p class="mt-0.5">{{ $phase['description'] }}</p>
                                                            @endif
                                                            @if (filled($phase['strategy'] ?? null))
                                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('الاستراتيجية:') }} {{ $phase['strategy'] }}</p>
                                                            @endif
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ol>
                                        @endif
                                        @break
                                    @case('general_lesson')
                                        @if (filled($data['title'] ?? null))
                                            <h5 class="font-semibold text-gray-900 dark:text-white">{{ $data['title'] }}</h5>
                                        @endif
                                        @if (filled($data['objectives'] ?? null))
                                            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300"><span class="font-semibold">{{ __('الأهداف:') }}</span> {{ $data['objectives'] }}</p>
                                        @endif
                                        @if (filled($data['content'] ?? null))
                                            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $data['content'] }}</p>
                                        @endif
                                        @break
                                    @case('warmup')
                                        <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                            <span class="font-bold">{{ __('التمهيد') }}</span>
                                            @if (filled($data['duration'] ?? null))
                                                <span class="text-gray-500 dark:text-gray-400">({{ $data['duration'] }} {{ __('د') }})</span>
                                            @endif
                                            — {{ $data['description'] ?? '' }}
                                        </p>
                                        @break
                                @endswitch
                            @empty
                                <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                    {{ __('لم تتم إضافة تفاصيل التحضير لهذا السجل بعد.') }}
                                </p>
                            @endforelse
                        </div>

                        <div
                            role="tabpanel"
                            x-show="activeTab === 'execution'"
                            x-cloak
                            class="space-y-4"
                        >
                            @if (filled($dailyLog->actual_content))
                                <div class="whitespace-pre-line rounded-xl bg-gray-50 px-4 py-4 text-sm leading-7 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    {{ $dailyLog->actual_content }}
                                </div>
                            @else
                                <p class="rounded-xl border border-dashed border-amber-200 bg-amber-50/50 px-4 py-5 text-sm leading-6 text-gray-600 dark:border-amber-700 dark:bg-amber-400/5 dark:text-gray-300">
                                    {{ __('لم يُسجّل الأداء الفعلي بعد. أكّد إنجاز الحصة لتحويل التحضير إلى سجل رسمي.') }}
                                </p>
                            @endif

                            @if (filled($dailyLog->homework))
                                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    <span class="font-bold text-gray-800 dark:text-gray-100">{{ __('الواجب المنزلي:') }}</span>
                                    {{ $dailyLog->homework }}
                                </p>
                            @endif
                            @if (filled($dailyLog->observations))
                                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    <span class="font-bold text-gray-800 dark:text-gray-100">{{ __('ملاحظات:') }}</span>
                                    {{ $dailyLog->observations }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="px-5 py-8 text-center sm:px-6">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-book-open" class="h-6 w-6" />
                    </span>
                    <h4 class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('ابدأ سجل اليوم') }}</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('سيظهر أحدث تحضير وسجل قسم هنا.') }}</p>
                    <a
                        href="{{ \App\Filament\Resources\DailyLogResource::getUrl('create') }}"
                        class="mt-4 inline-flex items-center rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
                    >
                        {{ __('إنشاء سجل يومي') }}
                    </a>
                </div>
            @endif
        </section>
    </div>
</section>
