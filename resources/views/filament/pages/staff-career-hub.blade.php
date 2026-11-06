<x-filament-panels::page>
    @php
        $evaluations = $this->evaluationsList;
        $advancements = $this->advancementsList;
        $movements = $this->movementsList;
        $profiles = $this->profilesList;

        $totalStaff = \App\Models\User::count();
        $evaluatedCount = $evaluations->count();
        $avgScore = $evaluatedCount > 0 ? round($evaluations->avg('administrative_score'), 2) : 0.00;
        $advCandidatesCount = $advancements->count();
        $movementsCount = $movements->count();
    @endphp

    <div class="space-y-6">

        {{-- Top Control & Header Bar --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 rounded-lg">
                    <x-heroicon-o-identification class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ __('تسيير المسار المهني، التنقيط السنوي، والترقية في الدرجات') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('القانون الأساسي العام للوظيفة العمومية (الأمر 06-03) والمرسوم التنفيذي 08-315 الخاص بعمال التربية') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Academic Year Selector --}}
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-700 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600">
                    <span class="text-xs text-gray-500 dark:text-gray-400">📅 {{ __('الموسم:') }}</span>
                    <select wire:model.live="selectedAcademicYear" class="text-xs bg-transparent border-0 p-0 text-gray-900 dark:text-white font-semibold focus:ring-0">
                        <option value="2025/2026">2025/2026</option>
                        <option value="2026/2027">2026/2027</option>
                        <option value="2024/2025">2024/2025</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <x-filament::button wire:click="openNewEvaluationModal" size="sm" color="primary" icon="heroicon-m-pencil-square">
                    {{ __('إعداد بطاقة تنقيط سنوية') }}
                </x-filament::button>

                <x-filament::button wire:click="syncAdvancementProjections" size="sm" color="amber" icon="heroicon-m-arrow-path">
                    {{ __('ضبط جدول الترقية (CAP)') }}
                </x-filament::button>

                <x-filament::button wire:click="openNewMovementModal" size="sm" color="gray" icon="heroicon-m-arrows-right-left">
                    {{ __('تسجيل حركة موظف') }}
                </x-filament::button>

                <a href="{{ route('print.staff-career.advancement-ledger', ['locale' => app()->getLocale(), 'year' => $this->selectedYear]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                    <x-heroicon-m-printer class="w-4 h-4" />
                    {{ __('معاينة جدول الترقية (A4)') }}
                </a>
            </div>
        </div>

        {{-- 4 Metric Telemetry Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('تعداد الموظفين والأساتذة') }}</span>
                    <x-heroicon-m-users class="w-4 h-4 text-primary-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $totalStaff }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500">
                    {{ __('هيئة التدريس والموظفون الإداريون') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('الملفات المنقطة') }}</span>
                    <x-heroicon-m-clipboard-document-check class="w-4 h-4 text-emerald-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $evaluatedCount }}
                </div>
                <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">
                    {{ __('معدل النقطة الإدارية:') }} <span class="font-bold">{{ $avgScore }} / 20</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('المترشحون للترقية في الدرجة') }}</span>
                    <x-heroicon-m-academic-cap class="w-4 h-4 text-amber-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $advCandidatesCount }}
                </div>
                <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                    {{ __('مستوفو شروط الأقدمية القانونية برسم') }} {{ $this->selectedYear }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('حركات المستخدمين المسجلة') }}</span>
                    <x-heroicon-m-arrow-path-rounded-square class="w-4 h-4 text-indigo-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $movementsCount }}
                </div>
                <div class="mt-1 text-[11px] text-indigo-600 dark:text-indigo-400">
                    {{ __('تنقلات، انتداب، واستيداع') }}
                </div>
            </div>
        </div>

        {{-- Navigation Tabs & Filter Bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-3">
            <nav class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
                <button
                    wire:click="setTab('evaluations')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $activeTab === 'evaluations' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                >
                    📑 {{ __('التنقيط والتقييم السنوي') }} ({{ $evaluatedCount }})
                </button>
                <button
                    wire:click="setTab('advancements')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $activeTab === 'advancements' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                >
                    🎖️ {{ __('جدول الترقية في الدرجات (CAP)') }} ({{ $advCandidatesCount }})
                </button>
                <button
                    wire:click="setTab('movements')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $activeTab === 'movements' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                >
                    🔄 {{ __('حركات الموظفين والاستيداع') }} ({{ $movementsCount }})
                </button>
                <button
                    wire:click="setTab('profiles')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $activeTab === 'profiles' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                >
                    👥 {{ __('السجلات والبطاقات المهنية') }} ({{ $totalStaff }})
                </button>
            </nav>

            {{-- Live Search Input --}}
            <div class="relative w-full sm:w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="{{ __('بحث بالاسم أو رقم الوظيفة...') }}"
                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 focus:border-primary-500 focus:ring-primary-500"
                />
            </div>
        </div>

        {{-- TAB 1: EVALUATIONS --}}
        @if ($activeTab === 'evaluations')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ __('سجل التنقيط والتقييم الإداري والتربوي السنوي') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('النقطة الإدارية من 0 إلى 20 موزعة على 4 معايير قانونية، مع نقطة التفتيش البيداغوجي.') }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">{{ __('الموظف / الأستاذ') }}</th>
                                <th class="p-3">{{ __('الرتبة والصفة') }}</th>
                                <th class="p-3 text-center">{{ __('المواظبة /5') }}</th>
                                <th class="p-3 text-center">{{ __('المردودية /5') }}</th>
                                <th class="p-3 text-center">{{ __('السلوك /5') }}</th>
                                <th class="p-3 text-center">{{ __('المبادرة /5') }}</th>
                                <th class="p-3 text-center">{{ __('النقطة الإدارية') }}</th>
                                <th class="p-3 text-center">{{ __('نقطة التفتيش') }}</th>
                                <th class="p-3 text-center">{{ __('المعدل المعتمد') }}</th>
                                <th class="p-3 text-center">{{ __('التقدير العام') }}</th>
                                <th class="p-3 text-center">{{ __('التوقيع والتبليغ') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($evaluations as $eval)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $eval->user?->name }}
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $eval->user?->registration_number ?? 'بدون رقم وظيفي' }}</div>
                                    </td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">
                                        {{ $eval->user?->statutory_grade ?? ($eval->user?->role?->label() ?? '—') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">{{ number_format($eval->punctuality_score, 2) }}</td>
                                    <td class="p-3 text-center font-mono">{{ number_format($eval->performance_score, 2) }}</td>
                                    <td class="p-3 text-center font-mono">{{ number_format($eval->conduct_score, 2) }}</td>
                                    <td class="p-3 text-center font-mono">{{ number_format($eval->initiative_score, 2) }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-primary-600 dark:text-primary-400">
                                        {{ number_format($eval->administrative_score, 2) }} / 20
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ $eval->pedagogical_score !== null ? number_format($eval->pedagogical_score, 2).' / 20' : '—' }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-extrabold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($eval->composite_score, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400">
                                            {{ $eval->appreciation?->label() ?? 'جيد' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $eval->staff_signature_status === 'acknowledged' ? 'bg-emerald-50 text-emerald-600' : ($eval->staff_signature_status === 'contested' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $eval->signature_status_label }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button wire:click="openEditEvaluationModal({{ $eval->id }})" class="p-1 text-gray-500 hover:text-primary-600" title="{{ __('تعديل النقطة') }}">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            </button>
                                            <a href="{{ route('print.staff-career.evaluation-sheet', ['locale' => app()->getLocale(), 'evaluation' => $eval->id]) }}" target="_blank" class="p-1 text-gray-500 hover:text-emerald-600" title="{{ __('معاينة الاستمارة الرسمية') }}">
                                                <x-heroicon-m-document-text class="w-4 h-4" />
                                            </a>
                                            <a href="{{ route('print.staff-career.evaluation-sheet.pdf', ['locale' => app()->getLocale(), 'evaluation' => $eval->id]) }}" class="p-1 text-gray-500 hover:text-blue-600" title="{{ __('تحميل PDF') }}">
                                                <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد بطاقات تنقيط مسجلة لهذا الموسم. انقر على "إعداد بطاقة تنقيط سنوية" للبدء.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: ADVANCEMENTS (CAP) --}}
        @if ($activeTab === 'advancements')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ __('جدول الترقية في الدرجات السنوي برسم سنة') }} {{ $this->selectedYear }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('المعروض على أنظار اللجنة الإدارية المتساوية الأعضاء (CAP) — وتائر: دنيا (2.5 سنة)، متوسطة (3 سنوات)، قصوى (3.5 سنة).') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('staff-career.advancements.export-csv', ['locale' => app()->getLocale(), 'year' => $this->selectedYear]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                            <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                            {{ __('تصدير CSV (CAP)') }}
                        </a>
                        <a href="{{ route('print.staff-career.advancement-ledger.pdf', ['locale' => app()->getLocale(), 'year' => $this->selectedYear]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
                            <x-heroicon-m-printer class="w-4 h-4" />
                            {{ __('طباعة جدول الترقية PDF') }}
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">{{ __('المترشح') }}</th>
                                <th class="p-3">{{ __('الرتبة القانونية') }}</th>
                                <th class="p-3 text-center">{{ __('الدرجة الحالية') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ مفعولها') }}</th>
                                <th class="p-3 text-center">{{ __('الدرجة المقترحة') }}</th>
                                <th class="p-3 text-center">{{ __('استحقاق الدنيا (2.5)') }}</th>
                                <th class="p-3 text-center">{{ __('استحقاق المتوسطة (3)') }}</th>
                                <th class="p-3 text-center">{{ __('استحقاق القصوى (3.5)') }}</th>
                                <th class="p-3 text-center">{{ __('الوتيرة المعتمدة') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ السريان') }}</th>
                                <th class="p-3 text-center">{{ __('حالة الملف') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($advancements as $adv)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $adv->user?->name }}
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $adv->user?->registration_number ?? '—' }}</div>
                                    </td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">
                                        {{ $adv->user?->statutory_grade ?? ($adv->user?->role?->label() ?? '—') }}
                                    </td>
                                    <td class="p-3 text-center font-bold">{{ $adv->current_step }}</td>
                                    <td class="p-3 text-center font-mono text-gray-500">
                                        {{ $adv->current_step_date?->format('Y/m/d') ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center font-black text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/30">
                                        {{ $adv->next_step }}
                                    </td>
                                    <td class="p-3 text-center font-mono text-[11px]">{{ $adv->eligible_at_minimum?->format('Y/m/d') ?? '—' }}</td>
                                    <td class="p-3 text-center font-mono text-[11px]">{{ $adv->eligible_at_medium?->format('Y/m/d') ?? '—' }}</td>
                                    <td class="p-3 text-center font-mono text-[11px]">{{ $adv->eligible_at_maximum?->format('Y/m/d') ?? '—' }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $adv->advancement_rhythm?->value === 'minimum' ? 'bg-emerald-100 text-emerald-800' : ($adv->advancement_rhythm?->value === 'maximum' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ $adv->advancement_rhythm?->shortLabel() ?? 'المتوسطة' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center font-mono font-semibold">{{ $adv->effective_date?->format('Y/m/d') ?? '—' }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $adv->status === 'approved_by_cap' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $adv->status_label }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($adv->status !== 'approved_by_cap')
                                            <button wire:click="openApproveAdvancementModal({{ $adv->id }})" class="px-2.5 py-1 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded transition-colors">
                                                {{ __('مصادقة CAP وترقية') }}
                                            </button>
                                        @else
                                            <span class="text-[11px] text-emerald-600 font-semibold">✓ {{ __('مُصادق عليه') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا يوجد مترشحون للترقية لهذه السنة. انقر على "ضبط جدول الترقية (CAP)" لاحتساب الاستحقاقات تلقائياً.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: MOVEMENTS --}}
        @if ($activeTab === 'movements')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ __('سجل حركات الموظفين، الاستيداع، الانتداب، والتقاعد') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('متابعة حركة التنقل السنوية، الإحالة على الاستيداع، الانتداب لدى هيئات أخرى، وتعيينات المستخدمين.') }}
                        </p>
                    </div>

                    <x-filament::button wire:click="openNewMovementModal" size="sm" color="primary" icon="heroicon-m-plus">
                        {{ __('تسجيل حركة جديدة') }}
                    </x-filament::button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">{{ __('الموظف') }}</th>
                                <th class="p-3">{{ __('نوع الحركة') }}</th>
                                <th class="p-3">{{ __('رقم وتاريخ القرار') }}</th>
                                <th class="p-3">{{ __('المؤسسة الأصلية') }}</th>
                                <th class="p-3">{{ __('المؤسسة المستقبلة') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ السريان') }}</th>
                                <th class="p-3 text-center">{{ __('نهاية الفترة') }}</th>
                                <th class="p-3 text-center">{{ __('الحالة') }}</th>
                                <th class="p-3">{{ __('الأسباب والملاحظات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($movements as $mov)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $mov->user?->name }}
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $mov->user?->statutory_grade ?? '—' }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                                            {{ $mov->movement_type?->label() ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-mono text-[11px]">
                                        {{ $mov->decision_number ?? 'بدون رقم' }}
                                        <div class="text-[10px] text-gray-400">{{ $mov->decision_date?->format('Y/m/d') ?? '' }}</div>
                                    </td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $mov->source_institution ?? '—' }}</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $mov->target_institution ?? '—' }}</td>
                                    <td class="p-3 text-center font-mono">{{ $mov->effective_date?->format('Y/m/d') }}</td>
                                    <td class="p-3 text-center font-mono text-gray-400">{{ $mov->end_date?->format('Y/m/d') ?? '—' }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                            {{ $mov->status_label }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-500 max-w-xs truncate">{{ $mov->reason ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد حركات مسجلة حالياً. انقر على "تسجيل حركة موظف" لإضافة حركة جديدة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 4: PROFILES --}}
        @if ($activeTab === 'profiles')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ __('السجلات والبطاقات المهنية لمستخدمي المؤسسة') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('أرقام التعريف الوظيفية، الدرجات الحالية، تواريخ السريان، وحساب الأقدمية العامة تلقائياً.') }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">{{ __('اللقب والاسم') }}</th>
                                <th class="p-3">{{ __('رقم التعريف الوظيفي') }}</th>
                                <th class="p-3">{{ __('الرتبة القانونية') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ أول تعيين') }}</th>
                                <th class="p-3 text-center">{{ __('الدرجة الحالية') }}</th>
                                <th class="p-3 text-center">{{ __('تاريخ مفعول الدرجة') }}</th>
                                <th class="p-3 text-center">{{ __('الأقدمية العامة') }}</th>
                                <th class="p-3">{{ __('المؤهل العلمي / الشهادة') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($profiles as $user)
                                @php
                                    $sen = $user->getGeneralSeniority();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                        <div class="text-[10px] text-gray-400">{{ $user->email }}</div>
                                    </td>
                                    <td class="p-3 font-mono text-[11px] text-gray-600 dark:text-gray-300">
                                        {{ $user->registration_number ?? '—' }}
                                    </td>
                                    <td class="p-3 text-gray-700 dark:text-gray-200 font-medium">
                                        {{ $user->statutory_grade ?? ($user->role?->label() ?? '—') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ $user->recruitment_date?->format('Y/m/d') ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-primary-600 dark:text-primary-400">
                                        {{ __('الدرجة') }} {{ $user->current_step ?? 0 }}
                                    </td>
                                    <td class="p-3 text-center font-mono text-gray-500">
                                        {{ $user->current_step_date?->format('Y/m/d') ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $sen['formatted'] }}
                                    </td>
                                    <td class="p-3 text-gray-500">{{ $user->diploma ?? '—' }}</td>
                                    <td class="p-3 text-center">
                                        <button wire:click="openEditProfileModal({{ $user->id }})" class="px-2.5 py-1 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                                            {{ __('تحديث البطاقة') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا يوجد موظفون مسجلون.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- MODAL 1: EVALUATION MODAL --}}
        @if ($isEvaluationModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $editingEvaluationId ? __('تعديل بطاقة التنقيط السنوية') : __('إعداد بطاقة تنقيط سنوية جديدة') }}
                        </h3>
                        <button wire:click="$set('isEvaluationModalOpen', false)" class="text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('الموظف / الأستاذ المعني:') }}</label>
                            <select wire:model="evaluationUserId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                @foreach ($this->staffUsers as $su)
                                    <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->statutory_grade ?? ($su->role?->label() ?? '') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-2">{{ __('عناصر النقطة الإدارية السنوية (المجموع / 20):') }}</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold mb-1">{{ __('1. المواظبة والانضباط (من 0 إلى 5):') }}</label>
                                    <input type="number" step="0.25" min="0" max="5" wire:model.live="punctualityScore" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                                </div>
                                <div>
                                    <label class="block font-semibold mb-1">{{ __('2. النشاط والمردودية (من 0 إلى 5):') }}</label>
                                    <input type="number" step="0.25" min="0" max="5" wire:model.live="performanceScore" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                                </div>
                                <div>
                                    <label class="block font-semibold mb-1">{{ __('3. السلوك والمظهر المهني (من 0 إلى 5):') }}</label>
                                    <input type="number" step="0.25" min="0" max="5" wire:model.live="conductScore" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                                </div>
                                <div>
                                    <label class="block font-semibold mb-1">{{ __('4. المبادرة والمساهمة (من 0 إلى 5):') }}</label>
                                    <input type="number" step="0.25" min="0" max="5" wire:model.live="initiativeScore" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                                </div>
                            </div>

                            <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
                                <span class="font-bold">{{ __('مجموع النقطة الإدارية المحسوبة:') }}</span>
                                <span class="text-sm font-black font-mono text-primary-600">
                                    {{ number_format($punctualityScore + $performanceScore + $conductScore + $initiativeScore, 2) }} / 20
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('نقطة التفتيش البيداغوجي (اختياري /20):') }}</label>
                                <input type="number" step="0.25" min="0" max="20" wire:model="pedagogicalScore" placeholder="16.50" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ آخر زيارة تفتيش:') }}</label>
                                <input type="date" wire:model="lastInspectionDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('التقدير العام لرئيس المؤسسة:') }}</label>
                                <select wire:model="evaluationAppreciation" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                    <option value="excellent">{{ __('ممتاز') }}</option>
                                    <option value="very_good">{{ __('جيد جداً') }}</option>
                                    <option value="good">{{ __('جيد') }}</option>
                                    <option value="acceptable">{{ __('مقبول') }}</option>
                                    <option value="poor">{{ __('ضعيف') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('وضعية توقيع وتبليغ الموظف:') }}</label>
                                <select wire:model="staffSignatureStatus" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                    <option value="pending">{{ __('قيد التبليغ والتوقيع') }}</option>
                                    <option value="acknowledged">{{ __('اطّلع ووقّع') }}</option>
                                    <option value="contested">{{ __('قدّم طعناً أو تحفظات') }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتوجيهات مدير المؤسسة:') }}</label>
                            <textarea wire:model="evaluatorNotes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" placeholder="{{ __('أستاذ جاد ومواظب يؤدي مهامه بكفاءة...') }}"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                        <x-filament::button wire:click="$set('isEvaluationModalOpen', false)" color="gray">
                            {{ __('إلغاء') }}
                        </x-filament::button>
                        <x-filament::button wire:click="saveEvaluation" color="primary">
                            {{ __('حفظ النقطة والتقييم') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 2: ADVANCEMENT APPROVAL MODAL --}}
        @if ($isAdvancementModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-w-md w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ __('المصادقة على الترقية في الدرجة (CAP)') }}
                        </h3>
                        <button wire:click="$set('isAdvancementModalOpen', false)" class="text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ __('عند تأكيد المصادقة، ستتم ترقية الموظف إلى الدرجة التالية مباشرة، وتحديث تاريخ سريان الدرجة والأثر المالي.') }}
                        </p>

                        <div>
                            <label class="block font-bold mb-1">{{ __('تاريخ سريان مفعول الترقية والأثر المالي:') }}</label>
                            <input type="date" wire:model="advancementEffectiveDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                        </div>

                        <div>
                            <label class="block font-bold mb-1">{{ __('مرجع محضر اللجنة المتساوية الأعضاء (CAP):') }}</label>
                            <input type="text" wire:model="advancementCommissionRef" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                        </div>

                        <div>
                            <label class="block font-bold mb-1">{{ __('ملاحظات:') }}</label>
                            <textarea wire:model="advancementNotes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                        <x-filament::button wire:click="$set('isAdvancementModalOpen', false)" color="gray">
                            {{ __('إلغاء') }}
                        </x-filament::button>
                        <x-filament::button wire:click="approveAdvancement" color="success">
                            {{ __('تأكيد الترقية وتحديث الملف') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 3: MOVEMENT MODAL --}}
        @if ($isMovementModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ __('تسجيل حركة موظف جديدة') }}
                        </h3>
                        <button wire:click="$set('isMovementModalOpen', false)" class="text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold mb-1">{{ __('الموظف المعني:') }}</label>
                            <select wire:model="movementUserId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                @foreach ($this->staffUsers as $su)
                                    <option value="{{ $su->id }}">{{ $su->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('نوع الحركة:') }}</label>
                                <select wire:model="movementType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                    <option value="transfer">{{ __('حركة نقل سنوية') }}</option>
                                    <option value="secondment">{{ __('انتداب لدى مؤسسة') }}</option>
                                    <option value="availability">{{ __('إحالة على الاستيداع') }}</option>
                                    <option value="retirement">{{ __('إحالة على التقاعد') }}</option>
                                    <option value="integration">{{ __('إعادة إدماج') }}</option>
                                    <option value="new_hire">{{ __('تعيين جديد') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('حالة الملف:') }}</label>
                                <select wire:model="movementStatus" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                                    <option value="approved">{{ __('معتمد ومؤشر') }}</option>
                                    <option value="in_progress">{{ __('ساري المفعول') }}</option>
                                    <option value="completed">{{ __('منجز ومكتمل') }}</option>
                                    <option value="pending">{{ __('قيد الدراسة') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('رقم القرار الوزاري / الولائي:') }}</label>
                                <input type="text" wire:model="movementDecisionNumber" placeholder="DEC/2026/..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ القرار:') }}</label>
                                <input type="date" wire:model="movementDecisionDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ السريان:') }}</label>
                                <input type="date" wire:model="movementEffectiveDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ النهاية (للاستيداع/الانتداب):') }}</label>
                                <input type="date" wire:model="movementEndDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('المؤسسة الأصلية:') }}</label>
                                <input type="text" wire:model="movementSource" placeholder="متوسطة..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('المؤسسة المستقبلة:') }}</label>
                                <input type="text" wire:model="movementTarget" placeholder="متوسطة..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold mb-1">{{ __('السبب / الملاحظات:') }}</label>
                            <textarea wire:model="movementReason" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                        <x-filament::button wire:click="$set('isMovementModalOpen', false)" color="gray">
                            {{ __('إلغاء') }}
                        </x-filament::button>
                        <x-filament::button wire:click="saveMovement" color="primary">
                            {{ __('تسجيل الحركة') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL 4: PROFILE EDIT MODAL --}}
        @if ($isProfileModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ __('تحديث البطاقة المهنية للموظف') }}
                        </h3>
                        <button wire:click="$set('isProfileModalOpen', false)" class="text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('رقم التعريف الوظيفي:') }}</label>
                                <input type="text" wire:model="profileRegistrationNumber" placeholder="M12345" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('رقم التعريف الوطني NIN:') }}</label>
                                <input type="text" wire:model="profileNIN" placeholder="10..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ الميلاد:') }}</label>
                                <input type="date" wire:model="profileBirthDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('مكان الميلاد:') }}</label>
                                <input type="text" wire:model="profileBirthPlace" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ أول تعيين بالوظيفة العمومية:') }}</label>
                                <input type="date" wire:model="profileRecruitmentDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ التعيين في الرتبة الحالية:') }}</label>
                                <input type="date" wire:model="profileGradeDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1">{{ __('الدرجة الحالية (0 إلى 12):') }}</label>
                                <input type="number" min="0" max="12" wire:model="profileCurrentStep" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold mb-1">{{ __('تاريخ مفعول الدرجة الحالية:') }}</label>
                                <input type="date" wire:model="profileStepDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-mono" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold mb-1">{{ __('الرتبة القانونية والصفة:') }}</label>
                            <input type="text" wire:model="profileStatutoryGrade" placeholder="أستاذ التعليم المتوسط، مستشار التربية..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                        </div>

                        <div>
                            <label class="block font-bold mb-1">{{ __('المؤهل العلمي / الشهادة:') }}</label>
                            <input type="text" wire:model="profileDiploma" placeholder="ليسانس، ماستر، شهادة عليا..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                        <x-filament::button wire:click="$set('isProfileModalOpen', false)" color="gray">
                            {{ __('إلغاء') }}
                        </x-filament::button>
                        <x-filament::button wire:click="saveProfile" color="primary">
                            {{ __('تحديث البطاقة المهنية') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
