@php
    $progress = $data['percentage'];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('نسبة تقدم المنهاج')"
        :value="$progress === null ? null : $progress.'%'"
        :description="__('موارد مكتملة من إجمالي الموارد ضمن النطاق')"
        icon="heroicon-o-chart-bar"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('موارد مكتملة')"
        :value="number_format($data['completed_resources'])"
        :description="__('من أصل :total مورد', ['total' => number_format($data['total_resources'])])"
        icon="heroicon-o-check-badge"
        tone="amber"
    />

    <x-dashboard.kpi-card
        :label="__('أزواج قسم ومادة')"
        :value="number_format($data['row_count'])"
        :description="__('صفوف المنهاج المطابقة للفلتر')"
        icon="heroicon-o-rectangle-stack"
        tone="info"
    />

    <x-dashboard.kpi-card
        :label="__('آخر تحديث')"
        :value="$data['as_of']->format('H:i')"
        :description="__('يُحتسب التقدم من السجلات المنجزة عند العرض')"
        icon="heroicon-o-clock"
        tone="gray"
    />
</div>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <div>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('تقدم المنهاج') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('أقل الأزواج تقدماً') }}</h2>
        </div>

        <a
            href="{{ \App\Filament\Pages\CurriculumProgress::getUrl() }}"
            class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
        >
            {{ __('فتح متابعة المنهاج') }}
            <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
        </a>
    </header>

    <div class="p-5">
        @if ($data['rows']->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-6 w-6" />
                </span>
                <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد بيانات منهاج لهذا النطاق') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('تظهر هنا الأزواج التي لها موارد مرتبطة وحصص مجدولة فقط.') }}</p>
            </div>
        @else
            <ul role="list" class="space-y-4">
                @foreach ($data['rows'] as $row)
                    <li>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950 dark:text-white">{{ $row['subject']->name }}</p>
                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $row['classroom']->display_name() }}
                                    @if ($row['level'])
                                        <span class="text-gray-400 dark:text-gray-500">·</span>
                                        {{ $row['level']->display_name() }}
                                    @endif
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $row['completed_resources'] }} / {{ $row['total_resources'] }}
                                <span class="text-gray-400 dark:text-gray-500">·</span>
                                {{ $row['percentage'] }}%
                            </span>
                        </div>

                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                            role="img"
                            aria-label="{{ $row['subject']->name }} — {{ $row['classroom']->display_name() }}: {{ $row['percentage'] }}%"
                        >
                            <div class="h-2 rounded-full bg-amber-500" style="width: {{ $row['percentage'] }}%"></div>
                        </div>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('تسلسلات: :completed من :total', ['completed' => $row['completed_sequences'], 'total' => $row['total_sequences']]) }}
                        </p>
                    </li>
                @endforeach
            </ul>

            @if ($data['row_count'] > $data['rows']->count())
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('يُعرض أول :shown من أصل :total زوجاً؛ افتح متابعة المنهاج للقائمة الكاملة.', ['shown' => $data['rows']->count(), 'total' => $data['row_count']]) }}
                </p>
            @endif
        @endif
    </div>
</section>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('كيف نقرأ الأرقام؟') }}</p>
        <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('تعريف تقدم المنهاج') }}</h2>
    </header>

    <div class="grid grid-cols-1 gap-3 p-5 text-sm leading-6 text-gray-600 dark:text-gray-300 md:grid-cols-2">
        <p>{{ __('النسبة = الموارد الفريدة المكتملة ÷ إجمالي الموارد المرتبطة بالأزواج المعروضة.') }}</p>
        <p>{{ __('يُحتسب المورد مكتملاً عندما يرتبط بسجل درس منجز؛ السجلات المخططة أو الملغاة لا تُحتسب.') }}</p>
        <p>{{ __('تُحتسب كل الموارد مرة واحدة حتى لو تكرر تدريسها في أكثر من سجل.') }}</p>
        <p>{{ __('عندما لا توجد موارد مرتبطة، تُعرض النسبة “—” بدل صفر مضلل.') }}</p>
    </div>
</section>
