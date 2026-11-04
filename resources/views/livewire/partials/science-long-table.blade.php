@php
    $isPhysics = $isPhysics ?? false;
@endphp

<div class="rounded-3xl bg-slate-900 border-2 border-slate-700/80 p-5 shadow-lg relative overflow-hidden transition hover:border-emerald-500/40">
    {{-- Table Header --}}
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-white/10 text-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-black text-xs shadow-inner">
                {{ $table['number'] }}
            </span>
            <div>
                <h4 class="font-bold text-white text-sm">
                    {{ __('طاولة تجارب كبرى #:num', ['num' => str_pad($table['number'], 2, '0', STR_PAD_LEFT)]) }}
                </h4>
                <p class="text-[10px] text-slate-400 font-medium">
                    {{ __('فوج مخبري :num', ['num' => $table['number']]) }} &bull; 4 {{ __('مقاعد') }}
                </p>
            </div>
        </div>

        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $table['assigned_count'] === 4 ? 'bg-emerald-900/60 text-emerald-300 border border-emerald-500/40' : ($table['assigned_count'] > 0 ? 'bg-teal-900/60 text-teal-300 border border-teal-500/40' : 'bg-slate-800 text-slate-400 border border-slate-700') }}">
            {{ $table['assigned_count'] }}/4 {{ __('مقاعد') }}
        </span>
    </div>

    {{-- Front Row (الجهة الأمامية / 2 Seats) --}}
    <div class="space-y-1">
        <div class="flex items-center justify-between px-1 text-[9px] font-semibold text-slate-400 uppercase tracking-wider">
            <span>{{ __('الجهة أ (أمامية)') }}</span>
            <span>2 {{ __('مقاعد') }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @foreach ($table['front_seats'] as $seat)
                @include('livewire.partials.science-table-seat', ['seat' => $seat, 'isPhysics' => $isPhysics])
            @endforeach
        </div>
    </div>

    {{-- Central Utility Bridge / Dosseret technique (جسر الخدمات والتغذية المركزي) --}}
    <div class="my-3 py-2 px-3 rounded-2xl bg-gradient-to-r from-slate-950 via-slate-800/90 to-slate-950 border border-slate-700/80 flex items-center justify-between gap-1 text-[10px] text-slate-300 shadow-inner select-none">
        <div class="flex items-center gap-1 font-mono text-amber-400">
            <span>⚡</span>
            <span class="text-[9px] font-semibold hidden sm:inline">{{ __('مآخذ 220V') }}</span>
        </div>
        <div class="flex items-center gap-1 font-mono text-yellow-400">
            <span>🟡</span>
            <span class="text-[9px] font-semibold hidden sm:inline">{{ __('صنابير غاز') }}</span>
        </div>
        <div class="flex items-center gap-1 font-mono text-cyan-400">
            <span>🚰</span>
            <span class="text-[9px] font-semibold hidden sm:inline">{{ __('حوض ماء') }}</span>
        </div>
        <div class="flex items-center gap-1 font-mono text-emerald-400">
            @if ($isPhysics)
                <span>🧪</span>
                <span class="text-[9px] font-semibold hidden sm:inline">{{ __('كواشف وأدوات') }}</span>
            @else
                <span>🔬</span>
                <span class="text-[9px] font-semibold hidden sm:inline">{{ __('مجهر وشرائح') }}</span>
            @endif
        </div>
    </div>

    {{-- Back Row (الجهة المقابلة / 2 Seats) --}}
    <div class="space-y-1">
        <div class="flex items-center justify-between px-1 text-[9px] font-semibold text-slate-400 uppercase tracking-wider">
            <span>{{ __('الجهة ب (مقابلة)') }}</span>
            <span>2 {{ __('مقاعد') }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @foreach ($table['back_seats'] as $seat)
                @include('livewire.partials.science-table-seat', ['seat' => $seat, 'isPhysics' => $isPhysics])
            @endforeach
        </div>
    </div>

    {{-- Workbench Table Surface Countertop & Legs Decoration --}}
    <div class="w-full h-1 bg-slate-700/80 rounded-full mt-3"></div>
    <div class="flex justify-between px-4">
        <div class="w-2 h-2.5 bg-slate-600 rounded-b"></div>
        <div class="w-2 h-2.5 bg-slate-600 rounded-b"></div>
    </div>
</div>

