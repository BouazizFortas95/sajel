<section class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-md shadow-amber-900/5 dark:border-gray-700 dark:bg-gray-800 dark:shadow-black/20">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 bg-amber-50/50 px-5 py-4 sm:px-6 dark:border-gray-700 dark:bg-gray-800/80">
        <div>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('دفتر التحضير وسجل القسم') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('السجل الذكي') }}</h2>
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
                    id="smart-log-tab-preparation"
                    role="tab"
                    aria-controls="smart-log-panel-preparation"
                    x-bind:tabindex="activeTab === 'preparation' ? 0 : -1"
                    x-on:click="activeTab = 'preparation'"
                    x-on:keydown.arrow-right.prevent="$el.nextElementSibling?.focus()"
                    x-on:keydown.arrow-left.prevent="$el.previousElementSibling?.focus()"
                    x-bind:aria-selected="activeTab === 'preparation'"
                    x-bind:class="activeTab === 'preparation' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800'"
                    class="rounded-lg px-3 py-2.5 text-sm font-bold transition"
                >
                    {{ __('التحضير المسبق') }}
                </button>
                <button
                    type="button"
                    id="smart-log-tab-execution"
                    role="tab"
                    aria-controls="smart-log-panel-execution"
                    x-bind:tabindex="activeTab === 'execution' ? 0 : -1"
                    x-on:click="activeTab = 'execution'"
                    x-on:keydown.arrow-right.prevent="$el.nextElementSibling?.focus()"
                    x-on:keydown.arrow-left.prevent="$el.previousElementSibling?.focus()"
                    x-bind:aria-selected="activeTab === 'execution'"
                    x-bind:class="activeTab === 'execution' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800'"
                    class="rounded-lg px-3 py-2.5 text-sm font-bold transition"
                >
                    {{ __('مرحلة الإنجاز') }}
                </button>
            </div>

            <div class="mt-5">
                <div
                    id="smart-log-panel-preparation"
                    role="tabpanel"
                    aria-labelledby="smart-log-tab-preparation"
                    x-show="activeTab === 'preparation'"
                    x-cloak
                    class="space-y-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $dailyLog->title }}</h3>
                            @if ($dailyLog->schedule)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $dailyLog->schedule->label() }}</p>
                            @endif
                        </div>
                        <time class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                            {{ $dailyLog->date->format('Y/m/d') }}
                        </time>
                    </div>

                    @forelse ($dailyLog->content_data ?? [] as $block)
                        @php($blockData = $block['data'] ?? [])
                        @switch($block['type'] ?? '')
                            @case('middle_school_lesson')
                                @if (filled($blockData['competence'] ?? null))
                                    <p class="rounded-xl bg-amber-50/70 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-amber-400/10 dark:text-gray-200">
                                        <span class="font-bold text-amber-800 dark:text-amber-300">{{ __('الكفاءة المستهدفة:') }}</span>
                                        {{ $blockData['competence'] }}
                                    </p>
                                @endif

                                @if (filled($blockData['pedagogical_situation'] ?? null))
                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $blockData['pedagogical_situation'] }}</p>
                                @endif

                                @if (filled($blockData['phases'] ?? []))
                                    <ol class="space-y-2">
                                        @foreach ($blockData['phases'] as $phase)
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
                                @if (filled($blockData['title'] ?? null))
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $blockData['title'] }}</h4>
                                @endif
                                @if (filled($blockData['objectives'] ?? null))
                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300"><span class="font-semibold">{{ __('الأهداف:') }}</span> {{ $blockData['objectives'] }}</p>
                                @endif
                                @if (filled($blockData['content'] ?? null))
                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $blockData['content'] }}</p>
                                @endif
                                @break
                            @case('warmup')
                                <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <span class="font-bold">{{ __('التمهيد') }}</span>
                                    @if (filled($blockData['duration'] ?? null))
                                        <span class="text-gray-500 dark:text-gray-400">({{ $blockData['duration'] }} {{ __('د') }})</span>
                                    @endif
                                    — {{ $blockData['description'] ?? '' }}
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
                    id="smart-log-panel-execution"
                    role="tabpanel"
                    aria-labelledby="smart-log-tab-execution"
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
            <h3 class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('ابدأ سجل اليوم') }}</h3>
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
