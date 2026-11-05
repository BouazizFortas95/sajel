<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if ($lab->isInformatics())
            {{ __('محطة التلميذ :num — :lab | سجِّل', ['num' => $station->station_number, 'lab' => $lab->name]) }}
        @else
            {{ __('طاولة تجارب :num — :lab | سجِّل', ['num' => $station->station_number, 'lab' => $lab->name]) }}
        @endif
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body
    class="h-full bg-slate-950 text-slate-100 overflow-x-hidden antialiased select-none"
    @if ($lab->isInformatics())
        x-data="{
            isLocked: {{ $station->is_locked ? 'true' : 'false' }},
            lockMessage: @js($station->lock_message ?? __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.')),
            activeBroadcast: @js($station->active_broadcast),
            internetEnabled: {{ $station->internet_enabled ? 'true' : 'false' }},
            isConnected: true,
            lastHeartbeat: new Date(),

            async checkState() {
                try {
                    const res = await fetch('{{ $pingUrl }}');
                    if (res.ok) {
                        const json = await res.json();
                        if (json.success && json.data) {
                            this.isLocked = !!json.data.is_locked;
                            this.lockMessage = json.data.lock_message || '{{ __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.') }}';
                            this.activeBroadcast = json.data.active_broadcast;
                            this.internetEnabled = !!json.data.internet_enabled;
                            this.isConnected = true;
                            this.lastHeartbeat = new Date();
                        }
                    } else {
                        this.isConnected = false;
                    }
                } catch (e) {
                    this.isConnected = false;
                }
            },

            async acknowledgeBroadcast() {
                try {
                    await fetch('{{ route('lab.station-acknowledge', ['locale' => app()->getLocale(), 'labStation' => $station]) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        }
                    });
                    this.activeBroadcast = null;
                } catch (e) {
                    this.activeBroadcast = null;
                }
            },

            init() {
                setInterval(() => this.checkState(), 3000);
            }
        }"
    @endif
