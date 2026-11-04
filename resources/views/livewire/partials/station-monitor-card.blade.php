@php
    $isSelected = $this->selectedStationId === $station['id'];
    $isAssigned = ! empty($station['student_id']);
    $isLocked = (bool) ($station['is_locked'] ?? false);
    $hasBroadcast = ! empty($station['active_broadcast']);
    $stationUrl = route('lab.station-client', ['locale' => app()->getLocale(), 'labStation' => $station['id']]);

    $isInformatics = $station['is_computer'] ?? ($selectedLab ? $selectedLab->isInformatics() : true);
    $isPhysics = $selectedLab ? $selectedLab->isPhysics() : false;
    $isNaturalScience = $selectedLab ? $selectedLab->isNaturalScience() : false;

    // Theme border and background based on state
    if ($isSelected) {
        $monitorBorder = 'border-amber-500 ring-2 ring-amber-400 dark:ring-amber-500 shadow-lg shadow-amber-500/20';
        $screenBg = 'bg-amber-950/60 text-amber-200';
    } elseif ($isLocked) {
        $monitorBorder = 'border-amber-600/70 dark:border-amber-500/50 shadow-md shadow-amber-900/30';
        $screenBg = 'bg-amber-950/90 text-amber-300';
    } elseif ($isAssigned) {
        $monitorBorder = 'border-blue-500/50 dark:border-blue-500/40 shadow-sm';
        $screenBg = 'bg-slate-900/90 text-blue-100';
    } else {
        $monitorBorder = 'border-emerald-500/40 dark:border-emerald-500/30';
        $screenBg = 'bg-emerald-950/40 text-emerald-200';
    }
@endphp

@if ($isInformatics)
    {{-- ================================================================= --}}
    {{-- Informatics Lab PC Monitor Card (NetStation)                     --}}
    {{-- ================================================================= --}}
    <div
        @if ($viewMode === 'seating')
            wire:click="selectStation({{ $station['id'] }})"
        @endif
        class="w-32 flex flex-col items-center group relative select-none {{ $viewMode === 'seating' ? 'cursor-pointer' : '' }}"
    >
        {{-- PC Monitor Screen Frame --}}
        <div class="w-full rounded-2xl p-2 bg-slate-900 border-2 {{ $monitorBorder }} transition duration-200 relative overflow-hidden">
            {{-- Screen Glare / Top Bar --}}
            <div class="flex items-center justify-between gap-1 pb-1 mb-1.5 border-b border-white/10 text-[10px]">
                <span class="font-bold flex items-center gap-1 font-mono">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isLocked ? 'bg-amber-400' : ($isAssigned ? 'bg-emerald-400' : 'bg-slate-500') }}"></span>
                    #{{ str_pad($station['station_number'], 2, '0', STR_PAD_LEFT) }}
                </span>
                <span class="text-[9px] font-mono text-slate-400 truncate max-w-[60px]">
                    {{ $station['ip_address'] ?? '192.168.1.'.$station['station_number'] }}
                </span>
            </div>

            {{-- Screen Inner Display --}}
            <div class="w-full h-16 rounded-lg {{ $screenBg }} p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden">
                @if ($isLocked)
                    <div class="flex flex-col items-center justify-center animate-pulse">
                        <x-filament::icon icon="heroicon-o-lock-closed" class="w-5 h-5 text-amber-400 mb-0.5"/>
                        <span class="text-[10px] font-black text-amber-300">{{ __('مقفل') }}</span>
                    </div>
                @elseif ($isAssigned)
                    <div class="flex flex-col items-center justify-center w-full">
                        <div class="w-5 h-5 rounded-full bg-blue-500/30 text-blue-300 flex items-center justify-center text-[10px] font-bold mb-1">
                            {{ mb_substr($station['student_name'] ?? 'T', 0, 1) }}
                        </div>
                        <span class="text-[10px] font-bold text-white truncate w-full px-0.5">
                            {{ $station['student_name'] }}
                        </span>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center text-emerald-400/80">
                        <x-filament::icon icon="heroicon-o-computer-desktop" class="w-5 h-5 mb-0.5 opacity-80"/>
                        <span class="text-[9px] font-medium">{{ __('متاح') }}</span>
                    </div>
                @endif

                {{-- Broadcast Alert indicator --}}
                @if ($hasBroadcast)
                    <div class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-blue-400 animate-ping" title="{{ __('بث نشط') }}"></div>
                @endif
            </div>

            {{-- Quick Action Buttons in NetStation Control Mode --}}
            @if ($viewMode === 'control')
                <div class="mt-2 pt-1.5 border-t border-white/10 flex items-center justify-between gap-1">
                    {{-- Single Station Lock/Unlock Toggle --}}
                    <button
                        type="button"
                        wire:click.stop="toggleStationLock({{ $station['id'] }})"
                        class="flex-1 py-1 px-1.5 rounded-md text-[10px] font-bold flex items-center justify-center gap-1 transition {{ $isLocked ? 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30' : 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30' }}"
                        title="{{ $isLocked ? __('إلغاء قفل هذه المحطة') : __('قفل شاشة هذه المحطة') }}"
                    >
                        <x-filament::icon icon="{{ $isLocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed' }}" class="w-3.5 h-3.5"/>
                        <span>{{ $isLocked ? __('فتح') : __('قفل') }}</span>
                    </button>

                    {{-- Open Student Kiosk Screen in new tab --}}
                    <a
                        href="{{ $stationUrl }}"
                        target="_blank"
                        class="p-1 rounded-md text-slate-400 hover:text-white hover:bg-white/10 transition"
                        title="{{ __('معاينة شاشة التلميذ في نافذة جديدة') }}"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3.5 h-3.5"/>
                    </a>
                </div>
            @elseif ($viewMode === 'seating' && $isAssigned)
                {{-- Unassign Button in Seating Mode --}}
                <button
                    type="button"
                    wire:click.stop="unassignStudent({{ $station['id'] }})"
                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow-md"
                    title="{{ __('إلغاء تعيين التلميذ') }}"
                >
                    &times;
                </button>
            @endif
        </div>

        {{-- Monitor Stand Bezel --}}
        <div class="w-6 h-2 bg-slate-700 dark:bg-slate-800 rounded-b"></div>
        <div class="w-12 h-1 bg-slate-600 dark:bg-slate-700 rounded-full"></div>
    </div>
