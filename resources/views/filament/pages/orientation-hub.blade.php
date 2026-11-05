<x-filament-panels::page>
    @php
        $kpis = $this->kpis;
        $candidates = $this->candidates;
        $classrooms = $this->classrooms;
    @endphp

    <div class="space-y-6">

        {{-- Top Hub Header & Status Banner --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 rounded-xl text-xl">
                            🧭
                        </span>
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ __('منظومة التوجيه المدرسي والقبول إلى السنة الأولى ثانوي (4AM)') }}
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('متابعة بطاقات الرغبات، حساب مؤشرات الملمح العلمي والأدبي، مداولات المجلس، وإدارة الطعون الولائية') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                        <span class="px-3 py-1 rounded-full bg-primary-100 text-primary-800 dark:bg-primary-950/50 dark:text-primary-300 font-bold border border-primary-200 dark:border-primary-800">
                            {{ __('السنة الدراسية:') }} {{ $this->academicYear }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200 border border-blue-200 dark:border-blue-800 font-bold">
                            {{ __('إجمالي مرشحي 4AM:') }} {{ $kpis['total_candidates'] }} {{ __('تلميذ') }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold">
                            {{ __('المقبولون بالثانوي:') }} {{ $kpis['admitted_count'] }} ({{ $kpis['admission_rate'] }}%)
                        </span>
                    </div>
                </div>

                {{-- Header Actions --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <a
                        href="{{ route('print.orientation.pv', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear, 'classroom_id' => $this->classroomId]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm cursor-pointer"
                    >
                        <span>📜</span>
                        <span>{{ __('معاينة وطباعة محضر المجلس (A4)') }}</span>
                    </a>

                    <a
                        href="{{ route('print.orientation.pv.pdf', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear, 'classroom_id' => $this->classroomId]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm cursor-pointer"
                    >
                        <span>📥</span>
                        <span>{{ __('تحميل المحضر (PDF)') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Interactive KPI Cards Row --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('تعداد 4AM') }}</div>
                <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $kpis['total_candidates'] }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('مرشحاً بالمؤسسة') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('بطاقات الرغبات') }}</div>
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $kpis['wishes_count'] }}</div>
                <div class="text-[10px] text-blue-600 dark:text-blue-400 font-bold mt-0.5">{{ $kpis['wishes_rate'] }}% {{ __('مصادق عليها') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('ج.م علوم وتكنولوجيا') }}</div>
                <div class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ $kpis['streams']['science_tech']['count'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400 font-bold mt-0.5">{{ $kpis['streams']['science_tech']['percentage'] }}%</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('ج.م آداب') }}</div>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $kpis['streams']['arts_humanities']['count'] }}</div>
                <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-0.5">{{ $kpis['streams']['arts_humanities']['percentage'] }}%</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('تكوين مهني / إعادة') }}</div>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $kpis['streams']['vocational_training']['count'] + $kpis['streams']['repeat_year']['count'] }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('توجيه بديل') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('الطعون الولائية') }}</div>
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $kpis['appeals']['total'] }}</div>
                <div class="text-[10px] text-purple-600 dark:text-purple-400 font-bold mt-0.5">{{ $kpis['appeals']['submitted'] }} {{ __('قيد الدراسة') }}</div>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            <button
                wire:click="setTab('wishes')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'wishes' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>📝</span>
                {{ __('بطاقات الرغبات والملمح البيداغوجي') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'wishes' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $kpis['wishes_count'] }}/{{ $kpis['total_candidates'] }}
                </span>
            </button>

            <button
                wire:click="setTab('deliberations')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'deliberations' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>⚖️</span>
                {{ __('مداولات مجلس القبول والتوجيه') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'deliberations' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $kpis['deliberated_count'] }} {{ __('مفصول فيها') }}
                </span>
            </button>

            <button
                wire:click="setTab('appeals')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'appeals' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>📑</span>
                {{ __('الطعون الولائية وتوزيع الشعب') }}
                @if($kpis['appeals']['total'] > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500 text-white font-bold">
                        {{ $kpis['appeals']['total'] }}
                    </span>
                @endif
            </button>
        </div>

        {{-- Filter Cockpit --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="w-full sm:w-64">
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('قسم السنة الرابعة متوسط:') }}
                    </label>
                    <select
                        wire:model.live="classroomId"
                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                    >
                        <option value="">{{ __('جميع أقسام 4AM') }} ({{ $classrooms->count() }} {{ __('أقسام') }})</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-72">
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('البحث عن تلميذ:') }}
                    </label>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="{{ __('الاسم، اللقب، أو رقم القيد...') }}"
                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400"
                    />
                </div>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                {{ __('المرشحون المعروضون:') }} <strong>{{ $candidates->count() }}</strong>
            </div>
        </div>

        {{-- TAB 1: WISHES & PEDAGOGICAL PROFILES --}}
        @if($activeTab === 'wishes')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📝</span>
                        <span>{{ __('جدول بطاقات الرغبات والملمح البيداغوجي (المواد العلمية مقابل المواد الأدبية)') }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('الملمح العلمي = (رياضيات + فيزياء + علوم) ÷ 3 | الملمح الأدبي = (عربية + فرنسية + إنجليزية + اجتماعيات) ÷ 4') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-10 text-center">#</th>
                                <th class="p-3 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-3 text-center">{{ __('رقم القيد') }}</th>
                                <th class="p-3 text-center">{{ __('القسم') }}</th>
                                <th class="p-3 text-center text-blue-700 dark:text-blue-400">{{ __('معدل الملمح العلمي') }}</th>
                                <th class="p-3 text-center text-amber-700 dark:text-amber-400">{{ __('معدل الملمح الأدبي') }}</th>
                                <th class="p-3 text-center font-bold">{{ __('المعدل السنوي') }}</th>
                                <th class="p-3 text-center">{{ __('الملمح الأرجح') }}</th>
                                <th class="p-3 text-center text-primary-700 dark:text-primary-300 font-bold">{{ __('الرغبة 1') }}</th>
                                <th class="p-3 text-center">{{ __('الرغبة 2') }}</th>
                                <th class="p-3 text-center">{{ __('موافقة الولي') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-100 font-medium">
                            @forelse($candidates as $idx => $row)
                                @php
                                    $st = $row['student'];
                                    $or = $row['orientation'];
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $st->full_name }}
                                    </td>
                                    <td class="p-3 text-center font-mono text-gray-600 dark:text-gray-400">{{ $row['matricule'] }}</td>
                                    <td class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $row['classroom_name'] }}</td>
                                    <td class="p-3 text-center font-bold text-blue-700 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-950/20">
                                        {{ $row['scientific_score'] !== null ? number_format($row['scientific_score'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-amber-700 dark:text-amber-400 bg-amber-50/30 dark:bg-amber-950/20">
                                        {{ $row['literary_score'] !== null ? number_format($row['literary_score'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-gray-900 dark:text-white">
                                        {{ $row['general_average'] !== null ? number_format($row['general_average'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($row['recommended'])
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $row['recommended']->value === 'science_tech' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' }}">
                                                {{ $row['recommended']->shortLabel() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center font-bold text-primary-700 dark:text-primary-300">
                                        {{ $or->first_choice?->shortLabel() ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $or->second_choice?->shortLabel() ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($or->guardian_approved)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                ✓ {{ __('موقع') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                ⏳ {{ __('معلق') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                wire:click="openWishesModal({{ $st->id }})"
                                                type="button"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition cursor-pointer"
                                                title="{{ __('تعديل الرغبات') }}"
                                            >
                                                ✏️ {{ __('تعديل') }}
                                            </button>

                                            <a
                                                href="{{ route('print.orientation.wishes', ['locale' => app()->getLocale(), 'student' => $st->id, 'academic_year' => $this->academicYear]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-bold rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/40 dark:hover:bg-blue-800/60 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700 transition"
                                                title="{{ __('معاينة بطاقة الرغبات الرسمية A4') }}"
                                            >
                                                📄
                                            </a>

                                            <a
                                                href="{{ route('print.orientation.wishes.pdf', ['locale' => app()->getLocale(), 'student' => $st->id, 'academic_year' => $this->academicYear]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition"
                                                title="{{ __('تحميل PDF') }}"
                                            >
                                                📥
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد سجلات تلاميذ مطابقة لمعايير البحث الحالية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: COUNCIL DELIBERATIONS & STREAM DECISIONS --}}
        @if($activeTab === 'deliberations')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <span>⚖️</span>
                        <span>{{ __('مداولات مجلس القبول والتوجيه: فحص معدلات BEM، معدل القبول، والقرار النهائي المعتمد') }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('القبول التلقائي عند BEM >= 10.0 أو معدل الإنقاذ (معدل سنوي + BEM) ÷ 2 >= 10.0') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-10 text-center">#</th>
                                <th class="p-3 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-3 text-center">{{ __('القسم') }}</th>
                                <th class="p-3 text-center">{{ __('معدل BEM') }}</th>
                                <th class="p-3 text-center">{{ __('معدل القبول') }}</th>
                                <th class="p-3 text-center">{{ __('وضعية القبول') }}</th>
                                <th class="p-3 text-center">{{ __('الرغبة 1') }}</th>
                                <th class="p-3 text-center font-black text-emerald-800 dark:text-emerald-300">{{ __('قرار المجلس النهائي') }}</th>
                                <th class="p-3 text-center">{{ __('الملاحظات') }}</th>
                                <th class="p-3 text-center">{{ __('القرار والتبليغ') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-100 font-medium">
                            @forelse($candidates as $idx => $row)
                                @php
                                    $st = $row['student'];
                                    $or = $row['orientation'];
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $st->full_name }}</td>
                                    <td class="p-3 text-center text-gray-600 dark:text-gray-300">{{ $row['classroom_name'] }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-teal-700 dark:text-teal-400">
                                        {{ $row['bem_score'] !== null ? number_format($row['bem_score'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-black text-gray-900 dark:text-white bg-gray-50/50 dark:bg-gray-800">
                                        {{ $row['admission_average'] !== null ? number_format($row['admission_average'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($row['is_admitted'])
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                ✓ {{ __('مقبول بالثانوي') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                                                ✗ {{ __('غير مقبول') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $or->first_choice?->shortLabel() ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center font-black">
                                        @if($or->final_decision)
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $or->final_decision->value === 'science_tech' ? 'bg-blue-100 text-blue-900 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-300 dark:border-blue-700' : ($or->final_decision->value === 'arts_humanities' ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                                {{ $or->final_decision->label() }}
                                            </span>
                                        @else
                                            <span class="text-xs text-amber-600 dark:text-amber-400 font-bold italic">
                                                ⏳ {{ __('بانتظار الفصل') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center text-gray-500 dark:text-gray-400 max-w-[150px] truncate">
                                        {{ $or->counselor_notes ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                wire:click="openDeliberationModal({{ $st->id }})"
                                                type="button"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm cursor-pointer"
                                            >
                                                ⚖️ {{ __('فصل') }}
                                            </button>

                                            <a
                                                href="{{ route('print.orientation.notification', ['locale' => app()->getLocale(), 'student' => $st->id, 'academic_year' => $this->academicYear]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition"
                                                title="{{ __('إشعار التوجيه الرسمي الفردي للولي A4') }}"
                                            >
                                                📬
                                            </a>

                                            <a
                                                href="{{ route('print.orientation.notification.pdf', ['locale' => app()->getLocale(), 'student' => $st->id, 'academic_year' => $this->academicYear]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition"
                                                title="{{ __('تحميل الإشعار PDF') }}"
                                            >
                                                📥
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد سجلات تلاميذ مطابقة لمعايير البحث الحالية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: APPEALS & ANALYTICS --}}
        @if($activeTab === 'appeals')
            <div class="space-y-6">

                {{-- Visual Streams Breakdown Progress Bars --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>📊</span>
                        <span>{{ __('التوزيع الإحصائي لتوجيه التلاميذ نحو شعب السنة الأولى ثانوي') }}</span>
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-teal-700 dark:text-teal-400">{{ __('جذع مشترك علوم وتكنولوجيا:') }}</span>
                                <span class="text-teal-900 dark:text-teal-200 font-mono">{{ $kpis['streams']['science_tech']['count'] }} {{ __('تلميذ') }} ({{ $kpis['streams']['science_tech']['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-teal-500 h-3 rounded-full transition-all duration-500" style="width: {{ $kpis['streams']['science_tech']['percentage'] }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-amber-700 dark:text-amber-400">{{ __('جذع مشترك آداب ولغات:') }}</span>
                                <span class="text-amber-900 dark:text-amber-200 font-mono">{{ $kpis['streams']['arts_humanities']['count'] }} {{ __('تلميذ') }} ({{ $kpis['streams']['arts_humanities']['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-amber-500 h-3 rounded-full transition-all duration-500" style="width: {{ $kpis['streams']['arts_humanities']['percentage'] }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-rose-700 dark:text-rose-400">{{ __('تكوين مهني وإعادة السنة:') }}</span>
                                <span class="text-rose-900 dark:text-rose-200 font-mono">{{ $kpis['streams']['vocational_training']['count'] + $kpis['streams']['repeat_year']['count'] }} {{ __('تلميذ') }} ({{ $kpis['streams']['vocational_training']['percentage'] + $kpis['streams']['repeat_year']['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-rose-500 h-3 rounded-full transition-all duration-500" style="width: {{ $kpis['streams']['vocational_training']['percentage'] + $kpis['streams']['repeat_year']['percentage'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Appeals Management Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                        <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📑</span>
                            <span>{{ __('سجل الطعون في التوجيه المحالة على اللجنة الولائية للطعن') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('تودع الطعون خلال مهلة 8 أيام قانونية من تاريخ استلام إشعار التوجيه') }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-3 w-10 text-center">#</th>
                                    <th class="p-3 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                    <th class="p-3 text-center">{{ __('القسم') }}</th>
                                    <th class="p-3 text-center">{{ __('قرار المؤسسة الأصلي') }}</th>
                                    <th class="p-3 text-center">{{ __('وضعية الطعن') }}</th>
                                    <th class="p-3 text-center">{{ __('قرار اللجنة الولائية') }}</th>
                                    <th class="p-3 text-center">{{ __('مبررات وملاحظات الطعن') }}</th>
                                    <th class="p-3 text-center">{{ __('تحديث الوضعية') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-100 font-medium">
                                @php
                                    $appealedRows = $candidates->filter(fn($r) => $r['appeal_status']->value !== 'none');
                                @endphp
                                @forelse($appealedRows as $idx => $row)
                                    @php
                                        $st = $row['student'];
                                        $or = $row['orientation'];
                                    @endphp
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                        <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $st->full_name }}</td>
                                        <td class="p-3 text-center text-gray-600 dark:text-gray-300">{{ $row['classroom_name'] }}</td>
                                        <td class="p-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                            {{ $or->final_decision?->label() ?? '—' }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $or->appeal_status->value === 'accepted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : ($or->appeal_status->value === 'rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-700' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700') }}">
                                                {{ $or->appeal_status->label() }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center font-bold text-primary-700 dark:text-primary-300">
                                            {{ $or->appeal_decision?->label() ?? '—' }}
                                        </td>
                                        <td class="p-3 text-center text-gray-500 dark:text-gray-400 max-w-[200px] truncate">
                                            {{ $or->appeal_notes ?? '—' }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <button
                                                wire:click="openAppealModal({{ $or->id }})"
                                                type="button"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition cursor-pointer"
                                            >
                                                ✏️ {{ __('تعديل') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('لا توجد طعون مسجلة حالياً.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        @endif

        {{-- MODAL 1: EDIT WISHES --}}
        @if($isWishesModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl w-full max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📝</span>
                            <span>{{ __('تعديل وتوثيق بطاقة رغبات التوجيه') }}</span>
                        </h3>
                        <button wire:click="$set('isWishesModalOpen', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('الرغبة الأولى:') }}
                            </label>
                            <select
                                wire:model="editFirstChoice"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            >
                                <option value="science_tech">{{ __('جذع مشترك علوم وتكنولوجيا') }}</option>
                                <option value="arts_humanities">{{ __('جذع مشترك آداب') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('الرغبة الثانية:') }}
                            </label>
                            <select
                                wire:model="editSecondChoice"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            >
                                <option value="arts_humanities">{{ __('جذع مشترك آداب') }}</option>
                                <option value="science_tech">{{ __('جذع مشترك علوم وتكنولوجيا') }}</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                wire:model="editGuardianApproved"
                                type="checkbox"
                                id="chk_guardian"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <label for="chk_guardian" class="font-bold text-gray-800 dark:text-gray-200 cursor-pointer">
                                {{ __('تأكيد استلام ومصادقة الولي الشرعي على بطاقة الرغبات') }}
                            </label>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('ملاحظات الولي أو مستشار التوجيه:') }}
                            </label>
                            <textarea
                                wire:model="editGuardianNotes"
                                rows="2"
                                placeholder="{{ __('أي ملاحظات إضافية...') }}"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="$set('isWishesModalOpen', false)"
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition cursor-pointer"
                        >
                            {{ __('إلغاء') }}
                        </button>
                        <button
                            wire:click="saveWishes"
                            type="button"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm cursor-pointer"
                        >
                            {{ __('حفظ الرغبات') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 2: DELIBERATION DECISION --}}
        @if($isDeliberationModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl w-full max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>⚖️</span>
                            <span>{{ __('تسجيل قرار مجلس القبول والتوجيه') }}</span>
                        </h3>
                        <button wire:click="$set('isDeliberationModalOpen', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('قرار المجلس النهائي (الشعبة الموجه إليها):') }}
                            </label>
                            <select
                                wire:model="deliberatingDecision"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            >
                                <option value="science_tech">{{ __('جذع مشترك علوم وتكنولوجيا') }}</option>
                                <option value="arts_humanities">{{ __('جذع مشترك آداب') }}</option>
                                <option value="vocational_training">{{ __('توجيه نحو التكوين والتعليم المهنيين') }}</option>
                                <option value="repeat_year">{{ __('إعادة السنة الرابعة متوسط') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('نقطة امتحان شهادة التعليم المتوسط (BEM):') }}
                            </label>
                            <input
                                wire:model="deliberatingBemScore"
                                type="number"
                                step="0.01"
                                min="0"
                                max="20"
                                placeholder="12.50"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            />
                            <p class="text-[10px] text-gray-500 mt-1">
                                {{ __('إذا تركت فارغة، يُعتمد المعدل السنوي العام كمعيار للقبول والتوجيه.') }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('ملاحظات وتوجيهات المجلس:') }}
                            </label>
                            <textarea
                                wire:model="deliberatingNotes"
                                rows="2"
                                placeholder="{{ __('توجيه مطابق للرغبة، مستوى جيد في المواد الأساسية...') }}"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="$set('isDeliberationModalOpen', false)"
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition cursor-pointer"
                        >
                            {{ __('إلغاء') }}
                        </button>
                        <button
                            wire:click="saveDeliberation"
                            type="button"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm cursor-pointer"
                        >
                            {{ __('تثبيت القرار') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 3: APPEAL STATUS --}}
        @if($isAppealModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl w-full max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📑</span>
                            <span>{{ __('تحديث وضعية الطعن أمام اللجنة الولائية') }}</span>
                        </h3>
                        <button wire:click="$set('isAppealModalOpen', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('وضعية الطعن:') }}
                            </label>
                            <select
                                wire:model="appealStatus"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            >
                                <option value="submitted">{{ __('طعن مودع قيد دراسة اللجنة الولائية') }}</option>
                                <option value="accepted">{{ __('تم قبول الطعن وإعادة التوجيه') }}</option>
                                <option value="rejected">{{ __('تم رفض الطعن وتثبيت القرار الأصلي') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('قرار اللجنة الولائية (في حال القبول):') }}
                            </label>
                            <select
                                wire:model="appealDecision"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            >
                                <option value="">{{ __('— بدون تغيير —') }}</option>
                                <option value="science_tech">{{ __('جذع مشترك علوم وتكنولوجيا') }}</option>
                                <option value="arts_humanities">{{ __('جذع مشترك آداب') }}</option>
                                <option value="vocational_training">{{ __('توجيه نحو التكوين المهني') }}</option>
                                <option value="repeat_year">{{ __('إعادة السنة') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('مبررات وملاحظات قرار اللجنة الولائية:') }}
                            </label>
                            <textarea
                                wire:model="appealNotes"
                                rows="2"
                                placeholder="{{ __('رقم محضر اللجنة الولائية، تاريخ البت، المبررات...') }}"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="$set('isAppealModalOpen', false)"
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition cursor-pointer"
                        >
                            {{ __('إلغاء') }}
                        </button>
                        <button
                            wire:click="saveAppeal"
                            type="button"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm cursor-pointer"
                        >
                            {{ __('حفظ القرار الولائي') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>

