@php
    $tips = [
        [
            'icon' => 'heroicon-o-clock',
            'title' => __('المؤقت'),
            'text' => __('شغّل المؤقت لمدة 1 أو 5 أو 10 دقائق، ويمكنك الإيقاف المؤقت ثم الاستئناف.'),
        ],
        [
            'icon' => 'heroicon-o-user-group',
            'title' => __('القرعة العشوائية'),
            'text' => __('كل ضغطة على «اختر تلميذاً» تسحب اسماً جديداً دون تكرار حتى تنتهي القائمة.'),
        ],
        [
            'icon' => 'heroicon-o-rectangle-group',
            'title' => __('تكوين المجموعات'),
            'text' => __('اختر عدد المجموعات ثم أنشئها؛ التوزيع عشوائي ومتوازن ويمكن إعادة توليده.'),
        ],
        [
            'icon' => 'heroicon-o-shield-check',
            'title' => __('الحصة المختارة'),
            'text' => __('تُحمَّل أسماء الحصة المختارة فقط، ولا تُحفظ نتائج القرعة أو المجموعات.'),
        ],
    ];
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('أدوات الحصة التفاعلية') }}</p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    {{ $selectedSchedule?->subject?->name ?? __('اختر الحصة') }}
                </h1>

                @if ($selectedSchedule)
                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $selectedSchedule->placeLabel() ?? __('بدون قسم') }}</span>
                        <span class="text-gray-400 dark:text-gray-500">·</span>
                        <span dir="ltr" class="font-mono">{{ $selectedSchedule->start_time?->format('H:i') }} — {{ $selectedSchedule->end_time?->format('H:i') }}</span>
                        <span class="text-gray-400 dark:text-gray-500">·</span>
                        <span>{{ $selectedSchedule->day_of_week?->label() }}</span>

                        @if ($isAdmin && $selectedSchedule->user)
                            <span class="text-gray-400 dark:text-gray-500">·</span>
                            <span>{{ $selectedSchedule->user->name }}</span>
                        @endif
                    </p>
                @endif
            </div>

            @if ($scheduleOptions !== [])
                <div class="w-full shrink-0 lg:w-96">
                    <label for="toolbar-schedule" class="mb-1.5 block text-xs font-bold text-gray-600 dark:text-gray-300">
                        {{ __('الحصة') }}
                    </label>

                    <select
                        id="toolbar-schedule"
                        wire:model.live="selectedScheduleId"
                        class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        @foreach ($scheduleOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <p class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('تُعرض حصص اليوم أولاً، ويمكنك اختيار أي حصة من جدولك.') }}
                    </p>
                </div>
            @endif
        </div>
    </section>

    @if ($scheduleOptions === [])
        <section class="rounded-2xl border border-amber-100 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-7 w-7" />
            </span>

            <h2 class="mt-4 text-lg font-bold text-gray-950 dark:text-white">{{ __('لا توجد حصص مسندة بعد.') }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                {{ $isAdmin
                    ? __('لا توجد حصص مسجّلة لهذا اليوم. أضف حصة جديدة إلى الجدول.')
                    : __('لم تُسند إليك أي حصة بعد. أضف حصة إلى جدولك الأسبوعي أولاً.') }}
            </p>

            <a
                href="{{ $isAdmin ? \App\Filament\Resources\ScheduleResource::getUrl() : \App\Filament\Pages\MySchedule::getUrl() }}"
                class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
            >
                {{ $isAdmin ? __('فتح إدارة الحصص') : __('فتح جدولي الأسبوعي') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>
        </section>
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Timer --}}
            <section
                class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                aria-labelledby="toolbar-timer-title"
                x-data="{
                    seconds: 0,
                    running: false,
                    started: false,
                    intervalId: null,
                    start(minutes) {
                        this.seconds = minutes * 60;
                        this.started = true;
                        this.resume();
                    },
                    resume() {
                        if (this.seconds <= 0) {
                            return;
                        }

                        this.running = true;
                        this.started = true;
                        clearInterval(this.intervalId);
                        this.intervalId = setInterval(() => {
                            if (!this.running || this.seconds <= 0) {
                                return;
                            }

                            this.seconds--;

                            if (this.seconds === 0) {
                                this.running = false;
                                clearInterval(this.intervalId);
                                this.intervalId = null;
                            }
                        }, 1000);
                    },
                    pause() {
                        this.running = false;
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    },
                    reset() {
                        this.pause();
                        this.seconds = 0;
                        this.started = false;
                    },
                    formatted() {
                        const minutes = Math.floor(this.seconds / 60).toString().padStart(2, '0');
                        const seconds = (this.seconds % 60).toString().padStart(2, '0');

                        return `${minutes}:${seconds}`;
                    }
                }"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('المؤقت') }}</p>
                        <h2 id="toolbar-timer-title" class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('مؤقت الحصة') }}</h2>
                    </div>

                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5" />
                    </span>
                </div>

                <p
                    class="mt-5 font-mono text-5xl font-bold tabular-nums tracking-tight text-gray-950 dark:text-white"
                    role="timer"
                    aria-live="off"
                    x-text="formatted()"
                >00:00</p>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        x-on:click="start(1)"
                        class="rounded-xl bg-amber-50 px-3 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:bg-amber-400/10 dark:text-amber-300 dark:hover:bg-amber-400/20"
                    >
                        {{ __('دقيقة واحدة') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="start(5)"
                        class="rounded-xl bg-amber-50 px-3 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:bg-amber-400/10 dark:text-amber-300 dark:hover:bg-amber-400/20"
                    >
                        {{ __('5 دقائق') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="start(10)"
                        class="rounded-xl bg-amber-50 px-3 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:bg-amber-400/10 dark:text-amber-300 dark:hover:bg-amber-400/20"
                    >
                        {{ __('10 دقائق') }}
                    </button>
                </div>

                <div class="mt-3 flex gap-2">
                    <button
                        type="button"
                        x-on:click="running ? pause() : resume()"
                        x-bind:disabled="seconds === 0"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-show="! running">{{ __('استئناف') }}</span>
                        <span x-show="running" x-cloak>{{ __('إيقاف مؤقت') }}</span>
                    </button>

                    <button
                        type="button"
                        x-on:click="reset()"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ __('إعادة ضبط') }}
                    </button>
                </div>

                <p class="mt-3 text-xs font-semibold text-success-700 dark:text-success-300" x-show="seconds === 0 && started" x-cloak>
                    {{ __('انتهى الوقت') }}
                </p>

                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    {{ __('يعمل المؤقت داخل المتصفح دون إرسال طلبات للخادم.') }}
                </p>
            </section>

            {{-- Random picker --}}
            <section class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900" aria-labelledby="toolbar-picker-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('القرعة العشوائية') }}</p>
                        <h2 id="toolbar-picker-title" class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('اختر تلميذ') }}</h2>
                    </div>

                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5" />
                    </span>
                </div>

                @if ($students === [])
                    <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ __('لا توجد أسماء في هذه الحصة.') }}
                    </p>
                @else
                    <p class="mt-5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ __('عدد التلاميذ') }}: <span class="font-bold text-gray-900 dark:text-white">{{ count($students) }}</span>
                    </p>

                    <details class="mt-2 text-start">
                        <summary class="cursor-pointer text-xs font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">
                            {{ __('قائمة التلاميذ') }}
                        </summary>

                        <ul class="mt-2 flex max-h-32 flex-wrap gap-1 overflow-y-auto rounded-xl bg-gray-50 p-2 dark:bg-gray-800">
                            @foreach ($students as $student)
                                <li class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-gray-700 shadow-sm dark:bg-gray-900 dark:text-gray-200">
                                    {{ $student['name'] }}
                                </li>
                            @endforeach
                        </ul>
                    </details>

                    <div class="mt-3 min-h-24 rounded-xl border border-dashed border-amber-200 bg-amber-50/60 p-4 text-center dark:border-amber-400/20 dark:bg-amber-400/10">
                        @if ($currentPickName)
                            <p class="text-xs font-bold text-amber-700 dark:text-amber-300">{{ __('التلميذ المختار') }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $currentPickName }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @if ($remaining === 0)
                                    {{ __('تم اختيار جميع التلاميذ؛ ستُعاد الدورة تلقائياً عند السحب التالي.') }}
                                @else
                                    {{ __('المتبقي: :count', ['count' => $remaining]) }}
                                @endif
                            </p>
                        @else
                            <p class="pt-5 text-sm text-gray-500 dark:text-gray-400">{{ __('لم يُسحب أي اسم بعد.') }}</p>
                        @endif
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            wire:click="pickRandom"
                            wire:loading.attr="disabled"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4" />
                            {{ __('اختر تلميذ') }}
                        </button>

                        <button
                            type="button"
                            wire:click="resetPicked"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            {{ __('إعادة ضبط') }}
                        </button>
                    </div>

                    @if ($picked !== [])
                        <div class="mt-4 flex flex-wrap gap-1.5" aria-label="{{ __('أسماء التلاميذ الذين تم اختيارهم') }}">
                            @foreach ($picked as $studentId)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    {{ $studentNames[$studentId] ?? '#'.$studentId }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        {{ __('بدون تكرار حتى نهاية الدورة؛ والقرعة لا تُحفظ.') }}
                    </p>
                @endif
            </section>

            {{-- Groups --}}
            <section class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900" aria-labelledby="toolbar-groups-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('تكوين المجموعات') }}</p>
                        <h2 id="toolbar-groups-title" class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('إنشاء مجموعات') }}</h2>
                    </div>

                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-o-rectangle-group" class="h-5 w-5" />
                    </span>
                </div>

                @if ($students === [])
                    <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ __('لا توجد أسماء في هذه الحصة.') }}
                    </p>
                @else
                    <div class="mt-5 grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                        <label for="toolbar-group-count" class="sr-only">{{ __('عدد المجموعات') }}</label>

                        <select
                            id="toolbar-group-count"
                            wire:model.live="groupCount"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach (range(2, 6) as $count)
                                <option value="{{ $count }}">{{ $count }} {{ __('مجموعات') }}</option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            wire:click="generateGroups"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                        >
                            <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
                            {{ $groups === [] ? __('إنشاء مجموعات') : __('إعادة التوليد') }}
                        </button>
                    </div>

                    @if ($groups !== [])
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($groups as $group)
                                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 dark:border-blue-400/20 dark:bg-blue-400/10">
                                    <p class="text-xs font-bold text-blue-800 dark:text-blue-300">
                                        {{ __('مجموعة') }} {{ $loop->iteration }}
                                        <span class="font-normal text-blue-600/80 dark:text-blue-300/80">({{ count($group) }})</span>
                                    </p>

                                    <ul class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($group as $studentId)
                                            <li class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-gray-700 shadow-sm dark:bg-gray-900 dark:text-gray-200">
                                                {{ $studentNames[$studentId] ?? '#'.$studentId }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('التوزيع عشوائي ومتوازن؛ أعد التوليد لترتيب آخر.') }}</p>
                    @else
                        <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            {{ __('اختر عدد المجموعات ثم أنشئها؛ التوزيع عشوائي ومتوازن.') }}
                        </p>
                    @endif
                @endif
            </section>
        </div>
    @endif

    {{-- Usage guide --}}
    <section class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900" aria-labelledby="toolbar-tips-title">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5" />
            </span>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ __('دليل سريع') }}</p>
                <h2 id="toolbar-tips-title" class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('كيف تستخدم أدوات الحصة؟') }}</h2>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ($tips as $tip)
                <article class="rounded-xl bg-amber-50/60 p-4 dark:bg-amber-400/10">
                    <div class="flex items-center gap-2">
                        <x-filament::icon :icon="$tip['icon']" class="h-4 w-4 shrink-0 text-amber-700 dark:text-amber-300" />
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $tip['title'] }}</h3>
                    </div>
                    <p class="mt-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $tip['text'] }}</p>
                </article>
            @endforeach
        </div>

        <p class="mt-4 rounded-xl border border-amber-100 bg-white px-4 py-3 text-xs leading-5 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            {{ __('هذه الأدوات مساعدة للعرض والتنشيط فقط: لا تسجّل الحضور ولا تعدّل السجل الرسمي.') }}
        </p>
    </section>
</div>
