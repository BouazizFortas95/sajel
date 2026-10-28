@php
    $behaviorConcernRate = $data['behavior_total'] > 0
        ? (int) round($data['open_concerns'] / $data['behavior_total'] * 100)
        : null;
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('ملاحظات سلوكية')"
        :value="number_format($data['behavior_total'])"
        :description="__('ملاحظات مسجلة داخل الفترة')"
        icon="heroicon-o-chat-bubble-left-right"
        tone="info"
    />

    <x-dashboard.kpi-card
        :label="__('ملاحظات إيجابية')"
        :value="number_format($data['positive_count'])"
        :description="__('من إجمالي الملاحظات المسجلة')"
        icon="heroicon-o-hand-thumb-up"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('متابعات مفتوحة')"
        :value="number_format($data['open_concerns'])"
        :description="__('ملاحظات سالبة أو انضباطية دون متابعة')"
        icon="heroicon-o-exclamation-triangle"
        tone="danger"
    />

    <x-dashboard.kpi-card
        :label="__('معالجات مفتوحة')"
        :value="number_format($data['open_remediations'])"
        :description="__('من :total معالجة في الفترة', ['total' => number_format($data['remediation_total'])])"
        icon="heroicon-o-academic-cap"
        tone="warning"
    />
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('المتابعة التربوية') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('نسبة المتابعات المفتوحة') }}</h2>
            </div>

            <x-filament::icon icon="heroicon-o-heart" class="h-5 w-5 shrink-0 text-amber-500" />
        </header>

        <div class="space-y-5 p-5">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-3xl font-bold text-gray-950 dark:text-white">
                        {{ $behaviorConcernRate === null ? '—' : $behaviorConcernRate.'%' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('من الملاحظات المسجلة تحتاج متابعة') }}</p>
                </div>

                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6" />
                </span>
            </div>

            <div
                class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                role="img"
                aria-label="{{ __('نسبة المتابعات المفتوحة') }}: {{ $behaviorConcernRate === null ? __('غير متاح') : $behaviorConcernRate.'%' }}"
            >
                @if ($behaviorConcernRate !== null)
                    <div class="h-2.5 rounded-full bg-danger-500" style="width: {{ $behaviorConcernRate }}%"></div>
                @endif
            </div>

            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                {{ __('تُعرض أعداد مجمعة فقط، دون أسماء التلاميذ، حمايةً للخصوصية.') }}
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ \App\Filament\Resources\BehaviorObservationResource::getUrl() }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
                >
                    {{ __('فتح الملاحظات والسلوك') }}
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                </a>

                <a
                    href="{{ \App\Filament\Resources\PedagogicalRemediationResource::getUrl() }}"
                    class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ __('فتح المعالجة البيداغوجية') }}
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('كيف نقرأ الأرقام؟') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('تعريفات الدعم والمتابعة') }}</h2>
        </header>

        <div class="grid grid-cols-1 gap-3 p-5 text-sm leading-6 text-gray-600 dark:text-gray-300">
            <p>{{ __('“متابعة مفتوحة” تعني ملاحظة سالبة أو انضباطية أو عامة لم تُعلَّم كمتابَعة بعد.') }}</p>
            <p>{{ __('“معالجة مفتوحة” تعني معالجة بحالة “مخطط” أو “مستمر” داخل الفترة.') }}</p>
            <p>{{ __('المعلم يرى فقط الملاحظات والمعالجات المرتبطة بجدوله أو التي سجلها بنفسه.') }}</p>
            <p>{{ __('الأعداد مجمعة؛ افتح الصفحة المخصصة لمراجعة التفاصيل وفق صلاحياتك.') }}</p>
        </div>
    </section>
</div>

@if ($data['behavior_total'] === 0 && $data['remediation_total'] === 0)
    <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-5 py-8 text-center dark:border-amber-700 dark:bg-amber-400/5">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
            <x-filament::icon icon="heroicon-o-heart" class="h-6 w-6" />
        </span>
        <h3 class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد بيانات دعم لهذه الفترة') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('سجّل ملاحظة سلوكية أو معالجة بيداغوجية لتظهر هنا.') }}</p>
    </div>
@endif
