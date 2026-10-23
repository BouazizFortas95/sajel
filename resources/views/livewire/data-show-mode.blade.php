<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('عرض المسلاط') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;800&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        .slide-enter { animation: fade 0.5s ease-out; }
        @keyframes fade { from { opacity: 0; transform: scale(1.02); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 min-h-screen text-white overflow-hidden select-none">
    <main class="h-screen flex flex-col justify-center items-center text-center px-8 slide-enter relative">
        @if ($currentSlide)
            <div class="absolute top-4 left-4 z-50">
                <a href="/ar/auth" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white/70 text-sm border border-white/10 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    {{ __('العودة للوحة التحكم') }}
                </a>
            </div>

            <div class="absolute top-8 left-8 text-xs text-white/30 font-mono">{{ $currentSlide['type'] ?? 'slide' }} · {{ $loop?->iteration ?? 1 }}/{{ count($slides) }}</div>

            @if ($currentSlide['type'] === 'cover')
                <h1 class="text-6xl md:text-8xl font-extrabold mb-6 bg-gradient-to-r from-amber-300 via-amber-200 to-amber-400 bg-clip-text text-transparent tracking-tight">
                    {{ $currentSlide['title'] ?? '' }}
                </h1>
                <p class="text-2xl md:text-4xl text-white/60 font-light">{{ $currentSlide['subtitle'] ?? '' }}</p>
                <div class="mt-16 text-sm text-white/30">{{ __('عرض المسلاط') }}</div>
            @else
                <h2 class="text-5xl md:text-7xl font-extrabold mb-10 text-amber-200 tracking-tight">
                    {{ $currentSlide['title'] ?? '' }}
                </h2>
                @if ($currentSlide['description'])
                    <p class="text-2xl md:text-3xl text-white/80 leading-relaxed max-w-4xl font-light">
                        {{ $currentSlide['description'] }}
                    </p>
                @endif
                @if ($currentSlide['duration'])
                    <div class="mt-12 text-amber-300/60 text-xl font-mono">{{ $currentSlide['duration'] }} {{ __('دقيقة') }}</div>
                @endif
            @endif
        @else
            <h1 class="text-6xl font-extrabold text-amber-300">{{ __('لا توجد بيانات') }}</h1>
            <p class="text-xl text-white/40 mt-4">{{ __('اختر سجلاً يومياً من القائمة لعرضه.') }}</p>
        @endif

        {{-- Navigation dots --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3">
            @foreach ($slides as $i => $s)
                <button onclick="window.livewire.emit('goToSlide', {{ $i }})" class="w-3 h-3 rounded-full transition {{ $i === $slideIndex ? 'bg-amber-400 scale-125' : 'bg-white/20 hover:bg-white/40' }}"></button>
            @endforeach
        </div>
    </main>

    <script>
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') {
                e.preventDefault();
                window.livewire.emit('next');
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                window.livewire.emit('prev');
            } else if (e.key >= '1' && e.key <= '9') {
                const idx = parseInt(e.key) - 1;
                window.livewire.emit('goToSlide', idx);
            } else if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>
