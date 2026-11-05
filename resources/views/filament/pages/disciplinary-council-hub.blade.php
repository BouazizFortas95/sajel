<x-filament-panels::page>
    @php
        $records = $this->records;
        $kpis = $this->kpis;
        $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
        $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';
        $isAdmin = auth()->user()?->isAdmin() ?? false;
    @endphp

    <div class="space-y-6">

        {{-- Top Navigation & Tabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-white/10 pb-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="$set('activeTab', 'hearings')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'hearings' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>⚖️</span>
                    {{ __('الجلسات التأديبية والمحاضر') }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'hearings' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $records->count() }}
                    </span>
                </button>

                <button
                    wire:click="$set('activeTab', 'sanctions')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'sanctions' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📋</span>
                    {{ __('سجل العقوبات والقرارات المعتمدة') }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'sanctions' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $records->filter(fn ($r) => $r->sanction !== null)->count() }}
                    </span>
                </button>

                <button
                    wire:click="$set('activeTab', 'analytics')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'analytics' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📊</span>
                    {{ __('المؤشرات والتقويم السلوكي') }}
                </button>
            </div>

            {{-- Quick Links Toolbar --}}
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('print.disciplinary.ledger', ['locale' => app()->getLocale(), 'academic_year' => $academicYear]) }}"
                    target="_blank"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-white/10 shadow-sm flex items-center gap-1.5 transition"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>{{ __('معاينة السجل السنوي') }}</span>
                </a>
                <a
                    href="{{ route('print.disciplinary.ledger.pdf', ['locale' => app()->getLocale(), 'academic_year' => $academicYear]) }}"
                    target="_blank"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-sm flex items-center gap-1.5 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>{{ __('تحميل السجل PDF') }}</span>
                </a>
            </div>
        </div>

        {{-- Filters Section (for Hearings & Sanctions) --}}
        @if($activeTab !== 'analytics')
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('البحث بالتلميذ أو رقم التعريف') }}</label>
                        <input
                            wire:model.live.debounce.300ms="searchQuery"
                            type="text"
                            placeholder="{{ __('اسم، لقب، أو رقم التسجيل...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('درجة المخالفة') }}</label>
                        <select
                            wire:model.live="selectedDegree"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">{{ __('جميع الدرجات') }}</option>
                            @foreach(\App\Enums\InfractionDegree::cases() as $degree)
                                <option value="{{ $degree->value }}">{{ $degree->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('حالة الجلسة') }}</label>
                        <select
                            wire:model.live="selectedStatus"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">{{ __('جميع الحالات') }}</option>
                            @foreach(\App\Enums\DisciplinaryStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('السنة الدراسية') }}</label>
                        <input
                            wire:model.live="academicYear"
                            type="text"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 font-mono"
                        />
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 1: HEARINGS & SESSIONS --}}
        @if($activeTab === 'hearings')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-white/10">
                            <tr>
                                <th class="p-3 font-bold w-12 text-center">#</th>
                                <th class="p-3 font-bold">{{ __('التلميذ / القسم') }}</th>
                                <th class="p-3 font-bold">{{ __('درجة المخالفة والواقعة') }}</th>
                                <th class="p-3 font-bold">{{ __('تاريخ وتوقيت الجلسة') }}</th>
                                <th class="p-3 font-bold text-center">{{ __('استدعاء الولي') }}</th>
                                <th class="p-3 font-bold text-center">{{ __('القرار / العقوبة') }}</th>
                                <th class="p-3 font-bold text-center">{{ __('إشعار الولي') }}</th>
                                <th class="p-3 font-bold text-center">{{ __('الإجراءات والمستندات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse($records as $record)
                                @php
                                    $student = $record->student;
                                    $classroom = $student?->group?->classroom;
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3 text-center font-mono font-bold text-gray-500">
                                        {{ $record->session_number ?? 'CD-'.$record->id }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $student?->full_name ?? '—' }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            <span class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.2 rounded font-semibold">{{ $classroom?->name ?? '—' }}</span>
                                            <span class="font-mono">{{ $student?->registration_number }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 max-w-xs">
                                        <div class="mb-1">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold
                                                {{ $record->degree === \App\Enums\InfractionDegree::ThirdDegree ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' :
                                                   ($record->degree === \App\Enums\InfractionDegree::SecondDegree ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300') }}">
                                                {{ $record->degree?->getLabel() }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2">
                                            {{ $record->incident_description }}
                                        </p>
                                    </td>
                                    <td class="p-3 font-mono text-[11px]">
                                        <div class="font-bold text-gray-800 dark:text-gray-200">{{ $formatDateTime($record->hearing_date) }}</div>
                                        <div class="text-[10px] text-gray-500">{{ __('الحادثة:') }} {{ $formatDate($record->incident_date) }}</div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button
                                            wire:click="toggleSummoned({{ $record->id }})"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold transition {{ $record->guardian_summoned ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-800 dark:bg-gray-700 dark:text-gray-300' }}"
                                            title="{{ __('انقر لتبديل حالة الاستدعاء') }}"
                                        >
                                            <span>{{ $record->guardian_summoned ? '✓ تم الاستدعاء' : 'لم يستدعَ' }}</span>
                                        </button>
                                        @if($record->guardian_summoned_at)
                                            <span class="block text-[9px] text-gray-400 font-mono mt-0.5">{{ $formatDate($record->guardian_summoned_at) }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($record->sanction)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                                {{ in_array($record->sanction->value, ['temporary_exclusion', 'definitive_exclusion']) ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200' }}">
                                                {{ $record->sanction->getLabel() }}
                                            </span>
                                            @if($record->exclusion_days)
                                                <div class="text-[10px] text-rose-600 font-bold mt-0.5">({{ $record->exclusion_days }} {{ __('أيام') }})</div>
                                            @endif
                                        @else
                                            <span class="text-amber-600 font-semibold text-[10px] bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded">
                                                ⏳ {{ __('قيد الجدولة / الانتظار') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <button
                                            wire:click="toggleNotified({{ $record->id }})"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold transition {{ $record->guardian_notified ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-800 dark:bg-gray-700 dark:text-gray-300' }}"
                                            title="{{ __('انقر لتبديل حالة إشعار الولي بالقرار') }}"
                                        >
                                            <span>{{ $record->guardian_notified ? '✓ تم التبليغ' : 'لم يُبلغ' }}</span>
                                        </button>
                                        @if($record->guardian_notified_at)
                                            <span class="block text-[9px] text-gray-400 font-mono mt-0.5">{{ $formatDate($record->guardian_notified_at) }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">
                                            {{-- Summons Print --}}
                                            <a
                                                href="{{ route('print.disciplinary.summons', ['locale' => app()->getLocale(), 'record' => $record->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded transition"
                                                title="{{ __('استدعاء الجلسة') }}"
                                            >
                                                ✉️ {{ __('استدعاء') }}
                                            </a>

                                            {{-- Deliberation Modal Trigger --}}
                                            @if($isAdmin && $record->status === \App\Enums\DisciplinaryStatus::Scheduled)
                                                <button
                                                    wire:click="openDeliberationModal({{ $record->id }})"
                                                    type="button"
                                                    class="px-2 py-1 text-[10px] font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded transition"
                                                >
                                                    ⚖️ {{ __('المداولة') }}
                                                </button>
                                            @endif

                                            {{-- Minutes PV Print --}}
                                            @if($record->status === \App\Enums\DisciplinaryStatus::Deliberated)
                                                <a
                                                    href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $record->id]) }}"
                                                    target="_blank"
                                                    class="px-2 py-1 text-[10px] font-semibold bg-slate-700 hover:bg-slate-800 text-white rounded transition"
                                                    title="{{ __('محضر الجلسة') }}"
                                                >
                                                    📄 {{ __('المحضر') }}
                                                </a>

                                                <a
                                                    href="{{ route('print.disciplinary.notification', ['locale' => app()->getLocale(), 'record' => $record->id]) }}"
                                                    target="_blank"
                                                    class="px-2 py-1 text-[10px] font-semibold bg-rose-100 hover:bg-rose-200 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200 rounded transition"
                                                    title="{{ __('إشعار الولي بالقرار مع حق الطعن') }}"
                                                >
                                                    📢 {{ __('الإشعار') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                        <div class="text-sm font-bold">{{ __('لا توجد ملفات تأديبية مسجلة') }}</div>
                                        <div class="text-xs mt-1">{{ __('يمكن للمدير جدولة جلسة تأديب جديدة بواسطة الزر أعلاه.') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: SANCTIONS LEDGER --}}
        @if($activeTab === 'sanctions')
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @php
                        $sanctionedRecords = $records->filter(fn ($r) => $r->sanction !== null);
                        $exclusions = $sanctionedRecords->filter(fn ($r) => in_array($r->sanction->value, ['temporary_exclusion', 'definitive_exclusion']));
                        $warnings = $sanctionedRecords->filter(fn ($r) => in_array($r->sanction->value, ['warning', 'reprimand']));
                        $restitutions = $sanctionedRecords->filter(fn ($r) => $r->restitution_amount && $r->restitution_amount > 0);
                    @endphp

                    {{-- Exclusions Card --}}
                    <div class="bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 rounded-xl p-4">
                        <div class="flex items-center justify-between pb-2 border-b border-rose-200 dark:border-rose-900/30">
                            <span class="font-bold text-xs text-rose-900 dark:text-rose-300">🛑 {{ __('قرارات الإقصاء (المؤقت والنهائي)') }}</span>
                            <span class="font-mono font-black text-rose-700 text-sm">{{ $exclusions->count() }}</span>
                        </div>
                        <div class="mt-3 space-y-2 max-h-80 overflow-y-auto text-xs">
                            @forelse($exclusions as $ex)
                                <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-rose-100 dark:border-rose-900/20 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $ex->student?->full_name }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ $ex->sanction->getLabel() }} ({{ $ex->exclusion_days }} {{ __('أيام') }})</div>
                                    </div>
                                    <a href="{{ route('print.disciplinary.notification', ['locale' => app()->getLocale(), 'record' => $ex->id]) }}" target="_blank" class="text-rose-700 hover:underline text-[10px] font-bold">
                                        {{ __('الإشعار') }} ↗
                                    </a>
                                </div>
                            @empty
                                <p class="text-[11px] text-gray-400 italic text-center py-4">{{ __('لا توجد قرارات إقصاء مسجلة') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Warnings Card --}}
                    <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 rounded-xl p-4">
                        <div class="flex items-center justify-between pb-2 border-b border-blue-200 dark:border-blue-900/30">
                            <span class="font-bold text-xs text-blue-900 dark:text-blue-300">⚠️ {{ __('عقوبات الإنذار والتوبيخ') }}</span>
                            <span class="font-mono font-black text-blue-700 text-sm">{{ $warnings->count() }}</span>
                        </div>
                        <div class="mt-3 space-y-2 max-h-80 overflow-y-auto text-xs">
                            @forelse($warnings as $w)
                                <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-blue-100 dark:border-blue-900/20 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $w->student?->full_name }}</div>
                                        <div class="text-[10px] text-blue-600 font-semibold">{{ $w->sanction->getLabel() }}</div>
                                    </div>
                                    <a href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $w->id]) }}" target="_blank" class="text-blue-700 hover:underline text-[10px] font-bold">
                                        {{ __('المحضر') }} ↗
                                    </a>
                                </div>
                            @empty
                                <p class="text-[11px] text-gray-400 italic text-center py-4">{{ __('لا توجد إنذارات أو توبيخات') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Restitutions Card --}}
                    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-xl p-4">
                        <div class="flex items-center justify-between pb-2 border-b border-amber-200 dark:border-amber-900/30">
                            <span class="font-bold text-xs text-amber-900 dark:text-amber-300">💰 {{ __('التعويضات المادية عن الإتلاف') }}</span>
                            <span class="font-mono font-black text-amber-700 text-sm">{{ number_format($restitutions->sum('restitution_amount'), 0) }} {{ __('د.ج') }}</span>
                        </div>
                        <div class="mt-3 space-y-2 max-h-80 overflow-y-auto text-xs">
                            @forelse($restitutions as $r)
                                <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-amber-100 dark:border-amber-900/20 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $r->student?->full_name }}</div>
                                        <div class="text-[10px] text-amber-700 font-mono font-bold">{{ number_format($r->restitution_amount, 2) }} DZD</div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 font-mono">{{ $formatDate($r->deliberated_at) }}</span>
                                </div>
                            @empty
                                <p class="text-[11px] text-gray-400 italic text-center py-4">{{ __('لا توجد تعويضات مالية مقررة') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: BEHAVIORAL ANALYTICS & KPIS --}}
        @if($activeTab === 'analytics')
            <div class="space-y-6">
                {{-- KPI Numbers Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-gray-500 block">{{ __('إجمالي الإحالات') }}</span>
                        <span class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ $kpis['total_records'] }}</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-emerald-600 block">{{ __('جلسات مفصول فيها') }}</span>
                        <span class="text-2xl font-black text-emerald-700 font-mono">{{ $kpis['deliberated_count'] }}</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-amber-600 block">{{ __('جلسات قيد النظر') }}</span>
                        <span class="text-2xl font-black text-amber-700 font-mono">{{ $kpis['scheduled_count'] }}</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-rose-600 block">{{ __('قرارات الإقصاء') }}</span>
                        <span class="text-2xl font-black text-rose-700 font-mono">{{ $kpis['temporary_exclusion_count'] + $kpis['definitive_exclusion_count'] }}</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-blue-600 block">{{ __('الإنذارات والتوبيخ') }}</span>
                        <span class="text-2xl font-black text-blue-700 font-mono">{{ $kpis['warning_count'] + $kpis['reprimand_count'] }}</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm text-center">
                        <span class="text-[11px] text-indigo-600 block">{{ __('نسبة إشعار الأولياء') }}</span>
                        <span class="text-2xl font-black text-indigo-700 font-mono">{{ $kpis['guardian_notified_rate'] }}%</span>
                    </div>
                </div>

                {{-- Detailed Distribution Breakdown --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- By Degree --}}
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <span>⚖️</span>
                            {{ __('التوزيع الإحصائي حسب درجة المخالفة التنظيمية') }}
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-blue-700 font-semibold">{{ __('مخالفات الدرجة الأولى') }}</span>
                                    <span class="font-mono font-bold">{{ $kpis['by_degree']['first_degree'] ?? 0 }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $kpis['total_records'] > 0 ? (($kpis['by_degree']['first_degree'] ?? 0) / $kpis['total_records']) * 100 : 0 }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-amber-700 font-semibold">{{ __('مخالفات الدرجة الثانية') }}</span>
                                    <span class="font-mono font-bold">{{ $kpis['by_degree']['second_degree'] ?? 0 }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $kpis['total_records'] > 0 ? (($kpis['by_degree']['second_degree'] ?? 0) / $kpis['total_records']) * 100 : 0 }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-rose-700 font-semibold">{{ __('مخالفات الدرجة الثالثة (الجسيمة)') }}</span>
                                    <span class="font-mono font-bold">{{ $kpis['by_degree']['third_degree'] ?? 0 }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-rose-600 h-2 rounded-full" style="width: {{ $kpis['total_records'] > 0 ? (($kpis['by_degree']['third_degree'] ?? 0) / $kpis['total_records']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- By Sanction --}}
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <span>📜</span>
                            {{ __('توزيع القرارات والعقوبات المصادق عليها') }}
                        </h3>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('إنذار كتابي') }}:</span>
                                <strong class="font-mono">{{ $kpis['by_sanction']['warning'] ?? 0 }}</strong>
                            </div>
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('توبيخ رسمي') }}:</span>
                                <strong class="font-mono">{{ $kpis['by_sanction']['reprimand'] ?? 0 }}</strong>
                            </div>
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('إقصاء مؤقت (1-8 أيام)') }}:</span>
                                <strong class="font-mono text-rose-700">{{ $kpis['by_sanction']['temporary_exclusion'] ?? 0 }}</strong>
                            </div>
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('إقصاء نهائي / تحويل') }}:</span>
                                <strong class="font-mono text-rose-800">{{ $kpis['by_sanction']['definitive_exclusion'] ?? 0 }}</strong>
                            </div>
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('أنشطة ذات نفع مدرسي') }}:</span>
                                <strong class="font-mono">{{ $kpis['by_sanction']['community_service'] ?? 0 }}</strong>
                            </div>
                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-white/5 flex justify-between">
                                <span class="text-gray-600">{{ __('تبرئة / حفظ الملف') }}:</span>
                                <strong class="font-mono text-emerald-700">{{ $kpis['by_sanction']['acquittal'] ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

