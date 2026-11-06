<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $sheet = $data['currentSheet'];
    @endphp

    <div class="space-y-6">

        {{-- Top Control & Filtering Bar --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg">
                    <x-heroicon-o-cake class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ __('المطعم المدرسي وتسيير نصف الداخلي (FJC)') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('الموسم الدراسي:') }} <span class="font-mono font-semibold">{{ $this->academicYear }}</span> · {{ $data['school']?->school_name }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Date Picker --}}
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-700 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600">
                    <span class="text-xs text-gray-500 dark:text-gray-400">📅 {{ __('اليوم:') }}</span>
                    <input type="date" wire:model.live="selectedDate" class="text-xs bg-transparent border-0 p-0 text-gray-900 dark:text-white font-mono focus:ring-0" />
                </div>

                {{-- Action Buttons --}}
                <x-filament::button wire:click="openAddEntryModal" size="sm" color="amber" icon="heroicon-m-plus-circle">
                    {{ __('إضافة مادة مستهلكة') }}
                </x-filament::button>

                <x-filament::button wire:click="openPantryModal" size="sm" color="gray" icon="heroicon-m-archive-box">
                    {{ __('تموين المخزن') }}
                </x-filament::button>

                <x-filament::button wire:click="seedStandardPantry" size="sm" color="gray" outlined icon="heroicon-m-sparkles">
                    {{ __('المؤونة القياسية') }}
                </x-filament::button>

                @if ($sheet)
                    <a href="{{ route('print.canteen.daily-sheet.pdf', ['locale' => app()->getLocale(), 'sheet' => $sheet->id]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                        <x-heroicon-m-printer class="w-4 h-4" />
                        {{ __('طباعة FJC (PDF)') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- 4 Metric Telemetry Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('التلاميذ نصف الداخليين') }}</span>
                    <x-heroicon-m-user-group class="w-4 h-4 text-emerald-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $sheet ? $sheet->half_board_students_present : 0 }}
                </div>
                <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">
                    {{ __('من أصل') }} {{ $data['totalHalfBoardEnrolled'] }} {{ __('مسجل بنصف الداخلي') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('إجمالي وجبات اليوم') }}</span>
                    <x-heroicon-m-cake class="w-4 h-4 text-blue-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $sheet ? $sheet->total_beneficiaries_count : 0 }}
                </div>
                <div class="mt-1 text-[11px] text-gray-500">
                    +{{ $sheet ? ($sheet->staff_beneficiaries_count + $sheet->service_workers_count) : 0 }} {{ __('موظف وعامل خدمة') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('تكلفة وجبة اليوم') }}</span>
                    <x-heroicon-m-banknotes class="w-4 h-4 text-amber-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ number_format((float) ($sheet?->total_cost ?? 0), 2) }} <span class="text-xs font-normal">{{ __('دج') }}</span>
                </div>
                <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400 font-mono">
                    {{ number_format((float) ($sheet?->cost_per_meal ?? 0), 2) }} {{ __('دج / الوجبة') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('تنبيهات الصحة والمخزن') }}</span>
                    <x-heroicon-m-bell-alert class="w-4 h-4 text-rose-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $data['studentsWithDietaryAlerts']->count() }}
                </div>
                <div class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">
                    {{ __('تلميذ بحساسية / بروتوكول PAI') }} · {{ $data['lowStockItems']->count() }} {{ __('مواد منخفضة') }}
                </div>
            </div>
        </div>

        {{-- Nav Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('sheet')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'sheet' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-document-text class="w-4 h-4" />
                {{ __('ورقة الاستهلاك اليومي (FJC)') }}
            </button>

            <button wire:click="setTab('pantry')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'pantry' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-archive-box class="w-4 h-4" />
                {{ __('مخزون المواد الغذائية') }} ({{ $data['pantryItems']->count() }})
            </button>

            <button wire:click="setTab('menus')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'menus' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-calendar-days class="w-4 h-4" />
                {{ __('البرنامج الأسبوعي للوجبات') }}
            </button>

            <button wire:click="setTab('bilan')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'bilan' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-chart-pie class="w-4 h-4" />
                {{ __('كشف التغذية لمديرية التربية') }}
            </button>
        </div>

        {{-- Tab 1: Daily Sheet (FJC) --}}
        @if ($activeTab === 'sheet')
            <div class="space-y-4">
                {{-- Headcount & Settings Bar --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">{{ __('رقم الورقة:') }}</span>
                            <span class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2.5 py-0.5 rounded">
                                {{ $sheet->reference_number }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded font-bold {{ $sheet->isCertified() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $sheet->status->label() }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if (! $sheet->isCertified())
                                <x-filament::button wire:click="certifySheet" size="xs" color="success" icon="heroicon-m-check-badge">
                                    {{ __('تأشيرة ومصادقة المقتصد') }}
                                </x-filament::button>
                            @else
                                <span class="text-xs text-emerald-600 font-bold">✓ {{ __('مؤشرة ومصادق عليها رسمياً') }}</span>
                            @endif

                            <x-filament::button wire:click="saveSheetDetails" size="xs" color="gray" icon="heroicon-m-check">
                                {{ __('حفظ التعداد والملاحظات') }}
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Headcounts --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('التلاميذ نصف الداخليين:') }}</label>
                            <input type="number" wire:model="halfBoardPresent" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs font-mono font-bold" />
                        </div>

                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الأساتذة والموظفون:') }}</label>
                            <input type="number" wire:model="staffPresent" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs font-mono" />
                        </div>

                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('عمال الخدمة والمطبخ:') }}</label>
                            <input type="number" wire:model="workersPresent" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs font-mono" />
                        </div>

                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('معاينة النظافة الصحية:') }}</label>
                            <input type="text" wire:model="hygieneStatus" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs" />
                        </div>

                        <div class="col-span-2">
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('قائمة وجبة اليوم (الطبق الرئيسي والمقبلات):') }}</label>
                            <input type="text" wire:model="menuDescription" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs" />
                        </div>

                        <div class="col-span-2">
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('تنبيهات الحساسيات الغذائية للوجبات الخاصة (PAI):') }}</label>
                            <input type="text" wire:model="allergyNotes" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 text-xs text-rose-600" />
                        </div>
                    </div>
                </div>

                {{-- Consumed Items Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                {{ __('المواد الغذائية والمؤونة المخرجة من المخزن لطهي وجبة اليوم') }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                {{ __('حساب آلي للمبالغ وتكلفة الوجبة الفردية مع الخصم الفعلي من مخزن المطعم.') }}
                            </p>
                        </div>

                        <x-filament::button wire:click="openAddEntryModal" size="xs" color="amber" icon="heroicon-m-plus">
                            {{ __('إضافة مادة غذائية') }}
                        </x-filament::button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-3">#</th>
                                    <th class="p-3">{{ __('الصنف') }}</th>
                                    <th class="p-3">{{ __('اسم المادة الغذائية') }}</th>
                                    <th class="p-3 text-center">{{ __('الوحدة') }}</th>
                                    <th class="p-3 text-center">{{ __('الكمية المستهلكة') }}</th>
                                    <th class="p-3 text-center">{{ __('سعر الوحدة (دج)') }}</th>
                                    <th class="p-3 text-center">{{ __('المبلغ الإجمالي (دج)') }}</th>
                                    <th class="p-3 text-center">{{ __('إجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($sheet->entries as $idx => $entry)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                        <td class="p-3 font-mono text-gray-400">{{ $idx + 1 }}</td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $entry->foodItem?->category?->label() }}
                                            </span>
                                        </td>
                                        <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $entry->foodItem?->name }}</td>
                                        <td class="p-3 text-center">{{ $entry->foodItem?->unit }}</td>
                                        <td class="p-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ number_format((float) $entry->quantity_consumed, 2) }}</td>
                                        <td class="p-3 text-center font-mono">{{ number_format((float) $entry->unit_price, 2) }}</td>
                                        <td class="p-3 text-center font-mono font-bold text-blue-600 dark:text-blue-400">{{ number_format((float) $entry->total_item_cost, 2) }}</td>
                                        <td class="p-3 text-center">
                                            <button wire:click="deleteConsumptionEntry({{ $entry->id }})" wire:confirm="{{ __('هل أنت متأكد من حذف هذه المادة واسترجاع الكمية للمخزن؟') }}" class="text-rose-500 hover:text-rose-700">
                                                <x-heroicon-m-trash class="w-4 h-4 inline" />
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-gray-400">
                                            {{ __('لم يتم تسجيل أية مواد مستهلكة في هذه الورقة اليومية بعد.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($sheet->entries->isNotEmpty())
                                <tfoot class="bg-gray-50 dark:bg-gray-750 font-bold border-t border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <td colspan="4" class="p-3 text-gray-900 dark:text-white">{{ __('المجموع الإجمالي لليوم:') }}</td>
                                        <td class="p-3 text-center font-mono text-emerald-600 dark:text-emerald-400">{{ number_format((float) $sheet->entries->sum('quantity_consumed'), 2) }}</td>
                                        <td class="p-3 text-center">—</td>
                                        <td class="p-3 text-center font-mono text-blue-600 dark:text-blue-400 text-sm">{{ number_format((float) $sheet->total_cost, 2) }} {{ __('دج') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tab 2: Pantry Inventory --}}
        @if ($activeTab === 'pantry')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ __('سجل مخزون المواد الغذائية والمؤونة بمطعم المؤسسة') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('متابعة دقيقة للأرصدة المتوفرة، عتبات الإنذار بالنفاد، وأسعار التموين المعتمدة.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::button wire:click="openPantryModal" size="xs" color="amber" icon="heroicon-m-plus">
                            {{ __('إضافة مادة جديدة') }}
                        </x-filament::button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">{{ __('الصنف') }}</th>
                                <th class="p-3">{{ __('اسم المادة') }}</th>
                                <th class="p-3 text-center">{{ __('الوحدة') }}</th>
                                <th class="p-3 text-center">{{ __('سعر الوحدة (دج)') }}</th>
                                <th class="p-3 text-center">{{ __('الرصيد بالمخزن') }}</th>
                                <th class="p-3 text-center">{{ __('عتبة الإنذار') }}</th>
                                <th class="p-3 text-center">{{ __('حالة المخزون') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($data['pantryItems'] as $idx => $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 font-mono text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $item->category->label() }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $item->name }}</td>
                                    <td class="p-3 text-center">{{ $item->unit }}</td>
                                    <td class="p-3 text-center font-mono">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="p-3 text-center font-mono font-bold {{ $item->isStockLow() ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ number_format((float) $item->current_stock, 2) }}
                                    </td>
                                    <td class="p-3 text-center font-mono text-gray-400">{{ number_format((float) $item->safety_threshold, 2) }}</td>
                                    <td class="p-3 text-center">
                                        @if ($item->isStockLow())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                ⚠️ {{ __('مخزون منخفض') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                ✓ {{ __('متوفر') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-400">
                                        {{ __('لا توجد مواد مسجلة في المخزن. اضغط على "المؤونة القياسية" لتهيئة المواد الوطنية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Tab 3: Weekly Menus --}}
        @if ($activeTab === 'menus')
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('البرنامج الأسبوعي للوجبات الغذائية المعتمد للمطعم') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('جدول متوازن غذائياً ومصادق عليه من طبيب وحدة الكشف والمتابعة الصحية UDS ومدير المؤسسة.') }}
                        </p>
                    </div>

                    @if ($data['weeklyMenu'])
                        <a href="{{ route('print.canteen.weekly-menu.pdf', ['locale' => app()->getLocale(), 'menu' => $data['weeklyMenu']->id]) }}" target="_blank" class="px-3.5 py-2 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 rounded-lg flex items-center gap-1.5 self-start md:self-auto">
                            <x-heroicon-m-printer class="w-4 h-4" />
                            {{ __('طباعة المعلقة الأسبوعية (PDF)') }}
                        </a>
                    @endif
                </div>

                {{-- Days Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <div class="font-bold text-xs text-amber-600 dark:text-amber-400 border-b border-gray-100 dark:border-gray-700 pb-1">
                            🗓️ {{ __('الأحد') }}
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ $data['weeklyMenu']?->sunday_menu ?? __('أرز مفور بالخضر ولحم البقر الطازج · سلطة خضراء · فاكهة موسمية · خبز.') }}
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <div class="font-bold text-xs text-amber-600 dark:text-amber-400 border-b border-gray-100 dark:border-gray-700 pb-1">
                            🗓️ {{ __('الاثنين') }}
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ $data['weeklyMenu']?->monday_menu ?? __('عدس يابس مطهو بالخضار والدجاج · بيض مسلوق · جبن أبيض · فاكهة · خبز.') }}
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <div class="font-bold text-xs text-amber-600 dark:text-amber-400 border-b border-gray-100 dark:border-gray-700 pb-1">
                            🗓️ {{ __('الثلاثاء') }}
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ $data['weeklyMenu']?->tuesday_menu ?? __('كسكسي تقليدي بمرق الخضر والدجاج · سلطة شوندر · ياغورت · خبز.') }}
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <div class="font-bold text-xs text-amber-600 dark:text-amber-400 border-b border-gray-100 dark:border-gray-700 pb-1">
                            🗓️ {{ __('الأربعاء') }}
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ $data['weeklyMenu']?->wednesday_menu ?? __('مقرونة غراتان بالصلصة واللحم المفروم · سلطة خيار · فاكهة · خبز.') }}
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <div class="font-bold text-xs text-amber-600 dark:text-amber-400 border-b border-gray-100 dark:border-gray-700 pb-1">
                            🗓️ {{ __('الخميس') }}
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ $data['weeklyMenu']?->thursday_menu ?? __('بطاطا كوشة بالسمك الطازج (سردين) · حساء خضار دافئ · فاكهة · خبز.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tab 4: Bilan --}}
        @if ($activeTab === 'bilan')
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('الكشف الشهري لحركة التغذية والإطعام المدرسي') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('التقرير الرسمي الموجه لمصلحة المالية والتجهيز بمديرية التربية لتبرير استهلاك الاعتمادات المالية.') }}
                        </p>
                    </div>

                    <a href="{{ route('print.canteen.periodic-bilan.pdf', ['locale' => app()->getLocale()]) }}" target="_blank" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg flex items-center gap-1.5 self-start md:self-auto shadow-sm">
                        <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                        {{ __('تصدير كشف مديرية التربية (PDF)') }}
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                    <div class="p-3 bg-gray-50 dark:bg-gray-750 rounded-lg">
                        <div class="text-[11px] text-gray-500">{{ __('إجمالي وجبات الشهر:') }}</div>
                        <div class="text-xl font-bold font-mono text-gray-900 dark:text-white mt-1">{{ number_format($data['monthlyMealsServed']) }}</div>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-750 rounded-lg">
                        <div class="text-[11px] text-gray-500">{{ __('المصاريف الإجمالية للتموين:') }}</div>
                        <div class="text-xl font-bold font-mono text-blue-600 dark:text-blue-400 mt-1">{{ number_format($data['monthlyExpenditure'], 2) }} {{ __('دج') }}</div>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-750 rounded-lg">
                        <div class="text-[11px] text-gray-500">{{ __('متوسط تكلفة الوجبة:') }}</div>
                        <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">{{ number_format($data['averageCostPerMeal'], 2) }} {{ __('دج') }}</div>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-750 rounded-lg">
                        <div class="text-[11px] text-gray-500">{{ __('الحالة المحاسبية:') }}</div>
                        <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ __('مطابقة للاعتمادات المالية المخصصة') }}</div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Add Consumption Entry Modal --}}
    @if ($isAddEntryModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        {{ __('إضافة مادة غذائية مستهلكة في وجبة اليوم') }}
                    </h3>
                    <button wire:click="closeAddEntryModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('المادة الغذائية المخرجة من المخزن:') }}</label>
                        <select wire:model.live="entryFoodItemId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            @foreach ($data['pantryItems'] as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ __('المتوفر:') }} {{ $item->current_stock }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الكمية المخرجة:') }}</label>
                        <input type="number" step="0.1" wire:model="entryQuantity" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs font-mono font-bold" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('سعر الوحدة المطبق (دج):') }}</label>
                        <input type="number" step="0.1" wire:model="entryUnitPrice" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs font-mono" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeAddEntryModal" type="button" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded-lg">
                        {{ __('إلغاء') }}
                    </button>
                    <x-filament::button wire:click="saveConsumptionEntry" color="amber" size="sm">
                        {{ __('تأكيد الإخراج والاستهلاك') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add/Restock Pantry Item Modal --}}
    @if ($isPantryModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        {{ __('إضافة أو تموين مادة غذائية في المخزن') }}
                    </h3>
                    <button wire:click="closePantryModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('اسم المادة:') }}</label>
                        <input type="text" wire:model="pantryName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" placeholder="{{ __('مثال: جبن أحمر مبشور') }}" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الصنف:') }}</label>
                        <select wire:model="pantryCategory" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="meat_fish">{{ __('لحوم ودواجن وأسماك') }}</option>
                            <option value="produce">{{ __('خضر وفواكه طازجة') }}</option>
                            <option value="dry_goods">{{ __('بقوليات وبقالة وحبوب') }}</option>
                            <option value="dairy">{{ __('ألبان وأجبان وبيض') }}</option>
                            <option value="bakery">{{ __('مخبوزات وعجائن') }}</option>
                            <option value="oils_condiments">{{ __('زيوت وتوابل ومعلبات') }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الوحدة:') }}</label>
                            <input type="text" wire:model="pantryUnit" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                        </div>

                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('سعر الوحدة (دج):') }}</label>
                            <input type="number" step="0.1" wire:model="pantryUnitPrice" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الكمية المضافة للمخزن:') }}</label>
                            <input type="number" step="0.1" wire:model="pantryCurrentStock" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs font-mono font-bold" />
                        </div>

                        <div>
                            <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('عتبة الإنذار بالنفاد:') }}</label>
                            <input type="number" step="0.1" wire:model="pantryThreshold" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs font-mono" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closePantryModal" type="button" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded-lg">
                        {{ __('إلغاء') }}
                    </button>
                    <x-filament::button wire:click="savePantryItem" color="amber" size="sm">
                        {{ __('حفظ وتحديث المخزن') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