>
    @if ($lab->isInformatics())
        {{-- ============================================================= --}}
        {{-- INFORMATICS LAB: NETSTATION SUITE KIOSK                       --}}
        {{-- ============================================================= --}}

        {{-- Fullscreen Locked Screen Overlay ----------------------------- --}}
        <div
            x-show="isLocked"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center p-6 bg-slate-950/95 backdrop-blur-xl"
            style="display: none;"
        >
            {{-- Warning Ambient Glow --}}
            <div class="absolute -top-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 w-96 h-96 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative max-w-2xl w-full text-center space-y-6">
                {{-- Lock Icon with Pulse Ring --}}
                <div class="relative mx-auto w-24 h-24 flex items-center justify-center rounded-3xl bg-amber-500/20 border-2 border-amber-500/40 shadow-2xl shadow-amber-500/20">
                    <span class="absolute inline-flex h-full w-full rounded-3xl bg-amber-400 opacity-20 animate-ping"></span>
                    <svg class="w-12 h-12 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>

                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        {{ __('المحطة الرئيسية للأستاذ') }} &bull; {{ __('قفل بيداغوجي') }}
                    </span>
                    <h1 class="text-3xl md:text-5xl font-black text-white mt-3 tracking-tight">
                        {{ __('الشاشة مقفلة بطلب من الأستاذ') }}
                    </h1>
                    <p class="text-slate-400 text-sm md:text-base mt-2">
                        {{ __('يرجى التوقف عن استخدام الفأرة ولوحة المفاتيح والتركيز مع الأستاذ.') }}
                    </p>
                </div>

                {{-- Custom Teacher Message Card --}}
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-amber-500/30 text-amber-100 shadow-xl">
                    <div class="flex items-center justify-center gap-2 text-xs font-bold text-amber-400 uppercase tracking-wider mb-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        {{ __('رسالة توجيهية من منصة الأستاذ') }}
                    </div>
                    <p class="text-lg md:text-xl font-bold leading-relaxed" x-text="lockMessage">{{ $station->lock_message ?? __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.') }}</p>
                </div>

                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <span>{{ $lab->name }}</span>
                    <span>&bull;</span>
                    <span>{{ __('محطة رقم :num', ['num' => $station->station_number]) }}</span>
                    <span>&bull;</span>
                    <span>{{ $student ? $student->full_name : __('تلميذ غير معين') }}</span>
                </div>
            </div>
        </div>

        {{-- Broadcast Message Alert Banner ------------------------------- --}}
        <div
            x-show="activeBroadcast && !isLocked"
            x-transition
            class="fixed top-4 inset-x-4 md:inset-x-auto md:right-8 md:max-w-lg z-40 p-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-2xl border border-blue-400/30"
            style="display: none;"
        >
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-xl bg-white/20">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-bold">{{ __('تنبيه وتوجيه من الأستاذ') }}</h4>
                    <p class="text-xs text-blue-100 mt-1 leading-relaxed" x-text="activeBroadcast"></p>
                    <div class="mt-3 flex justify-end">
                        <button
                            @click="acknowledgeBroadcast()"
                            class="px-3 py-1 rounded-lg text-xs font-bold bg-white text-blue-800 hover:bg-blue-50 transition shadow-sm"
                        >
                            {{ __('تم الاطلاع') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Student Station Dashboard ------------------------------- --}}
        <div class="min-h-full flex flex-col justify-between p-6 md:p-10">
            {{-- Top Bar --}}
            <header class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white">{{ $lab->name }}</h2>
                        <p class="text-xs text-slate-400">
                            {{ $schoolSetting?->school_name ?? __('المؤسسة التربوية') }} &bull; {{ $schoolSetting?->academic_year ?? '2026-2027' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Internet Status --}}
                    <div
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-2"
                        :class="internetEnabled ? 'bg-emerald-950/40 border-emerald-500/30 text-emerald-300' : 'bg-rose-950/40 border-rose-500/30 text-rose-300'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="internetEnabled ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                        <span x-text="internetEnabled ? '{{ __('الإنترنت متاح') }}' : '{{ __('الإنترنت محجوب') }}'"></span>
                    </div>

                    {{-- Leader PC Connection Status --}}
                    <div
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-2"
                        :class="isConnected ? 'bg-blue-950/40 border-blue-500/30 text-blue-300' : 'bg-rose-950/40 border-rose-500/30 text-rose-300'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="isConnected ? 'bg-blue-400 animate-pulse' : 'bg-rose-400'"></span>
                        <span x-text="isConnected ? '{{ __('متصل بالحاسوب الرئيسي') }}' : '{{ __('جاري إعادة الاتصال...') }}'"></span>
                    </div>

                    {{-- Station Badge --}}
                    <div class="px-4 py-1.5 rounded-xl bg-amber-500 text-slate-950 font-black text-sm shadow-lg shadow-amber-500/20">
                        {{ __('المحطة') }} #{{ $station->station_number }}
                    </div>
                </div>
            </header>

            {{-- Center Content / Student Session Identity --}}
            <main class="my-auto py-12">
                <div class="max-w-3xl mx-auto space-y-8">
                    {{-- Student Card --}}
                    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>

                        <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-start">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-slate-950 text-2xl font-black shadow-xl">
                                {{ $student ? mb_substr($student->first_name, 0, 1) : '💻' }}
                            </div>

                            <div class="flex-1 space-y-1">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">
                                    {{ __('الجلسة التطبيقية في المخبر') }}
                                </span>
                                <h3 class="text-2xl md:text-3xl font-black text-white">
                                    {{ $student ? $student->full_name : __('محطة جاهزة لاستقبال التلميذ') }}
                                </h3>
                                <p class="text-sm text-slate-400">
                                    @if ($student)
                                        {{ __('رقم التسجيل:') }} <span class="font-mono text-slate-300">{{ $student->registration_number }}</span>
                                        &bull;
                                        {{ __('الفوج:') }} <span class="text-slate-300">{{ $student->group?->display_name() }}</span>
                                    @else
                                        {{ __('يمكن للأستاذ تعيينك على هذه المحطة من منصة التحكم.') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($todayLog)
                        {{-- Active Lesson / Experiment Card --}}
                        <div class="p-6 rounded-2xl bg-slate-900/80 border border-blue-500/30 shadow-lg relative overflow-hidden">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-white/10">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-400 animate-pulse"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-400">
                                        {{ __('بطاقة التجربة البيداغوجية للحصة') }}
                                    </span>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                    {{ $todayLog->schedule?->subject?->name ?? __('المادة المقررة') }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2">
                                <h4 class="text-lg font-bold text-white">{{ $todayLog->title }}</h4>
                                @if ($todayLog->actual_content)
                                    <p class="text-sm text-slate-300 leading-relaxed">{{ $todayLog->actual_content }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-2">
                                    @if ($todayLog->schedule?->user)
                                        <span>
                                            {{ __('الأستاذ المشرف:') }} <strong class="text-slate-200">{{ $todayLog->schedule->user->name }}</strong>
                                        </span>
                                    @endif
                                    @if ($todayLog->homework)
                                        <span>&bull;</span>
                                        <span>
                                            {{ __('العمل المطلوب:') }} <strong class="text-amber-300">{{ $todayLog->homework }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Recommended Classroom Tools & Guidelines --}}
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 transition">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-white">{{ __('البرمجة والخوارزميات') }}</h4>
                            <p class="text-xs text-slate-400 mt-1">{{ __('تنفيذ البرامج والأنشطة المقررة وفق منهاج الجيل الثاني.') }}</p>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 transition">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-white">{{ __('المكتبيات والتوثيق') }}</h4>
                            <p class="text-xs text-slate-400 mt-1">{{ __('معالجة النصوص والجداول والعروض التقديمية التفاعلية.') }}</p>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 transition">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-white">{{ __('الانضباط والأمان الرقمي') }}</h4>
                            <p class="text-xs text-slate-400 mt-1">{{ __('احترام تعليمات الأستاذ والحفاظ على تجهيزات المخبر.') }}</p>
                        </div>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <div>
                    {{ __('نظام سجِّل للتحكم بمخابر الإعلام الآلي (NetStation Suite)') }}
                </div>
                <div>
                    {{ __('المنصة الرئيسية: تحكم مركزي متصل') }} &bull; {{ now()->format('Y/m/d') }}
                </div>
            </footer>
        </div>
    @else
        {{-- ============================================================= --}}
        {{-- SCIENCE LAB: DIGITAL EXPERIMENT BENCH CARD & SAFETY GUIDE     --}}
        {{-- ============================================================= --}}
        @php
            $isPhysics = $lab->isPhysics();
            $isNaturalScience = $lab->isNaturalScience();
        @endphp

        <div class="min-h-full flex flex-col justify-between p-6 md:p-10">
            {{-- Top Bar --}}
            <header class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center">
                        @if ($isPhysics)
                            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white">{{ $lab->name }}</h2>
                        <p class="text-xs text-slate-400">
                            {{ $schoolSetting?->school_name ?? __('المؤسسة التربوية') }} &bull; {{ $schoolSetting?->academic_year ?? '2026-2027' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Lab Safety Protocol Status --}}
                    <div class="px-3 py-1.5 rounded-xl border border-emerald-500/30 bg-emerald-950/40 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>{{ __('بروتوكول السلامة المخبرية ساري 🛡️') }}</span>
                    </div>

                    {{-- Bench Badge --}}
                    <div class="px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-slate-950 font-black text-sm shadow-lg shadow-emerald-500/20">
                        {{ __('طاولة التجارب') }} #{{ $station->station_number }}
                    </div>
                </div>
            </header>

            {{-- Main Content: Bench Identity & Science Protocols --}}
            <main class="my-auto py-10">
                <div class="max-w-4xl mx-auto space-y-8">
                    {{-- Assigned Student / Group Card --}}
                    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                        <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-start">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 text-2xl font-black shadow-xl">
                                {{ $student ? mb_substr($student->first_name, 0, 1) : '🧪' }}
                            </div>

                            <div class="flex-1 space-y-1">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                    {{ __('المحطة المخبرية للعمل التطبيقي والتجريبي') }}
                                </span>
                                <h3 class="text-2xl md:text-3xl font-black text-white">
                                    {{ $student ? $student->full_name : __('طاولة تجارب جاهزة لاستقبال تلاميذ الفوج') }}
                                </h3>
                                <p class="text-sm text-slate-400">
                                    @if ($student)
                                        {{ __('رقم التسجيل:') }} <span class="font-mono text-slate-300">{{ $student->registration_number }}</span>
                                        &bull;
                                        {{ __('الفوج التربوي:') }} <span class="text-slate-300">{{ $student->group?->display_name() }}</span>
                                    @else
                                        {{ __('يمكن للأستاذ توزيع وتثبيت التلاميذ على هذه المحطة من مخطط الجلوس.') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($todayLog)
                        {{-- Active Experiment Protocol Card --}}
                        <div class="p-6 rounded-3xl bg-slate-900/80 border border-emerald-500/30 shadow-xl relative overflow-hidden">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-white/10">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                        {{ __('بطاقة التجربة البيداغوجية للحصة') }}
                                    </span>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    {{ $todayLog->schedule?->subject?->name ?? __('النشاط التجريبي') }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div>
                                    <h4 class="text-xl font-bold text-white">{{ $todayLog->title }}</h4>
                                    @if ($todayLog->actual_content)
                                        <p class="text-sm text-slate-300 mt-1 leading-relaxed">{{ $todayLog->actual_content }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-2 border-t border-white/5">
                                    @if ($todayLog->schedule?->user)
                                        <span>
                                            {{ __('الأستاذ المشرف:') }} <strong class="text-slate-200">{{ $todayLog->schedule->user->name }}</strong>
                                        </span>
                                    @endif
                                    @if ($todayLog->homework)
                                        <span>&bull;</span>
                                        <span>
                                            {{ __('المهمة المطلوبة:') }} <strong class="text-emerald-300">{{ $todayLog->homework }}</strong>
                                        </span>
                                    @endif
                                    @if ($todayLog->observations)
                                        <span>&bull;</span>
                                        <span>
                                            {{ __('توجيهات السلامة:') }} <strong class="text-amber-300">{{ $todayLog->observations }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Discipline-Specific Safety & Protocol Rules --}}
                    @if ($isPhysics)
                        {{-- Physics & Chemistry Protocol Cards --}}
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-amber-500/20 hover:border-amber-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('الأمن الكهربائي والدارات') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('التحقق من قاطع التيار قبل التوصيل، وعدم تشغيل المولد إلا بعد إذن الأستاذ ومراقبته المباشرة.') }}
                                </p>
                            </div>

                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-blue-500/20 hover:border-blue-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('الكواشف والمحاليل') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('ارتداء المئزر الواقي، وتفادي استنشاق الغازات أو لمس المواد الكيميائية باليد المجردة.') }}
                                </p>
                            </div>

                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-emerald-500/20 hover:border-emerald-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('الزجاجيات ونظافة المحطة') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('فحص الأنابيب والحوجلات، ووضعها في الحوامل المخصصة وغسل اليدين فور انتهاء التجربة.') }}
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- Biology & Natural Sciences (SVT) Protocol Cards --}}
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-teal-500/20 hover:border-teal-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('العناية بالمجهر الضوئي') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('حمل المجهر بكلتا اليدين، والبدء بالتكبير الأصغر، وتنظيف العدسات بالورق المخصص دون فرك.') }}
                                </p>
                            </div>

                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-emerald-500/20 hover:border-emerald-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('إعداد الشرائح والعينات') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('وضع الساترة بزاوية 45 درجة لتفادي فقاعات الهواء، واستخدام الملاقط والمشارط بحذر.') }}
                                </p>
                            </div>

                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-purple-500/20 hover:border-purple-500/40 transition">
                                <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-white">{{ __('حفظ المجسمات والنظافة') }}</h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    {{ __('ترتيب المجسمات التشريحية بعد الفحص، ومسح سطح طاولة العمل وغسل اليدين جيداً.') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Session Practical Checklist --}}
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-300">
                        <span class="font-bold text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            {{ __('قائمة التحقق للتلميذ قبل مغادرة المخبر:') }}
                        </span>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700">
                                ✓ {{ __('ارتداء المئزر القطني') }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700">
                                ✓ {{ __('تدوين النتائج في الكراس') }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700">
                                ✓ {{ __('تسليم المحطة نظيفة ومرتبة') }}
                            </span>
                        </div>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <div>
                    {{ __('نظام سجِّل — بطاقة المحطة المخبرية وضوابط السلامة البيداغوجية') }}
                </div>
                <div>
                    {{ __('المخبر العلمي: نشاط تجريبي معتمد') }} &bull; {{ now()->format('Y/m/d') }}
                </div>
            </footer>
        </div>
    @endif
</body>
</html>
