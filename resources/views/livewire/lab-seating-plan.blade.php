{{--
    NetStation Lab Control & Science 6 Long-Tables Seating Cockpit.
    Adapts dynamically to Informatics Labs (Leader PC, locks, broadcasts) and Science Labs (6 Long Tables of 4 seats each with central service bridge).
--}}
@php
    $labs = $labs ?? collect();
    $groups = $groups ?? collect();

    $selectedLab = $labs->firstWhere('id', $this->labId);
    $selectedGroup = $groups->firstWhere('id', $this->groupId);

    $isInformatics = $selectedLab ? $selectedLab->isInformatics() : true;
    $isScience = $selectedLab && ! $selectedLab->isInformatics();
    $isPhysics = $selectedLab ? $selectedLab->isPhysics() : false;
    $isNaturalScience = $selectedLab ? $selectedLab->isNaturalScience() : false;

    $workingStations = $this->stations;
    $assignedCount = $this->assignedCount;
    $totalStations = $this->availableStationsCount;
    $lockedCount = $this->lockedCount;

    // Informatics Lab: Split stations into U-shape groups (top row, left column, right column)
    $stationCount = count($workingStations);
    $topCount = (int) ceil($stationCount / 3);
    $leftCount = (int) floor(($stationCount - $topCount) / 2);
    $rightCount = $stationCount - $topCount - $leftCount;

    $topStations = array_slice($workingStations, 0, $topCount);
    $leftStations = array_slice($workingStations, $topCount, $leftCount);
    $rightStations = array_slice($workingStations, $topCount + $leftCount, $rightCount);

    // Science Lab: 6 Long Tables with 4 seats each (2 front, 2 back) = 24 seats
    $scienceTables = [];
    if ($isScience) {
        for ($t = 1; $t <= 6; $t++) {
            $tableStations = [];
            for ($s = 1; $s <= 4; $s++) {
                $seatNumber = ($t - 1) * 4 + $s;
                $foundStation = collect($workingStations)->firstWhere('station_number', $seatNumber);
                $tableStations[$s] = $foundStation ?: [
                    'id' => null,
                    'station_number' => $seatNumber,
                    'device_type' => 'device',
                    'is_locked' => false,
                    'student_id' => null,
                    'student_name' => null,
                    'is_virtual' => true,
                ];
            }
            $scienceTables[$t] = [
                'number' => $t,
                'front_seats' => [$tableStations[1], $tableStations[2]],
                'back_seats' => [$tableStations[3], $tableStations[4]],
                'assigned_count' => collect($tableStations)->filter(fn ($st) => ! empty($st['student_id']))->count(),
            ];
        }

        $rightAisleTables = [$scienceTables[1], $scienceTables[2], $scienceTables[3]];
        $leftAisleTables = [$scienceTables[4], $scienceTables[5], $scienceTables[6]];
    }
@endphp

