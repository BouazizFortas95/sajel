<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $kpis = $data['kpis'];
        $transfers = $data['transfers'];
        $matriculeData = $data['matriculeData'];
        $levels = $data['levels'];
        $classrooms = $data['classrooms'];
        $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    @endphp

    <div class="space-y-6">

        {{-- Top Navigation & Tabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-white/10 pb-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="$set('activeTab', 'transfers')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'transfers' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>🔄</span>
                    {{ __('سجل الحركية والتحويلات') }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'transfers' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $kpis['total_transfers'] }}
                    </span>
                </button>

                <button
                    wire:click="$set('activeTab', 'matricule')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'matricule' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📜</span>
                    {{ __('سجل القيد العام للتلاميذ') }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'matricule' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $kpis['total_active_students'] }}
                    </span>
                </button>

                <button
                    wire:click="$set('activeTab', 'kpis')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'kpis' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📊</span>
                    {{ __('مؤشرات التدفق والحركية') }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">{{ __('الموسم') }}:</span>
                <span class="text-xs font-mono font-bold px-2.5 py-1 bg-amber-50 text-amber-900 border border-amber-200 rounded-lg dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                    {{ $academicYear }}
                </span>
            </div>
        </div>

        {{-- 4 KPI Ribbon Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('إجمالي حركات التحويل') }}</span>
                    <span class="text-base">📋</span>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                    {{ $kpis['total_transfers'] }}
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ __('مسجلة طيلة الموسم الدراسي الحالي') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span>{{ __('الوافدون الجدد (+)') }}</span>
                    <span class="text-base">📥</span>
                </div>
                <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1">
                    +{{ $kpis['inward_count'] }}
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ __('تلاميذ وافدون من مؤسسات أخرى') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div class="flex items-center justify-between text-xs text-rose-600 dark:text-rose-400 font-semibold">
                    <span>{{ __('المغادرون (-)') }}</span>
                    <span class="text-base">📤</span>
                </div>
                <div class="text-2xl font-black text-rose-700 dark:text-rose-400 mt-1">
                    -{{ $kpis['outward_count'] }}
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ __('تلاميذ محولون إلى مؤسسات أخرى') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div class="flex items-center justify-between text-xs text-blue-600 dark:text-blue-400 font-semibold">
                    <span>{{ __('صافي التدفق المدرسي') }}</span>
                    <span class="text-base">⚖️</span>
                </div>
                <div class="text-2xl font-black {{ $kpis['net_flow'] >= 0 ? 'text-blue-700 dark:text-blue-400' : 'text-rose-700 dark:text-rose-400' }} mt-1">
                    {{ $kpis['net_flow'] > 0 ? '+'.$kpis['net_flow'] : $kpis['net_flow'] }}
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ __('الفارق بين الوافدين والمغادرين') }}
                </p>
            </div>
        </div>

        {{-- TAB 1: TRANSFERS LEDGER --}}
        @if ($activeTab === 'transfers')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                {{-- Filter Bar --}}
                <div class="p-4 border-b border-gray-200 dark:border-white/10 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-gray-50/50 dark:bg-gray-900/20">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <div class="relative w-full sm:w-64">
                            <input
                                wire:model.live.debounce.300ms="searchQuery"
                                type="text"
                                placeholder="{{ __('بحث بالاسم، رقم القيد، المؤسسة...') }}"
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 px-3 py-1.5 focus:ring-primary-500"
                            />
                        </div>

                        <select wire:model.live="selectedType" class="text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 py-1.5">
                            <option value="">{{ __('جميع الأنواع (وافد + مغادر)') }}</option>
                            <option value="inward">{{ __('الوافدون فقط (Inward)') }}</option>
                            <option value="outward">{{ __('المغادرون فقط (Outward)') }}</option>
                        </select>

                        <select wire:model.live="selectedStatus" class="text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 py-1.5">
                            <option value="">{{ __('جميع الوضعيات') }}</option>
                            <option value="approved">{{ __('مصادق عليه') }}</option>
                            <option value="pending">{{ __('قيد الإجراء') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('print.student-transfer.ledger', ['locale' => app()->getLocale(), 'year' => $academicYear, 'type' => $selectedType]) }}"
                            target="_blank"
                            class="px-3 py-1.5 text-xs font-bold text-slate-800 bg-white dark:bg-gray-700 border border-gray-300 dark:border-white/10 rounded-lg hover:bg-gray-50 flex items-center gap-1.5 shadow-sm"
                        >
                            🖨️ {{ __('طباعة السجل (A4 عرضي)') }}
                        </a>
                    </div>
                </div>

                {{-- Transfers Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="p-3 text-start">#</th>
                                <th class="p-3 text-start">{{ __('التلميذ المعني') }}</th>
                                <th class="p-3 text-center">{{ __('النوع') }}</th>
                                <th class="p-3 text-start">{{ __('المؤسسة المقابلة') }}</th>
                                <th class="p-3 text-start">{{ __('السبب والمرجع') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ التحويل') }}</th>
                                <th class="p-3 text-center">{{ __('الملف وبراءة الذمة') }}</th>
                                <th class="p-3 text-center">{{ __('الوضعية') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($transfers as $index => $t)
                                @php
                                    $st = $t->student;
                                    $cls = $st?->group?->classroom;
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                    <td class="p-3 font-mono text-gray-400 text-center">{{ $transfers->firstItem() + $index }}</td>
                                    <td class="p-3">
                                        <div class="font-extrabold text-gray-900 dark:text-white">
                                            {{ $st?->full_name }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                                            <span class="font-mono text-amber-800 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                                                {{ $cls?->name ?? '—' }}
                                            </span>
                                            <span>{{ __('قيد') }}: {{ $st?->matricule_number ?? $st?->registration_number ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $t->isOutward() ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                            {{ $t->transfer_type?->label() }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $t->other_school_name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $t->other_directorate_name }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-xs font-semibold">{{ $t->reason?->label() }}</div>
                                        @if ($t->reference_number)
                                            <div class="text-[10px] text-gray-400 font-mono">{{ __('رخصة') }}: {{ $t->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center font-mono text-xs">
                                        {{ $formatDate($t->transfer_date) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 text-[11px] {{ $t->clearance_attached ? 'text-emerald-700 font-bold' : 'text-gray-400' }}">
                                                {{ $t->clearance_attached ? '✓ '.__('براءة الذمة') : '—' }}
                                            </span>
                                            <div>
                                                @if ($t->school_file_forwarded)
                                                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                        ✓ {{ __('تم إرسال الملف') }}
                                                    </span>
                                                @else
                                                    <button
                                                        wire:click="markFileForwarded({{ $t->id }})"
                                                        type="button"
                                                        class="text-[10px] text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded border border-amber-300 transition cursor-pointer"
                                                        title="{{ __('انقر لتوثيق إرسال الملف') }}"
                                                    >
                                                        📬 {{ __('توثيق إرسال الملف') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $t->isApproved() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ $t->status?->label() }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a
                                                href="{{ route('print.student-transfer.certificate', ['locale' => app()->getLocale(), 'transfer' => $t->id]) }}"
                                                target="_blank"
                                                class="px-2.5 py-1 text-[11px] font-bold text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg border border-primary-200 transition"
                                                title="{{ __('معاينة وطباعة شهادة التحويل') }}"
                                            >
                                                📄 {{ __('الشهادة') }}
                                            </a>
                                            <a
                                                href="{{ route('print.student-transfer.forwarding-slip', ['locale' => app()->getLocale(), 'transfer' => $t->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg border border-slate-300 transition"
                                                title="{{ __('جدول إرسال الملف المدرسي الأصلي') }}"
                                            >
                                                📬
                                            </a>
                                            @if ($t->status === \App\Enums\TransferStatus::Pending)
                                                <button
                                                    wire:click="approveTransfer({{ $t->id }})"
                                                    type="button"
                                                    class="px-2 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-300 cursor-pointer"
                                                    title="{{ __('المصادقة') }}"
                                                >
                                                    ✓
                                                </button>
                                                <button
                                                    wire:click="cancelTransfer({{ $t->id }})"
                                                    type="button"
                                                    class="px-2 py-1 text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded border border-rose-300 cursor-pointer"
                                                    title="{{ __('إلغاء') }}"
                                                >
                                                    ✕
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-500">
                                        {{ __('لا توجد حركات تحويل مسجلة وفق معايير البحث المحددة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($transfers->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-white/10">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 2: GENERAL MATRICULATION REGISTER (سجل القيد العام) --}}
        @if ($activeTab === 'matricule')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-white/10 flex flex-wrap items-center justify-between gap-3 bg-gray-50/50">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            📜 {{ __('سجل القيد العام للتلاميذ (Registre Matricule)') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('السجل القانوني الدائم لتسجيل التلاميذ وتواريخ الدخول والمغادرة') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select wire:model.live="selectedLevelId" class="text-xs rounded-xl border-gray-300 py-1.5">
                            <option value="">{{ __('جميع المستويات') }}</option>
                            @foreach ($levels as $lvl)
                                <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="selectedClassroomId" class="text-xs rounded-xl border-gray-300 py-1.5">
                            <option value="">{{ __('جميع الأقسام') }}</option>
                            @foreach ($classrooms as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px]">
                    <table class="w-full text-start text-xs text-gray-700 dark:text-gray-200 divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-[11px] font-bold text-gray-700 dark:text-gray-200 sticky top-0 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-2.5 text-center w-12">#</th>
                                <th class="p-2.5 text-start w-28">{{ __('رقم القيد (Matricule)') }}</th>
                                <th class="p-2.5 text-start">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-2.5 text-start">{{ __('تاريخ ومكان الازدياد') }}</th>
                                <th class="p-2.5 text-center">{{ __('القسم الحالي') }}</th>
                                <th class="p-2.5 text-center">{{ __('تاريخ الدخول') }}</th>
                                <th class="p-2.5 text-center">{{ __('تاريخ المغادرة') }}</th>
                                <th class="p-2.5 text-center">{{ __('الوضعية الحالية') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($matriculeData['students'] as $idx => $student)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ ! $student->is_active ? 'bg-rose-50/30 dark:bg-rose-950/20' : '' }}">
                                    <td class="p-2 text-center font-mono text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="p-2 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ $student->matricule_number ?? $student->registration_number ?? '—' }}
                                    </td>
                                    <td class="p-2 font-bold text-gray-900 dark:text-white">
                                        {{ $student->full_name }}
                                    </td>
                                    <td class="p-2 text-gray-600 dark:text-gray-300">
                                        {{ $formatDate($student->date_of_birth) }}
                                        @if ($student->place_of_birth)
                                            <span class="text-gray-400 dark:text-gray-500">({{ $student->place_of_birth }})</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-center font-bold text-amber-900 dark:text-amber-300">
                                        {{ $student->group?->classroom?->name ?? '—' }}
                                    </td>
                                    <td class="p-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                        {{ $formatDate($student->enrollment_date) }}
                                    </td>
                                    <td class="p-2 text-center font-mono {{ $student->departure_date ? 'text-rose-700 dark:text-rose-400 font-bold' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $formatDate($student->departure_date) }}
                                    </td>
                                    <td class="p-2 text-center">
                                        @if ($student->is_active)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                ✓ {{ __('متمدرس نظامي') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                🚪 {{ __('مغادر / محول') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-500">
                                        {{ __('لا توجد سجلات تلاميذ مسجلة وفق هذه الفلاتر.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: KPIS & ANALYTICS --}}
        @if ($activeTab === 'kpis')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>📈</span> {{ __('تحليل تدفق التلاميذ والتوازن الإداري') }}
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('التلاميذ المتمدرسون حالياً') }}:</span>
                            <strong class="text-sm font-black text-gray-900 dark:text-white">{{ $kpis['total_active_students'] }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('الوافدون الجدد إلى المؤسسة') }}:</span>
                            <strong class="text-sm font-black text-emerald-600 dark:text-emerald-400">+{{ $kpis['inward_count'] }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('المغادرون إلى مؤسسات أخرى') }}:</span>
                            <strong class="text-sm font-black text-rose-600 dark:text-rose-400">-{{ $kpis['outward_count'] }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-gray-800 dark:text-gray-200 font-bold">{{ __('صافي الأثر على التعداد الإجمالي') }}:</span>
                            <span class="px-2.5 py-1 rounded font-black text-xs {{ $kpis['net_flow'] >= 0 ? 'bg-blue-50 dark:bg-blue-900/40 text-blue-900 dark:text-blue-200' : 'bg-rose-50 dark:bg-rose-900/40 text-rose-900 dark:text-rose-200' }}">
                                {{ $kpis['net_flow'] > 0 ? '+'.$kpis['net_flow'] : $kpis['net_flow'] }} {{ __('تلميذ') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>📬</span> {{ __('متابعة إرسال واستلام الملفات المدرسية الأصلية') }}
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('الملفات التي تم توثيق إرسالها رسمياً') }}:</span>
                            <strong class="text-sm font-black text-emerald-600 dark:text-emerald-400">{{ $kpis['files_forwarded_count'] }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('حركات تحويل قيد الإجراء والانتظار') }}:</span>
                            <strong class="text-sm font-black text-amber-600 dark:text-amber-400">{{ $kpis['pending_count'] }}</strong>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-300 leading-relaxed">
                            💡 {{ __('توصية إدارية: يتعين إرسال الملف المدرسي الأصلي مصحوباً بجدول الإرسال وشهادة براءة الذمة خلال مهلة أقصاها 15 يوماً من استلام رخصة التحويل.') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>

