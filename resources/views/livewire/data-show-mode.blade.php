{{-- Interactive Smartboard / Data Show Mode — Projector Presentation Suite --}}
<div
    x-data="{
        theme: @entangle('theme'),
        fontScale: @entangle('fontScale'),
        timerRunning: @entangle('timerRunning'),
        timerSeconds: @entangle('timerSeconds'),
        spotlight: @entangle('spotlight'),
        pickedStudents: @entangle('pickedStudents'),
        slideIndex: @entangle('slideIndex'),
        slides: @entangle('content'),
        logs: @entangle('logs'),
        logId: @entangle('logId'),
        speechLocale: @js($speechLocale),
        ttsSupported: 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window,
        isSpeaking: false,
        isPaused: false,
        speechRate: 1.0,
        availableVoices: [],
        selectedVoiceURI: '',
        activeUtterance: null,
        speechLabels: {
            play: @js(__('قراءة الشريحة صوتياً')),
            pause: @js(__('إيقاف مؤقت')),
            resume: @js(__('استئناف')),
            stop: @js(__('إيقاف القراءة')),
            unsupported: @js(__('المتصفح الحالي لا يدعم القراءة الصوتية')),
            voice: @js(__('الصوت')),
            defaultVoice: @js(__('صوت المتصفح الافتراضي')),
            rate: @js(__('سرعة القراءة')),
            speaking: @js(__('جاري القراءة الصوتية...')),
        },
        timerInterval: null,
        voicesChangedHandler: null,
        beforeUnloadHandler: null,
        livewireNavigatingHandler: null,
        formatTimer() {
            const m = Math.floor(this.timerSeconds / 60).toString().padStart(2, '0');
            const s = (this.timerSeconds % 60).toString().padStart(2, '0');
            return m + ':' + s;
        },
        startTimer() { this.timerRunning = true; this.timerInterval = setInterval(() => { if (this.timerSeconds > 0) this.timerSeconds--; else { this.timerRunning = false; clearInterval(this.timerInterval); } }, 1000); },
        stopTimer() { this.timerRunning = false; clearInterval(this.timerInterval); },
        init() {
            try {
                const storedRate = Number(localStorage.getItem('sajel.datashow.speech_rate'));
                if ([0.8, 1.0, 1.2].includes(storedRate)) this.speechRate = storedRate;
            } catch (error) {
                console.error('Unable to load the saved narration speed.', error);
            }

            if (this.ttsSupported) {
                this.loadVoices();
                this.voicesChangedHandler = () => this.loadVoices();
                window.speechSynthesis.addEventListener('voiceschanged', this.voicesChangedHandler);
            }

            this.beforeUnloadHandler = () => this.stopSpeech();
            this.livewireNavigatingHandler = () => this.stopSpeech();
            window.addEventListener('beforeunload', this.beforeUnloadHandler);
            document.addEventListener('livewire:navigating', this.livewireNavigatingHandler);
            this.$watch('slideIndex', (value, previous) => {
                if (value !== previous) this.stopSpeech();
            });
            this.$watch('logId', (value, previous) => {
                if (value !== previous) this.stopSpeech();
            });
            this.$watch('speechRate', (value) => {
                if (![0.8, 1.0, 1.2].includes(Number(value))) {
                    this.speechRate = 1.0;

                    return;
                }

                try {
                    localStorage.setItem('sajel.datashow.speech_rate', String(value));
                } catch (error) {
                    console.error('Unable to save the narration speed.', error);
                }

                if (this.isSpeaking) this.speakSlide();
            });
            this.$watch('selectedVoiceURI', () => {
                if (this.isSpeaking) this.speakSlide();
            });
        },
        destroy() {
            this.stopSpeech();
            clearInterval(this.timerInterval);
            window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            document.removeEventListener('livewire:navigating', this.livewireNavigatingHandler);
            if (this.ttsSupported && this.voicesChangedHandler) {
                window.speechSynthesis.removeEventListener('voiceschanged', this.voicesChangedHandler);
            }
        },
        loadVoices() {
            this.availableVoices = window.speechSynthesis.getVoices();
            if (!this.availableVoices.some((voice) => voice.voiceURI === this.selectedVoiceURI)) {
                const language = this.speechLocale.split('-')[0].toLowerCase();
                const matchingVoices = this.availableVoices.filter((voice) =>
                    voice.lang.toLowerCase().startsWith(language),
                );
                const preferred = matchingVoices.find((voice) => voice.localService)
                    ?? matchingVoices[0];
                this.selectedVoiceURI = preferred?.voiceURI ?? '';
            }
        },
        speakSlide() {
            if (!this.ttsSupported) return;
            const text = this.slides[Number(this.slideIndex)]?.narration_text?.trim();
            if (!text) return;

            this.stopSpeech();
            const utterance = new SpeechSynthesisUtterance(text);
            const voice = this.availableVoices.find((item) => item.voiceURI === this.selectedVoiceURI);
            if (voice) utterance.voice = voice;
            utterance.lang = voice?.lang ?? this.speechLocale;
            utterance.rate = this.speechRate;
            utterance.onstart = () => {
                if (this.activeUtterance === utterance) this.isSpeaking = true;
            };
            utterance.onpause = () => {
                if (this.activeUtterance === utterance) this.isPaused = true;
            };
            utterance.onresume = () => {
                if (this.activeUtterance === utterance) this.isPaused = false;
            };
            utterance.onend = () => {
                if (this.activeUtterance === utterance) {
                    this.activeUtterance = null;
                    this.isSpeaking = false;
                    this.isPaused = false;
                }
            };
            utterance.onerror = (event) => {
                if (this.activeUtterance === utterance) {
                    if (event.error !== 'canceled' && event.error !== 'interrupted') {
                        console.error('Voice narration failed.', event.error);
                    }
                    this.activeUtterance = null;
                    this.isSpeaking = false;
                    this.isPaused = false;
                }
            };

            this.activeUtterance = utterance;
            this.isSpeaking = true;
            this.isPaused = false;
            window.speechSynthesis.speak(utterance);
        },
        toggleSpeech() {
            if (this.isSpeaking) this.stopSpeech();
            else this.speakSlide();
        },
        pauseResumeSpeech() {
            if (!this.ttsSupported || !this.isSpeaking) return;
            if (this.isPaused) window.speechSynthesis.resume();
            else window.speechSynthesis.pause();
        },
        stopSpeech() {
            if (this.ttsSupported) window.speechSynthesis.cancel();
            this.activeUtterance = null;
            this.isSpeaking = false;
            this.isPaused = false;
        },
        selectLog(id) {
            this.stopSpeech();
            $wire.selectLog(id);
        },
        goSlide(i) {
            this.stopSpeech();
            $wire.goToSlide(i);
        },
        nextSlide() {
            this.stopSpeech();
            $wire.next();
        },
        prevSlide() {
            this.stopSpeech();
            $wire.prev();
        },
        handleVoiceShortcut(event) {
            const target = event.target;
            if (
                event.key.toLowerCase() !== 'v'
                || event.altKey
                || event.ctrlKey
                || event.metaKey
                || target?.isContentEditable
                || target?.closest?.('input, textarea, select, [contenteditable=true]')
            ) return;

            event.preventDefault();
            this.toggleSpeech();
        },
        zoomIn() { $wire.zoomIn(); },
        zoomOut() { $wire.zoomOut(); },
        toggleTheme(t) { $wire.switchTheme(t); },
        toggleSpotlight() { $wire.toggleSpotlight(); },
        pickRandom() { $wire.pickRandomStudent(); },
        fullscreen() { if (document.fullscreenElement) document.exitFullscreen(); else document.documentElement.requestFullscreen(); },
    }"
    x-init="if(!timerInterval && timerRunning) startTimer()"
    @keydown.arrow-right.window="nextSlide(); $event.preventDefault();"
    @keydown.arrow-left.window="prevSlide(); $event.preventDefault();"
    @keydown.space.window="nextSlide(); $event.preventDefault();"
    @keydown.f.window="fullscreen(); $event.preventDefault();"
    @keydown.window="handleVoiceShortcut($event)"
    @keydown.escape.window="$wire.emit('prev');"
    class="min-h-screen transition-colors duration-700"
    :class="{
        'bg-slate-950 text-white': theme === 'cinema',
        'bg-amber-50 text-slate-950': theme === 'whiteboard',
        'bg-emerald-950 text-emerald-50': theme === 'chalkboard'
    }"
