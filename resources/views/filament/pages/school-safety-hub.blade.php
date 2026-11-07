<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 rounded-xl">
                    <x-heroicon-o-shield-check class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ __('منظومة الوقاية والأمن الداخلي والحوادث المدرسية (PSI)') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('مخطط الأمن الداخلي، سجل الحوادث المدرسية والتأمين، فحص أجهزة الإطفاء، وتمارين الإخلاء المشتركة') }}
                    </p>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl">
                <button
                    wire:click="setTab('incidents')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'incidents' ? 'bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-exclamation-triangle class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('الحوادث والتصريح الرسمي') }}
                </button>
                <button
                    wire:click="setTab('equipment')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'equipment' ? 'bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-fire class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('أجهزة الإطفاء والغاز') }}
                </button>
                <button
                    wire:click="setTab('drills')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'drills' ? 'bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-arrow-path-rounded-square class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('تمارين الإخلاء والمحاكاة') }}
                </button>
                <button
                    wire:click="setTab('psi_plan')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'psi_plan' ? 'bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-user-group class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('مخطط PSI وفرق التدخل') }}
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 1: INCIDENTS & DECLARATIONS                           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'incidents')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي الحوادث المسجلة') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['total_incidents'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('سنة') }} {{ $academicYear }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('حوادث التلاميذ المدرسية') }}</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $this->telemetry['student_accidents'] }}</div>
                    <div class="text-[11px] text-red-500/80 mt-1">{{ __('خاضعة للتأمين المدرسي') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('حوادث العمل للموظفين') }}</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $this->telemetry['work_accidents'] }}</div>
                    <div class="text-[11px] text-amber-500/80 mt-1">{{ __('خاضعة للضمان CNAS') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('حالات الإخلاء للمستشفى') }}</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $this->telemetry['hospital_evacuations'] }}</div>
                    <div class="text-[11px] text-purple-500/80 mt-1">{{ __('إسعاف استعجالي') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 col-span-2 md:col-span-1">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('ملفات معلقة للتصريح') }}</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $this->telemetry['pending_declarations'] }}</div>
                    <div class="text-[11px] text-orange-500/80 mt-1">{{ __('تستوجب الإرسال خلال 48 س') }}</div>
                </div>
            </div>

            <!-- Toolbar & Actions -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <x-heroicon-m-magnifying-glass class="w-4 h-4 absolute top-3 right-3 text-gray-400" />
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="{{ __('بحث برقم الحادث، الضحية، المكان...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 pr-9 focus:ring-red-500 focus:border-red-500">
                    </div>

                    <select wire:model.live="incidentTypeFilter" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="all">{{ __('جميع أنواع الحوادث') }}</option>
                        <option value="student_accident">{{ __('حادث مدرسي لتلميذ') }}</option>
                        <option value="work_accident">{{ __('حادث عمل لموظف') }}</option>
                        <option value="infrastructure_hazard">{{ __('خطر هيكلي بالمنشأة') }}</option>
                        <option value="material_damage">{{ __('أضرار مادية') }}</option>
                    </select>

                    <select wire:model.live="severityFilter" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="all">{{ __('جميع درجات الخطورة') }}</option>
                        <option value="minor">{{ __('بسيط / طفيف') }}</option>
                        <option value="moderate">{{ __('متوسط / إسعافات') }}</option>
                        <option value="severe">{{ __('خطير / إخلاء مستشفى') }}</option>
                        <option value="critical">{{ __('حرج جداً') }}</option>
                    </select>
                </div>

                <button
                    wire:click="openAccidentModal"
                    class="w-full md:w-auto px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5">
                    <x-heroicon-m-plus-circle class="w-4 h-4" />
                    {{ __('التصريح بحادث مدرسي / عمل') }}
                </button>
            </div>

            <!-- Incidents Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                            <tr>
                                <th class="p-3 text-start">{{ __('رقم الملف') }}</th>
                                <th class="p-3 text-start">{{ __('الضحية والصفة') }}</th>
                                <th class="p-3 text-start">{{ __('التاريخ والمكان') }}</th>
                                <th class="p-3 text-start">{{ __('الخطورة والإصابة') }}</th>
                                <th class="p-3 text-start">{{ __('الإخلاء الطبي') }}</th>
                                <th class="p-3 text-start">{{ __('التأمين والتصريح') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($this->incidents as $incident)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="p-3 font-mono font-bold text-red-600 dark:text-red-400">
                                        {{ $incident->incident_number }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $incident->victim_name }}</div>
                                        <div class="text-[11px] text-gray-500">
                                            {{ $incident->incident_type?->label() ?? $incident->victim_type }}
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div>{{ $incident->incident_date?->format('Y-m-d H:i') }}</div>
                                        <div class="text-[11px] text-gray-500 font-medium">{{ $incident->location }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                            {{ match($incident->severity_level?->value) {
                                                'minor' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                                'moderate' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                                'severe' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                                default => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                                            } }}">
                                            {{ $incident->severity_level?->label() ?? $incident->severity_level }}
                                        </span>
                                        @if($incident->bodily_injuries_description)
                                            <div class="text-[11px] text-gray-500 line-clamp-1 mt-0.5" title="{{ $incident->bodily_injuries_description }}">
                                                {{ $incident->bodily_injuries_description }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if($incident->hospital_evacuation)
                                            <span class="inline-flex items-center gap-1 text-purple-700 dark:text-purple-400 font-semibold text-[11px]">
                                                <x-heroicon-m-check-badge class="w-4 h-4" />
                                                {{ $incident->hospital_name ?: __('نقل استعجالي') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-[11px]">{{ __('عولج بالعيادة') }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if($incident->insurance_declared)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                {{ __('مصرح به رسمياً') }}
                                            </span>
                                        @else
                                            <button
                                                wire:click="markIncidentDeclared({{ $incident->id }})"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-orange-100 text-orange-800 hover:bg-orange-200 transition">
                                                {{ __('تأكيد التصريح (48س)') }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a
                                                href="{{ route('print.safety.accident-declaration', ['locale' => app()->getLocale(), 'incident' => $incident->id]) }}"
                                                target="_blank"
                                                class="p-1 text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                                title="{{ __('معاينة وطباعة الاستمارة الرسمية') }}">
                                                <x-heroicon-o-printer class="w-4 h-4" />
                                            </a>
                                            <a
                                                href="{{ route('print.safety.accident-declaration.pdf', ['locale' => app()->getLocale(), 'incident' => $incident->id]) }}"
                                                target="_blank"
                                                class="p-1 text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                                title="{{ __('تحميل نسخة PDF') }}">
                                                <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                                            </a>
                                            @if($incident->status?->value !== 'settled')
                                                <button
                                                    wire:click="markIncidentSettled({{ $incident->id }})"
                                                    class="p-1 text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                                    title="{{ __('تسوية الملف والشفاء') }}">
                                                    <x-heroicon-o-check-circle class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-gray-500">
                                        {{ __('لا توجد حوادث مسجلة مطابقة لمعايير البحث الحالية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 2: FIRE SAFETY & GAS EQUIPMENT                        -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'equipment')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي أجهزة السلامة والإطفاء') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['equipment_total'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('طفايات، كواشف غاز، إنذار') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أجهزة صالحة ومطابقة') }}</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $this->telemetry['equipment_operational'] }}</div>
                    <div class="text-[11px] text-green-500/80 mt-1">{{ __('جاهزة للاستعمال الفوري') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('تستوجب الشحن / الفحص') }}</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $this->telemetry['equipment_due_or_expired'] }}</div>
                    <div class="text-[11px] text-orange-500/80 mt-1">{{ __('انتهت صلاحية الصيانة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('نسبة الجاهزية الوقائية') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->telemetry['operational_rate'] }}%</div>
                    <div class="text-[11px] text-blue-500/80 mt-1">{{ __('معايير الحماية المدنية') }}</div>
                </div>
            </div>

            <!-- Toolbar & Actions -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="$set('equipmentFilter', 'all')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $equipmentFilter === 'all' ? 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ __('جميع الأجهزة') }}
                    </button>
                    <button
                        wire:click="$set('equipmentFilter', 'operational')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $equipmentFilter === 'operational' ? 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ __('الأجهزة المطابقة والصالحة') }}
                    </button>
                    <button
                        wire:click="$set('equipmentFilter', 'due_or_expired')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $equipmentFilter === 'due_or_expired' ? 'bg-orange-50 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ __('تستوجب الفحص الفوري') }}
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <a
                        href="{{ route('print.safety.equipment-register', ['locale' => app()->getLocale(), 'filter' => $equipmentFilter]) }}"
                        target="_blank"
                        class="px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-lg transition flex items-center gap-1.5">
                        <x-heroicon-o-printer class="w-4 h-4" />
                        {{ __('طباعة السجل الرسمي للأجهزة') }}
                    </a>

                    <button
                        wire:click="openEquipmentModal"
                        class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <x-heroicon-m-plus-circle class="w-4 h-4" />
                        {{ __('إضافة جهاز جديد للسجل') }}
                    </button>
                </div>
            </div>

            <!-- Equipment Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                            <tr>
                                <th class="p-3 text-start">{{ __('رمز وتعيين الجهاز') }}</th>
                                <th class="p-3 text-start">{{ __('نوع الجهاز') }}</th>
                                <th class="p-3 text-start">{{ __('الموقع الدقيق بالهيكل') }}</th>
                                <th class="p-3 text-start">{{ __('تاريخ آخر فحص') }}</th>
                                <th class="p-3 text-start">{{ __('تاريخ الفحص القادم') }}</th>
                                <th class="p-3 text-start">{{ __('الحالة التقنية') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($this->equipments as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="p-3 font-mono font-bold text-red-600 dark:text-red-400">
                                        {{ $item->equipment_code }}
                                    </td>
                                    <td class="p-3 font-medium">
                                        {{ $item->equipment_type?->label() ?? $item->equipment_type }}
                                    </td>
                                    <td class="p-3 font-medium text-gray-900 dark:text-white">
                                        {{ $item->exact_location }}
                                    </td>
                                    <td class="p-3 text-gray-500">
                                        {{ $item->last_inspection_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="p-3 font-semibold">
                                        <span class="{{ $item->isExpiredOrDue() ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $item->next_inspection_due_date?->format('Y-m-d') }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        @if($item->isExpiredOrDue())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                                {{ __('منتهي الصلاحية') }}
                                            </span>
                                        @elseif($item->isOperational())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                {{ __('صالح ومطابق') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300">
                                                {{ $item->inspection_status?->label() ?? 'صيانة' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <button
                                            wire:click="openInspectModal({{ $item->id }})"
                                            class="px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded transition">
                                            {{ __('تسجيل فحص دوري') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-gray-500">
                                        {{ __('لا توجد أجهزة مسجلة في السجل مطابقة للفلتر المحدد.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 3: EVACUATION DRILLS & SIMULATIONS                    -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'drills')
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل تمارين ومحاكاة الإخلاء الافتراضي للوقاية من الكوارث') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('محاضر رسمية مشتركة مع مصالح الحماية المدنية وفقاً للمخطط الداخلي للأمن PSI') }}</p>
                </div>
                <button
                    wire:click="openDrillModal"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <x-heroicon-m-plus-circle class="w-4 h-4" />
                    {{ __('تسجيل تمرين إخلاء افتراضي جديد') }}
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($this->drills as $drill)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2 mb-3">
                                <span class="font-mono font-bold text-red-600 dark:text-red-400 text-xs">{{ $drill->drill_code }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $drill->drill_date?->format('Y-m-d') }} ({{ $drill->drill_time }})
                                </span>
                            </div>

                            <div class="font-bold text-gray-900 dark:text-white text-sm mb-1">
                                {{ $drill->simulation_scenario?->label() ?? 'محاكاة إخلاء' }}
                            </div>

                            <div class="grid grid-cols-2 gap-2 my-3 text-xs bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg">
                                <div>
                                    <div class="text-[11px] text-gray-400">{{ __('مدة الإخلاء الكامل:') }}</div>
                                    <div class="font-bold text-green-600 dark:text-green-400 font-mono">{{ $drill->formattedDuration() }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] text-gray-400">{{ __('الأفراد الذين تم إخلاؤهم:') }}</div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $drill->total_persons_evacuated }} {{ __('فرد') }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[11px] text-gray-400">{{ __('نقطة التجمع:') }}</div>
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $drill->assembly_zone_used }}</div>
                                </div>
                            </div>

                            @if($drill->bottlenecks_and_remarks)
                                <div class="text-[11px] text-gray-500 bg-amber-50/50 dark:bg-amber-900/10 border border-amber-200/50 dark:border-amber-800/30 p-2 rounded mb-3">
                                    <strong class="text-amber-800 dark:text-amber-300 block mb-0.5">{{ __('النقائص المسجلة:') }}</strong>
                                    {{ $drill->bottlenecks_and_remarks }}
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 pt-3 mt-2">
                            <span class="text-[11px] font-semibold text-gray-500">
                                {{ __('التقييم:') }} <strong class="text-green-600">{{ match($drill->evaluation_rating) { 'excellent' => 'ممتاز', 'good' => 'جيد وناجح', 'average' => 'متوسط', default => 'ضعيف' } }}</strong>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <a
                                    href="{{ route('print.safety.drill-pv', ['locale' => app()->getLocale(), 'drill' => $drill->id]) }}"
                                    target="_blank"
                                    class="p-1.5 text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                    title="{{ __('معاينة وطباعة المحضر الرسمي') }}">
                                    <x-heroicon-o-printer class="w-4 h-4" />
                                </a>
                                <a
                                    href="{{ route('print.safety.drill-pv.pdf', ['locale' => app()->getLocale(), 'drill' => $drill->id]) }}"
                                    target="_blank"
                                    class="p-1.5 text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                    title="{{ __('تحميل نسخة PDF') }}">
                                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center text-gray-500">
                        {{ __('لم يتم تسجيل تمارين إخلاء افتراضية حتى الآن لهذه السنة الدراسية.') }}
                    </div>
                @endforelse
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 4: PSI EMERGENCY PLAN & INTERVENTION TEAMS            -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'psi_plan')
            @php $plan = $this->psiPlan; @endphp
            <div class="space-y-6">
                <!-- PSI Plan Banner -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-red-600 dark:text-red-400 text-sm">{{ $plan?->plan_code ?? 'PSI-2026-01' }}</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    {{ __('مخطط معتمد وساري المفعول') }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white mt-1">{{ $plan?->title ?? 'المخطط الداخلي للأمن PSI' }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="openMemberModal"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                                <x-heroicon-m-user-plus class="w-4 h-4" />
                                {{ __('تعيين عضو في فريق التدخل') }}
                            </button>
                        </div>
                    </div>

                    <!-- Leadership Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-xs">
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                            <span class="text-gray-400 block text-[11px]">{{ __('رئيس خلية الأزمة:') }}</span>
                            <strong class="text-gray-900 dark:text-white font-medium">{{ $plan?->crisis_cell_leader }}</strong>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                            <span class="text-gray-400 block text-[11px]">{{ __('منسق عمليات الإخلاء:') }}</span>
                            <strong class="text-gray-900 dark:text-white font-medium">{{ $plan?->evacuation_coordinator }}</strong>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                            <span class="text-gray-400 block text-[11px]">{{ __('مسؤول الإسعافات الأولية:') }}</span>
                            <strong class="text-gray-900 dark:text-white font-medium">{{ $plan?->first_aid_leader }}</strong>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                            <span class="text-gray-400 block text-[11px]">{{ __('المسؤول التقني والسلامة:') }}</span>
                            <strong class="text-gray-900 dark:text-white font-medium">{{ $plan?->technical_safety_officer }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contacts & Assembly Points -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Assembly Points -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                            <x-heroicon-m-map-pin class="w-4 h-4 text-red-500" />
                            {{ __('نقاط ومناطق التجمع الآمنة المعتمدة بالمؤسسة') }}
                        </h4>
                        <ul class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300">
                            @if(is_array($plan?->assembly_points))
                                @foreach($plan->assembly_points as $pt)
                                    <li class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                        <x-heroicon-m-check class="w-4 h-4 text-green-500 flex-shrink-0" />
                                        <span>{{ $pt }}</span>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    <!-- Emergency Numbers -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                            <x-heroicon-m-phone class="w-4 h-4 text-blue-500" />
                            {{ __('دليل أرقام الطوارئ والنجدة الرسمية (Numéros d\'urgence)') }}
                        </h4>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @if(is_array($plan?->emergency_contacts))
                                @foreach($plan->emergency_contacts as $entity => $num)
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded flex items-center justify-between">
                                        <span class="text-[11px] text-gray-600 dark:text-gray-400">{{ $entity }}</span>
                                        <strong class="text-red-600 dark:text-red-400 font-mono">{{ $num }}</strong>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Intervention Squad Table -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                            {{ __('تشكيلة فرق التدخل والإسعاف وتأمين المسالك بالمؤسسة') }}
                        </h4>
                        <span class="text-[11px] text-gray-500">
                            {{ $plan?->interventionTeams->count() ?? 0 }} {{ __('أعضاء مسجلين وموزعين على الأفواج') }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                                <tr>
                                    <th class="p-3 text-start">{{ __('اسم العضو') }}</th>
                                    <th class="p-3 text-start">{{ __('الفوج المسند') }}</th>
                                    <th class="p-3 text-start">{{ __('الرتبة / الصفة بالمؤسسة') }}</th>
                                    <th class="p-3 text-start">{{ __('الجناح أو المنطقة المسندة') }}</th>
                                    <th class="p-3 text-start">{{ __('شهادة مسعف أولي') }}</th>
                                    <th class="p-3 text-start">{{ __('رقم الهاتف') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                                @forelse($plan?->interventionTeams ?? [] as $member)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                        <td class="p-3 font-bold text-gray-900 dark:text-white">
                                            {{ $member->member_name }}
                                        </td>
                                        <td class="p-3 font-semibold">
                                            {{ $member->team_type?->label() ?? $member->team_type }}
                                        </td>
                                        <td class="p-3 text-gray-500">
                                            {{ $member->institution_role ?? 'أستاذ / موظف' }}
                                        </td>
                                        <td class="p-3 text-gray-700 dark:text-gray-300">
                                            {{ $member->assigned_zone ?? 'الجناح التربوي' }}
                                        </td>
                                        <td class="p-3">
                                            @if($member->is_certified_first_aider)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                    {{ __('مسعف معتمد من الحماية المدنية') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-[11px]">{{ __('تدريب داخلي') }}</span>
                                            @endif
                                        </td>
                                        <td class="p-3 font-mono text-gray-600 dark:text-gray-400">
                                            {{ $member->phone_number ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-gray-500">
                                            {{ __('لم يتم تعيين أعضاء فرق التدخل حتى الآن.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: DECLARE ACCIDENT                                 -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isAccidentModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-2xl w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-red-600" />
                        {{ __('التصريح بحادث مدرسي / حادث عمل (Déclaration d\'accident)') }}
                    </h3>
                    <button wire:click="closeAccidentModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('طبيعة الحادث:') }}</label>
                        <select wire:model.live="incidentType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="student_accident">{{ __('حادث مدرسي لتلميذ') }}</option>
                            <option value="work_accident">{{ __('حادث عمل لموظف / أستاذ') }}</option>
                            <option value="infrastructure_hazard">{{ __('خطر أو حادث بالمنشأة') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('درجة الخطورة:') }}</label>
                        <select wire:model="severityLevel" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="minor">{{ __('بسيط / طفيف (Léger)') }}</option>
                            <option value="moderate">{{ __('متوسط / إسعافات أولية (Moyen)') }}</option>
                            <option value="severe">{{ __('خطير / إخلاء مستشفى (Grave)') }}</option>
                            <option value="critical">{{ __('حرج جداً (Critique)') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ وتوقيت الحادث:') }}</label>
                        <input type="datetime-local" wire:model="incidentDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('المكان الدقيق بالمؤسسة:') }}</label>
                        <input type="text" wire:model="location" placeholder="{{ __('مثال: الملعب الرياضي، الفناء، المخبر، الدرج...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    @if($incidentType === 'student_accident')
                        <div class="col-span-2">
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اختيار التلميذ المصاب (إن وجد):') }}</label>
                            <select wire:model.live="victimStudentId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                <option value="">{{ __('— اختر التلميذ المصاب من القائمة —') }}</option>
                                @foreach($this->students as $std)
                                    <option value="{{ $std->id }}">{{ $std->full_name }} ({{ $std->group?->classroom?->name ?? 'قسم' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-span-2">
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اختيار الموظف / الأستاذ المصاب:') }}</label>
                            <select wire:model.live="victimUserId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                <option value="">{{ __('— اختر الموظف من القائمة —') }}</option>
                                @foreach($this->users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role?->label() ?? 'موظف' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اسم الضحية (تأكيد أو إدخال يدوي):') }}</label>
                        <input type="text" wire:model="victimName" placeholder="{{ __('الاسم واللقب الكامل') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الرواية المفصلة للوقائع وكيفية وقوع الحادث:') }}</label>
                        <textarea wire:model="circumstancesDescription" rows="2" placeholder="{{ __('وصف دقيق لما حدث أثناء الحصة أو الاستراحة...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('طبيعة الإصابات الجسدية والمعاينة الأولية:') }}</label>
                        <input type="text" wire:model="bodilyInjuriesDescription" placeholder="{{ __('مثال: كسر بالساعد الأيمن، جرح قطعي بالجبين، التواء بالكاحل...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الإسعافات المقدمة بالعيادة:') }}</label>
                        <input type="text" wire:model="immediateCareAdministered" placeholder="{{ __('تضميد، ثلج، مسكن...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الأيام المقدرة للعجز ITT:') }}</label>
                        <input type="number" wire:model="temporaryIncapacityDays" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="col-span-2 flex items-center gap-2">
                        <input type="checkbox" wire:model.live="hospitalEvacuation" id="hospCheck" class="rounded text-red-600 focus:ring-red-500">
                        <label for="hospCheck" class="text-gray-800 dark:text-gray-200 font-semibold">{{ __('تم الإخلاء والنقل الاستعجالي إلى المستشفى') }}</label>
                    </div>

                    @if($hospitalEvacuation)
                        <div class="col-span-2">
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اسم المستشفى / المؤسسة الاستشفائية:') }}</label>
                            <input type="text" wire:model="hospitalName" placeholder="{{ __('المستشفى الجامعي أو مصلحة الاستعجالات') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    @endif

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('شهادة الشهود الحاضرين (الأسماء والأقوال):') }}</label>
                        <input type="text" wire:model="witnessesStatements" placeholder="{{ __('تلميذين أو أستاذين شاهدا الحادث') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeAccidentModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitAccident" type="button" class="px-5 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                        {{ __('اعتماد وحفظ التصريح الرسمي') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: REGISTER FIRE EQUIPMENT                          -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isEquipmentModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-fire class="w-5 h-5 text-red-600" />
                        {{ __('تسجيل جهاز إطفاء أو سلامة جديد') }}
                    </h3>
                    <button wire:click="closeEquipmentModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('نوع العتاد:') }}</label>
                        <select wire:model="newEquipmentType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="extinguisher_powder">{{ __('مطفأة بودرة ABC (Extincteur poudre ABC)') }}</option>
                            <option value="extinguisher_co2">{{ __('مطفأة ثاني أكسيد الكربون CO2') }}</option>
                            <option value="extinguisher_water">{{ __('مطفأة ماء رغوي') }}</option>
                            <option value="co_detector">{{ __('كاشف تسرب غاز أحادي أكسيد الكربون CO') }}</option>
                            <option value="fire_alarm">{{ __('جرس إنذار حريق') }}</option>
                            <option value="emergency_lighting">{{ __('كتل الإنارة الذاتية للطوارئ') }}</option>
                            <option value="gas_shutoff_valve">{{ __('صمام أمان قطع الغاز') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الموقع الدقيق بالهيكل والمبنى:') }}</label>
                        <input type="text" wire:model="newEquipmentLocation" placeholder="{{ __('مثال: مدخل الإدارة، غرفة التدفئة المركزية Chaufferie، المخبر 1...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ آخر فحص:') }}</label>
                            <input type="date" wire:model="newEquipmentLastInspection" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الفحص القادم الملزم:') }}</label>
                            <input type="date" wire:model="newEquipmentNextDue" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الحالة التقنية:') }}</label>
                        <select wire:model="newEquipmentStatus" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="operational">{{ __('صالح وجاهز للاستعمال (Opérationnel)') }}</option>
                            <option value="needs_refill">{{ __('بحاجة لإعادة شحن وتعبئة') }}</option>
                            <option value="needs_maintenance">{{ __('بحاجة للصيانة والتصليح') }}</option>
                            <option value="out_of_service">{{ __('معطل وخارج الخدمة') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('المؤسسة الفاحصة المعتمدة:') }}</label>
                        <input type="text" wire:model="newEquipmentInspector" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeEquipmentModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitEquipment" type="button" class="px-5 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                        {{ __('حفظ الجهاز بالسجل') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: QUICK INSPECTION UPDATE                          -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isInspectModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-sm w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل نتائج الفحص الدوري للجهاز') }}
                    </h3>
                    <button wire:click="closeInspectModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الحالة بعد المعاينة:') }}</label>
                        <select wire:model="inspectStatus" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="operational">{{ __('صالح وجاهز للاستعمال (Opérationnel)') }}</option>
                            <option value="needs_refill">{{ __('بحاجة لإعادة شحن') }}</option>
                            <option value="needs_maintenance">{{ __('بحاجة لصيانة') }}</option>
                            <option value="out_of_service">{{ __('خارج الخدمة') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ الفحص القادم الملزم:') }}</label>
                        <input type="date" wire:model="inspectNextDue" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الهيئة الفاحصة:') }}</label>
                        <input type="text" wire:model="inspectEntity" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeInspectModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitInspection" type="button" class="px-5 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">
                        {{ __('تأكيد الفحص وتمديد الصلاحية') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 4: RECORD EVACUATION DRILL                          -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isDrillModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-xl w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-arrow-path-rounded-square class="w-5 h-5 text-red-600" />
                        {{ __('تحرير محضر تمرين إخلاء افتراضي للوقاية من الكوارث') }}
                    </h3>
                    <button wire:click="closeDrillModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ التمرين:') }}</label>
                        <input type="date" wire:model="drillDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('توقيت إطلاق صفارة الإنذار:') }}</label>
                        <input type="text" wire:model="drillTime" placeholder="10:15" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('سيناريو المحاكاة:') }}</label>
                        <select wire:model="drillScenario" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="earthquake">{{ __('تمرين وقاية وإخلاء ضد الزلازل (Simulation Séisme)') }}</option>
                            <option value="fire_outbreak">{{ __('تمرين إخلاء ضد نشوب حريق (Simulation Incendie)') }}</option>
                            <option value="gas_leak">{{ __('تمرين إخلاء ضد تسرب غازي (Simulation Fuite gaz)') }}</option>
                            <option value="general_disaster">{{ __('تمرين إخلاء شامل ضد الأخطار الكبرى') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('المدة المستغرقة للإخلاء (دقائق):') }}</label>
                        <input type="number" wire:model="drillMinutes" min="0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('ثواني:') }}</label>
                        <input type="number" wire:model="drillSeconds" min="0" max="59" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('عدد التلاميذ الذين تم إخلاؤهم:') }}</label>
                        <input type="number" wire:model="drillStudentsCount" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('عدد المؤطرين والعمال:') }}</label>
                        <input type="number" wire:model="drillStaffCount" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('منطقة ونقطة التجمع المعتمدة:') }}</label>
                        <input type="text" wire:model="drillAssemblyZone" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الشريك (وحدة الحماية المدنية):') }}</label>
                        <input type="text" wire:model="drillPartner" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('التقييم العام للعملية:') }}</label>
                        <select wire:model="drillRating" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="excellent">{{ __('ممتاز جداً (Très satisfaisant)') }}</option>
                            <option value="good">{{ __('ناجح وجيد (Satisfaisant)') }}</option>
                            <option value="average">{{ __('متوسط (Moyen)') }}</option>
                            <option value="needs_improvement">{{ __('بحاجة لتحسين وتكرار') }}</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('النقائص وصعوبات السيولة المسجلة:') }}</label>
                        <textarea wire:model="drillBottlenecks" rows="2" placeholder="{{ __('ازدحام عند مخرج الدرج، تأخر إخلاء الطابق...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('التدابير التصحيحية المقررة بالمخطط:') }}</label>
                        <textarea wire:model="drillCorrectiveMeasures" rows="2" placeholder="{{ __('إعادة توجيه حركة المرور، فحص مكبر الصوت...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeDrillModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitDrill" type="button" class="px-5 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                        {{ __('اعتماد وحفظ المحضر الرسمي') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 5: ADD INTERVENTION TEAM MEMBER                     -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isMemberModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-user-plus class="w-5 h-5 text-red-600" />
                        {{ __('تعيين عضو بفريق التدخل (PSI)') }}
                    </h3>
                    <button wire:click="closeMemberModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الفوج المسند:') }}</label>
                        <select wire:model="memberTeamType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="first_aid">{{ __('فوج الإسعافات الأولية والتضميد') }}</option>
                            <option value="evacuation_guide">{{ __('فوج التوجيه وتأمين مسالك الإخلاء') }}</option>
                            <option value="fire_containment">{{ __('فوج التدخل الأولي والإطفاء') }}</option>
                            <option value="security_perimeter">{{ __('فوج تأمين المنافذ والمحيط الخارجي') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اسم العضو:') }}</label>
                        <input type="text" wire:model="memberName" placeholder="{{ __('الاسم واللقب') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الرتبة والوظيفة بالمؤسسة:') }}</label>
                        <input type="text" wire:model="memberRole" placeholder="{{ __('أستاذ، مشرف تربية، عون صيانة...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الجناح أو المنطقة المسندة:') }}</label>
                        <input type="text" wire:model="memberZone" placeholder="{{ __('الجناح أ، المطعم، الساحة...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رقم هاتف الاتصال:') }}</label>
                        <input type="text" wire:model="memberPhone" placeholder="06XXXXXXXX" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" wire:model="memberIsCertified" id="firstAidCert" class="rounded text-red-600 focus:ring-red-500">
                        <label for="firstAidCert" class="text-gray-800 dark:text-gray-200 font-semibold">{{ __('متحصل على شهادة مسعف أولي من الحماية المدنية') }}</label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeMemberModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitMember" type="button" class="px-5 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                        {{ __('تعيين العضو بالفوج') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

