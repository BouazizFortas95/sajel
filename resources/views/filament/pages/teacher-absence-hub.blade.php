<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $absences = $viewData['absences'];
        $affectedSessions = $viewData['affectedSessions'];
        $kpis = $viewData['kpis'];
        $teacherOptions = $viewData['teacherOptions'];
        $isAdmin = $viewData['isAdmin'];
    @endphp

    <div class="space-y-6">

        {{-- 1. KPI Summary Cards Strip --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('غيابات اليوم النشطة') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ $kpis['active_today'] }}</span>
                    <span class="text-xs text-gray-400">/ {{ $kpis['total_absences'] }} {{ __('إجمالي') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('إجمالي الحصص المتأثرة') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ $kpis['total_sessions'] }}</span>
                    <span class="text-xs text-rose-500 font-bold font-mono">{{ $kpis['total_missed_hours'] }} {{ __('سا') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('الحصص المعوضة (مستخلفة)') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $kpis['covered_sessions'] }}</span>
                    <span class="text-xs text-emerald-600 font-bold">✓ {{ __('مغطاة') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('الحصص الشاغرة (بحاجة لبديل)') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono">{{ $kpis['uncovered_sessions'] }}</span>
                    @if ($kpis['uncovered_sessions'] > 0)
                        <span class="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-bold">⚠️ {{ __('شاغر') }}</span>
                    @else
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">✓ {{ __('مكتمل') }}</span>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('نسبة التغطية والاستمرارية') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-primary-600 dark:text-primary-400 font-mono">{{ $kpis['coverage_rate'] }}%</span>
                    <span class="text-xs text-gray-400">{{ $kpis['distinct_substitutes'] }} {{ __('أساتذة مساهمون') }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Interactive Controls & Tab Navigation Bar --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">

                {{-- Tabs Switcher --}}
                <div class="flex items-center gap-2 border-b md:border-b-0 border-gray-200 dark:border-gray-700 pb-2 md:pb-0">
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'radar')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'radar' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📡</span> {{ __('رادار الغيابات الحالية') }} ({{ $absences->count() }})
                    </button>

                    <button
                        type="button"
                        wire:click="$set('activeTab', 'sessions')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'sessions' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>⏱️</span> {{ __('الحصص المتأثرة والاستخلاف') }} ({{ $affectedSessions->count() }})
                    </button>

                    <button
                        type="button"
                        wire:click="$set('activeTab', 'ledger')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'ledger' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📑</span> {{ __('الكشف الشهري والأرشيف') }}
                    </button>
                </div>

                {{-- Filter Selectors --}}
                <div class="flex flex-wrap items-center gap-3">
                    @if ($isAdmin)
                        <div class="w-48">
                            <select
                                wire:model.live="selectedTeacherId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5"
                            >
                                <option value="">{{ __('كافة الأساتذة') }}</option>
                                @foreach ($teacherOptions as $tId => $tName)
                                    <option value="{{ $tId }}">{{ $tName }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="w-36">
                        <select
                            wire:model.live="statusFilter"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5"
                        >
                            <option value="all">{{ __('كافة الحالات') }}</option>
                            <option value="pending">{{ __('قيد المعالجة') }}</option>
                            <option value="covered">{{ __('مغطى بالاستخلاف') }}</option>
                            <option value="partially_covered">{{ __('مغطى جزئياً') }}</option>
                            <option value="uncovered">{{ __('شاغر غير مغطى') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <select wire:model.live="month" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>

                        <select wire:model.live="year" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5 font-mono">
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- 3. TAB CONTENT: RADAR (سجل الغيابات) --}}
        @if ($activeTab === 'radar')
            @if ($absences->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($absences as $absence)
                        @php
                            $sess = app(\App\Services\TeacherAbsenceService::class)->getAffectedSessions($absence);
                            $coveredCount = $sess->where('is_covered', true)->count();
                            $totalCount = $sess->count();
                            $status = $absence->status;
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                                        {{ $absence->teacher?->name }}
                                    </h3>
                                    <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">
                                        {{ $absence->teacher?->primarySubject?->name ?? __('أستاذ التعليم المتوسط') }}
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $absence->reason->color() === 'danger' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $absence->reason->label() }}
                                </span>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">{{ __('فترة الغياب') }}:</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200 font-mono">
                                        {{ $absence->starts_at?->format('d/m/Y') }} ← {{ $absence->ends_at?->format('d/m/Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">{{ __('المدة') }}:</span>
                                    <span class="font-bold text-gray-700 dark:text-gray-300">
                                        {{ $absence->durationLabel() }}
                                    </span>
                                </div>
                                @if ($absence->reference_number)
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">{{ __('رقم المرجع') }}:</span>
                                        <span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ $absence->reference_number }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Coverage Progress Bar --}}
                            <div>
                                <div class="flex justify-between text-xs font-semibold mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('تغطية الحصص بالاستخلاف') }}:</span>
                                    <span class="font-mono {{ $coveredCount === $totalCount ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $coveredCount }} / {{ $totalCount }} {{ __('حصة') }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                    @php $pct = $totalCount > 0 ? round(($coveredCount / $totalCount) * 100) : 100; @endphp
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>

                            {{-- Administrative Legal Protocols Strip (استئناف العمل والخصم من الراتب) --}}
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                <div class="flex flex-wrap items-center justify-between gap-1.5 text-[11px]">
                                    {{-- Work Resumption Status --}}
                                    @if ($absence->hasResumed())
                                        <div class="flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded text-emerald-800 dark:text-emerald-300 font-semibold">
                                            <span>✓ {{ __('استأنف') }}: {{ $absence->resumed_at?->format('d/m/Y') }}</span>
                                            <a
                                                href="{{ route('print.teacher-absence.resumption-pv', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}"
                                                target="_blank"
                                                class="text-emerald-700 dark:text-emerald-400 hover:underline font-bold"
                                                title="{{ __('معاينة محضر استئناف العمل') }}"
                                            >
                                                📄 {{ __('المحضر') }}
                                            </a>
                                            <a
                                                href="{{ route('print.teacher-absence.resumption-pv.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}"
                                                target="_blank"
                                                class="text-[9px] bg-emerald-600 text-white px-1 py-0.2 rounded font-bold"
                                                title="{{ __('تحميل PDF') }}"
                                            >
                                                PDF
                                            </a>
                                        </div>
                                    @elseif ($isAdmin)
                                        <button
                                            type="button"
                                            wire:click="openResumptionModal({{ $absence->id }})"
                                            class="px-2.5 py-1 text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded shadow-sm transition flex items-center gap-1 cursor-pointer"
                                            title="{{ __('توثيق عودة الأستاذ للعمل وتوليد المحضر الرسمي') }}"
                                        >
                                            <span>📋</span> {{ __('تسجيل استئناف العمل') }}
                                        </button>
                                    @else
                                        <span class="text-[10px] text-gray-400 italic">{{ __('لم يُسجل استئناف العمل بعد') }}</span>
                                    @endif

                                    {{-- Salary Deduction Notice Status --}}
                                    @if ($absence->hasSalaryDeduction())
                                        <div class="flex items-center gap-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 px-2 py-0.5 rounded text-rose-800 dark:text-rose-300 font-semibold">
                                            <span>⚠️ {{ __('خصم') }}: {{ $absence->deduction_days }} {{ __('يوم') }}</span>
                                            <a
                                                href="{{ route('print.teacher-absence.deduction-notice', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}"
                                                target="_blank"
                                                class="text-rose-700 dark:text-rose-400 hover:underline font-bold"
                                                title="{{ __('معاينة إشعار الخصم من الراتب') }}"
                                            >
                                                📄 {{ __('الإشعار') }}
                                            </a>
                                            <a
                                                href="{{ route('print.teacher-absence.deduction-notice.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}"
                                                target="_blank"
                                                class="text-[9px] bg-rose-600 text-white px-1 py-0.2 rounded font-bold"
                                                title="{{ __('تحميل PDF') }}"
                                            >
                                                PDF
                                            </a>
                                        </div>
                                    @elseif ($isAdmin)
                                        <button
                                            type="button"
                                            wire:click="openDeductionModal({{ $absence->id }})"
                                            class="px-2 py-0.8 text-[10.5px] font-bold text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950 rounded border border-rose-300 transition flex items-center gap-1 cursor-pointer"
                                            title="{{ __('إصدار إشعار الخصم من الراتب لغياب غير مبرر') }}"
                                        >
                                            <span>⚠️</span> {{ __('إشعار خصم') }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($absence->notes)
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic line-clamp-2">
                                    "{{ $absence->notes }}"
                                </p>
                            @endif

                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                                <span class="text-[10px] text-gray-400">
                                    {{ __('سُجل بواسطة') }}: {{ $absence->recordedBy?->name ?? __('الإدارة') }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="$set('activeTab', 'sessions')"
                                    class="text-primary-600 dark:text-primary-400 hover:underline font-bold text-xs"
                                >
                                    {{ __('عرض الحصص المتأثرة') }} ←
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center text-gray-500">
                    <span class="text-4xl">🎉</span>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-2">{{ __('لا توجد غيابات مسجلة للأساتذة تطابق الفلاتر المحددة') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('كافة الحصص والأنشطة البيداغوجية تسير بانتظام وفق جدول التوقيت الرسمي.') }}</p>
                </div>
            @endif

        {{-- 4. TAB CONTENT: AFFECTED SESSIONS & SUBSTITUTION (الحصص المتأثرة والاستخلاف الفوري) --}}
        @elseif ($activeTab === 'sessions')
            @if ($affectedSessions->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($affectedSessions as $session)
                        @php
                            $log = $session['daily_log'];
                            $substitute = $session['substitute_teacher'];
                            $absence = $session['parent_absence'];
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">

                            {{-- Left: Date & Time Info --}}
                            <div class="flex items-center gap-3">
                                <div class="bg-gray-100 dark:bg-gray-800 p-2.5 rounded-lg text-center min-w-[70px]">
                                    <span class="text-xs font-black text-gray-900 dark:text-white block">{{ $session['day_name'] }}</span>
                                    <span class="text-[10px] text-gray-500 font-mono">{{ \Carbon\Carbon::parse($session['date'])->format('d/m') }}</span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-extrabold bg-amber-50 dark:bg-amber-900/40 text-amber-900 dark:text-amber-300 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-700">
                                            {{ $session['classroom']?->name }} @if ($session['group']) ({{ $session['group']->name }}) @endif
                                        </span>
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                            {{ $session['subject']?->name }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-mono">
                                            {{ \Carbon\Carbon::parse($session['start_time'])->format('H:i') }} - {{ \Carbon\Carbon::parse($session['end_time'])->format('H:i') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('الأستاذ المتغيب:') }} <strong class="text-gray-700 dark:text-gray-300">{{ $absence->teacher?->name }}</strong>
                                        <span class="text-[10px] text-amber-700 bg-amber-50 dark:bg-amber-900/40 px-1 rounded font-medium">({{ $absence->reason->label() }})</span>
                                    </p>
                                </div>
                            </div>

                            {{-- Middle: Substitute Status & Name --}}
                            <div class="flex items-center gap-3">
                                @if ($session['is_covered'] && $substitute)
                                    <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 px-3 py-1.5 rounded-lg text-xs">
                                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 block">{{ __('الأستاذ المستخلف المكلف:') }}</span>
                                        <span class="font-extrabold text-emerald-950 dark:text-emerald-200 text-sm">{{ $substitute->name }}</span>
                                        <span class="text-[10px] text-emerald-600 block">({{ $substitute->primarySubject?->name ?? __('أستاذ مستخلف') }})</span>
                                    </div>
                                @else
                                    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 px-3 py-1.5 rounded-lg text-xs">
                                        <span class="text-[10px] font-bold text-rose-700 dark:text-rose-400 block">⚠️ {{ __('حالة الحصة:') }}</span>
                                        <span class="font-black text-rose-900 dark:text-rose-200">{{ __('شاغرة — بحاجة لأستاذ بديل') }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Right: Actions --}}
                            <div class="flex items-center gap-2">
                                @if ($session['is_covered'] && $log)
                                    {{-- Print Mandate Action --}}
                                    <a
                                        href="{{ route('print.teacher-absence.mandate', ['locale' => app()->getLocale(), 'absence' => $absence->id, 'log' => $log->id]) }}"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-lg transition flex items-center gap-1"
                                    >
                                        <span>🖨️</span> {{ __('استدعاء الاستخلاف') }}
                                    </a>

                                    <a
                                        href="{{ route('print.teacher-absence.mandate.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id, 'log' => $log->id]) }}"
                                        target="_blank"
                                        class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-lg transition"
                                        title="{{ __('تحميل استدعاء الاستخلاف (PDF)') }}"
                                    >
                                        PDF
                                    </a>

                                    {{-- Requisition Order Action (قرار تسخير إداري) --}}
                                    <a
                                        href="{{ route('print.teacher-absence.requisition-order', ['locale' => app()->getLocale(), 'absence' => $absence->id, 'log' => $log->id]) }}"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-bold rounded-lg transition flex items-center gap-1"
                                        title="{{ __('معاينة وطباعة قرار التسخير الإداري الرسمي (القانون 08-04)') }}"
                                    >
                                        <span>📜</span> {{ __('تسخير إداري') }}
                                    </a>

                                    <a
                                        href="{{ route('print.teacher-absence.requisition-order.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id, 'log' => $log->id]) }}"
                                        target="_blank"
                                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition"
                                        title="{{ __('تحميل قرار التسخير الإداري (PDF)') }}"
                                    >
                                        PDF
                                    </a>

                                    @if ($isAdmin)
                                        <button
                                            type="button"
                                            wire:click="unassignSubstitute({{ $log->id }})"
                                            wire:confirm="{{ __('هل أنت متأكد من إلغاء تكليف الأستاذ المستخلف لهذه الحصة؟') }}"
                                            class="px-2 py-1.5 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 rounded transition font-bold"
                                            title="{{ __('إلغاء التكليف') }}"
                                        >
                                            ✕
                                        </button>
                                    @endif
                                @elseif ($isAdmin)
                                    {{-- Assign Substitute Button --}}
                                    <button
                                        type="button"
                                        wire:click="openAssignModal({{ $session['schedule_id'] }}, '{{ $session['date'] }}')"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-1.5"
                                    >
                                        <span>➕</span> {{ __('تكليف أستاذ مستخلف') }}
                                    </button>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center text-gray-500">
                    <span class="text-4xl">🎯</span>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-2">{{ __('لا توجد حصص متأثرة بالغياب حالياً') }}</p>
                </div>
            @endif

        {{-- 5. TAB CONTENT: MONTHLY LEDGER & ARCHIVE (الكشف الشهري والأرشيف) --}}
        @elseif ($activeTab === 'ledger')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 gap-4">
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">{{ __('الكشف الشهري الإجمالي لغيابات الأساتذة والاستخلاف') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('السجل الإداري الرسمي المعد للإرسال لمديرية التربية (مصلحة الموظفين والتفتيش)') }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('print.teacher-absence.monthly-ledger', ['locale' => app()->getLocale(), 'year' => $year, 'month' => $month]) }}"
                            target="_blank"
                            class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-bold text-xs rounded-lg flex items-center gap-1.5 transition"
                        >
                            <span>🖨️</span> {{ __('عرض وطباعة الكشف كاملاً (A4 أفقي)') }}
                        </a>

                        <a
                            href="{{ route('print.teacher-absence.monthly-ledger.pdf', ['locale' => app()->getLocale(), 'year' => $year, 'month' => $month]) }}"
                            target="_blank"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition"
                        >
                            <span>📥</span> {{ __('تحميل الكشف الرسمي (PDF)') }}
                        </a>
                    </div>
                </div>

                {{-- Summary Table Preview --}}
                @php
                    $ledgerData = app(\App\Services\TeacherAbsenceService::class)->assembleMonthlyLedger($year, $month);
                @endphp

                @if ($ledgerData['records']->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                    <th class="p-2.5">#</th>
                                    <th class="p-2.5">{{ __('الأستاذ المتغيب') }}</th>
                                    <th class="p-2.5">{{ __('المادة') }}</th>
                                    <th class="p-2.5">{{ __('فترة الغياب') }}</th>
                                    <th class="p-2.5">{{ __('طبيعة الغياب / المرجع') }}</th>
                                    <th class="p-2.5">{{ __('الحصص') }}</th>
                                    <th class="p-2.5">{{ __('الضائعة') }}</th>
                                    <th class="p-2.5">{{ __('المعوضة') }}</th>
                                    <th class="p-2.5">{{ __('الأساتذة المستخلفون') }}</th>
                                    <th class="p-2.5">{{ __('الوضعية') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($ledgerData['records'] as $idx => $r)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="p-2.5 font-bold">{{ $idx + 1 }}</td>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $r['teacher']?->name }}</td>
                                        <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $r['subject'] }}</td>
                                        <td class="p-2.5 font-mono text-[11px]">{{ $r['starts_at']?->format('d/m/Y') }} ← {{ $r['ends_at']?->format('d/m/Y') }} ({{ $r['duration_days'] }} ي)</td>
                                        <td class="p-2.5">{{ $r['reason_label'] }}</td>
                                        <td class="p-2.5 font-bold">{{ $r['affected_sessions_count'] }}</td>
                                        <td class="p-2.5 font-mono text-rose-600 font-bold">{{ $r['hours_missed'] }} سا</td>
                                        <td class="p-2.5 font-mono text-emerald-600 font-bold">{{ $r['hours_covered'] }} سا</td>
                                        <td class="p-2.5 font-medium">{{ $r['substitutes_names'] }}</td>
                                        <td class="p-2.5">
                                            @if ($r['status']->value === 'covered')
                                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">✓ {{ __('مغطى') }}</span>
                                            @elseif ($r['status']->value === 'partially_covered')
                                                <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded">⚡ {{ __('جزئي') }}</span>
                                            @else
                                                <span class="bg-rose-100 text-rose-800 text-[10px] font-bold px-2 py-0.5 rounded">⚠️ {{ __('شاغر') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 text-xs">
                        {{ __('لم تسجل أية غيابات للأساتذة في هذا الشهر.') }}
                    </div>
                @endif
            </div>
        @endif

    </div>

    {{-- 6. MODAL DIALOG: ASSIGN SUBSTITUTE TEACHER --}}
    @if ($isAssignModalOpen && $this->activeSchedule)
        @php
            $schedule = $this->activeSchedule;
            $candidates = $this->candidateSubstitutes;
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">

                {{-- Modal Header --}}
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">
                            {{ __('تكليف أستاذ بمهمة استخلاف بيداغوجي') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $schedule->resolvedClassroom()?->name }} — {{ $schedule->subject?->name }} ({{ \Carbon\Carbon::parse($assigningDate)->translatedFormat('l d/m/Y') }})
                        </p>
                    </div>
                    <button type="button" wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                {{-- Target Lesson Summary Chip --}}
                <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 p-3 rounded-lg text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('التوقيت المقرر:') }}</span>
                        <strong class="font-mono text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('الأستاذ الأصلي المتغيب:') }}</span>
                        <strong class="text-gray-900 dark:text-white">{{ $schedule->user?->name }}</strong>
                    </div>
                </div>

                {{-- Candidate Teachers List --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block">
                        {{ __('اختر الأستاذ المستخلف من المتاحين حالياً:') }}
                    </label>

                    @if ($candidates->isNotEmpty())
                        <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
                            @foreach ($candidates as $cand)
                                @php
                                    $t = $cand['teacher'];
                                    $isSelected = $selectedSubstituteId === $t->id;
                                @endphp
                                <div
                                    wire:click="$set('selectedSubstituteId', {{ $t->id }})"
                                    class="cursor-pointer p-3 rounded-xl border text-xs transition flex items-center justify-between {{ $isSelected ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/40 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <input
                                            type="radio"
                                            name="selectedSubstitute"
                                            value="{{ $t->id }}"
                                            {{ $isSelected ? 'checked' : '' }}
                                            class="text-primary-600 focus:ring-primary-500"
                                        />
                                        <div>
                                            <strong class="text-sm font-bold text-gray-900 dark:text-white block">{{ $t->name }}</strong>
                                            <span class="text-[11px] text-gray-500">{{ $t->primarySubject?->name ?? __('أستاذ') }}</span>
                                        </div>
                                    </div>

                                    <div>
                                        @if ($cand['priority'] === 1)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                🌟 {{ __('نفس المادة') }}
                                            </span>
                                        @elseif ($cand['priority'] === 2)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                ⭐ {{ __('تخصص ثانوي') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                {{ __('مداومة تربوية') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center text-rose-500 bg-rose-50 dark:bg-rose-950/30 rounded-xl text-xs font-semibold">
                            ⚠️ {{ __('لا يوجد أساتذة متاحون بدون تضارب في هذا التوقيت بالتحديد.') }}
                        </div>
                    @endif
                </div>

                {{-- Pedagogical Instructions Field --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block">
                        {{ __('توجيهات أو مهام خاصة للأستاذ المستخلف (اختياري):') }}
                    </label>
                    <textarea
                        wire:model="substituteInstructions"
                        rows="2"
                        placeholder="{{ __('مثال: استكمال حل التمرين رقم 4 أو تسيير مراجعة جماعية...') }}"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    ></textarea>
                </div>

                {{-- Modal Footer Actions --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeAssignModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>

                    <button
                        type="button"
                        wire:click="confirmAssignSubstitute"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm transition"
                    >
                        {{ __('تأكيد التكليف بالاستخلاف') }}
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- MODAL: RECORD WORK RESUMPTION (تسجيل محضر استئناف العمل الرسمي) --}}
    @if ($isResumptionModalOpen)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
                
                {{-- Modal Header --}}
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📝</span> {{ __('تسجيل محضر استئناف العمل الرسمي') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('توثيق عودة الأستاذ لممارسة مهامه وإثبات تاريخ وتوقيت الاستئناف القانوني') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeResumptionModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                {{-- Form fields --}}
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('تاريخ وساعة استئناف العمل:') }}
                        </label>
                        <input
                            type="datetime-local"
                            wire:model="resumptionDate"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('رقم وتأشيرة المحضر (PV):') }}
                        </label>
                        <input
                            type="text"
                            wire:model="resumptionPvNumber"
                            placeholder="PV-REP-..."
                            class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('ملاحظات أو ظروف الاستئناف (اختياري):') }}
                        </label>
                        <textarea
                            wire:model="resumptionNotes"
                            rows="2"
                            placeholder="{{ __('مثال: استأنف الأستاذ عمله في التوقيت الصباحي بعد انقضاء الإجازة...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeResumptionModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>

                    <button
                        type="button"
                        wire:click="confirmResumption"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm transition"
                    >
                        {{ __('اعتماد وحفظ محضر الاستئناف') }}
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- MODAL: SALARY DEDUCTION NOTICE (إشعار الخصم من الراتب) --}}
    @if ($isDeductionModalOpen)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
                
                {{-- Modal Header --}}
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-rose-600 dark:text-rose-400 flex items-center gap-2">
                            <span>⚖️</span> {{ __('إصدار إشعار الخصم من الراتب') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('طبقا للقانون 90-21 والأمر 06-03 (خصم 1/30 من الراتب الأساسي عن كل يوم غياب غير مبرر)') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeDeductionModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                {{-- Form fields --}}
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('عدد أيام الغياب المقتطعة:') }}
                        </label>
                        <input
                            type="number"
                            min="1"
                            wire:model="deductionDays"
                            class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('رقم مرجع الإشعار المالي:') }}
                        </label>
                        <input
                            type="text"
                            wire:model="deductionRefNumber"
                            placeholder="NOTIF-DED-..."
                            class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 p-2.5 rounded-lg text-rose-800 dark:text-rose-300 text-xs">
                        ⚠️ <strong>{{ __('تنبيه قانوني') }}:</strong> {{ __('يتيح هذا الإشعار للموظف حق تقديم طعن مبرر خلال أجل أقصاه 48 ساعة قبل الإحالة لمصلحة الرواتب بمديرية التربية.') }}
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeDeductionModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>

                    <button
                        type="button"
                        wire:click="confirmDeduction"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-sm transition"
                    >
                        {{ __('تأكيد وإصدار إشعار الخصم') }}
                    </button>
                </div>

            </div>
        </div>
    @endif

</x-filament-panels::page>

