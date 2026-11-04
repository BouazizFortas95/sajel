{{--
    NetStation Lab Control & U-Shape Seating Cockpit.
    Leader PC commanding all lab stations with live lock, broadcast, internet toggle, and student assignment.
--}}
@php
    $labs = $labs ?? collect();
    $groups = $groups ?? collect();

    $selectedLab = $labs->firstWhere('id', $this->labId);
    $selectedGroup = $groups->firstWhere('id', $this->groupId);

    $workingStations = $this->stations;
    $assignedCount = $this->assignedCount;
    $totalStations = $this->availableStationsCount;
    $lockedCount = $this->lockedCount;

    // Split stations into U-shape groups: top row, left column, right column
    $stationCount = count($workingStations);
    $topCount = (int) ceil($stationCount / 3);
    $leftCount = (int) floor(($stationCount - $topCount) / 2);
    $rightCount = $stationCount - $topCount - $leftCount;

    $topStations = array_slice($workingStations, 0, $topCount);
    $leftStations = array_slice($workingStations, $topCount, $leftCount);
    $rightStations = array_slice($workingStations, $topCount + $leftCount, $rightCount);
@endphp

<div class="space-y-6">
    {{-- Header & Mode Tabs --------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
                        <x-filament::icon icon="heroicon-o-computer-desktop" class="w-6 h-6"/>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-950 dark:text-white flex items-center gap-2">
                            <span>{{ __('منظومة التحكم بالمخبر (NetStation Suite)') }}</span>
                            @if ($this->existingPlanId)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ __('خطة محفوظة') }}
                                </span>
                            @endif
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('إدارة ومراقبة محطات المخبر من حاسوب الأستاذ الرئيسي (Leader PC) وتوزيع التلاميذ') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Mode Switcher & Save Button --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- View Mode Toggle --}}
                <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="setViewMode('control')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'control' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    >
                        <x-filament::icon icon="heroicon-o-command-line" class="w-4 h-4"/>
                        <span>{{ __('وضع التحكم المباشر (NetStation)') }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setViewMode('seating')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'seating' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    >
                        <x-filament::icon icon="heroicon-o-user-group" class="w-4 h-4"/>
                        <span>{{ __('وضع تخطيط الجلوس') }}</span>
                    </button>
                </div>

                @if ($viewMode === 'seating' && $selectedLab && $selectedGroup)
                    <x-filament::button
                        wire:click="autoAssign"
                        color="success"
                        icon="heroicon-o-sparkles"
                        :disabled="count($this->students) === 0"
                        size="sm"
                    >
                        {{ __('توزيع تلقائي') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="clearAssignments"
                        color="gray"
                        icon="heroicon-o-trash"
                        :disabled="$assignedCount === 0"
                        size="sm"
                    >
                        {{ __('تفريغ') }}
                    </x-filament::button>
                @endif

                <x-filament::button
                    wire:click="savePlan"
                    :disabled="$assignedCount === 0"
                    icon="heroicon-o-check-circle"
                    size="sm"
                >
                    {{ __('حفظ الخطة') }}
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- Setup Selectors ------------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:items-end">
            {{-- Lab Select --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('المختبر') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="labId"
                        class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                    >
                        <option value="">{{ __('اختر المختبر') }}</option>
                        @foreach ($labs as $lab)
                            <option value="{{ $lab->id }}">{{ $lab->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            {{-- Group Select --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الفوج التربوي') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="groupId"
                        class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                    >
                        <option value="">{{ __('اختر الفوج') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            {{-- Plan Name --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('اسم الخطة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="planName"
                        placeholder="{{ __('مثال: خطة الجلوس - مخبر الإعلام الآلي') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </x-filament::input.wrapper>
            </div>

            {{-- Notes --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('ملاحظات الجلسة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="planNotes"
                        placeholder="{{ __('ملاحظات حول الأجهزة أو الجلسة...') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Live Network Counters --}}
        <div class="mt-4 grid gap-3 grid-cols-2 md:grid-cols-4">
            <div class="rounded-xl border border-stone-200 p-3 text-center dark:border-white/10 bg-stone-50/50 dark:bg-gray-800/30">
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ __('إجمالي الأجهزة') }}</p>
                <p class="text-2xl font-black text-gray-950 dark:text-white">{{ $totalStations }}</p>
            </div>
            <div class="rounded-xl border border-blue-200 p-3 text-center dark:border-blue-500/30 bg-blue-50/50 dark:bg-blue-900/20">
                <p class="text-[11px] font-bold text-blue-700 dark:text-blue-300">{{ __('التلاميذ المسجلون') }}</p>
                <p class="text-2xl font-black text-blue-800 dark:text-blue-200">{{ $assignedCount }} / {{ count($this->students) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 p-3 text-center dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-900/20">
                <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">{{ __('الشاشات المقفلة') }}</p>
                <p class="text-2xl font-black text-amber-800 dark:text-amber-200">{{ $lockedCount }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 p-3 text-center dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-900/20">
                <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">{{ __('حالة الإنترنت') }}</p>
                <p class="text-base font-black text-emerald-800 dark:text-emerald-200 mt-1">
                    {{ $globalInternetEnabled ? __('متاح للجميع') : __('محجوب مركزياً') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Main Cockpit View ------------------------------------------------ --}}
    @if ($selectedLab && $selectedGroup)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm space-y-8">
            {{-- Top Row Stations (Top of U) --}}
            <div class="flex flex-wrap justify-center gap-4">
                @foreach ($topStations as $station)
                    @include('livewire.partials.station-monitor-card', ['station' => $station])
                @endforeach
            </div>

            {{-- Middle Section: Left Columns, Center Leader Desk, Right Columns --}}
            <div class="flex justify-between items-center gap-4">
                {{-- Left Wing of U --}}
                <div class="flex flex-col gap-4">
                    @foreach (array_reverse($leftStations) as $station)
                        @include('livewire.partials.station-monitor-card', ['station' => $station])
                    @endforeach
                </div>

                {{-- Center: Leader PC (منصة الأستاذ / الحاسوب الرئيسي) --}}
                <div class="flex-1 flex flex-col items-center justify-center p-6 mx-4 rounded-3xl bg-slate-950 text-white border-2 border-amber-500/40 shadow-2xl relative overflow-hidden max-w-xl">
                    <div class="absolute -top-20 -right-20 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    {{-- Leader Screen Header --}}
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-amber-400">
                            {{ __('المحطة الرئيسية للأستاذ (Leader PC)') }}
                        </span>
                    </div>

                    <h3 class="text-lg md:text-xl font-black text-white text-center">
                        {{ __('لوحة التحكم البيداغوجي المباشر') }}
                    </h3>
                    <p class="text-xs text-slate-400 text-center mt-1 max-w-sm">
                        {{ __('إرسال الأوامر الفورية لجميع أجهزة التلاميذ في :lab', ['lab' => $selectedLab->name]) }}
                    </p>

                    {{-- Leader Global Action Bar --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full mt-6">
                        {{-- Lock All --}}
                        <button
                            type="button"
                            wire:click="lockAllStations"
                            class="p-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 flex flex-col items-center gap-1.5 transition text-center group"
                            title="{{ __('قفل جميع الشاشات') }}"
                        >
                            <x-filament::icon icon="heroicon-o-lock-closed" class="w-5 h-5 text-amber-400 group-hover:scale-110 transition"/>
                            <span class="text-[11px] font-bold">{{ __('قفل الكل') }}</span>
                        </button>

                        {{-- Unlock All --}}
                        <button
                            type="button"
                            wire:click="unlockAllStations"
                            class="p-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 flex flex-col items-center gap-1.5 transition text-center group"
                            title="{{ __('إلغاء قفل جميع الشاشات') }}"
                        >
                            <x-filament::icon icon="heroicon-o-lock-open" class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition"/>
                            <span class="text-[11px] font-bold">{{ __('فتح الكل') }}</span>
                        </button>

                        {{-- Broadcast Message Modal Trigger --}}
                        <button
                            type="button"
                            wire:click="$set('showBroadcastModal', true)"
                            class="p-2.5 rounded-xl bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 border border-blue-500/40 flex flex-col items-center gap-1.5 transition text-center group"
                            title="{{ __('بث توجيه أو رسالة للجميع') }}"
                        >
                            <x-filament::icon icon="heroicon-o-megaphone" class="w-5 h-5 text-blue-400 group-hover:scale-110 transition"/>
                            <span class="text-[11px] font-bold">{{ __('بث رسالة') }}</span>
                        </button>

                        {{-- Toggle Internet --}}
                        <button
                            type="button"
                            wire:click="toggleInternetAll"
                            class="p-2.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/40 flex flex-col items-center gap-1.5 transition text-center group"
                            title="{{ __('حجب أو إتاحة الإنترنت') }}"
                        >
                            <x-filament::icon icon="heroicon-o-globe-alt" class="w-5 h-5 text-purple-400 group-hover:scale-110 transition"/>
                            <span class="text-[11px] font-bold">{{ $globalInternetEnabled ? __('حجب الويب') : __('إتاحة الويب') }}</span>
                        </button>
                    </div>

                    {{-- Active broadcast notice on Leader Desk --}}
                    @php
                        $activeBcast = collect($this->stations)->firstWhere('active_broadcast')['active_broadcast'] ?? null;
                    @endphp
                    @if ($activeBcast)
                        <div class="mt-4 p-2.5 rounded-xl bg-blue-900/50 border border-blue-500/40 flex items-center justify-between gap-3 w-full">
                            <div class="flex items-center gap-2 overflow-hidden text-xs text-blue-200 truncate">
                                <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
                                <span class="font-bold">{{ __('بث نشط:') }}</span>
                                <span class="truncate">{{ $activeBcast }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="clearBroadcastMessage"
                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/30 text-rose-300 hover:bg-rose-500/50 shrink-0"
                            >
                                {{ __('إيقاف البث') }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Right Wing of U --}}
                <div class="flex flex-col gap-4">
                    @foreach ($rightStations as $station)
                        @include('livewire.partials.station-monitor-card', ['station' => $station])
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Seating Management & Student Roster (when in Seating Mode) --}}
        @if ($viewMode === 'seating')
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ __('قائمة تلاميذ الفوج للتوزيع على المقاعد') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('حدد محطة من المخطط أعلاه ثم اضغط على التلميذ لتعيينه بها، أو استخدم "توزيع تلقائي".') }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-stone-100 text-stone-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ __('المعينون:') }} {{ $assignedCount }} / {{ count($this->students) }}
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->students as $student)
                        @php
                            $assignedStation = collect($this->stations)->firstWhere('student_id', $student['id']);
                            $isAssigned = ! empty($assignedStation);
                        @endphp
                        <div
                            wire:click="assignStudent({{ $student['id'] }})"
                            class="p-3 rounded-xl border cursor-pointer hover:shadow-sm transition {{ $isAssigned ? 'bg-blue-50/80 dark:bg-blue-900/20 border-blue-200 dark:border-blue-600/40' : 'bg-white dark:bg-gray-800/80 border-gray-200 dark:border-white/10 hover:border-primary-400' }}"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-gray-950 dark:text-white truncate">{{ $student['full_name'] }}</span>
                                <span class="text-[10px] font-mono text-gray-400 dark:text-gray-500">{{ $student['registration_number'] }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                @if ($isAssigned)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300">
                                        <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5 text-blue-500"/>
                                        {{ __('محطة') }} {{ $assignedStation['station_number'] }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click.stop="unassignStudent({{ $assignedStation['id'] }})"
                                        class="text-[10px] text-rose-500 hover:text-rose-700 font-medium"
                                    >
                                        {{ __('إلغاء') }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500">
                                        <x-filament::icon icon="heroicon-o-minus-circle" class="h-3.5 w-3.5"/>
                                        {{ __('غير معيّن') }}
                                    </span>
                                    <span class="text-[10px] font-medium text-primary-600 dark:text-primary-400">
                                        {{ __('تعيين') }} &larr;
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-8 shadow-xs">
            <x-filament::icon icon="heroicon-o-computer-desktop" class="h-12 w-12 text-primary-500 mx-auto mb-3 opacity-80"/>
            <h4 class="text-lg font-bold text-gray-950 dark:text-white mb-1">{{ __('حدد المختبر والفوج للمتابعة') }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                {{ __('يرجى اختيار مختبر وفوج من القوائم أعلاه لعرض مخطط الجلوس وتفعيل منظومة التحكم NetStation.') }}
            </p>
        </div>
    @endif

    {{-- Broadcast Message Modal ----------------------------------------- --}}
    @if ($showBroadcastModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showBroadcastModal', false)"></div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 max-w-lg w-full shadow-2xl relative z-10 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-o-megaphone" class="w-6 h-6"/>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ __('بث رسالة فورية لجميع المحطات') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('تظهر هذه الرسالة مباشرة كشريط تنبيه على شاشات التلاميذ المتصلة.') }}</p>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">{{ __('نص الرسالة أو التوجيه:') }}</label>
                    <textarea
                        wire:model="broadcastMessageText"
                        rows="3"
                        placeholder="{{ __('مثال: افتحوا برنامج معالج النصوص صفحة 45 وابدأوا التمرين الأول.') }}"
                        class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm rounded-xl p-3 text-gray-950 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-filament::button
                        wire:click="$set('showBroadcastModal', false)"
                        color="gray"
                    >
                        {{ __('إلغاء') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="sendBroadcastMessage"
                        icon="heroicon-o-paper-airplane"
                    >
                        {{ __('إرسال البث الآن') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