>

    {{-- Top Control Bar (collapsible via CSS or always visible) --}}
    <div class="sticky top-0 z-40 backdrop-blur-md border-b border-white/10 bg-white/5 dark:bg-black/20 px-4 py-2 flex items-center gap-3 text-sm">
        <div class="flex items-center gap-2">
            <label for="logSelect" class="text-xs opacity-70">{{ __('الحصة') }}:</label>
            <select id="logSelect" @change="selectLog($event.target.value)" class="bg-white/10 border border-white/20 rounded px-2 py-1 text-xs min-w-[240px]">
                <option value="">{{ __('اختر السجل...') }}</option>
                @foreach ($logs as $l)
                    <option value="{{ $l['id'] }}" {{ $logId == $l['id'] ? 'selected' : '' }}>{{ $l['label'] }}</option>
                @endforeach
            </select>
            <button wire:click="loadLogs" type="button" class="p-1 rounded hover:bg-white/10" title="{{ __('تحديث القائمة') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </button>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <div class="flex items-center gap-1 border-r border-white/20 pr-2">
                <button
                    type="button"
                    @click="toggleSpeech()"
                    :disabled="!ttsSupported || !slides[Number(slideIndex)]?.narration_text"
                    title="{{ __('المتصفح الحالي لا يدعم القراءة الصوتية') }}"
                    x-effect="$el.title = ttsSupported ? speechLabels.play : speechLabels.unsupported"
                    :aria-label="speechLabels.play"
                    class="px-2 py-1 rounded bg-amber-500 text-slate-950 text-xs font-bold disabled:cursor-not-allowed disabled:opacity-40"
                >▶ <span x-text="speechLabels.play">{{ __('قراءة الشريحة صوتياً') }}</span></button>
                <button
                    type="button"
                    x-show="isSpeaking"
                    @click="pauseResumeSpeech()"
                    :aria-label="isPaused ? speechLabels.resume : speechLabels.pause"
                    class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-xs"
                ><span x-text="isPaused ? speechLabels.resume : speechLabels.pause">{{ __('إيقاف مؤقت') }}</span></button>
                <button
                    type="button"
                    x-show="isSpeaking"
                    @click="stopSpeech()"
                    :aria-label="speechLabels.stop"
                    class="px-2 py-1 rounded bg-rose-500/20 text-rose-200 text-xs"
                    x-text="speechLabels.stop"
                >{{ __('إيقاف القراءة') }}</button>
                <label class="sr-only" for="speechRate" x-text="speechLabels.rate"></label>
                <select id="speechRate" x-model.number="speechRate" class="bg-white/10 border border-white/20 rounded px-1 py-1 text-xs" :aria-label="speechLabels.rate">
                    <option value="0.8">0.8x</option>
                    <option value="1.0">1.0x</option>
                    <option value="1.2">1.2x</option>
                </select>
                <label class="sr-only" for="speechVoice" x-text="speechLabels.voice"></label>
                <select id="speechVoice" x-model="selectedVoiceURI" class="max-w-36 bg-white/10 border border-white/20 rounded px-1 py-1 text-xs" :aria-label="speechLabels.voice" :disabled="!ttsSupported || availableVoices.length === 0">
                    <option value="" x-text="speechLabels.defaultVoice">{{ __('صوت المتصفح الافتراضي') }}</option>
                    <template x-for="voice in availableVoices" :key="voice.voiceURI">
                        <option :value="voice.voiceURI" x-text="`${voice.name} (${voice.lang})`"></option>
                    </template>
                </select>
                <span
                    x-cloak
                    x-show="isSpeaking"
                    role="status"
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-1 text-xs font-semibold text-emerald-200"
                ><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span><span x-text="speechLabels.speaking">{{ __('جاري القراءة الصوتية...') }}</span></span>
            </div>
            <button @click="toggleTheme('cinema')" :class="theme==='cinema'?'bg-amber-400 text-slate-950':'bg-white/10'" class="px-2 py-0.5 rounded text-xs font-medium transition">Cinema</button>
            <button @click="toggleTheme('whiteboard')" :class="theme==='whiteboard'?'bg-amber-400 text-slate-950':'bg-white/10'" class="px-2 py-0.5 rounded text-xs font-medium transition">Whiteboard</button>
            <button @click="toggleTheme('chalkboard')" :class="theme==='chalkboard'?'bg-amber-400 text-slate-950':'bg-white/10'" class="px-2 py-0.5 rounded text-xs font-medium transition">Chalkboard</button>

            <button @click="zoomIn()" class="px-2 py-0.5 rounded bg-white/10 hover:bg-white/20 text-xs" title="A+">A+</button>
            <button @click="zoomOut()" class="px-2 py-0.5 rounded bg-white/10 hover:bg-white/20 text-xs" title="A-">A-</button>

            <button @click="toggleSpotlight()" :class="spotlight ? 'bg-amber-400 text-slate-950' : 'bg-white/10'" class="px-2 py-0.5 rounded text-xs font-medium" title="Spotlight">⚡</button>

            <button @click="fullscreen()" class="px-2 py-0.5 rounded bg-amber-500 text-white text-xs font-bold" title="Fullscreen">⛶</button>

            <a href="/ar/auth" class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-200 text-xs border border-rose-400/30 hover:bg-rose-500/30">{{ __('عودة') }}</a>
        </div>
    </div>

    {{-- Presentation Area --}}
    <main class="relative h-[calc(100vh-56px)] flex flex-col justify-center items-center text-center px-6 md:px-16 overflow-hidden">
        @if ($slides && count($slides) > 0)
            @php
                $slide = $slides[$slideIndex] ?? null;
                $scaleBase = match((int)floor($fontScale)) { 1 => 'text-2xl', 2 => 'text-3xl', 3 => 'text-4xl', default => 'text-5xl' };
                $scaleBody = match((int)floor($fontScale)) { 1 => 'text-xl', 2 => 'text-2xl', 3 => 'text-3xl', default => 'text-4xl' };
            @endphp

            <div class="w-full max-w-6xl" :class="spotlight ? 'opacity-95 scale-[1.02]' : ''" x-transition.opacity.duration.300ms>
                @if ($slide['type'] === 'cover')
                    <div class="mb-4">
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-amber-500 text-white">{{ $slide['subject_color'] ?? 'amber' }}</span>
                    </div>
                    <h1 class="font-extrabold leading-tight mb-6 tracking-tight" style="font-size: calc(3.5rem + {{ $fontScale * 1.5 }}rem);" :class="theme==='whiteboard' ? 'text-slate-950' : 'text-amber-200'">
                        {{ $slide['title'] ?? '' }}
                    </h1>
                    <p class="text-xl md:text-2xl font-light opacity-70 mb-12">{{ $slide['subtitle'] ?? '' }}</p>
                    @if ($slide['competence'])
                        <div class="border-r-4 border-amber-400 bg-white/5 rounded-xl p-6 max-w-3xl mx-auto text-right shadow-2xl">
                            <h3 class="text-sm font-bold text-amber-300 mb-2 tracking-widest">{{ __('الكفاءة المستهدفة') }}</h3>
                            <p class="text-2xl md:text-3xl leading-relaxed font-medium">{{ $slide['competence'] }}</p>
                        </div>
                    @endif
                @elseif ($slide['type'] === 'phase')
                    <div class="flex items-start gap-4 mb-6">
                        <span class="flex-shrink-0 w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center font-extrabold text-xl shadow-lg">{{ $slide['phase_number'] }}</span>
                        <h2 class="font-extrabold text-4xl md:text-6xl leading-tight" style="font-size: calc(2.5rem + {{ $fontScale * 1.2 }}rem);" :class="theme==='whiteboard' ? 'text-slate-950' : 'text-white'">{{ $slide['title'] }}</h2>
                    </div>
                    @if ($slide['duration'])
                        <div class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-white/10 text-amber-200 text-sm mb-6 font-mono border border-white/10">
                            ⏱️ {{ $slide['duration'] }} {{ __('دقيقة') }}
                        </div>
                    @endif
                    @if ($slide['description'])
                        <div class="bg-white/5 border border-white/10 rounded-3xl p-8 md:p-12 shadow-2xl text-right leading-loose backdrop-blur-md" style="font-size: calc(1.2rem + {{ $fontScale * 0.8 }}rem);">
                            <div class="prose prose-xl prose-invert max-w-none">{!! nl2br(e($slide['description'])) !!}</div>
                        </div>
                    @endif
                @elseif ($slide['type'] === 'summary')
                    <h2 class="font-extrabold text-4xl md:text-6xl mb-10" style="font-size: calc(2.5rem + {{ $fontScale * 1.2 }}rem);">{{ $slide['title'] }}</h2>
                    @if ($slide['register'])
                        <div class="bg-white/5 border border-white/10 rounded-3xl p-8 md:p-12 shadow-2xl text-right text-xl md:text-2xl leading-loose mb-6">{!! nl2br(e(strip_tags($slide['register']))) !!}</div>
                    @endif
                    @if ($slide['homework'])
                        <div class="bg-amber-500/10 border border-amber-400/30 rounded-2xl p-6 text-amber-100 text-xl md:text-2xl font-medium leading-relaxed">{{ __('الواجب المنزلي') }}: {{ $slide['homework'] }}</div>
                    @endif
                    @if ($slide['observations'])
                        <div class="mt-6 text-base md:text-xl text-white/60">{{ __('الملاحظات') }}: {{ $slide['observations'] }}</div>
                    @endif
                @endif
            </div>

            {{-- Navigation dots --}}
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-30">
                @foreach ($slides as $i => $s)
                    <button @click="goSlide({{ $i }})" class="w-2.5 h-2.5 rounded-full transition {{ $i === $slideIndex ? 'bg-amber-400 scale-125' : 'bg-white/30 hover:bg-white/60' }}"></button>
                @endforeach
            </div>
        @else
            {{-- Empty state with selector --}}
            <div class="text-center space-y-8">
                <h1 class="text-6xl font-extrabold text-amber-300 mb-4">{{ __('لا توجد بيانات') }}</h1>
                <p class="text-xl text-white/40">{{ __('اختر سجلاً يومياً لعرضه بالمسلاط.') }}</p>
                <div class="max-w-md mx-auto bg-white/10 rounded-xl p-6 border border-white/10 backdrop-blur">
                    <label class="block text-sm mb-2 text-white/70">{{ __('اختر الحصة') }}</label>
                    <div class="w-full space-y-1">
                        @if (!$logs)
                            <div class="text-xs text-white/30">{{ __('لا توجد حصص مسجلة.') }}</div>
                        @endif
                        @foreach ($logs as $l)
                            <button @click="selectLog({{ $l['id'] ?? 0 }})" class="w-full text-right px-3 py-2 rounded text-sm border border-white/10 mb-1 hover:bg-white/10 {{ $logId == ($l['id'] ?? 0) ? 'bg-amber-500 text-white' : 'bg-white/5 text-white/80' }} transition">{{ $l['label'] ?? '' }}</button>
                        @endforeach
                    </div>
                </div>
                <a href="/ar/auth/daily-logs/create" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 text-white font-bold hover:bg-amber-600 transition shadow-xl">{{ __('إنشاء تحضير درس جديد') }}</a>
            </div>
        @endif

        {{-- Quick Tools Drawer (bottom-right floating) --}}
        <div class="absolute bottom-6 right-6 z-40 flex gap-2" x-data="{ open: false }">
            <button @click="open = !open" class="w-12 h-12 rounded-full bg-amber-500 text-white shadow-2xl flex items-center justify-center text-xl hover:scale-105 transition" title="Tools">⚡</button>
            <div x-show="open" x-transition.origin.bottom.right class="absolute bottom-14 right-0 w-72 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-2xl p-4 shadow-2xl text-right">
                <div class="text-xs font-bold text-amber-300 mb-3">{{ __('أدوات الحصة السريعة') }}</div>
                <div class="space-y-2">
                    <button wire:click="toggleTimer(5)" class="w-full text-right px-3 py-2 rounded bg-white/10 hover:bg-white/20 text-sm">⏱️ 5 دقائق</button>
                    <button wire:click="toggleTimer(10)" class="w-full text-right px-3 py-2 rounded bg-white/10 hover:bg-white/20 text-sm">⏱️ 10 دقائق</button>
                    <button wire:click="pickRandomStudent" class="w-full text-right px-3 py-2 rounded bg-emerald-600 text-white text-sm">🎲 {{ __('قرعة عشوائية') }}</button>
                    <button wire:click="toggleSpotlight" class="w-full text-right px-3 py-2 rounded bg-blue-600 text-white text-sm">🔦 {{ __('مؤشر التركيز') }}</button>
                </div>
            </div>
        </div>

        {{-- Slide counter overlay --}}
        <div class="absolute top-4 right-4 z-30 text-white/40 font-mono text-sm">{{ $slideIndex + 1 }} / {{ count($slides) }}</div>
    </main>
</div>
