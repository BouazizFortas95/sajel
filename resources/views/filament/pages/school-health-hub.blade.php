<x-filament-panels::page>
    @php
        $service = app(\App\Services\SchoolHealthService::class);
        $overview = $service->getHealthOverview($this->academicYear);
        $levels = $this->levels;
        $bloodTypes = \App\Enums\BloodType::options();
        $chronicTypes = \App\Enums\ChronicConditionType::options();
        $epsStatuses = \App\Enums\EpsDispensationStatus::options();
        $hearingConditions = \App\Enums\HearingCondition::options();
        $dentalHygienes = \App\Enums\DentalHygieneStatus::options();
    @endphp

    <div class="space-y-6">
        {{-- Header Status & Controls Banner --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-heart class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ __('منظومة الصحة المدرسية ووحدة الكشف والمتابعة (UDS)') }}
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ __('الموسم') }}: {{ $this->academicYear }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('الفحوصات الطبية المنهجية، متابعة الأمراض المزمنة (PAI)، وإعفاءات التربية البدنية') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Print Preview Bilan --}}
                    <a
                        href="{{ route('print.school-health.annual-uds-bilan', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm"
                    >
                        <x-heroicon-o-printer class="w-4 h-4" />
                        <span>{{ __('معاينة حصيلة UDS') }}</span>
                    </a>

                    {{-- Download Bilan PDF --}}
                    <a
                        href="{{ route('print.school-health.annual-uds-bilan.pdf', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition shadow-sm"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ __('تحميل PDF رسمي') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Executive KPI Metrics Overview --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-emerald-200 dark:border-emerald-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ __('نسبة التغطية الطبية') }}</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $overview['coverage_rate'] }}%
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $overview['screened_count'] }} / {{ $overview['total_students'] }} {{ __('مفحوص') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-rose-200 dark:border-rose-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">{{ __('الأمراض المزمنة (PAI)') }}</span>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                    {{ $overview['chronic_count'] }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $overview['emergency_med_count'] }} {{ __('حالة بدواء بالمصحة') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">{{ __('إعفاءات التربية البدنية') }}</span>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                    {{ $overview['eps_exempt_count'] }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $overview['eps_exempt_rate'] }}% {{ __('من المتمدرسين') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-blue-200 dark:border-blue-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ __('نظارات طبية وأسنان') }}</span>
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">
                    {{ $overview['glasses_count'] }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $overview['caries_total'] }} {{ __('أسنان مسوسة مرصودة') }}</span>
            </div>
        </div>

        {{-- Interactive Tab Navigation --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3 overflow-x-auto">
            <button
                wire:click="$set('activeTab', 'screenings')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'screenings' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                <span>{{ __('سجل الفحوصات الطبية والبيومترية') }}</span>
            </button>

            <button
                wire:click="$set('activeTab', 'chronic')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'chronic' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-shield-exclamation class="w-4 h-4" />
                <span>{{ __('الأمراض المزمنة وبروتوكول الطوارئ (PAI)') }}</span>
                @if ($overview['chronic_count'] > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-500 text-white font-bold">{{ $overview['chronic_count'] }}</span>
                @endif
            </button>

            <button
                wire:click="$set('activeTab', 'eps')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'eps' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-no-symbol class="w-4 h-4" />
                <span>{{ __('إعفاءات التربية البدنية والرياضية (EPS)') }}</span>
                @if ($overview['eps_exempt_count'] > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500 text-white font-bold">{{ $overview['eps_exempt_count'] }}</span>
                @endif
            </button>

            <button
                wire:click="$set('activeTab', 'uds_bilan')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'uds_bilan' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-presentation-chart-line class="w-4 h-4" />
                <span>{{ __('الحصيلة السنوية لوحدة UDS') }}</span>
            </button>
        </div>

        {{-- TAB 1: SCREENINGS REGISTRY --}}
        @if ($this->activeTab === 'screenings')
            <div class="space-y-4">
                {{-- Filter bar --}}
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <input
                            wire:model.live.debounce.300ms="searchQuery"
                            type="text"
                            placeholder="{{ __('البحث باسم التلميذ أو رقم التسجيل...') }}"
                            class="px-3 py-1.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 w-64"
                        >

                        <select
                            wire:model.live="selectedLevelId"
                            class="px-3 py-1.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">{{ __('جميع المستويات التعليمية') }}</option>
                            @foreach ($levels as $lvl)
                                <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                            @endforeach
                        </select>

                        <select
                            wire:model.live="selectedFilterStatus"
                            class="px-3 py-1.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">{{ __('جميع الحالات') }}</option>
                            <option value="screened">{{ __('مفحوص طبياً') }}</option>
                            <option value="unscreened">{{ __('غير مفحوص بعد') }}</option>
                            <option value="chronic">{{ __('أمراض مزمنة') }}</option>
                            <option value="exempted">{{ __('معفى من الرياضة') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Students Table --}}
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 overflow-x-auto shadow-sm">
                    <table class="w-full text-xs text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2.5 px-3 text-right">#</th>
                                <th class="py-2.5 px-3 text-right">{{ __('التلميذ') }}</th>
                                <th class="py-2.5 px-3">{{ __('القسم / الفوج') }}</th>
                                <th class="py-2.5 px-3">{{ __('حالة الفحص') }}</th>
                                <th class="py-2.5 px-3">{{ __('القياسات (IMC)') }}</th>
                                <th class="py-2.5 px-3">{{ __('البصر / السمع') }}</th>
                                <th class="py-2.5 px-3">{{ __('الأسنان') }}</th>
                                <th class="py-2.5 px-3">{{ __('مرض مزمن') }}</th>
                                <th class="py-2.5 px-3">{{ __('التربية البدنية') }}</th>
                                <th class="py-2.5 px-3 text-center">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($this->students as $student)
                                @php
                                    $rec = $student->medicalRecords->first();
                                    $isScreened = $rec && $rec->hasScreening();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="py-2.5 px-3 text-right font-mono text-gray-400">{{ $student->id }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                        {{ $student->full_name }}
                                        <div class="text-[10px] text-gray-400 font-normal">
                                            {{ $student->birth_date?->format('d/m/Y') }} ({{ $student->isMale() ? __('ذكر') : __('أنثى') }})
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $student->group?->name ?? '—' }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($isScreened)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                                ✓ {{ $rec->screening_date?->format('d/m') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-500">
                                                {{ __('غير مفحوص') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        @if ($rec && $rec->bmi())
                                            <span class="font-bold">{{ $rec->bmi() }}</span>
                                            <div class="text-[9px] text-gray-400">{{ $rec->height_cm }}cm / {{ $rec->weight_kg }}kg</div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($rec && $isScreened)
                                            <span class="font-mono text-[10px] font-bold">{{ $rec->visual_acuity_right }}/{{ $rec->visual_acuity_left }}</span>
                                            @if ($rec->wears_glasses)
                                                <span class="text-[10px]" title="{{ __('يرتدي نظارات') }}">👓</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($rec && $isScreened)
                                            @if ($rec->dental_caries_count > 0)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300">
                                                    {{ $rec->dental_caries_count }} {{ __('تسوس') }}
                                                </span>
                                            @else
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ {{ __('سليمة') }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($rec && $rec->has_chronic_illness)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/50 text-rose-800 dark:text-rose-300">
                                                {{ $rec->chronic_condition_type?->icon() }} {{ $rec->chronic_condition_type?->label() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($rec && $rec->isExemptFromEps())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300">
                                                {{ $rec->eps_dispensation_status?->shortLabel() }}
                                            </span>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ __('لائق') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                wire:click="openScreeningModal({{ $student->id }})"
                                                type="button"
                                                class="px-2 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition"
                                                title="{{ __('فحص طبي / تحديث البيانات') }}"
                                            >
                                                🩺 {{ __('فحص') }}
                                            </button>

                                            <button
                                                wire:click="openDispensationModal({{ $student->id }})"
                                                type="button"
                                                class="px-2 py-1 text-[11px] font-bold rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition"
                                                title="{{ __('إعفاء تربية بدنية') }}"
                                            >
                                                🏃 {{ __('إعفاء') }}
                                            </button>

                                            <button
                                                wire:click="openChronicModal({{ $student->id }})"
                                                type="button"
                                                class="px-2 py-1 text-[11px] font-bold rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition"
                                                title="{{ __('مرض مزمن / بروتوكول طوارئ') }}"
                                            >
                                                ⚠️
                                            </button>

                                            @if ($rec)
                                                <a
                                                    href="{{ route('print.school-health.student-dossier', ['locale' => app()->getLocale(), 'record' => $rec->id]) }}"
                                                    target="_blank"
                                                    class="px-2 py-1 text-[11px] font-bold rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                    title="{{ __('طباعة البطاقة الصحية الفردية') }}"
                                                >
                                                    🖨️
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-gray-400">
                                        {{ __('لا توجد نتائج مطابقة لبحثك.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div>
                    {{ $this->students->links() }}
                </div>
            </div>
        @endif

        {{-- TAB 2: CHRONIC ILLNESSES & EMERGENCY PAI REGISTRY --}}
        @if ($this->activeTab === 'chronic')
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                        <x-heroicon-o-shield-exclamation class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                        <span>{{ __('سجل متابعة الأمراض المزمنة وبروتوكول الاستقبال الفردي للطوارئ (PAI)') }}</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        {{ __('الحالات الصحية الحرجة التي تتطلب يقظة خاصة، إيداع أدوية بمصحة المؤسسة، وبروتوكول إسعاف فوري.') }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->chronicStudents as $student)
                            @php $rec = $student->medicalRecords->first(); @endphp
                            <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">
                                            {{ $student->full_name }}
                                        </div>
                                        <div class="text-[10px] text-gray-500">
                                            {{ $student->group?->name }} | {{ $rec?->blood_type?->label() }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 flex items-center gap-1">
                                        {{ $rec?->chronic_condition_type?->icon() }}
                                        {{ $rec?->chronic_condition_type?->label() }}
                                    </span>
                                </div>

                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 p-2.5 rounded-lg border border-gray-200 dark:border-gray-800">
                                    <strong>{{ __('تفاصيل الحالة') }}:</strong> {{ $rec?->chronic_illness_details ?: __('لا توجد تفاصيل إضافية.') }}
                                </div>

                                <div class="text-xs text-rose-900 dark:text-rose-200 bg-rose-100/50 dark:bg-rose-900/30 p-2.5 rounded-lg border border-rose-200 dark:border-rose-800">
                                    <strong>{{ __('بروتوكول الطوارئ (PAI)') }}:</strong> {{ $rec?->emergency_protocol_notes ?: __('إشعار المصحة والولي فوراً.') }}
                                </div>

                                <div class="flex items-center justify-between text-xs pt-1">
                                    <span class="inline-flex items-center gap-1 {{ $rec?->emergency_medication_at_school ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-gray-400' }}">
                                        {{ $rec?->emergency_medication_at_school ? '💊 '.__('دواء مودع بالمصحة') : '⚪ '.__('لا يوجد دواء مودع') }}
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="openChronicModal({{ $student->id }})"
                                            type="button"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition"
                                        >
                                            {{ __('تعديل') }}
                                        </button>
                                        <a
                                            href="{{ route('print.school-health.student-dossier', ['locale' => app()->getLocale(), 'record' => $rec->id]) }}"
                                            target="_blank"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition"
                                        >
                                            🖨️ {{ __('البطاقة') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 py-8 text-center text-gray-400">
                                {{ __('لم يتم تسجيل أية حالات أمراض مزمنة في هذا الموسم الدراسي.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: EPS DISPENSATIONS REGISTRY --}}
        @if ($this->activeTab === 'eps')
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                        <x-heroicon-o-no-symbol class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        <span>{{ __('سجل الإعفاءات الطبية الرسمية من التربية البدنية والرياضية (EPS)') }}</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        {{ __('التلاميذ المعفون من ممارسة الرياضة بشهادات طبية معتمدة من وحدة UDS مع بيان فترة وسريان الإعفاء.') }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-center border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2.5 px-3 text-right">{{ __('التلميذ') }}</th>
                                    <th class="py-2.5 px-3">{{ __('القسم') }}</th>
                                    <th class="py-2.5 px-3">{{ __('نوع الإعفاء') }}</th>
                                    <th class="py-2.5 px-3">{{ __('فترة السريان') }}</th>
                                    <th class="py-2.5 px-3">{{ __('رقم الشهادة') }}</th>
                                    <th class="py-2.5 px-3 text-right">{{ __('الداعي الطبي') }}</th>
                                    <th class="py-2.5 px-3 text-center">{{ __('الإجراءات الرسمية') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($this->epsExemptStudents as $student)
                                    @php $rec = $student->medicalRecords->first(); @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="py-3 px-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                            {{ $student->full_name }}
                                        </td>
                                        <td class="py-3 px-3 font-semibold">{{ $student->group?->name }}</td>
                                        <td class="py-3 px-3">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                                {{ $rec?->eps_dispensation_status?->label() }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 font-mono">
                                            {{ $rec?->eps_dispensation_start_date?->format('d/m/Y') }} ➔
                                            {{ $rec?->eps_dispensation_end_date ? $rec->eps_dispensation_end_date->format('d/m/Y') : __('نهاية الموسم') }}
                                        </td>
                                        <td class="py-3 px-3 font-mono font-bold text-gray-600 dark:text-gray-300">
                                            {{ $rec?->eps_medical_certificate_ref ?? 'CERT-EPS-'.$rec->id }}
                                        </td>
                                        <td class="py-3 px-3 text-right text-gray-600 dark:text-gray-400">
                                            {{ $rec?->eps_dispensation_reason ?: __('إعفاء معلل طبياً') }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    wire:click="openDispensationModal({{ $student->id }})"
                                                    type="button"
                                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition"
                                                >
                                                    {{ __('تعديل') }}
                                                </button>
                                                <a
                                                    href="{{ route('print.school-health.eps-dispensation', ['locale' => app()->getLocale(), 'record' => $rec->id]) }}"
                                                    target="_blank"
                                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition"
                                                >
                                                    🖨️ {{ __('شهادة الإعفاء') }}
                                                </a>
                                                <a
                                                    href="{{ route('print.school-health.eps-dispensation.pdf', ['locale' => app()->getLocale(), 'record' => $rec->id]) }}"
                                                    target="_blank"
                                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 text-rose-600 transition"
                                                    title="{{ __('تحميل PDF') }}"
                                                >
                                                    📥
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-gray-400">
                                            {{ __('لا توجد إعفاءات مسجلة من التربية البدنية. جميع التلاميذ لائقون بدنياً.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 4: UDS ANNUAL STATISTICAL BILAN --}}
        @if ($this->activeTab === 'uds_bilan')
            <div class="space-y-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <x-heroicon-o-presentation-chart-line class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ __('الحصيلة السنوية الرسمية لنشاط وحدة الكشف والمتابعة الصحية المدرسية') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('التقرير السنوي الشامل الموجه لمصلحة الصحة المدرسية بمديرية الصحة ومديرية التربية لولاية الوادي.') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('print.school-health.annual-uds-bilan', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                                target="_blank"
                                class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm"
                            >
                                🖨️ {{ __('معاينة وطباعة الورقة الرسمية (A4)') }}
                            </a>
                            <a
                                href="{{ route('print.school-health.annual-uds-bilan.pdf', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                                target="_blank"
                                class="px-3.5 py-2 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition shadow-sm"
                            >
                                📥 {{ __('تحميل ملف PDF') }}
                            </a>
                        </div>
                    </div>

                    {{-- Level Coverage Matrix --}}
                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-xl">
                        <table class="w-full text-xs text-center border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-800 dark:text-gray-200">
                                    <th class="py-2.5 px-3 text-right">{{ __('المستوى التعليمي') }}</th>
                                    <th class="py-2.5 px-3">{{ __('التعداد الإجمالي') }}</th>
                                    <th class="py-2.5 px-3 text-emerald-700 dark:text-emerald-400">{{ __('المفحوصون سريرياً') }}</th>
                                    <th class="py-2.5 px-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300">{{ __('نسبة التغطية') }}</th>
                                    <th class="py-2.5 px-3 text-rose-700 dark:text-rose-400">{{ __('حالات الأمراض المزمنة') }}</th>
                                    <th class="py-2.5 px-3 text-amber-700 dark:text-amber-400">{{ __('معفون من الرياضة') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($overview['level_breakdown'] as $code => $lvl)
                                    <tr>
                                        <td class="py-2.5 px-3 text-right font-bold text-gray-900 dark:text-gray-100">{{ $lvl['name'] }}</td>
                                        <td class="py-2.5 px-3 font-semibold">{{ $lvl['total'] }}</td>
                                        <td class="py-2.5 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ $lvl['screened'] }}</td>
                                        <td class="py-2.5 px-3 font-black bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300">{{ $lvl['coverage_rate'] }}%</td>
                                        <td class="py-2.5 px-3 font-bold text-rose-600 dark:text-rose-400">{{ $lvl['chronic'] }}</td>
                                        <td class="py-2.5 px-3 font-bold text-amber-600 dark:text-amber-400">{{ $lvl['eps_exempt'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 1: SCREENING VISIT --}}
        @if ($this->isScreeningModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-xl border border-gray-200 dark:border-gray-800 my-8">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm flex items-center gap-2">
                            <span>🩺</span>
                            <span>{{ __('تسجيل الفحص الطبي المنهجي والمؤشرات البيومترية') }}</span>
                        </h3>
                        <button wire:click="$set('isScreeningModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm">✕</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div class="col-span-2">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ المعني بالفحص') }}</label>
                            <select wire:model="screeningStudentId" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                <option value="">{{ __('اختر التلميذ...') }}</option>
                                @foreach (\App\Models\Student::with('group')->orderBy('first_name')->get() as $st)
                                    <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الفحص') }}</label>
                            <input wire:model="screeningDate" type="date" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الزمرة الدموية (Groupe sanguin)') }}</label>
                            <select wire:model="bloodType" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                @foreach ($bloodTypes as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الطول (cm)') }}</label>
                            <input wire:model="heightCm" type="number" step="0.5" placeholder="155.0" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الوزن (kg)') }}</label>
                            <input wire:model="weightKg" type="number" step="0.5" placeholder="48.0" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('حدة البصر - العين اليمنى') }}</label>
                            <input wire:model="visualAcuityRight" type="text" placeholder="10/10" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('حدة البصر - العين اليسرى') }}</label>
                            <input wire:model="visualAcuityLeft" type="text" placeholder="10/10" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('حاسة السمع') }}</label>
                            <select wire:model="hearingCondition" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                @foreach ($hearingConditions as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('صحة الفم والأسنان') }}</label>
                            <select wire:model="dentalHygiene" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                @foreach ($dentalHygienes as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد الأسنان المنخورة (تسوس)') }}</label>
                            <input wire:model="dentalCariesCount" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التوجيه نحو طبيب مختص (اختياري)') }}</label>
                            <input wire:model="specialistReferral" type="text" placeholder="{{ __('طب العيون، جراحة الأسنان...') }}" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>

                        <div class="col-span-2">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات الطبيب الفاحص') }}</label>
                            <textarea wire:model="generalClinicalNotes" rows="2" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                        </div>

                        <div class="col-span-2 flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="wearsGlasses" type="checkbox" class="rounded text-emerald-600">
                                <span>{{ __('يرتدي نظارات طبية') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="followUpRequired" type="checkbox" class="rounded text-amber-600">
                                <span>{{ __('حالة تتطلب متابعة دورية بالمصحة') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-800 pt-3">
                        <button wire:click="$set('isScreeningModalOpen', false)" type="button" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                            {{ __('إلغاء') }}
                        </button>
                        <button wire:click="saveScreening" type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                            {{ __('حفظ الفحص الطبي') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 2: EPS DISPENSATION --}}
        @if ($this->isDispensationModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm flex items-center gap-2">
                            <span>🏃</span>
                            <span>{{ __('توثيق إعفاء طبي من التربية البدنية والرياضية (EPS)') }}</span>
                        </h3>
                        <button wire:click="$set('isDispensationModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm">✕</button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ المعني') }}</label>
                            <select wire:model="dispensationStudentId" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                <option value="">{{ __('اختر التلميذ...') }}</option>
                                @foreach (\App\Models\Student::with('group')->orderBy('first_name')->get() as $st)
                                    <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('طبيعة الإعفاء') }}</label>
                            <select wire:model="epsStatus" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                @foreach ($epsStatuses as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ بدء الإعفاء') }}</label>
                                <input wire:model="epsStartDate" type="date" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الانتهاء (اختياري)') }}</label>
                                <input wire:model="epsEndDate" type="date" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الشهادة الطبية الرسمية') }}</label>
                            <input wire:model="epsCertificateRef" type="text" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-mono">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التعليل الطبي للشهادة') }}</label>
                            <textarea wire:model="epsReason" rows="2" placeholder="{{ __('حالة صحية مانعة من المجهود البدني...') }}" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-800 pt-3">
                        <button wire:click="$set('isDispensationModalOpen', false)" type="button" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                            {{ __('إلغاء') }}
                        </button>
                        <button wire:click="saveDispensation" type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-sm">
                            {{ __('توثيق الإعفاء الطبي') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 3: CHRONIC CONDITION & EMERGENCY PAI PROTOCOL --}}
        @if ($this->isChronicModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{{ __('ملف الحالة الصحية الخاصة وبروتوكول الطوارئ (PAI)') }}</span>
                        </h3>
                        <button wire:click="$set('isChronicModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm">✕</button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ') }}</label>
                            <select wire:model="chronicStudentId" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                <option value="">{{ __('اختر التلميذ...') }}</option>
                                @foreach (\App\Models\Student::with('group')->orderBy('first_name')->get() as $st)
                                    <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تصنيف المرض المزمن') }}</label>
                            <select wire:model="chronicConditionType" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                @foreach ($chronicTypes as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تفاصيل التشخيص الطبي والعلاجي') }}</label>
                            <textarea wire:model="chronicIllnessDetails" rows="2" placeholder="{{ __('اسم الدواء المعتاد، الجرعات، موانع الاستعمال...') }}" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('بروتوكول الإسعاف والتدخل المستعجل (PAI)') }}</label>
                            <textarea wire:model="emergencyProtocolNotes" rows="2" placeholder="{{ __('الإجراءات الواجب اتخاذها فوراً عند حدوث نوبة أو عارض...') }}" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                        </div>

                        <div class="flex items-center gap-4 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-rose-700 dark:text-rose-400">
                                <input wire:model="hasChronicIllness" type="checkbox" class="rounded text-rose-600">
                                <span>{{ __('تأكيد تسجيل الحالة كمرض مزمن') }}</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer font-bold text-emerald-700 dark:text-emerald-400">
                                <input wire:model="emergencyMedicationAtSchool" type="checkbox" class="rounded text-emerald-600">
                                <span>{{ __('دواء استعجالي مودع بمصحة المؤسسة') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-800 pt-3">
                        <button wire:click="$set('isChronicModalOpen', false)" type="button" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                            {{ __('إلغاء') }}
                        </button>
                        <button wire:click="saveChronicCondition" type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-sm">
                            {{ __('حفظ بروتوكول الطوارئ') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
