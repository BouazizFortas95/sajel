<x-filament-panels::page>
    @php
        $receptionService = app(\App\Services\TeacherParentReceptionService::class);
        $stats = $this->stats;
        $teachers = $this->teachers;
        $subjects = $this->subjects;
        $canManage = auth()->user()?->isAdmin() || auth()->user()?->isLeadership();
    @endphp

    <div class="space-y-6">
        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('إجمالي أساتذة المؤسسة') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-950/50 rounded-lg text-blue-600 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="w-6 h-6" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('مواعيد استقبال محددة ومعتمدة') }}</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['assigned'] }}</p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-lg text-emerald-600 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-check-badge" class="w-6 h-6" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('مواعيد قيد التحديد (بحاجة لفراغ)') }}</p>
                    <p class="text-2xl font-bold {{ $stats['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500' }} mt-1">
                        {{ $stats['pending'] }}
                    </p>
                </div>
                <div class="p-3 {{ $stats['pending'] > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-gray-50 dark:bg-gray-800 text-gray-400' }} rounded-lg">
                    <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Filters & Search Bar --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex-1 w-full md:w-auto relative">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="{{ __('البحث باسم الأستاذ...') }}"
                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 pr-9 focus:ring-primary-500 focus:border-primary-500"
                />
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <select
                    wire:model.live="selectedSubjectId"
                    class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 px-3"
                >
                    <option value="">{{ __('جميع المواد التعليمية') }}</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="selectedDay"
                    class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 px-3"
                >
                    <option value="">{{ __('جميع أيام الأسبوع') }}</option>
                    <option value="sunday">{{ __('الأحد') }}</option>
                    <option value="monday">{{ __('الإثنين') }}</option>
                    <option value="tuesday">{{ __('الثلاثاء') }}</option>
                    <option value="wednesday">{{ __('الأربعاء') }}</option>
                    <option value="thursday">{{ __('الخميس') }}</option>
                </select>

                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-1">
                    <button
                        type="button"
                        wire:click="$set('selectedStatus', 'all')"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $selectedStatus === 'all' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}"
                    >
                        {{ __('الكل') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedStatus', 'assigned')"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $selectedStatus === 'assigned' ? 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}"
                    >
                        {{ __('المعتمد') }} ({{ $stats['assigned'] }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedStatus', 'pending')"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $selectedStatus === 'pending' ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}"
                    >
                        {{ __('قيد التحديد') }} ({{ $stats['pending'] }})
                    </button>
                </div>
            </div>
        </div>

        {{-- Teachers List & Gap Analysis --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="w-5 h-5 text-gray-500" />
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                        {{ __('جدول الأساتذة ومواعيد استقبال أولياء التلاميذ') }} ({{ $teachers->count() }})
                    </h3>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('الموعد يُعتمد مباشرة في استدعاءات أولياء التلاميذ للمتابعة السلوكية.') }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-3 text-center w-12">#</th>
                            <th class="p-3">{{ __('الأستاذ(ة)') }}</th>
                            <th class="p-3">{{ __('المادة المقررة') }}</th>
                            <th class="p-3">{{ __('الأقسام المسندة') }}</th>
                            <th class="p-3 text-center">{{ __('الحصص') }}</th>
                            <th class="p-3">{{ __('فترات الفراغ المكتشفة في جدول التوقيت') }}</th>
                            <th class="p-3">{{ __('موعد الاستقبال المعتمد') }}</th>
                            @if ($canManage)
                                <th class="p-3 text-center w-28">{{ __('إجراءات') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($teachers as $idx => $t)
                            @php
                                $classes = $t->schedules
                                    ->map(fn ($s) => $s->classroom?->name ?? $s->group?->classroom?->name)
                                    ->filter()
                                    ->unique()
                                    ->sort(SORT_NATURAL)
                                    ->values();
                                $gaps = $receptionService->detectGapSlots($t);
                                $hasSlot = $t->hasParentReceptionSlot();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="p-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="p-3">
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $t->name }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $t->email }}</div>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ $t->primarySubject?->name ?? __('عام') }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @forelse ($classes as $cName)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                                {{ $cName }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 text-[11px]">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="p-3 text-center font-bold text-gray-700 dark:text-gray-300">
                                    {{ $t->schedules->count() }}
                                </td>
                                <td class="p-3">
                                    @if (! empty($gaps))
                                        <div class="flex flex-col gap-1 max-w-sm">
                                            @foreach (array_slice($gaps, 0, 2) as $gap)
                                                <div class="flex items-center justify-between text-[11px] bg-slate-50 dark:bg-slate-900/40 p-1.5 rounded border {{ $gap['is_between_sessions'] ? 'border-emerald-300 dark:border-emerald-800' : 'border-gray-200 dark:border-gray-700' }}">
                                                    <div class="flex items-center gap-1.5">
                                                        @if ($gap['is_between_sessions'])
                                                            <span class="w-2 h-2 rounded-full bg-emerald-500" title="{{ __('فراغ بين حصتين داخل اليوم') }}"></span>
                                                        @else
                                                            <span class="w-2 h-2 rounded-full bg-slate-400" title="{{ __('فترة فراغ محاذية') }}"></span>
                                                        @endif
                                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $gap['day_label'] }}:</span>
                                                        <span class="font-mono text-gray-600 dark:text-gray-300">{{ $gap['start_time'] }} - {{ $gap['end_time'] }}</span>
                                                        <span class="text-gray-400">({{ $gap['duration_minutes'] }} د)</span>
                                                    </div>
                                                    @if ($canManage)
                                                        <button
                                                            type="button"
                                                            wire:click="applySlot({{ $t->id }}, '{{ $gap['day'] }}', '{{ $gap['start_time'] }}', '{{ $gap['end_time'] }}')"
                                                            class="text-[10px] px-2 py-0.5 rounded font-semibold bg-emerald-100 hover:bg-emerald-200 text-emerald-800 dark:bg-emerald-900/60 dark:hover:bg-emerald-900 dark:text-emerald-200 transition-colors"
                                                            title="{{ __('اعتماد هذا الفراغ كموعد رسمي للاستقبال') }}"
                                                        >
                                                            {{ __('اعتماد') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-[11px]">{{ __('لا يوجد جدول حصص مسجل') }}</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if ($hasSlot)
                                        <div class="space-y-1">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                <span>📅 {{ $t->parentReceptionDayLabel() }}</span>
                                                <span class="font-mono" dir="ltr">({{ $t->parentReceptionTimeRange() }})</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <span>📍</span>
                                                <span>{{ $t->parent_reception_location ?: __('مكتب الاستشارة التربوية') }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-400 border border-red-200 dark:border-red-800">
                                            ⏳ {{ __('قيد التحديد') }}
                                        </span>
                                    @endif
                                </td>
                                @if ($canManage)
                                    <td class="p-3 text-center">
                                        @if ($hasSlot)
                                            <button
                                                type="button"
                                                wire:click="clearSlot({{ $t->id }})"
                                                wire:confirm="{{ __('هل أنت متأكد من إلغاء موعد الاستقبال لهذا الأستاذ؟') }}"
                                                class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium"
                                                title="{{ __('إلغاء الموعد') }}"
                                            >
                                                {{ __('إلغاء الموعد') }}
                                            </button>
                                        @else
                                            @if (! empty($gaps))
                                                <button
                                                    type="button"
                                                    wire:click="applySlot({{ $t->id }}, '{{ $gaps[0]['day'] }}', '{{ $gaps[0]['start_time'] }}', '{{ $gaps[0]['end_time'] }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold bg-primary-600 hover:bg-primary-700 text-white transition-colors"
                                                >
                                                    <x-filament::icon icon="heroicon-o-bolt" class="w-3.5 h-3.5" />
                                                    <span>{{ __('تعيين آلي') }}</span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 8 : 7 }}" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('لم يتم العثور على أي أساتذة يطابقون خيارات البحث أو التصفية.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
