@php
    $gradeCompletion = $data['grade_rows'] > 0
        ? (int) round($data['scored_rows'] / $data['grade_rows'] * 100)
        : null;
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('تقييمات قادمة')"
        :value="number_format($data['upcoming_count'])"
        :description="__('خلال الأسبوعين القادمين')"
        icon="heroicon-o-calendar"
        tone="info"
    />

    <x-dashboard.kpi-card
        :label="__('تقييمات الفترة')"
        :value="number_format($data['recent_count'])"
        :description="__('ضمن الفترة المحددة حتى اليوم')"
        icon="heroicon-o-clipboard-document-list"
        tone="amber"
    />

    <x-dashboard.kpi-card
        :label="__('علامات مدخلة')"
        :value="number_format($data['scored_rows'])"
        :description="__('علامة من :total صف علامة موجود', ['total' => number_format($data['grade_rows'])])"
        icon="heroicon-o-pencil-square"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('تقييمات لم تبدأ')"
        :value="number_format($data['not_started_count'])"
        :description="__('تقييمات الفترة دون أي صف علامة')"
        icon="heroicon-o-exclamation-triangle"
        tone="danger"
    />
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('التقييمات') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('التقييمات القادمة') }}</h2>
            </div>

            <x-filament::icon icon="heroicon-o-calendar" class="h-5 w-5 shrink-0 text-amber-500" />
        </header>

        <div class="p-5">
            @if ($data['upcoming']->isEmpty())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-calendar" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد تقييمات قادمة') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('ستظهر التقييمات المجدولة خلال الأسبوعين القادمين هنا.') }}</p>
                </div>
            @else
                <ul role="list" class="space-y-3">
                    @foreach ($data['upcoming'] as $assessment)
                        @php($schedule = $assessment->schedule)

                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950 dark:text-white">{{ $assessment->title }}</p>
                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $schedule?->subject?->name ?? __('بدون مادة') }}
                                    <span class="text-gray-400 dark:text-gray-500">·</span>
                                    {{ $schedule?->placeLabel() ?? __('بدون قسم') }}
                                </p>
                            </div>

                            <div class="text-end">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                                    {{ $assessment->type?->label() ?? '—' }}
                                </span>
                                <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    {{ $assessment->date?->format('Y/m/d') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="border-t border-amber-100 px-5 py-4 dark:border-gray-700">
            <a
                href="{{ \App\Filament\Resources\AssessmentResource::getUrl() }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
            >
                {{ __('فتح التقييمات') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('سجل العلامات') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('تقدم إدخال العلامات') }}</h2>
            </div>

            <span class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $gradeCompletion === null ? '—' : $gradeCompletion.'%' }}
            </span>
        </header>

        <div class="space-y-5 p-5">
            <div
                class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                role="img"
                aria-label="{{ __('نسبة العلامات المدخلة') }}: {{ $gradeCompletion === null ? __('غير متاح') : $gradeCompletion.'%' }}"
            >
                @if ($gradeCompletion !== null)
                    <div class="h-2.5 rounded-full bg-success-500" style="width: {{ $gradeCompletion }}%"></div>
                @endif
            </div>

            <dl class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                    <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('صفوف العلامات') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($data['grade_rows']) }}</dd>
                </div>

                <div class="rounded-xl bg-success-50 p-3 dark:bg-success-400/10">
                    <dt class="text-xs font-semibold text-success-700 dark:text-success-300">{{ __('علامات مدخلة') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-success-800 dark:text-success-200">{{ number_format($data['scored_rows']) }}</dd>
                </div>

                <div class="rounded-xl bg-warning-50 p-3 dark:bg-warning-400/10">
                    <dt class="text-xs font-semibold text-warning-800 dark:text-warning-200">{{ __('تقييمات ناقصة') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-warning-900 dark:text-warning-100">{{ number_format($data['assessments_with_missing_scores']) }}</dd>
                </div>

                <div class="rounded-xl bg-danger-50 p-3 dark:bg-danger-400/10">
                    <dt class="text-xs font-semibold text-danger-700 dark:text-danger-300">{{ __('تقييمات لم تبدأ') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-danger-800 dark:text-danger-200">{{ number_format($data['not_started_count']) }}</dd>
                </div>
            </dl>

            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                {{ __('المقام هو صفوف العلامات الموجودة فقط. التقييمات التي لم تُنشأ لها صفوف لا تُحتسب صفراً ولا تُدخل في النسبة.') }}
            </p>
        </div>
    </section>
</div>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('كيف نقرأ الأرقام؟') }}</p>
        <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('تعريفات التقييم والعلامات') }}</h2>
    </header>

    <div class="grid grid-cols-1 gap-3 p-5 text-sm leading-6 text-gray-600 dark:text-gray-300 md:grid-cols-2">
        <p>{{ __('“تقييمات الفترة” هي التقييمات التي يقع تاريخها داخل الفترة المحددة وحتى اليوم.') }}</p>
        <p>{{ __('“لم تبدأ” تعني عدم وجود أي صف علامة لذلك التقييم، وليس أن كل التلاميذ حصلوا على صفر.') }}</p>
        <p>{{ __('“علامات مدخلة” تعني صفوفاً تحمل علامة رقمية؛ الصفوف الفارغة تبقى ناقصة.') }}</p>
        <p>{{ __('لا تُعرض معدلات لأن التقييمات قد تستخدم سلالم تنقيط مختلفة.') }}</p>
    </div>
</section>