@else
    {{-- ================================================================= --}}
    {{-- Science Lab Experiment Workbench (Physics / Chemistry / SVT)     --}}
    {{-- ================================================================= --}}
    @php
        $benchBorder = $isSelected
            ? 'border-emerald-500 ring-2 ring-emerald-400 dark:ring-emerald-500 shadow-lg shadow-emerald-500/20'
            : ($isAssigned
                ? 'border-teal-500/60 dark:border-teal-500/50 shadow-sm'
                : 'border-slate-700/80 hover:border-emerald-500/50');
        $benchSurfaceBg = $isAssigned ? 'bg-teal-950/60 text-teal-100' : 'bg-slate-800/60 text-emerald-300';
    @endphp
    <div
        wire:click="selectStation({{ $station['id'] }})"
        class="w-32 sm:w-36 flex flex-col items-center group relative select-none cursor-pointer"
    >
        {{-- Table Surface / Workbench Frame --}}
        <div class="w-full rounded-2xl p-2.5 bg-slate-900 border-2 {{ $benchBorder }} transition duration-200 relative overflow-hidden">
            {{-- Top Bar: Bench number & Status dot --}}
            <div class="flex items-center justify-between gap-1 pb-1 mb-1.5 border-b border-white/10 text-[10px]">
                <span class="font-bold flex items-center gap-1 font-mono text-white">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isAssigned ? 'bg-teal-400' : 'bg-emerald-400' }}"></span>
                    {{ __('طاولة') }} #{{ str_pad($station['station_number'], 2, '0', STR_PAD_LEFT) }}
                </span>
                <span class="text-[9px] font-semibold text-emerald-400/90 truncate">
                    @if ($isPhysics)
                        {{ __('فيزياء/كيمياء') }}
                    @elseif ($isNaturalScience)
                        {{ __('طبيعة وحياة') }}
                    @else
                        {{ __('تجارب') }}
                    @endif
                </span>
            </div>

            {{-- Table Center: Assigned Student or Ready Experiment Bench --}}
            <div class="w-full h-16 rounded-lg {{ $benchSurfaceBg }} p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden">
                @if ($isAssigned)
                    <div class="flex flex-col items-center justify-center w-full">
                        <div class="w-5 h-5 rounded-full bg-teal-500/30 text-teal-200 flex items-center justify-center text-[10px] font-bold mb-1">
                            {{ mb_substr($station['student_name'] ?? 'T', 0, 1) }}
                        </div>
                        <span class="text-[10px] font-bold text-white truncate w-full px-0.5">
                            {{ $station['student_name'] }}
                        </span>
                        <span class="text-[8px] text-teal-300/80">{{ __('تلميذ مجرّب') }}</span>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center text-emerald-400/80">
                        @if ($isPhysics)
                            <x-filament::icon icon="heroicon-o-beaker" class="w-5 h-5 mb-0.5 text-amber-400 opacity-90"/>
                        @else
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5 mb-0.5 text-emerald-400 opacity-90"/>
                        @endif
                        <span class="text-[9px] font-medium text-slate-300">{{ __('طاولة جاهزة') }}</span>
                    </div>
                @endif
            </div>

            {{-- Bench Equipment Indicator / Link --}}
            <div class="mt-2 pt-1 border-t border-white/10 flex items-center justify-between text-[9px] text-slate-400">
                <span class="truncate">
                    @if ($isPhysics)
                        {{ __('مأخذ ⚡ كواشف') }}
                    @elseif ($isNaturalScience)
                        {{ __('مجهر 🔬 شرائح') }}
                    @else
                        {{ __('عتاد مخبري 🧪') }}
                    @endif
                </span>
                <a
                    href="{{ $stationUrl }}"
                    target="_blank"
                    class="p-0.5 rounded text-slate-400 hover:text-white hover:bg-white/10 transition"
                    title="{{ __('دليل محطة التجارب') }}"
                    wire:click.stop
                >
                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3 h-3"/>
                </a>
            </div>

            {{-- Unassign Button in Seating Mode --}}
            @if ($isAssigned)
                <button
                    type="button"
                    wire:click.stop="unassignStudent({{ $station['id'] }})"
                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow-md"
                    title="{{ __('إلغاء تعيين التلميذ') }}"
                >
                    &times;
                </button>
            @endif
        </div>

        {{-- Workbench Table Surface Countertop & Legs --}}
        <div class="w-28 h-1 bg-slate-700 dark:bg-slate-600 rounded-full mt-0.5"></div>
        <div class="w-24 flex justify-between px-1">
            <div class="w-1.5 h-2 bg-slate-600 dark:bg-slate-700 rounded-b"></div>
            <div class="w-1.5 h-2 bg-slate-600 dark:bg-slate-700 rounded-b"></div>
        </div>
    </div>
@endif
