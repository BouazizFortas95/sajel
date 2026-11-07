@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp

<div class="space-y-6" dir="{{ $dir }}">
    {{-- Header Banner: Developer Cockpit & Multi-Institution Aggregator --}}
    <section class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white shadow-md dark:border-gray-800">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/20 px-3 py-1 text-xs font-semibold text-indigo-300 ring-1 ring-inset ring-indigo-400/30">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-4 w-4" />
                    <span>{{ __('وضع المطور ومدير المنصة — لوحة تجميع بيانات شبكة المؤسسات التربوية') }}</span>
                </div>

                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">
                    {{ $platformSetting->platform_name ?? 'سَجِّل — Sajel' }}
                </h1>

                <p class="text-sm text-slate-300">
                    {{ $platformSetting->platform_slogan ?? __('المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية') }}
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-slate-400">
                    <span class="inline-flex items-center gap-1 font-medium text-amber-300">
                        <x-filament::icon icon="heroicon-o-user-circle" class="h-3.5 w-3.5" />
                        {{ $user->name }} ({{ __('المطور / مسؤول النظام') }})
                    </span>
                    <span>·</span>
                    <span class="inline-flex items-center gap-1">
                        <x-filament::icon icon="heroicon-o-server" class="h-3.5 w-3.5 text-emerald-400" />
                        PHP {{ $telemetry['php_version'] }} · {{ strtoupper($telemetry['db_driver']) }} ({{ $telemetry['db_tables_count'] }} {{ __('جدول') }})
                    </span>
                    <span>·</span>
                    <span class="inline-flex items-center gap-1 text-indigo-300">
                        <x-filament::icon icon="heroicon-o-calendar" class="h-3.5 w-3.5" />
                        {{ now()->locale(app()->getLocale())->translatedFormat('l j F Y') }}
                    </span>
                </div>
            </div>

            {{-- Active Institution Card & Quick Switcher --}}
            <div class="rounded-xl border border-indigo-400/30 bg-indigo-950/60 p-4 shadow-inner lg:max-w-md">
                <div class="flex items-center justify-between gap-3 pb-2 border-b border-indigo-800/60">
                    <span class="text-xs font-bold text-amber-400 flex items-center gap-1">
                        <x-filament::icon icon="heroicon-s-check-circle" class="h-4 w-4 text-emerald-400" />
                        {{ __('المؤسسة التشغيلية النشطة حالياً') }}
                    </span>
                    <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-300">
                        {{ __('المعتمدة في الوثائق') }}
                    </span>
                </div>

                <div class="mt-2.5">
                    <h2 class="text-base font-bold text-white">
                        {{ $currentInstitution?->name ?? __('متوسطة الشهيد العربي بن مهيدي') }}
                    </h2>
                    <p class="text-xs text-indigo-200 mt-0.5">
                        {{ $currentInstitution?->directorate_name ?? '—' }}
                        @if ($currentInstitution?->code)
                            · <span class="font-mono text-amber-200">{{ $currentInstitution->code }}</span>
                        @endif
                    </p>
                    <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-300">
                        <span>{{ __('المدير:') }} {{ $currentInstitution?->principal_name ?? __('غير محدد') }}</span>
                        <span>·</span>
                        <span>{{ __('الطور:') }}
                            @if ($currentInstitution?->stage === 'primary') {{ __('ابتدائي') }}
                            @elseif ($currentInstitution?->stage === 'secondary') {{ __('ثانوي') }}
                            @elseif ($currentInstitution?->stage === 'complex') {{ __('مجمع مدرسي') }}
                            @else {{ __('متوسط') }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Top Aggregated KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Institutions --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ __('إجمالي المؤسسات المسجلة') }}</span>
                <span class="rounded-xl bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $totalInstitutions }}</span>
                <span class="text-xs font-medium text-gray-500">{{ __('مؤسسة تعليمية') }}</span>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs">
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    {{ $activeInstitutions }} {{ __('نشطة') }}
                </span>
                @if ($pendingInstitutions > 0)
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        {{ $pendingInstitutions }} {{ __('قيد التحضير') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Aggregated Capacity --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ __('طاقة الاستيعاب التجميعية') }}</span>
                <span class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalCapacity) }}</span>
                <span class="text-xs font-medium text-gray-500">{{ __('مقعد بيداغوجي') }}</span>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $enrolledStudentsInActiveSchool }}</span> {{ __('تلميذ متمدرس بالمؤسسة النشطة') }}
            </p>
        </div>

        {{-- Total Users & Staff --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ __('المستخدمون والتأطير التربوي') }}</span>
                <span class="rounded-xl bg-amber-50 p-2 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-users" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $usersCount }}</span>
                <span class="text-xs font-medium text-gray-500">{{ __('مستخدم مؤطر') }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $teachersCount }} {{ __('أستاذ') }}</span> ·
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $principalsCount }} {{ __('مدير') }}</span> ·
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $censorsCount }} {{ __('ناظر') }}</span>
            </div>
        </div>

        {{-- Educational Stages Breakdown --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ __('التوزيع حسب الأطوار التعليمية') }}</span>
                <span class="rounded-xl bg-purple-50 p-2 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <x-filament::icon icon="heroicon-o-chart-pie" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-1 text-center">
                <div class="rounded-lg bg-emerald-50 py-1 dark:bg-emerald-950/40">
                    <span class="block text-base font-bold text-emerald-700 dark:text-emerald-300">{{ $stageCounts['primary'] }}</span>
                    <span class="block text-[10px] text-emerald-800 dark:text-emerald-400">{{ __('ابتدائي') }}</span>
                </div>
                <div class="rounded-lg bg-blue-50 py-1 dark:bg-blue-950/40">
                    <span class="block text-base font-bold text-blue-700 dark:text-blue-300">{{ $stageCounts['middle'] }}</span>
                    <span class="block text-[10px] text-blue-800 dark:text-blue-400">{{ __('متوسط') }}</span>
                </div>
                <div class="rounded-lg bg-purple-50 py-1 dark:bg-purple-950/40">
                    <span class="block text-base font-bold text-purple-700 dark:text-purple-300">{{ $stageCounts['secondary'] }}</span>
                    <span class="block text-[10px] text-purple-800 dark:text-purple-400">{{ __('ثانوي') }}</span>
                </div>
            </div>
            <p class="mt-2.5 text-[11px] text-gray-400 text-center">
                {{ __('تغطية شاملة للمنظومة التربوية الجزائرية') }}
            </p>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3 dark:border-gray-800">
        <nav class="flex flex-wrap items-center gap-2" aria-label="Tabs">
            <button
                type="button"
                wire:click="setTab('aggregator')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'aggregator' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="h-4 w-4" />
                <span>{{ __('مجمّع شبكة المؤسسات التربوية') }} ({{ $totalInstitutions }})</span>
            </button>

            <button
                type="button"
                wire:click="setTab('wilayas')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'wilayas' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-map" class="h-4 w-4" />
                <span>{{ __('التوزيع الجغرافي والولايات') }} ({{ $wilayasDistribution->count() }})</span>
            </button>

            <button
                type="button"
                wire:click="setTab('active_school_preview')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'active_school_preview' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-eye" class="h-4 w-4" />
                <span>{{ __('معاينة لوحة المؤسسة النشطة') }}</span>
            </button>

            <button
                type="button"
                wire:click="setTab('system_ops')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'system_ops' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-4 w-4" />
                <span>{{ __('العمليات الفنية والصيانة') }}</span>
            </button>
        </nav>

        <div class="flex items-center gap-2">
            <a
                href="{{ $createInstitutionUrl }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700"
            >
                <x-filament::icon icon="heroicon-o-plus-circle" class="h-4 w-4" />
                <span>{{ __('إضافة مؤسسة جديدة') }}</span>
            </a>

            <a
                href="{{ $registryUrl }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
                <x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4" />
                <span>{{ __('سجل المؤسسات الكامل') }}</span>
            </a>
        </div>
    </div>

    {{-- TAB 1: Multi-Institution Ecosystem Aggregator --}}
    @if ($activeTab === 'aggregator')
        <div class="space-y-4">
            {{-- Search & Filters --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">{{ __('البحث في المؤسسات') }}</label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchTerm"
                            placeholder="{{ __('اسم المؤسسة، الرمز، الولاية...') }}"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">{{ __('الطور التعليمي') }}</label>
                        <select
                            wire:model.live="stageFilter"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('جميع الأطوار') }}</option>
                            <option value="primary">{{ __('التعليم الابتدائي') }}</option>
                            <option value="middle">{{ __('التعليم المتوسط') }}</option>
                            <option value="secondary">{{ __('التعليم الثانوي') }}</option>
                            <option value="complex">{{ __('مجمع مدرسي') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">{{ __('حالة التشغيل') }}</label>
                        <select
                            wire:model.live="statusFilter"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('جميع الحالات') }}</option>
                            <option value="active">{{ __('نشطة ومفعلة') }}</option>
                            <option value="pending">{{ __('قيد التجهيز') }}</option>
                            <option value="suspended">{{ __('معلقة مؤقتاً') }}</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ __('إعادة تعيين الفلاتر') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Institutions Grid --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @forelse ($institutions as $inst)
                    <div class="relative overflow-hidden rounded-2xl border {{ $inst->is_current ? 'border-emerald-400 bg-emerald-50/20 ring-2 ring-emerald-500/20 dark:border-emerald-500/40 dark:bg-emerald-950/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' }} p-5 shadow-sm transition hover:shadow-md">
                        @if ($inst->is_current)
                            <div class="absolute top-0 end-0 rounded-bl-xl bg-emerald-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm">
                                ✓ {{ __('المؤسسة النشطة المعتمدة') }}
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md px-2 py-0.5 text-[11px] font-bold
                                        @if ($inst->stage === 'primary') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                                        @elseif ($inst->stage === 'middle') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                        @elseif ($inst->stage === 'secondary') bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300
                                        @endif
                                    ">
                                        @if ($inst->stage === 'primary') {{ __('ابتدائي') }}
                                        @elseif ($inst->stage === 'middle') {{ __('متوسط') }}
                                        @elseif ($inst->stage === 'secondary') {{ __('ثانوي') }}
                                        @else {{ __('مجمع') }}
                                        @endif
                                    </span>

                                    @if ($inst->code)
                                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $inst->code }}</span>
                                    @endif
                                </div>

                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    {{ $inst->name }}
                                </h3>

                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $inst->directorate_name }} · {{ $inst->wilaya }} ({{ $inst->municipality }})
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-100 pt-3 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            <div>
                                <span class="block text-[11px] text-gray-400">{{ __('المدير(ة):') }}</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $inst->principal_name ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="block text-[11px] text-gray-400">{{ __('طاقة الاستيعاب:') }}</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $inst->capacity ? number_format($inst->capacity).' مقعد' : '—' }}</span>
                            </div>
                            <div>
                                <span class="block text-[11px] text-gray-400">{{ __('نظام التمدرس:') }}</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">
                                    @if ($inst->regime === 'half_board') {{ __('نصف داخلي') }}
                                    @elseif ($inst->regime === 'internat') {{ __('داخلي') }}
                                    @else {{ __('خارجي') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                @if ($inst->status === 'active')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        {{ __('نشطة ومفعلة') }}
                                    </span>
                                @elseif ($inst->status === 'pending')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                        {{ __('قيد التجهيز') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 dark:text-red-400">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        {{ __('معلقة') }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if (! $inst->is_current)
                                    <button
                                        type="button"
                                        wire:click="makeInstitutionCurrent({{ $inst->id }})"
                                        class="inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300"
                                    >
                                        <x-filament::icon icon="heroicon-o-check-badge" class="h-3.5 w-3.5" />
                                        <span>{{ __('تعيين كنشطة للنظام') }}</span>
                                    </button>
                                @endif

                                <a
                                    href="{{ \App\Filament\Resources\EducationalInstitutionResource::getUrl('edit', ['record' => $inst->id]) }}"
                                    class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    <x-filament::icon icon="heroicon-o-pencil" class="h-3.5 w-3.5" />
                                    <span>{{ __('تعديل') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <x-filament::icon icon="heroicon-o-building-office-2" class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ __('لا توجد مؤسسات مطابقة لخيارات البحث') }}</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ __('يمكنك إضافة مؤسسة تربوية جديدة للشبكة أو إعادة تعيين الفلاتر.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- TAB 2: Wilayas & Geographic Distribution --}}
    @if ($activeTab === 'wilayas')
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($wilayasDistribution as $w)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('ولاية') }} {{ $w->wilaya ?? __('غير محددة') }}
                            </h3>
                            <span class="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ $w->count }} {{ __('مؤسسات') }}
                            </span>
                        </div>

                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                            <p>{{ __('إجمالي طاقة المقاعد:') }} <span class="font-bold text-gray-900 dark:text-white">{{ number_format($w->total_capacity) }}</span></p>
                        </div>

                        <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                            <span class="text-[11px] font-semibold text-gray-500">{{ __('المؤسسات التابعة:') }}</span>
                            <ul class="mt-1 space-y-1 text-xs text-gray-700 dark:text-gray-300">
                                @foreach ($institutions->where('wilaya', $w->wilaya) as $item)
                                    <li class="flex items-center justify-between">
                                        <span>{{ $item->name }}</span>
                                        @if ($item->is_current)
                                            <span class="text-[10px] font-bold text-emerald-600">({{ __('النشطة') }})</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- TAB 3: Operational School Preview --}}
    @if ($activeTab === 'active_school_preview')
        <div class="space-y-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            {{ __('معاينة لوحة القيادة التربوية للمؤسسة النشطة:') }} {{ $currentInstitution?->name }}
                        </h3>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                            {{ __('هذه هي لوحة التحكم المخصصة لمدير المؤسسة وهيئة التدريس لمتابعة الحصص، الحضور، والسجلات اليومية.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                @livewire('dashboard-stats')
            </div>
        </div>
    @endif

    {{-- TAB 4: Technical Operations & Quick Actions --}}
    @if ($activeTab === 'system_ops')
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            {{-- Quick Operations --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-indigo-600" />
                    <span>{{ __('إجراءات الصيانة السريعة للخادم') }}</span>
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 p-3 dark:border-gray-800">
                        <div>
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ __('تفريغ وتحديث كاش النظام') }}</p>
                            <p class="text-[11px] text-gray-500">{{ __('مسح كاش القوالب والإعدادات والمسارات.') }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="clearSystemCache"
                            class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 dark:bg-amber-950 dark:text-amber-300"
                        >
                            {{ __('مسح الكاش الآن') }}
                        </button>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-gray-100 p-3 dark:border-gray-800">
                        <div>
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ __('وضع الصيانة للمنصة') }}</p>
                            <p class="text-[11px] text-gray-500">
                                {{ $platformSetting->is_maintenance_mode ? __('المنصة حالياً في وضع الصيانة') : __('المنصة تعمل بشكل طبيعي') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleMaintenance"
                            class="rounded-lg {{ $platformSetting->is_maintenance_mode ? 'bg-emerald-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950 dark:text-red-300' }} px-3 py-1.5 text-xs font-bold"
                        >
                            {{ $platformSetting->is_maintenance_mode ? __('إلغاء الصيانة') : __('تفعيل الصيانة') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Navigation Shortcuts for Developer --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-squares-plus" class="h-5 w-5 text-indigo-600" />
                    <span>{{ __('روابط الإدارة المركزية والتحكم') }}</span>
                </h3>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a
                        href="{{ \App\Filament\Pages\DeveloperSettingsHub::getUrl() }}"
                        class="rounded-xl border border-gray-200 p-3 font-semibold text-gray-800 hover:border-indigo-400 hover:bg-indigo-50/40 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-indigo-950/30"
                    >
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4 text-indigo-500 mb-1" />
                        <span>{{ __('إعدادات المطور والهوية') }}</span>
                    </a>

                    <a
                        href="{{ $usersUrl }}"
                        class="rounded-xl border border-gray-200 p-3 font-semibold text-gray-800 hover:border-indigo-400 hover:bg-indigo-50/40 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-indigo-950/30"
                    >
                        <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4 text-indigo-500 mb-1" />
                        <span>{{ __('إدارة المستخدمين والأدوار') }}</span>
                    </a>

                    <a
                        href="{{ \App\Filament\Pages\RakmanaSyncHub::getUrl() }}"
                        class="rounded-xl border border-gray-200 p-3 font-semibold text-gray-800 hover:border-indigo-400 hover:bg-indigo-50/40 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-indigo-950/30"
                    >
                        <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-4 w-4 text-indigo-500 mb-1" />
                        <span>{{ __('الربط مع الرقمنة الوزارية') }}</span>
                    </a>

                    <a
                        href="{{ \App\Filament\Pages\AdministrativeArchiveHub::getUrl() }}"
                        class="rounded-xl border border-gray-200 p-3 font-semibold text-gray-800 hover:border-indigo-400 hover:bg-indigo-50/40 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-indigo-950/30"
                    >
                        <x-filament::icon icon="heroicon-o-archive-box" class="h-4 w-4 text-indigo-500 mb-1" />
                        <span>{{ __('الأرشيف والبريد الإداري') }}</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