<div class="space-y-6">
    {{-- Header & Mode Tabs --------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    @if ($isScience)
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                            @if ($isPhysics)
                                <x-filament::icon icon="heroicon-o-beaker" class="w-6 h-6"/>
                            @else
                                <x-filament::icon icon="heroicon-o-sparkles" class="w-6 h-6"/>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-gray-950 dark:text-white flex items-center gap-2">
                                <span>{{ $selectedLab->labTypeLabel() }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ __('مخطط طاولات وتجارب المخبر (6 طاولات كبرى × 4 مقاعد)') }}
                                </span>
                                @if ($this->existingPlanId)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ __('خطة محفوظة') }}
                                    </span>
                                @endif
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('تنظيم وتوزيع تلاميذ الفوج على الطاولات الـ 6 المخبرية (أفواج العمل التطبيقي) ومتابعة بروتوكول السلامة') }}
                            </p>
                        </div>
                    @else
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
                    @endif
                </div>
            </div>

            {{-- Mode Switcher & Save Button --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($isInformatics)
                    {{-- Informatics Lab: View Mode Toggle --}}
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
                @endif

                {{-- Experiment Protocol Button --}}
                <x-filament::button
                    type="button"
                    wire:click="$set('showExperimentModal', true)"
                    color="info"
                    icon="heroicon-o-clipboard-document-list"
                    size="sm"
                >
                    {{ __('بروتوكول التجربة') }}
                </x-filament::button>

                @if (! $isInformatics)
                    {{-- Science Lab: Safety Charter Button --}}
                    <button
                        type="button"
                        wire:click="$set('showSafetyModal', true)"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 shadow-sm"
                    >
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"/>
                        <span>{{ __('ميثاق السلامة المخبرية') }}</span>
                    </button>
                @endif

                @if ($selectedGroup && count($this->students) > 0)
                    <x-filament::button
                        type="button"
                        wire:click="markAllPresent"
                        color="success"
                        icon="heroicon-o-check-badge"
                        size="sm"
                    >
                        {{ __('تعيين الجميع حاضراً') }}
                    </x-filament::button>
                @endif

                @if (($viewMode === 'seating' || $isScience) && $selectedLab && $selectedGroup)
                    <x-filament::button
                        wire:click="autoAssign"
                        color="gray"
                        icon="heroicon-o-sparkles"
                        :disabled="count($this->students) === 0"
                        size="sm"
                    >
                        {{ __('توزيع تلقائي للأفواج') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="clearAssignments"
                        color="gray"
                        icon="heroicon-o-trash"
                        :disabled="$assignedCount === 0"
                        size="sm"
                    >
                        {{ __('تفريغ الطاولات') }}
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
            {{-- Schedule Select --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الحصة الدراسية (الجدول الأسبوعي)') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="scheduleId"
                        class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                    >
                        <option value="">{{ __('اختر الحصة (اختياري)') }}</option>
                        @foreach ($schedules as $sched)
                            <option value="{{ $sched->id }}">
                                {{ $sched->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            {{-- Attendance Date --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('تاريخ الحصة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="date"
                        wire:model.live="attendanceDate"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </x-filament::input.wrapper>
            </div>

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
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:items-end">
            {{-- Plan Name --}}
            <div class="grid gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('اسم الخطة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="planName"
                        placeholder="{{ $isScience ? __('مثال: خطة طاولات التجارب - مخبر العلوم') : __('مثال: خطة الجلوس - مخبر الإعلام الآلي') }}"
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
                        placeholder="{{ $isScience ? __('ملاحظات حول عتاد التجربة أو الجلسة...') : __('ملاحظات حول الأجهزة أو الجلسة...') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Live Attendance Strip --}}
        @if ($selectedGroup && count($this->students) > 0)
            <div class="mt-4 p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-slate-50/70 dark:bg-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>{{ __('تسجيل الحضور التفاعلي للحصة') }}</span>
                            @if ($this->dailyLogId)
                                <span class="px-2 py-0.5 rounded text-[10px] bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 font-normal">
                                    {{ __('دفتر النصوص مربوط') }} #{{ $this->dailyLogId }}
                                </span>
                            @endif
                        </h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ __('انقر على أي مقعد أو شاشة لتبديل الحالة بين (حاضر ✔️، غائب ❌، متأخر ⏳، معفى 📝)') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ __('الحاضرون:') }} {{ $this->presentCount }}</span>
                    </div>
                    <div class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/30 text-xs font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>{{ __('الغائبون:') }} {{ $this->absentCount }}</span>
                    </div>
                    <div class="px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>{{ __('المتأخرون:') }} {{ $this->lateCount }}</span>
                    </div>
                    @if ($this->excusedCount > 0)
                        <div class="px-3 py-1.5 rounded-lg bg-sky-500/10 text-sky-700 dark:text-sky-300 border border-sky-500/30 text-xs font-bold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <span>{{ __('المعفون:') }} {{ $this->excusedCount }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Live Network / Lab Counters --}}
        <div class="mt-4 grid gap-3 grid-cols-2 md:grid-cols-4">
            @if ($isScience)
                {{-- Science Lab Counters --}}
                <div class="rounded-xl border border-stone-200 p-3 text-center dark:border-white/10 bg-stone-50/50 dark:bg-gray-800/30">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ __('إجمالي المقاعد (6 طاولات)') }}</p>
                    <p class="text-2xl font-black text-gray-950 dark:text-white">{{ $totalStations }}</p>
                </div>
                <div class="rounded-xl border border-teal-200 p-3 text-center dark:border-teal-500/30 bg-teal-50/50 dark:bg-teal-900/20">
                    <p class="text-[11px] font-bold text-teal-700 dark:text-teal-300">{{ __('التلاميذ المعينون') }}</p>
                    <p class="text-2xl font-black text-teal-800 dark:text-teal-200">{{ $assignedCount }} / {{ count($this->students) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 p-3 text-center dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-900/20">
                    <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">{{ __('مقاعد شاغرة') }}</p>
                    <p class="text-2xl font-black text-emerald-800 dark:text-emerald-200">{{ max(0, $totalStations - $assignedCount) }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 p-3 text-center dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-900/20">
                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">{{ __('بروتوكول السلامة') }}</p>
                    <p class="text-base font-black text-amber-800 dark:text-amber-200 mt-1">
                        {{ __('معتمد وقيد التطبيق 🛡️') }}
                    </p>
                </div>
            @else
                {{-- Informatics Lab Counters --}}
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
            @endif
        </div>
    </div>

    {{-- Main Cockpit View ------------------------------------------------ --}}
    @if ($selectedLab && $selectedGroup)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm space-y-8">
            @if ($isInformatics)
                {{-- ========================================================= --}}
                {{-- INFORMATICS LAB: U-SHAPE STATIONS & LEADER PC             --}}
                {{-- ========================================================= --}}
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
            @else
                {{-- ========================================================= --}}
                {{-- SCIENCE LAB: 6 LONG TABLES WITH 4 SEATS EACH              --}}
                {{-- ========================================================= --}}

                {{-- Front: Teacher Demonstration & Preparation Desk --}}
                <div class="w-full rounded-3xl bg-slate-950 text-white border-2 border-emerald-500/40 shadow-2xl p-6 relative overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                                @if ($isPhysics)
                                    <x-filament::icon icon="heroicon-o-beaker" class="w-7 h-7"/>
                                @else
                                    <x-filament::icon icon="heroicon-o-sparkles" class="w-7 h-7"/>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">
                                        {{ __('منصة التجارب النموذجية للأستاذ (Teacher Demo Bench)') }}
                                    </span>
                                </div>
                                <h3 class="text-lg md:text-xl font-black text-white mt-0.5">
                                    {{ $isPhysics ? __('منصة تجارب الفيزياء والكيمياء للأستاذ') : ($isNaturalScience ? __('منصة العروض المجهرية والتشريح للأستاذ') : __('منصة العرض والتجارب المخبرية للأستاذ')) }}
                                </h3>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    {{ __('إجراء التجربة النموذجية ومتابعة أفواج العمل على الطاولات الـ 6 في :lab', ['lab' => $selectedLab->name]) }}
                                </p>
                            </div>
                        </div>

                        {{-- Action toolbar for science bench --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <x-filament::button
                                type="button"
                                wire:click="autoAssign"
                                color="success"
                                icon="heroicon-o-sparkles"
                                size="sm"
                            >
                                {{ __('توزيع تلقائي للأفواج') }}
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="clearAssignments"
                                color="gray"
                                icon="heroicon-o-trash"
                                size="sm"
                            >
                                {{ __('تفريغ الطاولات') }}
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="$set('showSafetyModal', true)"
                                color="warning"
                                icon="heroicon-o-shield-check"
                                size="sm"
                            >
                                {{ __('ميثاق السلامة') }}
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="savePlan"
                                icon="heroicon-o-check-circle"
                                size="sm"
                            >
                                {{ __('حفظ المخطط') }}
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Lab Safety & Equipment Status Ribbon --}}
                    <div class="mt-4 pt-1 flex flex-wrap items-center justify-around gap-3 text-xs text-emerald-200">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            {{ __('المآزر والوقاية: إلزامية 🥼') }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            {{ __('قواطع الطوارئ: مفحوصة ⚡') }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            {{ __('العتاد والكواشف: جاهزة 🧪') }}
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-300">
                            <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                            {{ __('طاقة الاستيعاب: 6 طاولات كبرى × 4 مقاعد (24 تلميذ)') }}
                        </span>
                    </div>
                </div>

                {{-- Central Aisle Divider / Direction Indicator --}}
                <div class="relative flex items-center justify-center my-4">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-dashed border-gray-300 dark:border-white/10"></div>
                    </div>
                    <span class="relative px-4 py-1 rounded-full bg-white dark:bg-gray-800 text-[11px] font-bold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-white/10 shadow-xs">
                        {{ __('جهة السبورة ومنصة العرض للأستاذ ⬆️') }}
                    </span>
                </div>

                {{-- 6 Long Tables Laboratory Floor Plan (2 Aisles x 3 Tables) --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 relative">
                    {{-- Right Row (الرواق الأيمن: طاولات 1، 2، 3) --}}
                    <div class="space-y-6">
                        <div class="flex items-center justify-between px-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <span>{{ __('الرواق الأيمن — أفواج العمل 1 إلى 3') }}</span>
                            <span>3 {{ __('طاولات') }} &bull; 12 {{ __('مقعد') }}</span>
                        </div>

                        @foreach ($rightAisleTables as $table)
                            @include('livewire.partials.science-long-table', ['table' => $table, 'isPhysics' => $isPhysics])
                        @endforeach
                    </div>

                    {{-- Left Row (الرواق الأيسر: طاولات 4، 5، 6) --}}
                    <div class="space-y-6">
                        <div class="flex items-center justify-between px-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <span>{{ __('الرواق الأيسر — أفواج العمل 4 إلى 6') }}</span>
                            <span>3 {{ __('طاولات') }} &bull; 12 {{ __('مقعد') }}</span>
                        </div>

                        @foreach ($leftAisleTables as $table)
                            @include('livewire.partials.science-long-table', ['table' => $table, 'isPhysics' => $isPhysics])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Seating Management & Student Roster --}}
        @if ($viewMode === 'seating' || $isScience)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ __('قائمة تلاميذ الفوج للتوزيع على المقاعد') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $isScience ? __('حدد مقعداً من إحدى الطاولات الـ 6 أعلاه ثم اضغط على التلميذ لتعيينه بها، أو استخدم "توزيع تلقائي للأفواج".') : __('حدد محطة من المخطط أعلاه ثم اضغط على التلميذ لتعيينه بها، أو استخدم "توزيع تلقائي".') }}
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
                            class="p-3 rounded-xl border cursor-pointer hover:shadow-sm transition {{ $isAssigned ? 'bg-teal-50/80 dark:bg-teal-900/20 border-teal-200 dark:border-teal-600/40' : 'bg-white dark:bg-gray-800/80 border-gray-200 dark:border-white/10 hover:border-primary-400' }}"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-gray-950 dark:text-white truncate">{{ $student['full_name'] }}</span>
                                <span class="text-[10px] font-mono text-gray-400 dark:text-gray-500">{{ $student['registration_number'] }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                @if ($isAssigned)
                                    @php
                                        $tableNum = (int) ceil($assignedStation['station_number'] / 4);
                                        $seatInTable = (($assignedStation['station_number'] - 1) % 4) + 1;
                                    @endphp
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-teal-700 dark:text-teal-300">
                                        <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5 text-teal-500"/>
                                        @if ($isScience)
                                            {{ __('طاولة :table • مقعد :seat', ['table' => $tableNum, 'seat' => $seatInTable]) }}
                                        @else
                                            {{ __('محطة') }} {{ $assignedStation['station_number'] }}
                                        @endif
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
            <x-filament::icon icon="heroicon-o-beaker" class="h-12 w-12 text-primary-500 mx-auto mb-3 opacity-80"/>
            <h4 class="text-lg font-bold text-gray-950 dark:text-white mb-1">{{ __('حدد المختبر والفوج للمتابعة') }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                {{ __('يرجى اختيار مختبر وفوج من القوائم أعلاه لعرض مخطط الجلوس وإدارة محطات وتجارب المخبر.') }}
            </p>
        </div>
    @endif

    {{-- Broadcast Message Modal (Informatics Only) ---------------------- --}}
    @if ($showBroadcastModal && $isInformatics)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showBroadcastModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-megaphone" class="w-6 h-6"/>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ __('بث توجيه جماعي لجميع الشاشات') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ستظهر هذه الرسالة أعلى شاشات أجهزة التلاميذ فوراً.') }}</p>
                    </div>
                </div>

                <textarea
                    wire:model="broadcastMessageText"
                    rows="3"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded-xl p-3 text-sm bg-white dark:bg-gray-800 text-gray-950 dark:text-white focus:ring-1 focus:ring-primary-500"
                    placeholder="{{ __('اكتب نص التوجيه هنا (مثال: يرجى حفظ ملف العمل وإيقاف البرامج...)') }}"
                ></textarea>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="$set('showBroadcastModal', false)"
                        size="sm"
                    >
                        {{ __('إلغاء') }}
                    </x-filament::button>
                    <x-filament::button
                        type="button"
                        wire:click="sendBroadcastMessage"
                        size="sm"
                        :disabled="blank($broadcastMessageText)"
                    >
                        {{ __('إرسال البث الآن') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Science Lab Safety Modal --------------------------------------- --}}
    @if ($showSafetyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showSafetyModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">
                                {{ __('ميثاق وقواعد السلامة والأمن المخبري') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('المنشور الوزاري المنظم للأنشطة التجريبية في التعليم المتوسط') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('showSafetyModal', false)"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5"/>
                    </button>
                </div>

                @if ($isPhysics)
                    {{-- Physics & Chemistry Safety Rules --}}
                    <div class="space-y-3">
                        <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-amber-800 dark:text-amber-300">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                {{ __('إرشادات الأمن الكهربائي') }}
                            </h4>
                            <p>{{ __('قطع التيار الكهربائي دائماً قبل تركيب أو تفكيك الدارات، وعدم تشغيل المولد إلا بعد موافقة وتفقد الأستاذ.') }}</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 text-xs text-blue-900 dark:text-blue-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-blue-800 dark:text-blue-300">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                {{ __('التعامل مع الكواشف والزجاجيات') }}
                            </h4>
                            <p>{{ __('ارتداء المئزر القطني الأبيض والنظارات الواقية، تفادي استنشاق الغازات أو لمس الكواشف، والتعامل الحذر مع أنابيب الاختبار والحوجلات.') }}</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-xs text-emerald-900 dark:text-emerald-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ __('نهاية النشاط والتنظيف') }}
                            </h4>
                            <p>{{ __('غسل اليدين جيداً بالماء والصابون، التأكد من غلق مآخذ الكهرباء ومحابس الغاز والماء، وترتيب العتاد في أماكنه المخصصة.') }}</p>
                        </div>
                    </div>
                @else
                    {{-- Biology & Natural Sciences Safety Rules --}}
                    <div class="space-y-3">
                        <div class="p-3.5 rounded-xl bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-800/40 text-xs text-teal-900 dark:text-teal-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-teal-800 dark:text-teal-300">
                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                {{ __('العناية بالمجهر الضوئي') }}
                            </h4>
                            <p>{{ __('حمل المجهر بكلتا اليدين (القاعدة والذراع)، البدء بالتكبير الأصغر، وتنظيف العدسات بورق التنظيف الخاص دون فرك خشن.') }}</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-xs text-emerald-900 dark:text-emerald-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ __('إعداد الشرائح والعينات البيولوجية') }}
                            </h4>
                            <p>{{ __('وضع الساترة بزاوية 45 درجة لتفادي فقاعات الهواء، واستخدام الملاقط والمشارط تحت إشراف وتوجيه الأستاذ المباشر.') }}</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/40 text-xs text-purple-900 dark:text-purple-200 space-y-1.5">
                            <h4 class="font-bold flex items-center gap-1.5 text-purple-800 dark:text-purple-300">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                {{ __('حفظ المجسمات ونظافة المحطة') }}
                            </h4>
                            <p>{{ __('الحفاظ على المجسمات التشريحية واللوحات الحائطية، تعقيم وتنظيف سطح الطاولة وغسل اليدين فور انتهاء الحصة المخبرية.') }}</p>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <x-filament::button
                        type="button"
                        wire:click="$set('showSafetyModal', false)"
                        size="sm"
                    >
                        {{ __('فهمت وتعهدت بالتطبيق') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Experiment Protocol Modal --------------------------------------- --}}
    @if ($showExperimentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showExperimentModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">
                                {{ __('بروتوكول التجربة المخبرية والتحضير البيداغوجي') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $activeSchedule ? $activeSchedule->subject?->name : ($selectedLab ? $selectedLab->name : '') }} &bull; {{ $this->attendanceDate }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('showExperimentModal', false)"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5"/>
                    </button>
                </div>

                {{-- Experiment Card / Daily Log Context --}}
                <div class="space-y-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-white/10 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ $activeDailyLog?->title ?? __('حصة تجريبية مخبرية') }}
                            </span>
                            @if ($this->dailyLogId)
                                <a
                                    href="{{ route('filament.admin.resources.daily-logs.view', ['record' => $this->dailyLogId]) }}"
                                    target="_blank"
                                    class="text-primary-600 dark:text-primary-400 hover:underline font-semibold flex items-center gap-1"
                                >
                                    <span>{{ __('فتح دفتر النصوص') }}</span>
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3.5 h-3.5"/>
                                </a>
                            @endif
                        </div>

                        @if ($activeDailyLog?->actual_content)
                            <div class="pt-2 border-t border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300">
                                <p class="font-bold mb-1">{{ __('سير الأنشطة والخطوات التجريبية:') }}</p>
                                @if (\Illuminate\Support\Str::contains((string) $activeDailyLog->actual_content, ['<p', '<br', '<div', '<ul', '<ol', '<strong', '<span']))
                                    <div class="leading-relaxed">
                                        {!! strip_tags((string) $activeDailyLog->actual_content, '<p><br><strong><b><em><u><ul><ol><li><span>') !!}
                                    </div>
                                @else
                                    <p class="leading-relaxed whitespace-pre-line">{{ $activeDailyLog->actual_content }}</p>
                                @endif
                            </div>
                        @else
                            <div class="pt-2 border-t border-gray-200 dark:border-white/5 text-gray-500 dark:text-gray-400">
                                <p>{{ __('لم يتم تدوين ملخص تفصيلي في دفتر النصوص بعد. يمكنك فتح دفتر النصوص لتوثيق خطوات التجربة.') }}</p>
                            </div>
                        @endif

                        @if ($activeDailyLog?->homework)
                            <div class="pt-2 border-t border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300">
                                <p class="font-bold text-amber-700 dark:text-amber-400 mb-0.5">{{ __('العمل المطلوب / تقرير التجربة:') }}</p>
                                <p class="leading-relaxed">{{ $activeDailyLog->homework }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Supervisor & Group details --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-white/5">
                            <p class="text-gray-500 dark:text-gray-400 font-medium">{{ __('الأستاذ المشرف') }}</p>
                            <p class="font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $activeDailyLog?->schedule?->user?->name ?? auth()->user()?->name }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-white/5">
                            <p class="text-gray-500 dark:text-gray-400 font-medium">{{ __('الفوج والمخبر') }}</p>
                            <p class="font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $selectedGroup?->display_name() }} &bull; {{ $selectedLab?->name }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <x-filament::button
                        type="button"
                        wire:click="$set('showExperimentModal', false)"
                        size="sm"
                    >
                        {{ __('إغلاق') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
