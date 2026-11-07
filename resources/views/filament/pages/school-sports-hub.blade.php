<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 rounded-xl">
                    <x-heroicon-o-trophy class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ __('الجمعية الثقافية والرياضية المدرسية (ASCS) والمنافسات') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('إجازات وبطاقات التلاميذ الرياضية، الفحص الطبي UDS، البطولات المدرسية (LWSS)، وسجل مالية الجمعية والعتاد الرياضي') }}
                    </p>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl">
                <button
                    wire:click="switchTab('licenses')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'licenses' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-identification class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('إجازات وبطاقات التلاميذ') }}
                </button>
                <button
                    wire:click="switchTab('disciplines')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'disciplines' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-squares-2x2 class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('الاختصاصات والنوادي') }}
                </button>
                <button
                    wire:click="switchTab('competitions')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'competitions' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-flag class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('البطولات والمنافسات') }}
                </button>
                <button
                    wire:click="switchTab('finances_equipment')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'finances_equipment' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-banknotes class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('مالية الجمعية والعتاد') }}
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 1: LICENSES & MEMBERS (ASCS)                          -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'licenses')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي التلاميذ المنخرطين') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['total_licenses'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('سنة') }} {{ $academicYear }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الذكور المنخرطون') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->telemetry['boys_licensed'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('رياضات جماعية وفردية') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الإناث المنخرطات') }}</div>
                    <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $this->telemetry['girls_licensed'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('مشاركة وتأطير نسوي') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجازات تنافسية للبطولات') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $this->telemetry['competitive_licenses'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('مؤهلة لبطولة الرابطة LWSS') }}</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="openLicenseModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('إصدار إجازة رياضية جديدة (Licence ASCS)') }}
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="{{ __('بحث برقم الإجازة أو اسم التلميذ...') }}"
                        class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white w-64"
                    />
                    <select
                        wire:model.live="disciplineFilter"
                        class="text-xs px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع الاختصاصات') }}</option>
                        @foreach($this->disciplines as $disc)
                            <option value="{{ $disc->id }}">{{ $disc->name_ar }}</option>
                        @endforeach
                    </select>
                    <select
                        wire:model.live="licenseTypeFilter"
                        class="text-xs px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع أنواع الإجازات') }}</option>
                        @foreach(\App\Enums\SportsLicenseType::cases() as $slt)
                            <option value="{{ $slt->value }}">{{ $slt->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Licenses Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رقم الإجازة') }}</th>
                                <th class="py-3 px-4">{{ __('التلميذ(ة)') }}</th>
                                <th class="py-3 px-4">{{ __('القسم / الفوج') }}</th>
                                <th class="py-3 px-4">{{ __('النشاط / الاختصاص') }}</th>
                                <th class="py-3 px-4">{{ __('نوع الإجازة') }}</th>
                                <th class="py-3 px-4">{{ __('الفحص الطبي UDS') }}</th>
                                <th class="py-3 px-4">{{ __('الترخيص الأبوي') }}</th>
                                <th class="py-3 px-4">{{ __('الحالة') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('المطبوعات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->licenses as $lic)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-700 dark:text-blue-400">
                                        {{ $lic->license_number }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $lic->student?->full_name }}
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $lic->student?->registration_number }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $lic->student?->group?->classroom?->name }} ({{ $lic->student?->group?->name }})
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ $lic->discipline?->name_ar }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ $lic->license_type?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($lic->medical_aptitude_verdict === \App\Enums\SportsMedicalAptitude::FitApt) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 @endif">
                                            {{ $lic->medical_aptitude_verdict?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 text-[11px] {{ $lic->parental_consent_signed ? 'text-emerald-600' : 'text-rose-600' }}">
                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" />
                                            {{ $lic->parental_consent_signed ? __('موافق وموقع') : __('غير موقع') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">
                                            {{ $lic->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('print.sports.student-license', ['locale' => app()->getLocale(), 'license' => $lic->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition"
                                                title="{{ __('طباعة بطاقة الإجازة') }}">
                                                <x-heroicon-m-identification class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.sports.medical-fiche', ['locale' => app()->getLocale(), 'license' => $lic->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition"
                                                title="{{ __('استمارة الفحص الطبي والترخيص الأبوي') }}">
                                                <x-heroicon-m-document-text class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.sports.student-license.pdf', ['locale' => app()->getLocale(), 'license' => $lic->id]) }}"
                                                class="px-2 py-1 text-[11px] bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-400 rounded transition font-bold"
                                                title="{{ __('تحميل PDF') }}">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد إجازات رياضية مسجلة وفق معايير البحث المحددة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 2: DISCIPLINES & CLUBS                                -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'disciplines')
            <!-- Action Bar -->
            <div class="flex items-center justify-between bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('النشاطات والاختصاصات والنوادي الرياضية والثقافية المعتمدة') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('الرياضات الفردية والجماعية، الفئات العمرية، الأساتذة المؤطرون، وجدول الحصص التدريبية.') }}
                    </p>
                </div>
                <button
                    wire:click="openDisciplineModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <x-heroicon-m-plus class="w-4 h-4" />
                    {{ __('اعتماد اختصاص / نشاط جديد') }}
                </button>
            </div>

            <!-- Disciplines Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($this->disciplines as $disc)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-xs text-blue-600 dark:text-blue-400 font-bold">{{ $disc->discipline_code }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $disc->category?->label() }}
                                </span>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $disc->name_ar }}</h4>
                            @if($disc->name_fr)
                                <div class="text-xs text-gray-400 font-mono">{{ $disc->name_fr }}</div>
                            @endif

                            <div class="mt-3 space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <div>الأستاذ المؤطر: <strong class="text-gray-800 dark:text-gray-200">{{ $disc->supervising_teacher_name }}</strong></div>
                                <div>أوقات التمارين: <strong class="text-gray-800 dark:text-gray-200">{{ $disc->training_schedule }}</strong></div>
                                <div>الفئة والجنس: <strong class="text-gray-800 dark:text-gray-200">{{ $disc->age_category?->label() }} — {{ $disc->target_genders?->label() }}</strong></div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                            <span class="text-gray-500">المنخرطون:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">{{ $disc->licenses_count }} / {{ $disc->max_participants }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 py-8 text-center text-gray-500">
                        {{ __('لا توجد اختصاصات رياضية معتمدة.') }}
                    </div>
                @endforelse
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 3: COMPETITIONS & TOURNAMENTS (LWSS)                  -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'competitions')
            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('سجل البطولات والمنافسات المدرسية (Calendrier des Compétitions LWSS)') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('المباريات المدرسية بين الأفواج، تصفيات الدائرة، البطولة الولائية، وتسجيل النتائج والروح الرياضية.') }}
                    </p>
                </div>
                <button
                    wire:click="openEventModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <x-heroicon-m-plus class="w-4 h-4" />
                    {{ __('تسجيل مقابلة / مشاركة في بطولة') }}
                </button>
            </div>

            <!-- Competitions Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رمز التظاهرة') }}</th>
                                <th class="py-3 px-4">{{ __('المنافسة / المقابلة') }}</th>
                                <th class="py-3 px-4">{{ __('الاختصاص') }}</th>
                                <th class="py-3 px-4">{{ __('المستوى التنافسي') }}</th>
                                <th class="py-3 px-4">{{ __('التاريخ والمكان') }}</th>
                                <th class="py-3 px-4">{{ __('المنافس') }}</th>
                                <th class="py-3 px-4">{{ __('النتيجة') }}</th>
                                <th class="py-3 px-4">{{ __('الترتيب والروح الرياضية') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('ورقة المقابلة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->events as $evt)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-700 dark:text-blue-400">
                                        {{ $evt->event_code }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                                        {{ $evt->event_name }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ $evt->discipline?->name_ar }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ $evt->competition_level?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        <div>{{ $evt->event_date?->format('Y-m-d') }} ({{ $evt->event_time }})</div>
                                        <div class="text-[10px] text-gray-400">{{ $evt->location_venue }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $evt->opponent_team_name ?: '—' }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold">
                                        @if($evt->our_score !== null && $evt->opponent_score !== null)
                                            <span class="text-base text-blue-700 dark:text-blue-400">{{ $evt->our_score }} - {{ $evt->opponent_score }}</span>
                                            <div class="text-[10px]">
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded font-medium
                                                    @if($evt->match_result === \App\Enums\SportsMatchResult::Win) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                                    @elseif($evt->match_result === \App\Enums\SportsMatchResult::Loss) bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400
                                                    @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 @endif">
                                                    {{ $evt->match_result?->label() }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">{{ __('مبرمجة') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>{{ $evt->ranking_achieved ?: 'مشاركة منتظمة' }}</div>
                                        <div class="text-[10px] text-gray-400">الروح الرياضية: <strong>{{ $evt->fair_play_score }}/10</strong></div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('print.sports.competition-pv', ['locale' => app()->getLocale(), 'event' => $evt->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition">
                                                <x-heroicon-m-printer class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.sports.competition-pv.pdf', ['locale' => app()->getLocale(), 'event' => $evt->id]) }}"
                                                class="px-2 py-1 text-[11px] bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-400 rounded transition font-bold">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد منافسات مسجلة لهذه السنة الدراسية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 4: FINANCES & SPORTS EQUIPMENT                        -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'finances_equipment')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('مداخيل واشتراكات ASCS') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        {{ number_format($this->telemetry['treasury_income_dzd'], 2) }} دج
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('اشتراكات التلاميذ وإعانات') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('مصاريف الأنشطة والعتاد') }}</div>
                    <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1 font-mono">
                        {{ number_format($this->telemetry['treasury_expense_dzd'], 2) }} دج
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('نقل، عتاد، إطعام، جوائز') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الرصيد الصافي المتاح بالخزينة') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1 font-mono">
                        {{ number_format($this->telemetry['treasury_balance_dzd'], 2) }} دج
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('حساب الخزينة البريدية للجمعية') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الحصيلة الأدبية والمالية') }}</div>
                    <div class="flex items-center gap-2 mt-2">
                        <a
                            href="{{ route('print.sports.ascs-bilan', ['locale' => app()->getLocale(), 'academicYear' => str_replace('/', '-', $academicYear)]) }}"
                            target="_blank"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1">
                            <x-heroicon-m-printer class="w-3.5 h-3.5" />
                            {{ __('معاينة') }}
                        </a>
                        <a
                            href="{{ route('print.sports.ascs-bilan.pdf', ['locale' => app()->getLocale(), 'academicYear' => str_replace('/', '-', $academicYear)]) }}"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition font-mono">
                            PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="openTransactionModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('قيد عملية مالية بالخزينة (مداخيل / مصاريف)') }}
                    </button>
                    <button
                        wire:click="openEquipmentModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                        <x-heroicon-m-archive-box class="w-4 h-4" />
                        {{ __('إضافة عتاد رياضي للمخزن') }}
                    </button>
                </div>
            </div>

            <!-- Financial Transactions Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 font-bold text-sm text-gray-900 dark:text-white">
                    {{ __('دفتر حركة الخزينة المالية للجمعية (Livre de Caisse ASCS)') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('المرجع') }}</th>
                                <th class="py-3 px-4">{{ __('نوع العملية') }}</th>
                                <th class="py-3 px-4">{{ __('البيان والشرح') }}</th>
                                <th class="py-3 px-4">{{ __('المبلغ (دج)') }}</th>
                                <th class="py-3 px-4">{{ __('تاريخ العملية') }}</th>
                                <th class="py-3 px-4">{{ __('طريقة الدفع') }}</th>
                                <th class="py-3 px-4">{{ __('المسؤول / أمين المال') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->transactions as $tx)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold">{{ $tx->transaction_reference }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($tx->transaction_type->isIncome()) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @else bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400 @endif">
                                            {{ $tx->transaction_type?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">{{ $tx->description }}</td>
                                    <td class="py-3 px-4 font-mono font-bold {{ $tx->transaction_type->isIncome() ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $tx->transaction_type->isIncome() ? '+' : '-' }} {{ number_format((float) $tx->amount_dzd, 2) }} دج
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                                    <td class="py-3 px-4">{{ $tx->payment_method }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $tx->recorded_by_name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-gray-500">
                                        {{ __('لا توجد حركات مالية مسجلة لهذه السنة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sports Equipment Inventory Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 font-bold text-sm text-gray-900 dark:text-white">
                    {{ __('جرد عتاد ولوازم التربية البدنية والرياضية (Matériel EPS)') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رمز العتاد') }}</th>
                                <th class="py-3 px-4">{{ __('اسم العتاد / التجهيز') }}</th>
                                <th class="py-3 px-4">{{ __('الاختصاص التابع له') }}</th>
                                <th class="py-3 px-4">{{ __('العدد الإجمالي') }}</th>
                                <th class="py-3 px-4">{{ __('الصالح للاستعمال') }}</th>
                                <th class="py-3 px-4">{{ __('التالف / الخردة') }}</th>
                                <th class="py-3 px-4">{{ __('الحالة العامة') }}</th>
                                <th class="py-3 px-4">{{ __('مكان التخزين') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->equipment as $eq)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold">{{ $eq->equipment_code }}</td>
                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">{{ $eq->item_name }}</td>
                                    <td class="py-3 px-4">{{ $eq->discipline?->name_ar ?? __('مشترك عام') }}</td>
                                    <td class="py-3 px-4 font-mono font-bold">{{ $eq->quantity_total }}</td>
                                    <td class="py-3 px-4 font-mono text-emerald-600 font-bold">{{ $eq->quantity_operational }}</td>
                                    <td class="py-3 px-4 font-mono text-rose-600 font-bold">{{ $eq->quantity_damaged }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($eq->condition_rating === \App\Enums\SportsEquipmentCondition::New) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @elseif($eq->condition_rating === \App\Enums\SportsEquipmentCondition::Good) bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400
                                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 @endif">
                                            {{ $eq->condition_rating?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $eq->storage_location }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-gray-500">
                                        {{ __('لا يوجد عتاد رياضي مقيد في الجرد.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: ISSUE LICENSE                                      -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isLicenseModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('إصدار إجازة رياضية للتلميذ (Licence ASCS)') }}
                    </h3>
                    <button wire:click="closeLicenseModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('اختر التلميذ(ة)') }} *</label>
                            <select wire:model="selectedStudentId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="">{{ __('اختر التلميذ من القائمة...') }}</option>
                                @foreach($this->students as $std)
                                    <option value="{{ $std->id }}">{{ $std->full_name }} ({{ $std->group?->classroom?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('النشاط / الاختصاص الرياضي') }} *</label>
                            <select wire:model="selectedDisciplineId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach($this->disciplines as $disc)
                                    <option value="{{ $disc->id }}">{{ $disc->name_ar }} ({{ $disc->discipline_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الإجازة') }}</label>
                            <select wire:model="licenseType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsLicenseType::cases() as $slt)
                                    <option value="{{ $slt->value }}">{{ $slt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الفحص الطبي للقدرة البدنية') }}</label>
                            <select wire:model="medicalAptitude" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsMedicalAptitude::cases() as $sma)
                                    <option value="{{ $sma->value }}">{{ $sma->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2 border border-gray-200 dark:border-gray-800 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                        <div class="font-bold text-gray-800 dark:text-gray-200 mb-1">{{ __('التأكيد والاشتراطات الإدارية:') }}</div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="parentalConsent" class="rounded border-gray-300 text-blue-600" />
                                <span>{{ __('موافقة وترخيص الولي') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="insuranceCovered" class="rounded border-gray-300 text-blue-600" />
                                <span>{{ __('التأمين الرياضي') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="feePaid" class="rounded border-gray-300 text-blue-600" />
                                <span>{{ __('تسديد الاشتراك') }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات إضافية') }}</label>
                        <textarea wire:model="licenseNotes" rows="2" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeLicenseModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveLicense" class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                        {{ __('إصدار الإجازة') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: ADD DISCIPLINE                                     -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isDisciplineModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('اعتماد اختصاص / نشاط رياضي أو ثقافي جديد') }}
                    </h3>
                    <button wire:click="closeDisciplineModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم النشاط بالعربية') }} *</label>
                            <input type="text" wire:model="disciplineNameAr" placeholder="مثال: كرة السلة إناث" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الاسم بالفرنسية (اختياري)') }}</label>
                            <input type="text" wire:model="disciplineNameFr" placeholder="Basketball Filles" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تصنيف النشاط') }}</label>
                            <select wire:model="disciplineCategory" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\AscsDisciplineCategory::cases() as $adc)
                                    <option value="{{ $adc->value }}">{{ $adc->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الأستاذ المؤطر') }} *</label>
                            <input type="text" wire:model="supervisingTeacher" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الفئة المستهدفة') }}</label>
                            <select wire:model="targetGenders" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsGenderTarget::cases() as $sgt)
                                    <option value="{{ $sgt->value }}">{{ $sgt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الفئة العمرية') }}</label>
                            <select wire:model="ageCategory" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\StudentAgeCategory::cases() as $sac)
                                    <option value="{{ $sac->value }}">{{ $sac->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('طاقة الاستيعاب القصوى') }}</label>
                            <input type="number" wire:model="maxParticipants" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('جدول وأوقات الحصص التدريبية') }}</label>
                        <input type="text" wire:model="trainingSchedule" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeDisciplineModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveDiscipline" class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                        {{ __('حفظ الاختصاص') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: RECORD COMPETITION / MATCH EVENT                   -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isEventModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل مقابلة أو مشاركة في بطولة مدرسية') }}
                    </h3>
                    <button wire:click="closeEventModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('عنوان المقابلة / التظاهرة') }} *</label>
                            <input type="text" wire:model="eventName" placeholder="مثال: تصفيات نصف نهائي بطولة الدائرة" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الاختصاص الرياضي') }} *</label>
                            <select wire:model="eventDisciplineId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach($this->disciplines as $disc)
                                    <option value="{{ $disc->id }}">{{ $disc->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('المستوى التنافسي') }}</label>
                            <select wire:model="competitionLevel" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsCompetitionLevel::cases() as $scl)
                                    <option value="{{ $scl->value }}">{{ $scl->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ المقابلة') }} *</label>
                            <input type="date" wire:model="eventDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('التوقيت') }}</label>
                            <input type="text" wire:model="eventTime" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الميدان / القاعة المستضيفة') }} *</label>
                            <input type="text" wire:model="locationVenue" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الفريق المنافس') }}</label>
                            <input type="text" wire:model="opponentTeam" placeholder="مثال: متوسطة الشهداء" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 border border-gray-200 dark:border-gray-800 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('أهدافنا / نتيجتنا') }}</label>
                            <input type="number" wire:model="ourScore" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('أهداف المنافس') }}</label>
                            <input type="number" wire:model="opponentScore" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نتيجة المقابلة') }}</label>
                            <select wire:model="matchResult" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsMatchResult::cases() as $smr)
                                    <option value="{{ $smr->value }}">{{ $smr->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الترتيب / الميدالية') }}</label>
                            <input type="text" wire:model="rankingAchieved" placeholder="المرتبة الأولى / تأهل للدور القادم" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الأساتذة والمؤطرون المرافقون') }}</label>
                            <input type="text" wire:model="chaperonesList" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeEventModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveEvent" class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                        {{ __('حفظ المقابلة') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: RECORD ASCS TRANSACTION                            -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isTransactionModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('قيد حركة مالية بحساب خزينة الجمعية ASCS') }}
                    </h3>
                    <button wire:click="closeTransactionModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع العملية المالية') }}</label>
                        <select wire:model="transactionType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                            @foreach(\App\Enums\AscsTransactionType::cases() as $att)
                                <option value="{{ $att->value }}">{{ $att->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('المبلغ (دج)') }} *</label>
                            <input type="number" step="0.01" wire:model="transactionAmount" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ العملية') }} *</label>
                            <input type="date" wire:model="transactionDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('البيان والشرح') }} *</label>
                        <input type="text" wire:model="transactionDescription" placeholder="مثال: اشتراكات الفوج الأول / اقتناء كرات يد" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('طريقة الدفع') }}</label>
                            <select wire:model="paymentMethod" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="Cash">{{ __('نقداً (Espèces)') }}</option>
                                <option value="PostalCheck">{{ __('صك بريدي / تحويل CCP') }}</option>
                                <option value="BankTransfer">{{ __('تحويل بنكي') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الوصل أو الفاتورة') }}</label>
                            <input type="text" wire:model="receiptRef" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeTransactionModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveTransaction" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                        {{ __('حفظ وقيد العملية') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: ADD SPORTS EQUIPMENT                               -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isEquipmentModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('إضافة عتاد وتجهيزات رياضية للمخزن') }}
                    </h3>
                    <button wire:click="closeEquipmentModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم العتاد / التجهيز') }} *</label>
                        <input type="text" wire:model="equipmentName" placeholder="مثال: كرات يد حجم 02، بساط جمباز..." class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الاختصاص التابع له') }}</label>
                            <select wire:model="equipmentDisciplineId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="">{{ __('مشترك عام (جميع الأنشطة)') }}</option>
                                @foreach($this->disciplines as $disc)
                                    <option value="{{ $disc->id }}">{{ $disc->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('العدد الإجمالي') }} *</label>
                            <input type="number" wire:model="quantityTotal" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الحالة العامة للعتاد') }}</label>
                            <select wire:model="equipmentCondition" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\SportsEquipmentCondition::cases() as $sec)
                                    <option value="{{ $sec->value }}">{{ $sec->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('مكان التخزين') }}</label>
                            <input type="text" wire:model="storageLocation" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeEquipmentModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveEquipment" class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                        {{ __('حفظ العتاد') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>

