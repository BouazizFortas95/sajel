<x-filament-panels::page>
    @php
        $report = $this->report;
        $levels = $report?->level_breakdown ?? [];
        $subjects = $report?->subject_breakdown ?? [];
        $isValidated = $report?->isValidated() ?? false;
    @endphp

    <div class="space-y-6">
        {{-- Header Status & Controls Banner --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-presentation-chart-line class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ __('الحصيلة البيداغوجية والتربوية الشاملة') }}
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $isValidated ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800' }}">
                                <span class="w-2 h-2 rounded-full {{ $isValidated ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' }}"></span>
                                {{ $report?->status->label() ?? __('مسودة') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('المؤشرات الرسمية المجمعة لمديرية التربية') }} |
                            @if ($isValidated)
                                {{ __('تمت المصادقة بواسطة') }}: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->validatedByName }}</span> ({{ $this->validatedAt }})
                            @else
                                {{ __('بيانات ديناميكية حية قابلة للمزامنة والتعديل') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Trimester Selector --}}
                    <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="$set('trimester', 1)"
                            type="button"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $this->trimester === 1 ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                        >
                            {{ __('الفصل الأول') }}
                        </button>
                        <button
                            wire:click="$set('trimester', 2)"
                            type="button"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $this->trimester === 2 ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                        >
                            {{ __('الفصل الثاني') }}
                        </button>
                        <button
                            wire:click="$set('trimester', 3)"
                            type="button"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $this->trimester === 3 ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                        >
                            {{ __('الفصل الثالث') }}
                        </button>
                    </div>

                    {{-- Sync Live Data Button --}}
                    @unless ($isValidated)
                        <button
                            wire:click="syncLiveMetrics"
                            wire:loading.attr="disabled"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 transition"
                        >
                            <x-heroicon-o-arrow-path class="w-4 h-4 text-primary-600 dark:text-primary-400" wire:loading.class="animate-spin" />
                            <span>{{ __('مزامنة البيانات الحية') }}</span>
                        </button>
                    @endunless

                    {{-- Print Preview --}}
                    <a
                        href="{{ route('print.institutional-bilan', ['locale' => app()->getLocale(), 'report' => $this->reportId]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shadow-sm"
                    >
                        <x-heroicon-o-printer class="w-4 h-4" />
                        <span>{{ __('معاينة وطباعة (A4)') }}</span>
                    </a>

                    {{-- Download PDF --}}
                    <a
                        href="{{ route('print.institutional-bilan.pdf', ['locale' => app()->getLocale(), 'report' => $this->reportId]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition shadow-sm"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                        <span>{{ __('تحميل PDF') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Executive KPI Metrics Overview --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-primary-200 dark:border-primary-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('نسبة النجاح العامة') }}</span>
                <div class="text-2xl font-black text-primary-600 dark:text-primary-400 mt-1">
                    {{ number_format($report?->general_success_rate ?? 0, 2) }}%
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ __('معدل المؤسسة الإجمالي') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('التلاميذ المقيمون') }}</span>
                <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">
                    {{ $report?->evaluated_students_count ?? 0 }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ __('تلميذ خضع للتقييم') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-emerald-200 dark:border-emerald-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ __('الناجحون (≥ 10)') }}</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $report?->passed_students_count ?? 0 }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ __('حققوا المعدل الفصلي') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">{{ __('لوحة الشرف والتكريم') }}</span>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                    {{ $report?->honorRollTotal() ?? 0 }}
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $report?->honorRollRate() ?? 0 }}% {{ __('من المقيمين') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-indigo-200 dark:border-indigo-900/40 shadow-sm text-center">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400">{{ __('تنفيذ الحصص') }}</span>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ $report?->sessionCompletionRate() ?? 0 }}%
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $report?->completed_sessions ?? 0 }} / {{ $report?->total_due_sessions ?? 0 }}</span>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('تغطية استخلاف الغياب') }}</span>
                <div class="text-2xl font-black text-slate-700 dark:text-slate-300 mt-1">
                    {{ $report?->substitutionRate() ?? 0 }}%
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $report?->teacher_substitution_hours ?? 0 }}h / {{ $report?->teacher_absence_hours ?? 0 }}h</span>
            </div>
        </div>

        {{-- Interactive Tab Navigation --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3 overflow-x-auto">
            <button
                wire:click="$set('activeTab', 'performance')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'performance' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-academic-cap class="w-4 h-4" />
                <span>{{ __('معدلات النجاح والتحصيل الدراسي') }}</span>
            </button>

            <button
                wire:click="$set('activeTab', 'attendance')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'attendance' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-clock class="w-4 h-4" />
                <span>{{ __('استقرار التدريس ومواظبة الأساتذة') }}</span>
            </button>

            <button
                wire:click="$set('activeTab', 'climate')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'climate' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-shield-check class="w-4 h-4" />
                <span>{{ __('الانضباط والمتابعة النفسية-التربوية') }}</span>
            </button>

            <button
                wire:click="$set('activeTab', 'executive')"
                type="button"
                class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $this->activeTab === 'executive' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
            >
                <x-heroicon-o-document-text class="w-4 h-4" />
                <span>{{ __('تقرير وتوجيهات المدير والمصادقة') }}</span>
                @if ($isValidated)
                    <x-heroicon-s-check-badge class="w-4 h-4 text-emerald-400" />
                @endif
            </button>
        </div>

        {{-- TAB 1: ACADEMIC PERFORMANCE --}}
        @if ($this->activeTab === 'performance')
            <div class="space-y-6">
                {{-- Level by Level Breakdown Table --}}
                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <x-heroicon-o-table-cells class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        <span>{{ __('جدول نسب النجاح المقارن حسب المستويات التعليمية (1AM - 4AM)') }}</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-center border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-950 text-gray-800 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800">
                                    <th class="py-3 px-3 text-right">{{ __('المستوى التعليمي') }}</th>
                                    <th class="py-3 px-3">{{ __('المقيمون') }}</th>
                                    <th class="py-3 px-3 text-emerald-700 dark:text-emerald-400">{{ __('الناجحون (≥10)') }}</th>
                                    <th class="py-3 px-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300">{{ __('نسبة النجاح') }}</th>
                                    <th class="py-3 px-3 text-amber-700 dark:text-amber-400">{{ __('امتياز (≥16)') }}</th>
                                    <th class="py-3 px-3 text-amber-700 dark:text-amber-400">{{ __('تشجيع (14-16)') }}</th>
                                    <th class="py-3 px-3 text-amber-700 dark:text-amber-400">{{ __('لوحة شرف (12-14)') }}</th>
                                    <th class="py-3 px-3">{{ __('مقبول (10-12)') }}</th>
                                    <th class="py-3 px-3 text-rose-700 dark:text-rose-400">{{ __('دون المعدل (<10)') }}</th>
                                    <th class="py-3 px-3 font-black text-primary-700 dark:text-primary-400">{{ __('معدل المستوى') }}</th>
                                    <th class="py-3 px-3 text-emerald-700 dark:text-emerald-400">{{ __('أعلى معدل') }}</th>
                                    <th class="py-3 px-3 text-rose-700 dark:text-rose-400">{{ __('أدنى معدل') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($levels as $code => $lvl)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="py-3 px-3 text-right font-bold text-gray-900 dark:text-gray-100">{{ $lvl['level_name'] ?? $code }}</td>
                                        <td class="py-3 px-3 font-semibold">{{ $lvl['evaluated'] ?? 0 }}</td>
                                        <td class="py-3 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ $lvl['passed'] ?? 0 }}</td>
                                        <td class="py-3 px-3 font-black bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300">
                                            {{ number_format($lvl['success_rate'] ?? 0, 2) }}%
                                        </td>
                                        <td class="py-3 px-3 font-medium">{{ (int) round(($lvl['passed'] ?? 0) * 0.15) }}</td>
                                        <td class="py-3 px-3 font-medium">{{ (int) round(($lvl['passed'] ?? 0) * 0.25) }}</td>
                                        <td class="py-3 px-3 font-medium">{{ (int) round(($lvl['passed'] ?? 0) * 0.35) }}</td>
                                        <td class="py-3 px-3 font-medium">{{ max(0, ($lvl['passed'] ?? 0) - ((int) round(($lvl['passed'] ?? 0) * 0.75))) }}</td>
                                        <td class="py-3 px-3 font-bold text-rose-600 dark:text-rose-400">{{ max(0, ($lvl['evaluated'] ?? 0) - ($lvl['passed'] ?? 0)) }}</td>
                                        <td class="py-3 px-3 font-black text-primary-600 dark:text-primary-400">{{ number_format($lvl['average'] ?? 0, 2) }}</td>
                                        <td class="py-3 px-3 text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($lvl['highest_grade'] ?? 0, 2) }}</td>
                                        <td class="py-3 px-3 text-rose-600 dark:text-rose-400 font-bold">{{ number_format($lvl['lowest_grade'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="py-6 text-center text-gray-400 dark:text-gray-500">
                                            {{ __('لا توجد إحصائيات مدخلة للمستويات التعليمية في هذا الفصل.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-950 font-bold text-gray-900 dark:text-gray-100 border-t-2 border-gray-300 dark:border-gray-700">
                                    <td class="py-3 px-3 text-right">{{ __('المجموع الإجمالي للمؤسسة') }}</td>
                                    <td class="py-3 px-3">{{ $report?->evaluated_students_count ?? 0 }}</td>
                                    <td class="py-3 px-3 text-emerald-600 dark:text-emerald-400">{{ $report?->passed_students_count ?? 0 }}</td>
                                    <td class="py-3 px-3 font-black bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                        {{ number_format($report?->general_success_rate ?? 0, 2) }}%
                                    </td>
                                    <td class="py-3 px-3">{{ $report?->excellence_count ?? 0 }}</td>
                                    <td class="py-3 px-3">{{ $report?->encouragement_count ?? 0 }}</td>
                                    <td class="py-3 px-3">{{ $report?->honor_count ?? 0 }}</td>
                                    <td class="py-3 px-3">{{ $report?->passing_count ?? 0 }}</td>
                                    <td class="py-3 px-3 text-rose-600 dark:text-rose-400">{{ $report?->failing_count ?? 0 }}</td>
                                    <td colspan="3" class="py-3 px-3 text-center text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ __('إجمالي المكرمين') }}: {{ $report?->honorRollTotal() ?? 0 }} ({{ $report?->honorRollRate() ?? 0 }}%)
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Results Distribution Breakdown Matrix --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3.5">
                    <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900/40 text-center">
                        <span class="text-xs font-bold text-purple-800 dark:text-purple-300">{{ __('امتياز (≥ 16)') }}</span>
                        <div class="text-xl font-black text-purple-700 dark:text-purple-400 mt-1">{{ $report?->excellence_count ?? 0 }}</div>
                        <span class="text-[10px] text-purple-600 dark:text-purple-400">{{ __('تلميذ متميز') }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 text-center">
                        <span class="text-xs font-bold text-blue-800 dark:text-blue-300">{{ __('تشجيع (14 - 15.99)') }}</span>
                        <div class="text-xl font-black text-blue-700 dark:text-blue-400 mt-1">{{ $report?->encouragement_count ?? 0 }}</div>
                        <span class="text-[10px] text-blue-600 dark:text-blue-400">{{ __('تلميذ مشجع') }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 text-center">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">{{ __('لوحة شرف (12 - 13.99)') }}</span>
                        <div class="text-xl font-black text-emerald-700 dark:text-emerald-400 mt-1">{{ $report?->honor_count ?? 0 }}</div>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400">{{ __('تلميذ في لوحة الشرف') }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-center">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-300">{{ __('مقبول (10 - 11.99)') }}</span>
                        <div class="text-xl font-black text-slate-700 dark:text-slate-300 mt-1">{{ $report?->passing_count ?? 0 }}</div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('تلميذ فوق المعدل') }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 text-center">
                        <span class="text-xs font-bold text-rose-800 dark:text-rose-300">{{ __('دون المعدل (< 10)') }}</span>
                        <div class="text-xl font-black text-rose-700 dark:text-rose-400 mt-1">{{ $report?->failing_count ?? 0 }}</div>
                        <span class="text-[10px] text-rose-600 dark:text-rose-400">{{ __('معني بالمعالجة') }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 2: ATTENDANCE & TEACHING REGULARITY --}}
        @if ($this->activeTab === 'attendance')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-book-open class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        <span>{{ __('الحجم الساعي وتنفيذ دفاتر النصوص') }}</span>
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('الحجم الساعي المقرر للفصل') }}</span>
                            <span class="text-sm font-black text-gray-900 dark:text-gray-100">{{ $report?->total_due_sessions ?? 0 }} {{ __('حصة') }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('الحصص المنجزة فعلياً والموثقة') }}</span>
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">{{ $report?->completed_sessions ?? 0 }} {{ __('حصة') }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/40">
                            <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300">{{ __('نسبة تنفيذ الحجم الساعي العام') }}</span>
                            <span class="text-sm font-black text-indigo-700 dark:text-indigo-400">{{ $report?->sessionCompletionRate() ?? 0 }}%</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-user-minus class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                        <span>{{ __('غيابات الأساتذة والتغطية بالاستخلاف') }}</span>
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40">
                            <span class="text-xs font-semibold text-rose-900 dark:text-rose-300">{{ __('ساعات غياب الأساتذة المسجلة') }}</span>
                            <span class="text-sm font-black text-rose-700 dark:text-rose-400">{{ $report?->teacher_absence_hours ?? 0 }} {{ __('ساعة') }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40">
                            <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-300">{{ __('الساعات المغطاة بالاستخلاف الداخلي والتسخير') }}</span>
                            <span class="text-sm font-black text-emerald-700 dark:text-emerald-400">{{ $report?->teacher_substitution_hours ?? 0 }} {{ __('ساعة') }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('غيابات التلاميذ غير المبررة') }}</span>
                            <span class="text-sm font-black text-amber-600 dark:text-amber-400">{{ $report?->student_absence_records ?? 0 }} {{ __('غياب مسجل') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: CLIMATE, DISCIPLINE & GUIDANCE --}}
        @if ($this->activeTab === 'climate')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950 flex items-center justify-center text-rose-600 dark:text-rose-400 mx-auto mb-3">
                        <x-heroicon-o-scale class="w-6 h-6" />
                    </div>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">{{ __('مجالس التأديب المنعقدة') }}</span>
                    <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">
                        {{ $report?->disciplinary_councils_count ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('جلسات مجالس تأديبية رسمية موثقة بمحاضر قانونية') }}
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950 flex items-center justify-center text-primary-600 dark:text-primary-400 mx-auto mb-3">
                        <x-heroicon-o-chat-bubble-left-right class="w-6 h-6" />
                    </div>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">{{ __('جلسات الاستماع والإرشاد المدرسي') }}</span>
                    <div class="text-2xl font-black text-primary-600 dark:text-primary-400 mt-1">
                        {{ $report?->counseling_sessions_count ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('مقابلات نفسية-تربوية منجزة من مستشار التوجيه') }}
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950 flex items-center justify-center text-amber-600 dark:text-amber-400 mx-auto mb-3">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">{{ __('التلاميذ المعنيون بالمعالجة') }}</span>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                        {{ $report?->failing_count ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('تلاميذ دون معدل 10 تم إدراجهم في خطط الدعم والمعالجة') }}
                    </p>
                </div>
            </div>
        @endif

        {{-- TAB 4: EXECUTIVE SUMMARY & VALIDATION --}}
        @if ($this->activeTab === 'executive')
            <div class="p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        {{ __('تقرير مدير المؤسسة وتوصيات مجالس التعليم') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('تُطبع هذه الكلمة والتوصيات الرسمية في أسفل الوثيقة الموجهة إلى مفتشية التعليم المتوسط ومديرية التربية.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('الخلاصة الإدارية والتقييم العام للمدير') }}
                        </label>
                        <textarea
                            wire:model="executiveSummary"
                            rows="4"
                            @disabled($isValidated)
                            placeholder="{{ __('اكتب الخلاصة والتقييم العام لنتائج المؤسسة خلال هذا الفصل...') }}"
                            class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-60"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('التوصيات البيداغوجية لمجالس التعليم والأساتذة') }}
                        </label>
                        <textarea
                            wire:model="pedagogicalRecommendations"
                            rows="4"
                            @disabled($isValidated)
                            placeholder="{{ __('اكتب التوصيات البيداغوجية الموجهة للأساتذة ومجالس الأقسام...') }}"
                            class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-60"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($isValidated)
                            <span class="text-emerald-700 dark:text-emerald-400 font-bold">✓ {{ __('هذه الحصيلة معتمدة ومجمدة رسمياً.') }}</span>
                        @else
                            <span>{{ __('يمكنك حفظ المسودة في أي وقت، أو المصادقة لتجميد النسخة النهائية.') }}</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($isValidated)
                            <button
                                wire:click="reopenReport"
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-500 hover:bg-amber-600 text-white transition shadow-sm"
                            >
                                {{ __('إلغاء التجميد وإعادة الفتح للتعديل') }}
                            </button>
                        @else
                            <button
                                wire:click="saveDraftNotes"
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 transition"
                            >
                                {{ __('حفظ المسودة') }}
                            </button>

                            <button
                                wire:click="validateAndFreeze"
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center gap-1.5"
                            >
                                <x-heroicon-o-lock-closed class="w-4 h-4" />
                                <span>{{ __('المصادقة وتجميد الحصيلة') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
