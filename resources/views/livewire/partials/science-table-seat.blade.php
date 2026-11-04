@php
    $seatId = $seat['id'] ?? null;
    $isSelected = $seatId && $this->selectedStationId === $seatId;
    $isAssigned = ! empty($seat['student_id']);
    $isVirtual = $seat['is_virtual'] ?? false;
    $stationUrl = $seatId ? route('lab.station-client', ['locale' => app()->getLocale(), 'labStation' => $seatId]) : null;

    if ($isSelected) {
        $seatBorder = 'border-emerald-500 ring-2 ring-emerald-400 bg-emerald-950/70 shadow-lg shadow-emerald-500/20';
    } elseif ($isAssigned) {
        $seatBorder = 'border-teal-500/60 bg-teal-950/40 text-teal-100 hover:border-teal-400';
    } elseif (! $isVirtual) {
        $seatBorder = 'border-slate-700/80 bg-slate-800/50 text-slate-300 hover:border-emerald-500/50 hover:bg-slate-800/80';
    } else {
        $seatBorder = 'border-dashed border-slate-800 bg-slate-900/30 text-slate-600 opacity-60';
    }
@endphp

<div
    @if ($seatId)
        wire:click="selectStation({{ $seatId }})"
    @endif
    class="rounded-2xl border-2 {{ $seatBorder }} p-2.5 transition duration-200 relative select-none {{ $seatId ? 'cursor-pointer group' : 'cursor-not-allowed' }}"
>
    {{-- Seat Top Bar --}}
    <div class="flex items-center justify-between text-[10px] pb-1 mb-1.5 border-b border-white/10">
        <span class="font-bold font-mono flex items-center gap-1.5 {{ $isSelected ? 'text-emerald-300' : 'text-slate-200' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $isAssigned ? 'bg-teal-400' : ($seatId ? 'bg-emerald-400' : 'bg-slate-600') }}"></span>
            {{ __('مقعد') }} #{{ str_pad($seat['station_number'], 2, '0', STR_PAD_LEFT) }}
        </span>

        @if ($stationUrl)
            <a
                href="{{ $stationUrl }}"
                target="_blank"
                wire:click.stop
                class="text-slate-400 hover:text-white transition p-0.5"
                title="{{ __('دليل محطة التجارب') }}"
            >
                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3 h-3"/>
            </a>
        @endif
    </div>

    {{-- Seat Body --}}
    <div class="h-14 flex flex-col items-center justify-center text-center">
        @if ($isAssigned)
            <div class="flex items-center gap-2 w-full px-1">
                <div class="w-7 h-7 rounded-full bg-teal-500/30 text-teal-200 border border-teal-400/40 flex items-center justify-center text-xs font-bold shrink-0">
                    {{ mb_substr($seat['student_name'] ?? 'T', 0, 1) }}
                </div>
                <div class="flex-1 text-right overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ $seat['student_name'] }}</p>
                    <p class="text-[9px] text-teal-300/80 font-medium">{{ __('تلميذ مجرّب') }}</p>
                </div>
            </div>
        @elseif (! $isVirtual)
            <div class="flex flex-col items-center justify-center text-slate-400 group-hover:text-emerald-400 transition">
                <x-filament::icon icon="heroicon-o-user" class="w-4 h-4 mb-0.5 opacity-60 group-hover:opacity-100"/>
                <span class="text-[10px] font-semibold text-slate-300">{{ __('مقعد شاغر') }}</span>
                <span class="text-[8px] text-slate-400">{{ __('انقر للتعيين') }}</span>
            </div>
        @else
            <div class="flex flex-col items-center justify-center text-slate-600">
                <span class="text-[10px]">{{ __('مقعد غير متاح') }}</span>
            </div>
        @endif
    </div>

    {{-- Unassign Button if assigned --}}
    @if ($isAssigned && $seatId)
        <button
            type="button"
            wire:click.stop="unassignStudent({{ $seatId }})"
            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow-md transition"
            title="{{ __('إلغاء تعيين التلميذ') }}"
        >
            &times;
        </button>
    @endif
</div>

