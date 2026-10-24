<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#f59e0b">
    <link rel="icon" href="/pwa-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/pwa-icon-192.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('سجِّل — دفتر تحضيرك وسجل قسمك في مكان واحد') }}</title>
    @vite('resources/js/app.js')
    @include('partials.pwa-registration')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        sajel: {
                            50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
                            400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309',
                            800: '#92400e', 900: '#78350f',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; }
        .hero-glow {
            background:
                radial-gradient(60rem 60rem at 85% -10%, rgba(245, 158, 11, .18), transparent 60%),
                radial-gradient(50rem 50rem at 10% 110%, rgba(245, 158, 11, .12), transparent 60%);
        }
        .hero-grid {
            background-image: linear-gradient(to left, rgba(120, 53, 15, .06) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(120, 53, 15, .06) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(70% 60% at 50% 30%, #000 30%, transparent 100%);
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">

    @php
        $panel = \Filament\Facades\Filament::getDefaultPanel();
        $panelPath = trim($panel->getPath(), '/');
        $panelPath = preg_replace('#^\{locale\}/#', '', $panelPath);
        $baseUrl = '/' . app()->getLocale() . '/' . $panelPath;
        $loginUrl = $baseUrl . '/login';
        $registerUrl = $baseUrl . '/register';
        $dashboardUrl = $baseUrl !== '' ? $baseUrl : '/';
        $guard = $panel->getAuthGuard();
        $isAuthed = auth($guard)->check();
    @endphp

    {{-- ======================= Header ======================= --}}
    <header class="sticky top-0 z-40 border-b border-sajel-100 bg-white/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ $dashboardUrl }}" class="flex items-center gap-2.5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-sajel-500 text-lg font-black text-white shadow-sm shadow-sajel-500/40">س</span>
                <span class="text-2xl font-black tracking-tight">سجِّل</span>
            </a>

            <nav class="flex items-center gap-3">
                <label class="sr-only" for="welcome-language-switcher">{{ __('Language') }}</label>
                <select
                    id="welcome-language-switcher"
                    aria-label="{{ __('Language') }}"
                    class="rounded-xl border border-sajel-100 bg-white px-3 py-2 text-sm font-semibold text-gray-700 focus:border-sajel-500 focus:outline-none focus:ring-2 focus:ring-sajel-200"
                    onchange="if (this.value) window.location.assign(this.value)"
                >
                    @foreach (['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français'] as $locale => $language)
                        <option value="{{ url('/' . $locale) }}" @selected(app()->getLocale() === $locale)>{{ $language }}</option>
                    @endforeach
                </select>
                @if ($isAuthed)
                    <a href="{{ $dashboardUrl }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-sajel-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-sajel-500/40 transition hover:bg-sajel-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                        {{ __('لوحة التحكم') }}
                    </a>
                @else
                    <a href="{{ $loginUrl }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-sajel-50 hover:text-sajel-700">
                        {{ __('تسجيل الدخول') }}
                    </a>
                    <a href="{{ $registerUrl }}"
                       class="inline-flex items-center rounded-xl bg-sajel-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-sajel-500/40 transition hover:bg-sajel-600">
                        {{ __('إنشاء حساب') }}
                    </a>
                @endif
            </nav>
        </div>
    </header>

    {{-- ======================= Hero ======================= --}}
    <section class="hero-glow relative overflow-hidden">
        <div class="hero-grid absolute inset-0"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pb-24 pt-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pt-28">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-sajel-200 bg-sajel-50 px-4 py-1.5 text-sm font-bold text-sajel-700">
                    <span class="h-2 w-2 rounded-full bg-sajel-500"></span>
                    {{ __('منصة SaaS ذكية للمعلمين') }}
                </span>

                <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    {{ __('دفتر التحضير اليومي') }}
                    <span class="text-sajel-600">{{ __('ودفتر النصوص') }}</span>
                    {{ __('في مكان واحد') }}
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-gray-600">
                    {{ __('سجِّل يدمج الكتاب اليومي وسجل القسم في كيان واحد ذكي: حضّر دروسك بكتل ديناميكية، سجّل ما قدمته فعلياً في القسم، ثم اعتمد الإنجاز بنقرة ليصبح سجلك الرسمي تلقائياً.') }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-4">
                    @if ($isAuthed)
                        <a href="{{ $dashboardUrl }}"
                           class="inline-flex items-center gap-2 rounded-2xl bg-sajel-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-sajel-500/30 transition hover:bg-sajel-600">
                            {{ __('ادخل إلى لوحة التحكم') }}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                        </a>
                    @else
                        <a href="{{ $registerUrl }}"
                           class="inline-flex items-center gap-2 rounded-2xl bg-sajel-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-sajel-500/30 transition hover:bg-sajel-600">
                            {{ __('ابدأ مجاناً الآن') }}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                        </a>
                        <a href="{{ $loginUrl }}"
                           class="inline-flex items-center rounded-2xl border-2 border-sajel-200 px-8 py-4 text-base font-bold text-sajel-800 transition hover:border-sajel-400 hover:bg-sajel-50">
                            {{ __('لدي حساب بالفعل') }}
                        </a>
                    @endif
                </div>

                <p class="mt-6 text-sm text-gray-500">
                    {{ __('مدعوم للمرحلة المتوسطة أولاً — وجاهز لتوسيعه لباقي المراحل التعليمية.') }}
                </p>
            </div>

            {{-- Mock preview: the smart daily log --}}
            <div class="relative">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl shadow-sajel-900/10">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-red-400"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                            <span class="h-3 w-3 rounded-full bg-green-400"></span>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ __('السجل اليومي — الأحد') }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-sm font-bold">
                        <span class="rounded-lg bg-sajel-500 px-3 py-2 text-white">{{ __('التحضير المسبق') }}</span>
                        <span class="rounded-lg bg-gray-100 px-3 py-2 text-gray-500">{{ __('مرحلة الإنجاز') }}</span>
                    </div>

                    <div class="mt-5 space-y-3 text-sm text-gray-700">
                        <p class="font-bold">{{ __('درس: جمع وطرح الكسور') }}</p>
                        <p><span class="font-semibold text-sajel-700">{{ __('الكفاءة:') }}</span> {{ __('يحسب التلميذ مجموعات وفروق الكسور') }}</p>
                        <ul class="space-y-1.5 pr-1">
                            <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-sajel-500"></span> {{ __('التمهيد (5 د) — وضعية الانطلاق') }}</li>
                            <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-sajel-500"></span> {{ __('الاكتشاف (20 د) — عمل في أفواج') }}</li>
                            <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-sajel-500"></span> {{ __('الترسيخ (15 د) — قاعدة الجمع') }}</li>
                        </ul>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-bold text-green-700">✓ {{ __('منجز') }}</span>
                        <span class="text-xs text-gray-400">{{ __('حُوِّل تلقائياً إلى سجل رسمي') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================= Features ======================= --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-black tracking-tight sm:text-4xl">{{ __('لماذا سجِّل؟') }}</h2>
            <p class="mt-4 text-lg text-gray-600">{{ __('لأن دفترك وسجلك كتاب واحد — لا داعي للكتابتين.') }}</p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $features = [
                    ['icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                     'title' => __('دفتران في دفتر واحد'),
                     'text' => __('التحضير المسبق والأداء الفعلي في استمارة واحدة متصلة، بلا نسخ ولا تكرار.')],
                    ['icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6A2.25 2.25 0 0 1 16 3.75h2.25A2.25 2.25 0 0 1 20.5 6v2.25A2.25 2.25 0 0 1 18.25 10.5H16a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H16a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H16a2.25 2.25 0 0 1-2.25-2.25v-2.25Z',
                     'title' => __('تحضير ديناميكي بالكتل'),
                     'text' => __('ابنِ دروسك بكتل مرنة: كفاءة مستهدفة، مواقف بيداغوجية، ومراحل الدرس.')],
                    ['icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                     'title' => __('اعتماد الإنجاز بنقرة'),
                     'text' => __('زر "تأكيد الإنجاز" يحوّل التحضير إلى سجل رسمي ملخّص فوراً.')],
                    ['icon' => 'M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
                     'title' => __('مراحل تعليمية متعددة'),
                     'text' => __('يبدأ بالمرحلة المتوسطة، ومصمم ليعمل مع أي أساس تعليمي تضيفه لاحقاً.')],
                ];
            @endphp

            @foreach ($features as $feature)
                <div class="group rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-sajel-200 hover:shadow-lg hover:shadow-sajel-500/10">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-sajel-50 text-sajel-600 transition group-hover:bg-sajel-500 group-hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ======================= How it works ======================= --}}
    <section class="border-y border-sajel-100 bg-sajel-50/60">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-black sm:text-3xl">{{ __('كيف يعمل سجِّل؟') }}</h2>

            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @php
                    $steps = [
                        ['n' => '1', 't' => __('رتّب هيكلك'), 'd' => __('أساس تعليمي ← مستوى ← قسم ← مجموعة، ثم أضف موادك.')],
                        ['n' => '2', 't' => __('أضف حصصك'), 'd' => __('جدول أسبوعي: اليوم، التوقيت، المادة والقسم.')],
                        ['n' => '3', 't' => __('سجّل وأكّد'), 'd' => __('حضّر يومياً بالكتل، سجّل الأداء الفعلي، ثم اعتمد الإنجاز.')],
                    ];
                @endphp

                @foreach ($steps as $step)
                    <div class="flex items-start gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-sajel-500 text-lg font-black text-white">{{ $step['n'] }}</span>
                        <div>
                            <h3 class="font-bold">{{ $step['t'] }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $step['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================= CTA ======================= --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-sajel-500 px-8 py-14 text-center shadow-xl shadow-sajel-500/30 sm:px-14">
            <h2 class="text-3xl font-black text-white sm:text-4xl">{{ __('جاهز لدمج دفترك وسجلك؟') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sajel-50">
                {{ __('أنشئ حسابك وابدأ أول تحضير ذكي اليوم — خطوة واحدة تفصلك عن سجل قسم رسمي تلقائي.') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @if ($isAuthed)
                    <a href="{{ $dashboardUrl }}" class="rounded-2xl bg-white px-8 py-3.5 font-bold text-sajel-700 shadow-md transition hover:bg-sajel-50">
                        {{ __('افتح لوحة التحكم') }}
                    </a>
                @else
                    <a href="{{ $registerUrl }}" class="rounded-2xl bg-white px-8 py-3.5 font-bold text-sajel-700 shadow-md transition hover:bg-sajel-50">
                        {{ __('إنشاء حساب مجاني') }}
                    </a>
                    <a href="{{ $loginUrl }}" class="rounded-2xl border-2 border-white/70 px-8 py-3.5 font-bold text-white transition hover:bg-white/10">
                        {{ __('تسجيل الدخول') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ======================= Footer ======================= --}}
    <footer class="border-t border-gray-100">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8">
            <div class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-sajel-500 text-sm font-black text-white">س</span>
                <span class="font-black">سجِّل</span>
                <span class="text-sm text-gray-400">— {{ __('دفتر اليومي ودفتر النصوص في مكان واحد') }}</span>
            </div>

            <nav class="flex items-center gap-5 text-sm font-semibold text-gray-600">
                <a href="{{ $dashboardUrl }}" class="transition hover:text-sajel-600">{{ __('لوحة التحكم') }}</a>
                <a href="{{ $loginUrl }}" class="transition hover:text-sajel-600">{{ __('تسجيل الدخول') }}</a>
                @unless ($isAuthed)
                    <a href="{{ $registerUrl }}" class="transition hover:text-sajel-600">{{ __('إنشاء حساب') }}</a>
                @endunless
            </nav>

            <p class="text-xs text-gray-400">© {{ now()->year }} سجِّل. {{ __('جميع الحقوق محفوظة.') }}</p>
        </div>
    </footer>
</body>
</html>