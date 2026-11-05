<x-filament-panels::page>
    @php
        $kpis = $this->kpis;
        $students = $this->students;
        $sessions = $this->sessions;
        $classrooms = $this->classrooms;
    @endphp

    <div class="space-y-6">

        {{-- Top Hub Header Banner --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl text-xl">
                            ❤️
                        </span>
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ __('الملف النفسي والتربوي ومتابعات مستشار التوجيه المدرسي') }}
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('المتابعة الإرشادية الفردية، التشخيص النفسي والاجتماعي، صعوبات التعلم، وسجلات المقابلات المحمية بالسر المهني') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                        <span class="px-3 py-1 rounded-full bg-primary-100 text-primary-800 dark:bg-primary-950/50 dark:text-primary-300 font-bold border border-primary-200 dark:border-primary-800">
                            {{ __('السنة الدراسية:') }} {{ $this->academicYear }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200 border border-blue-200 dark:border-blue-800 font-bold">
                            {{ __('إجمالي التلاميذ:') }} {{ $kpis['total_students'] }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold">
                            {{ __('المقابلات المنفذة:') }} {{ $kpis['total_sessions'] }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold">
                            {{ __('نسبة المعالجة:') }} {{ $kpis['resolution_rate'] }}%
                        </span>
                    </div>
                </div>

                {{-- Header Actions --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        wire:click="openSessionModal()"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm cursor-pointer"
                    >
                        <span>✍️</span>
                        <span>{{ __('تسجيل مقابلة إرشادية جديدة') }}</span>
                    </button>

                    <a
                        href="{{ route('print.counseling.ledger', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm cursor-pointer"
                    >
                        <span>📋</span>
                        <span>{{ __('سجل المقابلات السنوي (A4)') }}</span>
                    </a>

                    <a
                        href="{{ route('print.counseling.ledger.pdf', ['locale' => app()->getLocale(), 'academic_year' => $this->academicYear]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm cursor-pointer"
                    >
                        <span>📥</span>
                        <span>{{ __('تحميل السجل (PDF)') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('إجمالي المقابلات') }}</div>
                <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $kpis['total_sessions'] }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('جلسة إرشادية') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('تمت معالجتها') }}</div>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $kpis['resolved_sessions'] }}</div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">{{ $kpis['resolution_rate'] }}% {{ __('مغلقة بنجاح') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('قيد المتابعة') }}</div>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $kpis['in_progress_sessions'] + $kpis['open_sessions'] }}</div>
                <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-0.5">{{ __('تتطلب جلسات لاحقة') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('الوضعية الأسرية الخاصة') }}</div>
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $kpis['orphan_count'] + $kpis['divorced_count'] }}</div>
                <div class="text-[10px] text-purple-600 dark:text-purple-400 font-bold mt-0.5">{{ $kpis['orphan_count'] }} {{ __('يتامى') }} | {{ $kpis['divorced_count'] }} {{ __('طلاق') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('صعوبات التعلم') }}</div>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $kpis['difficulties_count'] }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ __('تلميذاً مشخصاً') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">{{ __('تنبيهات صحية') }}</div>
                <div class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ $kpis['chronic_count'] + $kpis['pe_exemption_count'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400 font-bold mt-0.5">{{ $kpis['chronic_count'] }} {{ __('مزمن') }} | {{ $kpis['pe_exemption_count'] }} {{ __('إعفاء رياضة') }}</div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            <button
                wire:click="setTab('profiles')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'profiles' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>🗂️</span>
                {{ __('الملفات النفسية والتربوية للتلاميذ') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'profiles' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $students->count() }}
                </span>
            </button>

            <button
                wire:click="setTab('sessions')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'sessions' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>🗣️</span>
                {{ __('جلسات المقابلات والمتابعة الإرشادية') }}
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'sessions' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    {{ $sessions->count() }}
                </span>
            </button>

            <button
                wire:click="setTab('kpis')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'kpis' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
            >
                <span>📊</span>
                {{ __('الإحصائيات والتحليل الإرشادي') }}
            </button>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="w-full sm:w-60">
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('القسم التربوي:') }}
                    </label>
                    <select
                        wire:model.live="classroomId"
                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                    >
                        <option value="">{{ __('جميع الأقسام') }} ({{ $classrooms->count() }})</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-64">
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

                @if($activeTab === 'sessions')
                    <div class="w-full sm:w-56">
                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('سبب المقابلة:') }}
                        </label>
                        <select
                            wire:model.live="sessionReasonFilter"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        >
                            <option value="">{{ __('جميع الأسباب') }}</option>
                            @foreach(\App\Enums\CounselingSessionReason::cases() as $r)
                                <option value="{{ $r->value }}">{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-44">
                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('حالة المقابلة:') }}
                        </label>
                        <select
                            wire:model.live="sessionStatusFilter"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        >
                            <option value="">{{ __('جميع الحالات') }}</option>
                            @foreach(\App\Enums\CounselingSessionStatus::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                @if($activeTab === 'profiles')
                    {{ __('التلاميذ المعروضون:') }} <strong>{{ $students->count() }}</strong>
                @else
                    {{ __('المقابلات المعروضة:') }} <strong>{{ $sessions->count() }}</strong>
                @endif
            </div>
        </div>

        {{-- TAB 1: PROFILES TABLE --}}
        @if($activeTab === 'profiles')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🗂️</span>
                        <span>{{ __('جدول الملفات النفسية والتربوية للتلاميذ') }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('يتضمن مؤشرات الوسط الأسري، الحالة الصحية، صعوبات التعلم والمسار الدراسي') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-10 text-center">#</th>
                                <th class="p-3 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-3 text-center">{{ __('القسم') }}</th>
                                <th class="p-3 text-center">{{ __('الوضعية العائلية') }}</th>
                                <th class="p-3 text-center">{{ __('المنحة') }}</th>
                                <th class="p-3 text-center">{{ __('تنبيهات صحية وبدنية') }}</th>
                                <th class="p-3 text-center">{{ __('صعوبات التعلم') }}</th>
                                <th class="p-3 text-center">{{ __('الإعادة') }}</th>
                                <th class="p-3 text-center">{{ __('المقابلات') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-100 font-medium">
                            @forelse($students as $idx => $st)
                                @php $prof = $st->counselingProfile; @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3 text-start">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $st->full_name }}</div>
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">{{ $st->registration_number ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-bold text-gray-700 dark:text-gray-300">
                                        {{ $st->group?->classroom?->name ?? '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($prof && $prof->family_status)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $prof->family_status->color() }}-100 text-{{ $prof->family_status->color() }}-800 dark:bg-{{ $prof->family_status->color() }}-950/50 dark:text-{{ $prof->family_status->color() }}-300">
                                                {{ $prof->family_status->label() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($prof?->social_grant)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                ✓ {{ __('ممنوح') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex flex-wrap items-center justify-center gap-1">
                                            @if($prof?->pe_exemption)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300" title="{{ __('إعفاء تربية بدنية') }}">
                                                    🏃‍♂️✕
                                                </span>
                                            @endif
                                            @if(!empty($prof?->chronic_conditions))
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300" title="{{ $prof->chronicConditionsList() }}">
                                                    🩺 {{ count((array) $prof->chronic_conditions) }}
                                                </span>
                                            @endif
                                            @if($prof?->blood_type)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                    {{ $prof->blood_type }}
                                                </span>
                                            @endif
                                            @if(!$prof?->pe_exemption && empty($prof?->chronic_conditions) && !$prof?->blood_type)
                                                <span class="text-gray-400 text-[11px]">{{ __('عادية') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        @if(!empty($prof?->learning_difficulties))
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                                ⚠️ {{ count((array) $prof->learning_difficulties) }} {{ __('صعوبات') }}
                                            </span>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400 text-[11px]">✓ {{ __('لا توجد') }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center font-bold">
                                        @if(($prof?->repeated_years_count ?? 0) > 0)
                                            <span class="text-rose-600 dark:text-rose-400">{{ $prof->repeated_years_count }} {{ __('سنة') }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <button
                                            wire:click="openSessionModal({{ $st->id }})"
                                            type="button"
                                            class="px-2 py-1 text-[11px] font-bold rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 hover:bg-blue-100 transition cursor-pointer"
                                        >
                                            + {{ __('مقابلة') }}
                                        </button>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                wire:click="openProfileModal({{ $st->id }})"
                                                type="button"
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300 hover:bg-primary-100 transition cursor-pointer"
                                                title="{{ __('تعديل الملف النفسي والتربوي') }}"
                                            >
                                                ✏️ {{ __('تعديل') }}
                                            </button>

                                            <a
                                                href="{{ route('print.counseling.dossier', ['locale' => app()->getLocale(), 'student' => $st->id]) }}"
                                                target="_blank"
                                                class="p-1 rounded-lg text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition"
                                                title="{{ __('معاينة وطباعة البطاقة A4') }}"
                                            >
                                                🖨️
                                            </a>

                                            <a
                                                href="{{ route('print.counseling.dossier.pdf', ['locale' => app()->getLocale(), 'student' => $st->id]) }}"
                                                target="_blank"
                                                class="p-1 rounded-lg text-rose-600 dark:text-rose-400 hover:opacity-80 transition"
                                                title="{{ __('تحميل الملف بصيغة PDF') }}"
                                            >
                                                📥
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا يوجد تلاميذ مسجلون يطابقون خيارات البحث.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: COUNSELING SESSIONS TABLE --}}
        @if($activeTab === 'sessions')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🗣️</span>
                        <span>{{ __('سجل المقابلات والمتابعات الإرشادية الفردية') }}</span>
                    </div>
                    <button
                        wire:click="openSessionModal()"
                        type="button"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition cursor-pointer"
                    >
                        + {{ __('تسجيل مقابلة جديدة') }}
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-10 text-center">#</th>
                                <th class="p-3 text-center">{{ __('تاريخ المقابلة') }}</th>
                                <th class="p-3 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-3 text-center">{{ __('القسم') }}</th>
                                <th class="p-3 text-center">{{ __('سبب / داعي المقابلة') }}</th>
                                <th class="p-3 text-center">{{ __('المستهدف') }}</th>
                                <th class="p-3 text-start">{{ __('الخطة الإرشادية والتوصيات') }}</th>
                                <th class="p-3 text-center">{{ __('الحالة') }}</th>
                                <th class="p-3 text-center">{{ __('المتابعة القادمة') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-100 font-medium">
                            @forelse($sessions as $idx => $s)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-gray-800 dark:text-gray-200">
                                        {{ $s->session_date ? $s->session_date->format('Y/m/d') : '-' }}
                                    </td>
                                    <td class="p-3 text-start font-bold text-gray-900 dark:text-white">
                                        {{ $s->student->full_name ?? '-' }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-gray-700 dark:text-gray-300">
                                        {{ $s->student?->group?->classroom?->name ?? '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $s->reason_category->color() }}-100 text-{{ $s->reason_category->color() }}-800 dark:bg-{{ $s->reason_category->color() }}-950/50 dark:text-{{ $s->reason_category->color() }}-300">
                                            {{ $s->reason_category->label() }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center text-gray-600 dark:text-gray-400">
                                        {{ $s->intervieweeLabel() }}
                                    </td>
                                    <td class="p-3 text-start text-xs max-w-xs truncate" title="{{ $s->action_plan ?: $s->observations }}">
                                        {{ $s->action_plan ? \Illuminate\Support\Str::limit($s->action_plan, 45) : \Illuminate\Support\Str::limit($s->observations, 45) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <select
                                            wire:change="toggleSessionStatus({{ $s->id }}, $event.target.value)"
                                            class="px-2 py-1 text-[11px] font-bold rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            @foreach(\App\Enums\CounselingSessionStatus::cases() as $st)
                                                <option value="{{ $st->value }}" @selected($s->status === $st)>{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        @if($s->follow_up_date)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $s->follow_up_date->isPast() ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                                {{ $s->follow_up_date->format('Y/m/d') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a
                                                href="{{ route('print.counseling.session', ['locale' => app()->getLocale(), 'session' => $s->id]) }}"
                                                target="_blank"
                                                class="p-1 rounded-lg text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition"
                                                title="{{ __('معاينة تقرير المقابلة A4') }}"
                                            >
                                                🖨️
                                            </a>

                                            <a
                                                href="{{ route('print.counseling.session.pdf', ['locale' => app()->getLocale(), 'session' => $s->id]) }}"
                                                target="_blank"
                                                class="p-1 rounded-lg text-rose-600 dark:text-rose-400 hover:opacity-80 transition"
                                                title="{{ __('تحميل التقرير بصيغة PDF') }}"
                                            >
                                                📥
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد مقابلات مسجلة تطابق معايير التصفية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: ANALYTICS & KPIS --}}
        @if($activeTab === 'kpis')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Reasons Breakdown Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <span class="flex items-center gap-2">
                            <span>📈</span>
                            <span>{{ __('توزيع المقابلات الإرشادية حسب دواعي التدخل') }}</span>
                        </span>
                        <span class="text-xs text-gray-500 font-mono">{{ $kpis['total_sessions'] }} {{ __('مقابلة') }}</span>
                    </div>

                    <div class="space-y-3">
                        @foreach($kpis['reasons_breakdown'] as $item)
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-{{ $item['reason']->color() }}-500"></span>
                                        {{ $item['reason']->label() }}
                                    </span>
                                    <span class="font-mono text-gray-600 dark:text-gray-400">
                                        {{ $item['count'] }} ({{ $item['percentage'] }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                    <div class="bg-{{ $item['reason']->color() }}-500 h-2 rounded-full transition-all duration-300" style="width: {{ $item['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Risk Factors & Social Indicators Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
                    <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
                        <span>🛡️</span>
                        <span>{{ __('مؤشرات المتابعة الاجتماعية والصحية والتعلمية بالمؤسسة') }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('حالات اليتم (أب / أم / كلاهما)') }}</div>
                            <div class="text-xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $kpis['orphan_count'] }}</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('طلاق / انفصال الوالدين') }}</div>
                            <div class="text-xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $kpis['divorced_count'] }}</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('مستفيدو المنحة المدرسية') }}</div>
                            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $kpis['social_grant_count'] }}</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('تلاميذ يعانون أمراضاً مزمنة') }}</div>
                            <div class="text-xl font-black text-red-600 dark:text-red-400 mt-1">{{ $kpis['chronic_count'] }}</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('إعفاءات رسمية من التربية البدنية') }}</div>
                            <div class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $kpis['pe_exemption_count'] }}</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('صعوبات تعلم مشخصة') }}</div>
                            <div class="text-xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $kpis['difficulties_count'] }}</div>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed pt-2 border-t border-gray-200 dark:border-gray-700">
                        {{ __('تخضع هذه البيانات للسرية المهنية المطلقة وتستغل حصرياً من طرف مستشار التوجيه والإرشاد المدرسي والمدير لتشخيص الحالات وتقديم الدعم البيداغوجي والنفسي المناسب.') }}
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- MODAL 1: EDIT PROFILE MODAL --}}
    @if($showProfileModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl max-w-2xl w-full p-6 space-y-5 text-right">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="font-bold text-base text-gray-900 dark:text-white flex items-center gap-2">
                        <span>✏️</span>
                        <span>{{ __('تعديل وتحديث الملف النفسي والتربوي للتلميذ') }}</span>
                    </h3>
                    <button wire:click="$set('showProfileModal', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white text-lg">✕</button>
                </div>

                <form wire:submit="saveProfile" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الوضعية العائلية:') }}</label>
                            <select wire:model="familyStatus" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\FamilyStatus::cases() as $fs)
                                    <option value="{{ $fs->value }}">{{ $fs->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('فصيلة الدم:') }}</label>
                            <select wire:model="bloodType" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                <option value="">{{ __('غير مصرح / غير معروفة') }}</option>
                                @foreach(['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'] as $bt)
                                    <option value="{{ $bt }}">{{ $bt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد الإخوة:') }}</label>
                            <input wire:model="siblingsCount" type="number" min="0" max="20" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('رتبة التلميذ بين إخوته:') }}</label>
                            <input wire:model="birthOrder" type="number" min="1" max="20" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('مهنة الأب:') }}</label>
                            <input wire:model="fatherJob" type="text" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('مهنة الأم:') }}</label>
                            <input wire:model="motherJob" type="text" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('المدرسة الابتدائية الأصلية:') }}</label>
                            <input wire:model="primarySchoolName" type="text" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('سنة التخرج من الابتدائي:') }}</label>
                            <input wire:model="primaryGraduationYear" type="text" placeholder="2022" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد سنوات الإعادة والتكرار:') }}</label>
                            <input wire:model="repeatedYearsCount" type="number" min="0" max="10" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الطموح المهني للتلميذ:') }}</label>
                            <input wire:model="careerAspirations" type="text" placeholder="{{ __('طبيب، مهندس، أستاذ...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-1 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200 font-bold">
                            <input wire:model="socialGrant" type="checkbox" class="rounded border-gray-300 text-primary-600" />
                            <span>{{ __('مستفيد من المنحة المدرسية الخاصة (3000 دج / 5000 دج)') }}</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200 font-bold">
                            <input wire:model="peExemption" type="checkbox" class="rounded border-gray-300 text-primary-600" />
                            <span>{{ __('معفى رسمياً من مادة التربية البدنية والرياضية') }}</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200 font-bold">
                            <input wire:model="hasPhysicalDisability" type="checkbox" class="rounded border-gray-300 text-primary-600" />
                            <span>{{ __('يعاني من إعاقة حركية أو حسية') }}</span>
                        </label>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الأمراض المزمنة (مفصولة بفواصل):') }}</label>
                            <input wire:model="chronicConditionsText" type="text" placeholder="{{ __('ربو، حساسية، سكري، ضعف بصر...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('صعوبات التعلم المشخصة (مفصولة بفواصل):') }}</label>
                            <input wire:model="learningDifficultiesText" type="text" placeholder="{{ __('صعوبات قراءة، تعثر في الحساب، تشتت انتباه...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('السلوك والسمات الشخصية (مفصولة بفواصل):') }}</label>
                            <input wire:model="behaviorTendenciesText" type="text" placeholder="{{ __('هادئ، منتبه، خجول، نشط، قيادي...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الميول والهوايات والاهتمامات (مفصولة بفواصل):') }}</label>
                            <input wire:model="interestsAndTalentsText" type="text" placeholder="{{ __('الرسم، الرياضة، المعلوماتية، المطالعة...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات عامة لمستشار التوجيه:') }}</label>
                            <textarea wire:model="counselorGeneralNotes" rows="2" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="$set('showProfileModal', false)" type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">
                            {{ __('إلغاء') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition">
                            {{ __('حفظ التعديلات') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL 2: NEW COUNSELING SESSION MODAL --}}
    @if($showSessionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl max-w-xl w-full p-6 space-y-4 text-right">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="font-bold text-base text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🗣️</span>
                        <span>{{ __('تسجيل مقابلة إرشادية فردية جديدة') }}</span>
                    </h3>
                    <button wire:click="$set('showSessionModal', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white text-lg">✕</button>
                </div>

                <form wire:submit="saveSession" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ المعني:') }}</label>
                        <select wire:model="sessionStudentId" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                            <option value="">{{ __('اختر التلميذ...') }}</option>
                            @foreach($students as $st)
                                <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name }})</option>
                            @endforeach
                        </select>
                        @error('sessionStudentId') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ المقابلة:') }}</label>
                            <input wire:model="sessionDate" type="date" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('المستهدف بالمقابلة:') }}</label>
                            <select wire:model="sessionIntervieweeType" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                <option value="student_only">{{ __('التلميذ بمفرده') }}</option>
                                <option value="guardian_only">{{ __('ولي الأمر بمفرده') }}</option>
                                <option value="student_and_guardian">{{ __('التلميذ بحضور الولي') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('سبب / داعي المقابلة:') }}</label>
                            <select wire:model="sessionReason" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\CounselingSessionReason::cases() as $r)
                                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('حالة المتابعة:') }}</label>
                            <select wire:model="sessionStatus" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\CounselingSessionStatus::cases() as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('وقائع وملاحظات المقابلة:') }}</label>
                        <textarea wire:model="sessionObservations" rows="3" placeholder="{{ __('تدوين حيثيات المقابلة، شكوى التلميذ أو الولي، السلوك الملاحظ...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"></textarea>
                        @error('sessionObservations') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الخطة الإرشادية والتوصيات والتدابير المتخذة:') }}</label>
                        <textarea wire:model="sessionActionPlan" rows="2" placeholder="{{ __('النصائح المقدمة، تعديل السلوك، إحالة لطبيب، متابعة بيداغوجية...') }}" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ المتابعة القادم (اختياري):') }}</label>
                        <input wire:model="sessionFollowUpDate" type="date" class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="$set('showSessionModal', false)" type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">
                            {{ __('إلغاء') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition">
                            {{ __('تسجيل المقابلة') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</x-filament-panels::page>
