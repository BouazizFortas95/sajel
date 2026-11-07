<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 rounded-xl">
                    <x-heroicon-o-wrench-screwdriver class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ __('صيانة المنشآت المدرسية، التدفئة المركزية وسجل الطاقة والمرافق') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('إدارة أوامر الأشغال والصيانة الوقائية، المراقبة التقنية للغلايات والتدفئة، تطهير باشات الماء ومطابقة فواتير الطاقة (سونلغاز / ADE)') }}
                    </p>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl">
                <button
                    wire:click="switchTab('work_orders')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'work_orders' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-wrench class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('الهياكل وأوامر الأشغال') }}
                </button>
                <button
                    wire:click="switchTab('central_heating')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'central_heating' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-fire class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('التدفئة المركزية والغلايات') }}
                </button>
                <button
                    wire:click="switchTab('water_hygiene')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'water_hygiene' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-beaker class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('باشات وخزانات الماء الشروب') }}
                </button>
                <button
                    wire:click="switchTab('utilities_energy')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'utilities_energy' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-bolt class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('عدادات الطاقة واستهلاك المرافق') }}
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 1: WORK ORDERS & FACILITIES                           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'work_orders')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي المنشآت والهياكل') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['total_facilities'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ $this->telemetry['facilities_needing_restoration'] }} {{ __('بحاجة لترميم') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أوامر الصيانة المفتوحة') }}</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $this->telemetry['open_work_orders'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('قيد الإنجاز والبرمجة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أعطاب طارئة ومستعجلة') }}</div>
                    <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $this->telemetry['urgent_emergencies'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('أولوية قصوى وسلامة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الأوامر المنجزة والمغلقة') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $this->telemetry['completed_work_orders'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('سنة') }} {{ $academicYear }}</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="openWorkOrderModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('تسجيل أمر أشغال جديد (Bon de travail)') }}
                    </button>
                    <button
                        wire:click="openFacilityModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                        <x-heroicon-m-building-office class="w-4 h-4" />
                        {{ __('إضافة هيكل / جناح') }}
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="{{ __('بحث في أوامر الصيانة والمواقع...') }}"
                        class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white w-64"
                    />
                    <select
                        wire:model.live="categoryFilter"
                        class="text-xs px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع المجالات') }}</option>
                        @foreach(\App\Enums\MaintenanceCategory::cases() as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                    <select
                        wire:model.live="priorityFilter"
                        class="text-xs px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع الأولويات') }}</option>
                        @foreach(\App\Enums\MaintenancePriority::cases() as $pr)
                            <option value="{{ $pr->value }}">{{ $pr->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Work Orders Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رقم الأمر') }}</th>
                                <th class="py-3 px-4">{{ __('الهيكل / الموقع الدقيق') }}</th>
                                <th class="py-3 px-4">{{ __('المجال') }}</th>
                                <th class="py-3 px-4">{{ __('الأولوية') }}</th>
                                <th class="py-3 px-4">{{ __('وصف العطب') }}</th>
                                <th class="py-3 px-4">{{ __('تاريخ التبليغ') }}</th>
                                <th class="py-3 px-4">{{ __('التكلفة المنجزة') }}</th>
                                <th class="py-3 px-4">{{ __('الحالة') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->workOrders as $order)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $order->work_order_number }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ $order->facility?->facility_name ?? __('موقع غير مصنف') }}
                                        </div>
                                        <div class="text-[11px] text-gray-500">{{ $order->exact_location }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300">
                                            {{ $order->category?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($order->priority_level === \App\Enums\MaintenancePriority::EmergencySafety) bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400
                                            @elseif($order->priority_level === \App\Enums\MaintenancePriority::Urgent) bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400
                                            @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 @endif">
                                            {{ $order->priority_level?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs truncate" title="{{ $order->issue_description }}">
                                        {{ $order->issue_description }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $order->report_date?->format('Y-m-d') }}
                                        <div class="text-[10px] text-gray-400">{{ $order->reported_by_name }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-900 dark:text-white">
                                        {{ number_format((float) $order->actual_cost, 2) }} دج
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($order->status === \App\Enums\MaintenanceStatus::Completed) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @elseif($order->status === \App\Enums\MaintenanceStatus::InProgress) bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400
                                            @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400 @endif">
                                            {{ $order->status?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if($order->status !== \App\Enums\MaintenanceStatus::Completed)
                                                <button
                                                    wire:click="openCompleteModal({{ $order->id }})"
                                                    class="px-2 py-1 text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 rounded transition font-medium"
                                                    title="{{ __('تأكيد إنجاز الأشغال') }}">
                                                    {{ __('إنجاز') }}
                                                </button>
                                            @endif
                                            <a
                                                href="{{ route('print.maintenance.work-order', ['locale' => app()->getLocale(), 'workOrder' => $order->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition"
                                                title="{{ __('معاينة أمر الأشغال') }}">
                                                <x-heroicon-m-printer class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.maintenance.work-order.pdf', ['locale' => app()->getLocale(), 'workOrder' => $order->id]) }}"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition font-bold"
                                                title="{{ __('تحميل PDF') }}">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد أوامر أشغال مسجلة وفق معايير البحث المحددة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 2: CENTRAL HEATING & BOILERS                          -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'central_heating')
            <!-- Safety Compliance Alert Banner -->
            <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/30 dark:to-orange-950/30 border border-red-200 dark:border-red-900/50 rounded-xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400 rounded-lg shrink-0">
                        <x-heroicon-o-shield-exclamation class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-950 dark:text-red-300">
                            {{ __('بروتوكول السلامة الإلزامي للتدفئة المركزية والغلايات (Sécurité Chaufferie & CO)') }}
                        </h4>
                        <p class="text-xs text-red-800 dark:text-red-400 mt-0.5">
                            {{ __('تطبيقاً للمنشور الوزاري الخاص بموسم الشتاء: إلزامية فحص صمام الأمان، تطهير شبكة المشعات، تنظيف المداخن، والتأكد التام من جاهزية كواشف أحادي أكسيد الكربون وفتحات التهوية.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="openHeatingModal"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-sm transition whitespace-nowrap">
                        <x-heroicon-m-plus class="w-4 h-4 inline -mt-0.5" />
                        {{ __('تقييد محضر معاينة تقنية للغلاية') }}
                    </button>
                </div>
            </div>

            <!-- Heating Logs Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رقم المحضر') }}</th>
                                <th class="py-3 px-4">{{ __('رمز الغلاية والطراز') }}</th>
                                <th class="py-3 px-4">{{ __('نوع الوقود') }}</th>
                                <th class="py-3 px-4">{{ __('تاريخ المعاينة') }}</th>
                                <th class="py-3 px-4">{{ __('الضغط / الحرارة') }}</th>
                                <th class="py-3 px-4">{{ __('صمام الأمان & كاشف CO') }}</th>
                                <th class="py-3 px-4">{{ __('المداخن & التهوية') }}</th>
                                <th class="py-3 px-4">{{ __('قرار الصلاحية') }}</th>
                                <th class="py-3 px-4">{{ __('المشرف المعتمد') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('محضر المعاينة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->heatingLogs as $hlog)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $hlog->log_reference }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $hlog->boiler_code }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $hlog->boiler_brand_model ?: 'Chaufferie Centrale' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            {{ $hlog->fuel_type?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        {{ $hlog->inspection_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono">
                                        <div><strong>{{ $hlog->pressure_bar }}</strong> Bar</div>
                                        <div class="text-[10px] text-gray-500">{{ $hlog->water_temp_celsius }} °C</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 text-[11px] {{ $hlog->safety_valve_tested && $hlog->co_detector_tested ? 'text-emerald-600' : 'text-rose-600' }}">
                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" />
                                            {{ __('صمام سليم وكاشف مفعل') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 text-[11px] {{ $hlog->chimneys_cleaned && $hlog->room_ventilation_ok ? 'text-emerald-600' : 'text-amber-600' }}">
                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" />
                                            {{ __('تهوية ومداخن مطابقة') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($hlog->status === \App\Enums\HeatingInspectionStatus::CompliantApproved) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @elseif($hlog->status === \App\Enums\HeatingInspectionStatus::ConditionalUse) bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400
                                            @else bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400 @endif">
                                            {{ $hlog->status?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $hlog->certified_by_name }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('print.maintenance.heating-pv', ['locale' => app()->getLocale(), 'log' => $hlog->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition">
                                                <x-heroicon-m-printer class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.maintenance.heating-pv.pdf', ['locale' => app()->getLocale(), 'log' => $hlog->id]) }}"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition font-bold">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد محاضر مراقبة للتدفئة المركزية مقيدة لهذه السنة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 3: WATER HYGIENE & DISINFECTION                       -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'water_hygiene')
            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('سجل النظافة والتطهير الدوري لخزانات وباشات الماء الشروب (Hygiène des Bâches à Eau)') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('متابعة دورية كل 3 أشهر مع المكتب البلدي للنظافة BHM، وقياس نسبة الكلور المتبقي الصالح للشرب.') }}
                    </p>
                </div>
                <button
                    wire:click="openWaterModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <x-heroicon-m-plus class="w-4 h-4" />
                    {{ __('تسجيل عملية غسيل وتطهير خزان / باشة') }}
                </button>
            </div>

            <!-- Water Inspections Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رقم السجل') }}</th>
                                <th class="py-3 px-4">{{ __('الخزان المعني') }}</th>
                                <th class="py-3 px-4">{{ __('النوع الهندسي') }}</th>
                                <th class="py-3 px-4">{{ __('السعة التخزينية') }}</th>
                                <th class="py-3 px-4">{{ __('تاريخ التدخل') }}</th>
                                <th class="py-3 px-4">{{ __('الكلور المتبقي') }}</th>
                                <th class="py-3 px-4">{{ __('المطابقة للشرب') }}</th>
                                <th class="py-3 px-4">{{ __('موعد التدخل القادم') }}</th>
                                <th class="py-3 px-4">{{ __('الجهة المنفذة') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('شهادة النظافة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->waterInspections as $winsp)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $winsp->inspection_reference }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $winsp->tank_name_code }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $winsp->tank_type?->label() }}
                                    </td>
                                    <td class="py-3 px-4 font-mono">
                                        {{ $winsp->capacity_cubic_meters }} م³
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        {{ $winsp->inspection_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-sky-700 dark:text-sky-400">
                                        {{ $winsp->residual_chlorine_ppm }} PPM
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($winsp->water_potable_clearance === \App\Enums\WaterPotabilityStatus::CompliantPotable) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @else bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400 @endif">
                                            {{ $winsp->water_potable_clearance?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $winsp->next_disinfection_due_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $winsp->executed_by }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('print.maintenance.water-register', ['locale' => app()->getLocale(), 'inspection' => $winsp->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition">
                                                <x-heroicon-m-printer class="w-3.5 h-3.5 inline" />
                                            </a>
                                            <a
                                                href="{{ route('print.maintenance.water-register.pdf', ['locale' => app()->getLocale(), 'inspection' => $winsp->id]) }}"
                                                class="px-2 py-1 text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded transition font-bold">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد سجلات تطهير لخزانات وباشات الماء مقيدة لهذه السنة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 4: ENERGY & UTILITY METERS (SONELGAZ / ADE)           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'utilities_energy')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي العدادات النشطة') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['active_meters_count'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('كهرباء، غاز، ماء') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('حجم نفقات الفواتير') }}</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1 font-mono">
                        {{ number_format($this->telemetry['total_utility_bills_volume_dzd'], 2) }} دج
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('سنة') }} {{ $academicYear }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('مطابقة الفواتير مع العدادات') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">100%</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('تدقيق محاسبي دوري') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('طباعة الحصيلة السنوية') }}</div>
                    <div class="flex items-center gap-2 mt-2">
                        <a
                            href="{{ route('print.maintenance.energy-bilan', ['locale' => app()->getLocale(), 'academicYear' => str_replace('/', '-', $academicYear)]) }}"
                            target="_blank"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1">
                            <x-heroicon-m-printer class="w-3.5 h-3.5" />
                            {{ __('معاينة') }}
                        </a>
                        <a
                            href="{{ route('print.maintenance.energy-bilan.pdf', ['locale' => app()->getLocale(), 'academicYear' => str_replace('/', '-', $academicYear)]) }}"
                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition font-mono">
                            PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="openBillModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('تسجيل قراءة عداد ومطابقة فاتورة') }}
                    </button>
                    <button
                        wire:click="openMeterModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                        <x-heroicon-m-cpu-chip class="w-4 h-4" />
                        {{ __('إضافة عداد جديد') }}
                    </button>
                </div>
            </div>

            <!-- Bills Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">{{ __('رقم السند') }}</th>
                                <th class="py-3 px-4">{{ __('العداد والمرفق') }}</th>
                                <th class="py-3 px-4">{{ __('الفصل / الثلاثي') }}</th>
                                <th class="py-3 px-4">{{ __('تاريخ القراءة') }}</th>
                                <th class="py-3 px-4">{{ __('الرمز السابق') }}</th>
                                <th class="py-3 px-4">{{ __('الرمز الحالي') }}</th>
                                <th class="py-3 px-4">{{ __('الاستهلاك') }}</th>
                                <th class="py-3 px-4">{{ __('مبلغ الفاتورة') }}</th>
                                <th class="py-3 px-4">{{ __('رقم الفاتورة') }}</th>
                                <th class="py-3 px-4">{{ __('حالة المطابقة') }}</th>
                                <th class="py-3 px-4">{{ __('المدقق') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->bills as $bill)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $bill->bill_reference }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $bill->meter?->meter_code }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $bill->meter?->meter_type?->label() }} ({{ $bill->meter?->utility_provider }})</div>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $bill->billing_period_quarter }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $bill->reading_date_start?->format('Y-m-d') }} إلى {{ $bill->reading_date_end?->format('Y-m-d') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono">{{ number_format($bill->previous_index) }}</td>
                                    <td class="py-3 px-4 font-mono">{{ number_format($bill->current_index) }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($bill->consumption_units) }} {{ $bill->meter?->unit_of_measure }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ number_format((float) $bill->invoiced_amount_dzd, 2) }} دج
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $bill->invoice_number ?: '—' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($bill->payment_status === \App\Enums\UtilityPaymentStatus::MandatedPaid) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @elseif($bill->payment_status === \App\Enums\UtilityPaymentStatus::AuditedApproved) bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400
                                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 @endif">
                                            {{ $bill->payment_status?->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ $bill->meter_auditor_name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد سجلات فواتير مقيدة لهذه السنة.') }}
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
    <!-- MODAL: CREATE WORK ORDER                                  -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isWorkOrderModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل أمر أشغال وتدخل صيانة جديد (Bon de travail)') }}
                    </h3>
                    <button wire:click="closeWorkOrderModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الهيكل / الجناح المعني') }}</label>
                            <select wire:model="selectedFacilityId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="">{{ __('اختر الهيكل (اختياري)...') }}</option>
                                @foreach($this->facilities as $fac)
                                    <option value="{{ $fac->id }}">{{ $fac->facility_name }} ({{ $fac->facility_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الموقع الدقيق للعطب') }} *</label>
                            <input type="text" wire:model="exactLocation" placeholder="مثال: القاعة رقم 04، الطابق الأول" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('مجال الصيانة') }} *</label>
                            <select wire:model="workCategory" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\MaintenanceCategory::cases() as $cat)
                                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('درجة الأولوية') }} *</label>
                            <select wire:model="priorityLevel" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\MaintenancePriority::cases() as $pr)
                                    <option value="{{ $pr->value }}">{{ $pr->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('وصف دقيق للعطب والأشغال المطلوبة') }} *</label>
                        <textarea wire:model="issueDescription" rows="3" placeholder="وصف العطب، القطع التالفة، الخطر المحتمل..." class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المُبلّغ') }} *</label>
                            <input type="text" wire:model="reportedByName" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('طبيعة جهة التدخل') }}</label>
                            <select wire:model="interventionType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="InternalStaff">{{ __('أعوان الصيانة بالمؤسسة (OP)') }}</option>
                                <option value="ExternalContractor">{{ __('مقاولة / مؤسسة خاصة معتمدة') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('التكلفة التقديرية (دج)') }}</label>
                            <input type="number" step="0.01" wire:model="estimatedCost" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التدخل المبرمج') }}</label>
                            <input type="date" wire:model="scheduledDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeWorkOrderModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveWorkOrder" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                        {{ __('حفظ وتوليد أمر الأشغال') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: COMPLETE WORK ORDER                                -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isCompleteModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تأكيد إنجاز الأشغال وغلق أمر الصيانة') }}
                    </h3>
                    <button wire:click="closeCompleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الانتهاء من الأشغال') }} *</label>
                        <input type="date" wire:model="completionDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('التكلفة الفعلية النهائية (دج)') }}</label>
                        <input type="number" step="0.01" wire:model="actualCost" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تقرير التقني وملاحظات الإنجاز') }}</label>
                        <textarea wire:model="technicianNotes" rows="3" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeCompleteModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveCompletedOrder" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                        {{ __('تأكيد الإنجاز والغلق') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: ADD FACILITY                                       -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isFacilityModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('إضافة هيكل / جناح مدرسي') }}
                    </h3>
                    <button wire:click="closeFacilityModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم الهيكل / الجناح') }} *</label>
                        <input type="text" wire:model="facilityName" placeholder="مثال: الجناح البيداغوجي ج" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الهيكل') }}</label>
                            <select wire:model="facilityType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\FacilityType::cases() as $ft)
                                    <option value="{{ $ft->value }}">{{ $ft->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('المبنى / البلوك') }}</label>
                            <input type="text" wire:model="buildingBlock" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد الحجرات') }}</label>
                            <input type="number" wire:model="totalRoomsCount" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الحالة العامة') }}</label>
                            <select wire:model="conditionRating" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\FacilityCondition::cases() as $cond)
                                    <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeFacilityModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveFacility" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                        {{ __('حفظ الهيكل') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: HEATING INSPECTION                                 -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isHeatingModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تقييد محضر معاينة تقنية للتدفئة المركزية والغلايات') }}
                    </h3>
                    <button wire:click="closeHeatingModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('رمز الغلاية') }} *</label>
                            <input type="text" wire:model="boilerCode" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('العلامة والطراز') }}</label>
                            <input type="text" wire:model="boilerBrand" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الوقود') }}</label>
                            <select wire:model="fuelType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\BoilerFuelType::cases() as $bf)
                                    <option value="{{ $bf->value }}">{{ $bf->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ المعاينة') }} *</label>
                            <input type="date" wire:model="heatingInspectionDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('ضغط الماء بالشبكة (Bar)') }}</label>
                            <input type="number" step="0.1" wire:model="pressureBar" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('درجة الحرارة (°C)') }}</label>
                            <input type="number" wire:model="waterTempCelsius" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="space-y-2 border border-gray-200 dark:border-gray-800 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                        <div class="font-bold text-gray-800 dark:text-gray-200 mb-1">{{ __('فحوصات السلامة الإلزامية:') }}</div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="safetyValveTested" class="rounded border-gray-300 text-emerald-600" />
                                <span>{{ __('صمام الأمان مجرب وصالح') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="coDetectorTested" class="rounded border-gray-300 text-emerald-600" />
                                <span>{{ __('كاشف أحادي أكسيد الكربون CO مفعل') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="roomVentilationOk" class="rounded border-gray-300 text-emerald-600" />
                                <span>{{ __('فتحات التهوية الطبيعية مطابقة') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="chimneysCleaned" class="rounded border-gray-300 text-emerald-600" />
                                <span>{{ __('المداخن مُنظفة وسالكة') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('قرار الصلاحية النهائي') }}</label>
                            <select wire:model="heatingStatus" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\HeatingInspectionStatus::cases() as $hs)
                                    <option value="{{ $hs->value }}">{{ $hs->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('القائم بالمعاينة والمصادقة') }} *</label>
                            <input type="text" wire:model="heatingCertifiedBy" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeHeatingModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveHeatingInspection" class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white">
                        {{ __('حفظ محضر التدفئة') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: WATER TANK DISINFECTION                            -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isWaterModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل عملية غسيل وتطهير خزان وباشة الماء') }}
                    </h3>
                    <button wire:click="closeWaterModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تعريف الخزان / الباشة') }} *</label>
                            <input type="text" wire:model="tankCode" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('النوع الهندسي') }}</label>
                            <select wire:model="tankType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\WaterTankType::cases() as $wt)
                                    <option value="{{ $wt->value }}">{{ $wt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('السعة (م³)') }}</label>
                            <input type="number" step="0.1" wire:model="tankCapacityM3" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التدخل') }} *</label>
                            <input type="date" wire:model="waterInspectionDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الجهة المنفذة') }} *</label>
                            <input type="text" wire:model="executedByEntity" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نسبة الكلور المتبقي (PPM)') }}</label>
                            <input type="number" step="0.05" wire:model="residualChlorine" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('شهادة المطابقة للشرب') }}</label>
                            <select wire:model="waterClearance" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\WaterPotabilityStatus::cases() as $wps)
                                    <option value="{{ $wps->value }}">{{ $wps->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التدخل القادم') }}</label>
                            <input type="date" wire:model="nextWaterDueDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeWaterModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveWaterDisinfection" class="px-4 py-2 text-xs font-semibold rounded-lg bg-sky-600 hover:bg-sky-700 text-white">
                        {{ __('حفظ محضر التطهير') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: REGISTER UTILITY METER                             -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isMeterModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل عداد طاقة / مياه جديد') }}
                    </h3>
                    <button wire:click="closeMeterModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع العداد') }}</label>
                            <select wire:model="meterType" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\UtilityMeterType::cases() as $mt)
                                    <option value="{{ $mt->value }}">{{ $mt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('المورد / الشركة') }}</label>
                            <input type="text" wire:model="meterProvider" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الرقم التسلسلي للعداد (N° de série)') }} *</label>
                        <input type="text" wire:model="meterSerial" placeholder="مثال: 98452104" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('موقع العداد بالمؤسسة') }} *</label>
                        <input type="text" wire:model="meterLocation" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeMeterModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveUtilityMeter" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                        {{ __('حفظ العداد') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL: RECORD UTILITY BILL                                -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isBillModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ __('تسجيل قراءة دورية للعداد ومطابقة الفاتورة') }}
                    </h3>
                    <button wire:click="closeBillModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('العداد المعني') }} *</label>
                            <select wire:model="selectedMeterId" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach($this->meters as $m)
                                    <option value="{{ $m->id }}">{{ $m->meter_code }} - {{ $m->meter_type?->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الفصل / الثلاثي') }}</label>
                            <select wire:model="billingQuarter" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="Q1">{{ __('الثلاثي الأول (Q1)') }}</option>
                                <option value="Q2">{{ __('الثلاثي الثاني (Q2)') }}</option>
                                <option value="Q3">{{ __('الثلاثي الثالث (Q3)') }}</option>
                                <option value="Q4">{{ __('الثلاثي الرابع (Q4)') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ بداية القراءة') }} *</label>
                            <input type="date" wire:model="readingStartDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ نهاية القراءة') }} *</label>
                            <input type="date" wire:model="readingEndDate" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الرمز السابق (Ancien index)') }} *</label>
                            <input type="number" wire:model="previousIndex" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('الرمز الحالي (Nouvel index)') }} *</label>
                            <input type="number" wire:model="currentIndex" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('مبلغ الفاتورة (دج)') }} *</label>
                            <input type="number" step="0.01" wire:model="invoicedAmountDzd" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الفاتورة الرسمية') }}</label>
                            <input type="text" wire:model="invoiceNumber" placeholder="رقم فاتورة سونلغاز / ADE" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('حالة المطابقة والتسديد') }}</label>
                            <select wire:model="billPaymentStatus" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\UtilityPaymentStatus::cases() as $ups)
                                    <option value="{{ $ups->value }}">{{ $ups->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('القائم بالمطابقة والتدقيق') }} *</label>
                            <input type="text" wire:model="meterAuditor" class="w-full text-xs p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
                    <button wire:click="closeBillModal" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="saveUtilityBill" class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white">
                        {{ __('حفظ ومطابقة الفاتورة') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
