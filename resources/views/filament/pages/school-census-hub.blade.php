<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $record = $data['record'];
        $demographics = $data['demographics'];
        $agePyramid = $data['age_pyramid'];
        $staffing = $data['staffing'];
        $school = $data['school'];
        $isCertified = $data['is_certified'];
    @endphp

    <div class="space-y-6">

        {{-- Official Survey Header & Status Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 rounded-xl text-xl">
                            🏛️
                        </span>
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ __('الكشف الإحصائي الشامل للدخول المدرسي إلى غاية 15 أكتوبر') }}
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('الجمهورية الجزائرية الديمقراطية الشعبية - وزارة التربية الوطنية | استمارة مديرية الدراسات والإعلام الآلي ونظم المعلومات') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                        <span class="px-3 py-1 rounded-full font-bold {{ $isCertified ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700' }}">
                            {{ $isCertified ? '✓ ' . __('مصادق عليه ومجمد رسمياً') : '⏳ ' . __('في طور الإعداد (Draft - بيانات حية)') }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold">
                            {{ __('السنة الدراسية:') }} {{ $this->academicYear }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold">
                            {{ __('تاريخ الإحصاء المرجعي:') }} {{ $record->census_date ? $record->census_date->format('Y/m/d') : '2026/10/15' }}
                        </span>

                        @if($isCertified && $this->certifiedByName)
                            <span class="px-3 py-1 rounded-full bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 font-semibold border border-primary-200 dark:border-primary-800">
                                {{ __('صودق بواسطة:') }} {{ $this->certifiedByName }} ({{ $this->certifiedAt }})
                            </span>
                        @endif

                        @if($this->referenceNumber)
                            <span class="px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800 font-mono text-[11px]">
                                {{ __('رقم التسجيل:') }} {{ $this->referenceNumber }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions Group --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <a
                        href="{{ route('print.school-census', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>{{ __('معاينة وطباعة الكشف (A4)') }}</span>
                    </a>

                    <a
                        href="{{ route('print.school-census.pdf', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm"
                    >
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>{{ __('تحميل PDF') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3 overflow-x-auto">
            <button
                wire:click="$set('activeTab', 'demographics')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'demographics' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>👥</span>
                {{ __('الكشف الإحصائي للتلاميذ والأفواج التربوية') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'demographics' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $demographics['total_students'] }}
                </span>
            </button>

            <button
                wire:click="$set('activeTab', 'pyramid_staffing')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'pyramid_staffing' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>📊</span>
                {{ __('الهرم العمري والتأطير البيداغوجي') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'pyramid_staffing' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $staffing['total_teachers'] }} {{ __('أستاذ') }}
                </span>
            </button>

            <button
                wire:click="$set('activeTab', 'facilities')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'facilities' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>🏫</span>
                {{ __('المرافق والهياكل القاعدية والمصادقة') }}
                @if($isCertified)
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500 text-white font-bold">
                        ✓
                    </span>
                @endif
            </button>
        </div>

        {{-- TAB 1: DEMOGRAPHICS AND PEDAGOGICAL DIVISIONS --}}
        @if($activeTab === 'demographics')
            <div class="space-y-6">

                {{-- KPI Cards Row --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('إجمالي التلاميذ') }}</div>
                        <div class="text-2xl font-black text-primary-600 dark:text-primary-400 mt-1">{{ $demographics['total_students'] }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('مسجلون فعلياً') }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('الأفواج التربوية') }}</div>
                        <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ $demographics['total_divisions'] }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('فوجاً تعليمياً') }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('معدل الفوج') }}</div>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $demographics['average_per_division'] }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('تلميذ / فوج') }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('الذكور') }}</div>
                        <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $demographics['total_boys'] }}</div>
                        <div class="text-[10px] text-blue-500 dark:text-blue-400 font-bold mt-0.5">{{ $demographics['boys_percentage'] }}%</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('الإناث') }}</div>
                        <div class="text-2xl font-black text-pink-600 dark:text-pink-400 mt-1">{{ $demographics['total_girls'] }}</div>
                        <div class="text-[10px] text-pink-500 dark:text-pink-400 font-bold mt-0.5">{{ $demographics['girls_percentage'] }}%</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('نصف داخلي') }}</div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $demographics['total_demi_internes'] }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('مستفيد من المطعم') }}</div>
                    </div>
                </div>

                {{-- Table by Levels --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
                        <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📋</span>
                            <span>{{ __('جدول توزيع التلاميذ والأفواج التربوية حسب المستوى والنظام') }}</span>
                        </div>
                        <span class="text-xs text-gray-600 dark:text-gray-300 font-semibold">
                            {{ __('المطابقة الوزارية لاستمارة 15 أكتوبر') }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-950 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800">
                                    <th class="p-3 text-right text-gray-800 dark:text-gray-100">{{ __('المستوى التعليمي') }}</th>
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100">{{ __('الأفواج التربوية') }}</th>
                                    <th class="p-3 text-center text-blue-700 dark:text-blue-400">{{ __('الذكور') }}</th>
                                    <th class="p-3 text-center text-pink-700 dark:text-pink-400">{{ __('الإناث') }}</th>
                                    <th class="p-3 text-center font-black bg-amber-100/70 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200">{{ __('المجموع العام') }}</th>
                                    <th class="p-3 text-center text-gray-700 dark:text-gray-300">{{ __('نسبة الإناث') }}</th>
                                    <th class="p-3 text-center text-gray-700 dark:text-gray-300">{{ __('خارجي') }}</th>
                                    <th class="p-3 text-center text-emerald-700 dark:text-emerald-400">{{ __('نصف داخلي') }}</th>
                                    <th class="p-3 text-center text-gray-400 dark:text-gray-500">{{ __('داخلي') }}</th>
                                    <th class="p-3 text-center font-bold text-amber-700 dark:text-amber-400">{{ __('معدل الفوج') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                @forelse($demographics['levels'] as $row)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $row['level_name'] }}</td>
                                        <td class="p-3 text-center font-bold text-gray-800 dark:text-gray-200">{{ $row['divisions_count'] }}</td>
                                        <td class="p-3 text-center text-blue-600 dark:text-blue-400 font-semibold">{{ $row['boys_count'] }}</td>
                                        <td class="p-3 text-center text-pink-600 dark:text-pink-400 font-semibold">{{ $row['girls_count'] }}</td>
                                        <td class="p-3 text-center font-black bg-amber-50 dark:bg-amber-950/40 text-amber-950 dark:text-amber-200">{{ $row['total_students'] }}</td>
                                        <td class="p-3 text-center text-gray-600 dark:text-gray-300">{{ $row['girls_percentage'] }}%</td>
                                        <td class="p-3 text-center text-gray-700 dark:text-gray-300">{{ $row['externes_count'] }}</td>
                                        <td class="p-3 text-center text-emerald-600 dark:text-emerald-400 font-bold">{{ $row['demi_internes_count'] }}</td>
                                        <td class="p-3 text-center text-gray-400 dark:text-gray-500">{{ $row['internes_count'] }}</td>
                                        <td class="p-3 text-center font-bold text-amber-600 dark:text-amber-400">{{ $row['average_per_division'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('لا توجد مستويات تعليمية مسجلة حالياً.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-950 font-black text-gray-900 dark:text-white border-t-2 border-gray-300 dark:border-gray-800">
                                    <td class="p-3 text-gray-900 dark:text-white">{{ __('المجموع الإجمالي للمؤسسة') }}</td>
                                    <td class="p-3 text-center text-primary-700 dark:text-primary-300">{{ $demographics['total_divisions'] }}</td>
                                    <td class="p-3 text-center text-blue-700 dark:text-blue-400">{{ $demographics['total_boys'] }}</td>
                                    <td class="p-3 text-center text-pink-700 dark:text-pink-400">{{ $demographics['total_girls'] }}</td>
                                    <td class="p-3 text-center text-lg bg-amber-100/70 dark:bg-amber-950/70 text-amber-950 dark:text-amber-200 font-black">{{ $demographics['total_students'] }}</td>
                                    <td class="p-3 text-center text-gray-700 dark:text-gray-300">{{ $demographics['girls_percentage'] }}%</td>
                                    <td class="p-3 text-center text-gray-700 dark:text-gray-300">{{ $demographics['total_externes'] }}</td>
                                    <td class="p-3 text-center text-emerald-700 dark:text-emerald-400">{{ $demographics['total_demi_internes'] }}</td>
                                    <td class="p-3 text-center text-gray-500 dark:text-gray-400">{{ $demographics['total_internes'] }}</td>
                                    <td class="p-3 text-center text-amber-700 dark:text-amber-400">{{ $demographics['average_per_division'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        @endif

        {{-- TAB 2: AGE PYRAMID & TEACHER STAFFING --}}
        @if($activeTab === 'pyramid_staffing')
            <div class="space-y-6">

                {{-- Age Pyramid Section --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
                        <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🎂</span>
                            <span>{{ __('الهرم العمري للتلاميذ: التوزيع المتقاطع حسب سنة الميلاد والطور الدراسي') }}</span>
                        </div>
                        <span class="text-xs text-gray-600 dark:text-gray-300 font-semibold">
                            {{ __('المعايير الديموغرافية لوزارة التربية الوطنية') }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-950 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800">
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100" rowspan="2">{{ __('سنة الميلاد') }}</th>
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100" rowspan="2">{{ __('السن (سنة)') }}</th>
                                    @foreach($agePyramid['levels'] as $lvl)
                                        <th class="p-2 text-center border-x border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-100" colspan="3">{{ $lvl->name }}</th>
                                    @endforeach
                                    <th class="p-3 text-center bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-white" colspan="3">{{ __('المجموع العام') }}</th>
                                </tr>
                                <tr class="bg-gray-50 dark:bg-gray-900 text-[10px] text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800">
                                    @foreach($agePyramid['levels'] as $lvl)
                                        <th class="p-1.5 text-center text-blue-600 dark:text-blue-400">{{ __('ذ') }}</th>
                                        <th class="p-1.5 text-center text-pink-600 dark:text-pink-400">{{ __('إ') }}</th>
                                        <th class="p-1.5 text-center font-bold text-gray-700 dark:text-gray-200">{{ __('م') }}</th>
                                    @endforeach
                                    <th class="p-1.5 text-center text-blue-700 dark:text-blue-300">{{ __('ذكور') }}</th>
                                    <th class="p-1.5 text-center text-pink-700 dark:text-pink-300">{{ __('إناث') }}</th>
                                    <th class="p-1.5 text-center font-black text-gray-900 dark:text-white">{{ __('المجموع') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                @foreach($agePyramid['rows'] as $row)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition">
                                        <td class="p-2.5 text-center font-mono font-bold text-gray-800 dark:text-gray-200">{{ $row['birth_year'] }}</td>
                                        <td class="p-2.5 text-center font-bold text-amber-600 dark:text-amber-400">{{ $row['age'] }}</td>
                                        @foreach($agePyramid['levels'] as $lvl)
                                            @php $lvlData = $row['by_level'][$lvl->id] ?? ['boys' => 0, 'girls' => 0, 'total' => 0]; @endphp
                                            <td class="p-2 text-center text-blue-600 dark:text-blue-400 font-mono">{{ $lvlData['boys'] ?: '-' }}</td>
                                            <td class="p-2 text-center text-pink-600 dark:text-pink-400 font-mono">{{ $lvlData['girls'] ?: '-' }}</td>
                                            <td class="p-2 text-center font-bold text-gray-900 dark:text-white font-mono bg-gray-100 dark:bg-gray-800">{{ $lvlData['total'] ?: '-' }}</td>
                                        @endforeach
                                        <td class="p-2.5 text-center text-blue-700 dark:text-blue-300 font-bold font-mono bg-gray-100 dark:bg-gray-800">{{ $row['total_boys'] }}</td>
                                        <td class="p-2.5 text-center text-pink-700 dark:text-pink-300 font-bold font-mono bg-gray-100 dark:bg-gray-800">{{ $row['total_girls'] }}</td>
                                        <td class="p-2.5 text-center font-black font-mono bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Teacher Staffing and Weekly Hours Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
                        <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span>👨‍🏫</span>
                            <span>{{ __('التأطير البيداغوجي وتوزيع الأساتذة والحصص الأسبوعية حسب المادة التعليمية') }}</span>
                        </div>
                        <div class="text-xs font-bold text-primary-600 dark:text-primary-400">
                            {{ __('المعدل العام للتأطير بالمؤسسة:') }} {{ $staffing['overall_ratio'] }} {{ __('تلميذ لكل أستاذ') }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-950 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800">
                                    <th class="p-3 text-right text-gray-800 dark:text-gray-100">{{ __('المادة التعليمية') }}</th>
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100">{{ __('تعداد الأساتذة') }}</th>
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100">{{ __('الحجم الساعي الأسبوعي (ساعة)') }}</th>
                                    <th class="p-3 text-center text-gray-800 dark:text-gray-100">{{ __('معدل التأطير (تلميذ / أستاذ)') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                @forelse($staffing['subjects'] as $sub)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $sub['subject_name'] }}</td>
                                        <td class="p-3 text-center font-bold text-primary-700 dark:text-primary-400">{{ $sub['teachers_count'] }}</td>
                                        <td class="p-3 text-center font-mono">{{ $sub['weekly_hours'] }} {{ __('سا') }}</td>
                                        <td class="p-3 text-center font-bold {{ $sub['ratio'] > 40 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $sub['ratio'] > 0 ? $sub['ratio'] . ' ' . __('تلميذ') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('لا توجد مواد تعليمية مسجلة حالياً.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-900 font-black text-gray-900 dark:text-white border-t-2 border-gray-300 dark:border-gray-700">
                                    <td class="p-3">{{ __('المجموع الإجمالي') }}</td>
                                    <td class="p-3 text-center text-primary-600 dark:text-primary-400">{{ $staffing['total_teachers'] }} {{ __('أستاذ') }}</td>
                                    <td class="p-3 text-center font-mono">{{ $staffing['total_weekly_hours'] }} {{ __('سا') }}</td>
                                    <td class="p-3 text-center text-emerald-600 dark:text-emerald-400">{{ $staffing['overall_ratio'] }} {{ __('تلميذ / أستاذ') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        @endif

        {{-- TAB 3: FACILITIES, INFRASTRUCTURE AND OFFICIAL CERTIFICATION --}}
        @if($activeTab === 'facilities')
            <div class="space-y-6">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Form: Facilities & Structural Metrics --}}
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>🏫</span>
                                <span>{{ __('بيانات الهياكل والمرافق المادية والتأطير الإداري') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('تُدرج هذه الأرقام في الاستمارة الرسمية لمديرية التربية لمطابقة الطاقة الاستيعابية.') }}
                            </p>
                        </div>

                        <form wire:submit.prevent="saveFacilities" class="space-y-6">
                            {{-- Rooms and Labs --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-200 mb-3 uppercase tracking-wider">
                                    {{ __('1. القاعات والمخابر التعليمية:') }}
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('الحجرات العادية') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="ordinaryClassroomsCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('القاعات المتخصصة') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="specializedRoomsCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('مخابر العلوم/الفيزياء') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="scienceLabsCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('قاعات الإعلام الآلي') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="computerLabsCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            {{-- Capacities --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-200 mb-3 uppercase tracking-wider">
                                    {{ __('2. طاقات الاستيعاب والخدمات الملحقة:') }}
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('طاقة استيعاب المكتبة المدرسية (مقعد)') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="libraryCapacity"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('طاقة استيعاب المطعم المدرسي (وجبة/يوم)') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="canteenCapacity"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            {{-- Admin & Support Staff --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-200 mb-3 uppercase tracking-wider">
                                    {{ __('3. التأطير الإداري وأعوان الدعم:') }}
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('الموظفون الإداريون') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="administrativeStaffCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('مشرفو التربية والحراسة') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="educationSupervisorsCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('العمال المهنيون وأعوان الخدمة') }}
                                        </label>
                                        <input
                                            type="number"
                                            wire:model.defer="serviceWorkersCount"
                                            min="0"
                                            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            {{-- Reference and Notes --}}
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ __('رقم المراسلة / التسجيل الإداري') }}
                                    </label>
                                    <input
                                        type="text"
                                        wire:model.defer="referenceNumber"
                                        placeholder="إحصاء-2026-2027/01"
                                        class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ __('ملاحظات وتوجيهات مدير المؤسسة') }}
                                    </label>
                                    <textarea
                                        wire:model.defer="observations"
                                        rows="2"
                                        placeholder="{{ __('أي ملاحظات خاصة بظروف الدخول المدرسي أو العجز في التأطير...') }}"
                                        class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    class="px-5 py-2.5 text-xs font-bold rounded-xl bg-gray-900 dark:bg-primary-600 hover:bg-gray-800 dark:hover:bg-primary-700 text-white transition shadow-sm cursor-pointer"
                                >
                                    💾 {{ __('حفظ وتحديث بيانات المرافق') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Card: Official Certification & Snapshot Freezing --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>✍️</span>
                                <span>{{ __('المصادقة الرسمية وتجميد الكشف') }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('إجراء قانوني يلزم إدارة المؤسسة ويجمد أرقام 15 أكتوبر لمنع أي تعديل مستقبلي إثر تحويلات التلاميذ.') }}
                            </p>
                        </div>

                        @if($isCertified)
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700 rounded-xl space-y-3">
                                <div class="flex items-center gap-2 text-emerald-900 dark:text-emerald-200 font-bold text-xs">
                                    <span class="text-lg">✅</span>
                                    <span>{{ __('تمت المصادقة الرسمية وتجميد الإحصاء بنجاح') }}</span>
                                </div>
                                <div class="text-[11px] text-emerald-800 dark:text-emerald-300 space-y-1">
                                    <p><strong>{{ __('تاريخ المصادقة:') }}</strong> {{ $this->certifiedAt }}</p>
                                    <p><strong>{{ __('المسؤول المصادق:') }}</strong> {{ $this->certifiedByName }}</p>
                                    <p><strong>{{ __('المرجع الإداري:') }}</strong> {{ $this->referenceNumber }}</p>
                                </div>
                                <p class="text-[10px] text-gray-600 dark:text-gray-400 leading-relaxed border-t border-emerald-200 dark:border-emerald-800 pt-2">
                                    {{ __('البيانات مجمدة حالياً في أرشيف المؤسسة. حتى وإن طرأت تحويلات أو تنقلات للتلاميذ خلال السنة، تبقى وثيقة 15 أكتوبر الرسمية ثابتة مطابقة للأصل.') }}
                                </p>
                            </div>

                            @if(auth()->user()?->isAdmin())
                                <button
                                    type="button"
                                    wire:click="unlockRecord"
                                    wire:confirm="{{ __('هل أنت متأكد من رغبتك في إلغاء تجميد الكشف؟ سيعود إلى الوضع التحريري وتحديث الأرقام تلقائياً مع التلاميذ الحاليين.') }}"
                                    class="w-full px-4 py-2 text-xs font-semibold rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition cursor-pointer"
                                >
                                    ⚠️ {{ __('إلغاء التجميد والعودة للوضع التحريري (Draft)') }}
                                </button>
                            @endif

                        @else
                            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-xl space-y-2 text-xs text-amber-900 dark:text-amber-200">
                                <span class="font-bold flex items-center gap-1.5">
                                    <span>⚠️</span>
                                    <span>{{ __('تنبيه إداري قبل المصادقة:') }}</span>
                                </span>
                                <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                                    {{ __('بمجرد النقر على المصادقة أدناه، سيتم أخذ لقطة إحصائية ثابتة (Snapshot) لتعداد التلاميذ، توزيع الأفواج، الهرم العمري والتأطير البيداغوجي كما هي محددة في تاريخ 15 أكتوبر.') }}
                                </p>
                            </div>

                            @if(auth()->user()?->isAdmin())
                                <button
                                    type="button"
                                    wire:click="certifyRecord"
                                    wire:confirm="{{ __('هل تؤكد صحة كافة المعطيات الإحصائية وترغب في المصادقة الرسمية وتجميد استمارة 15 أكتوبر؟') }}"
                                    class="w-full py-3 px-4 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <span>✍️</span>
                                    <span>{{ __('المصادقة وتجميد كشف 15 أكتوبر رسمياً') }}</span>
                                </button>
                            @else
                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">
                                    {{ __('المصادقة الرسمية مقتصرة على مدير المؤسسة فقط.') }}
                                </div>
                            @endif
                        @endif
                    </div>

                </div>

            </div>
        @endif

    </div>
</x-filament-panels::page>
