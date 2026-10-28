@php
    $identity = [
        __('اسم المؤسسة') => $settings->school_name,
        __('مديرية التربية') => $settings->directorate_name,
        __('البلدية') => $settings->municipality_name,
        __('السنة الدراسية') => $settings->academic_year,
        __('المدير') => $settings->principal_name,
        __('المستشار') => $settings->counselor_name,
        __('الرقم الميكانيكي') => $settings->school_code,
    ];
@endphp

<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('المعلمون')"
        :value="number_format($data['teachers'])"
        icon="heroicon-o-user-group"
        tone="amber"
    />

    <x-dashboard.kpi-card
        :label="__('التلاميذ')"
        :value="number_format($data['students'])"
        icon="heroicon-o-academic-cap"
        tone="info"
    />

    <x-dashboard.kpi-card
        :label="__('الأقسام')"
        :value="number_format($data['classrooms'])"
        icon="heroicon-o-building-library"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('المواد')"
        :value="number_format($data['subjects'])"
        icon="heroicon-o-book-open"
        tone="gray"
    />
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('المؤسسة') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('الهوية الرسمية') }}</h2>
            </div>

            <x-filament::icon icon="heroicon-o-building-library" class="h-5 w-5 shrink-0 text-amber-500" />
        </header>

        <dl class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($identity as $label => $value)
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                    <dd class="text-sm font-bold text-gray-950 dark:text-white">{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="border-t border-amber-100 px-5 py-4 dark:border-gray-700">
            <a
                href="{{ \App\Filament\Pages\ManageSchoolSettings::getUrl() }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600"
            >
                {{ __('فتح إعدادات المؤسسة') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('التقويم المدرسي') }}</p>
                <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('العطل القادمة') }}</h2>
            </div>

            <a
                href="{{ \App\Filament\Resources\SchoolHolidayResource::getUrl() }}"
                class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
            >
                {{ __('إدارة العطل') }}
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
            </a>
        </header>

        <div class="p-5">
            @if ($data['holidays']->isEmpty())
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-sun" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد عطل قادمة مسجلة') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('أضف العطل من صفحة إدارة العطل لتُستثنى من احتساب الحصص.') }}</p>
                </div>
            @else
                <ul role="list" class="space-y-3">
                    @foreach ($data['holidays'] as $holiday)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950 dark:text-white">{{ $holiday->title }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $holiday->type ?? __('عطلة مدرسية') }}</p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700" dir="ltr">
                                {{ $holiday->start_date?->format('Y/m/d') }}
                                @if ($holiday->end_date && ! $holiday->end_date->isSameDay($holiday->start_date))
                                    — {{ $holiday->end_date->format('Y/m/d') }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('التشغيل اليومي') }}</p>
        <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('أدوات المتابعة المؤسسية') }}</h2>
    </header>

    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2">
        <a
            href="{{ \App\Filament\Pages\SchoolSupervisionHub::getUrl() }}"
            class="group flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 transition hover:border-amber-200 hover:bg-amber-50/70 dark:border-gray-700 dark:bg-gray-800/80 dark:hover:border-amber-400/30 dark:hover:bg-amber-400/5"
        >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" />
            </span>

            <span class="min-w-0">
                <span class="block font-bold text-gray-950 dark:text-white">{{ __('المراقبة العامة والتفتيش') }}</span>
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">{{ __('الالتزام اليومي، الغيابات، والملاحظات الميدانية.') }}</span>
            </span>
        </a>

        <a
            href="{{ \App\Filament\Pages\OfficialReports::getUrl() }}"
            class="group flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 transition hover:border-amber-200 hover:bg-amber-50/70 dark:border-gray-700 dark:bg-gray-800/80 dark:hover:border-amber-400/30 dark:hover:bg-amber-400/5"
        >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                <x-filament::icon icon="heroicon-o-document-chart-bar" class="h-5 w-5" />
            </span>

            <span class="min-w-0">
                <span class="block font-bold text-gray-950 dark:text-white">{{ __('التقارير الرسمية') }}</span>
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">{{ __('تقارير الالتزام والنتائج الرسمية.') }}</span>
            </span>
        </a>

        <a
            href="{{ \App\Filament\Pages\MotivationPulseHub::getUrl() }}"
            class="group flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 transition hover:border-amber-200 hover:bg-amber-50/70 dark:border-gray-700 dark:bg-gray-800/80 dark:hover:border-amber-400/30 dark:hover:bg-amber-400/5"
        >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5" />
            </span>

            <span class="min-w-0">
                <span class="block font-bold text-gray-950 dark:text-white">{{ __('نبض التميز والتحفيز') }}</span>
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">{{ __('مؤشرات التحفيز والتميز التربوي.') }}</span>
            </span>
        </a>

        <a
            href="{{ \App\Filament\Pages\ClassCouncilHub::getUrl() }}"
            class="group flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 transition hover:border-amber-200 hover:bg-amber-50/70 dark:border-gray-700 dark:bg-gray-800/80 dark:hover:border-amber-400/30 dark:hover:bg-amber-400/5"
        >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                <x-filament::icon icon="heroicon-o-users" class="h-5 w-5" />
            </span>

            <span class="min-w-0">
                <span class="block font-bold text-gray-950 dark:text-white">{{ __('مجالس الأقسام') }}</span>
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">{{ __('متابعة المجالس والقرارات التربوية.') }}</span>
            </span>
        </a>
    </div>
</section>
