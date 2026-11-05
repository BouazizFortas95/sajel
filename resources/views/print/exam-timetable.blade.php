@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('رزنامة الفروض المحروسة والامتحانات') }} — {{ $classroom->name }} — {{ $period['name'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .print-sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
        }

        .exam-table {
            border-collapse: collapse;
            width: 100%;
        }

        .exam-table th,
        .exam-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: center;
        }

        .exam-table th {
            background-color: #f1f5f9;
            font-weight: 800;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .print-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8 antialiased">
    {{-- On-screen Print Controls Bar --}}
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
            <span class="text-sm font-bold">{{ __('معاينة طباعة الرزنامة الرسمية للفروض والامتحانات') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                onclick="window.print()"
                class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-sm transition"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('طباعة الآن (A4)') }}</span>
            </button>
            <a
                href="{{ route('print.exam-timetable.pdf', ['locale' => app()->getLocale(), 'classroom' => $classroom, 'trimester' => $trimester]) }}"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('تحميل ملف PDF') }}</span>
            </a>
            <button
                type="button"
                onclick="window.close()"
                class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
                {{ __('إغلاق') }}
            </button>
        </div>
    </div>

    {{-- Official A4 Printable Sheet --}}
    <div class="print-sheet p-6 sm:p-10 rounded-2xl border border-slate-200 shadow-lg text-slate-900">
        {{-- Algerian Republic Header --}}
        <header class="text-center pb-4 border-b-2 border-slate-900 space-y-1">
            <h1 class="text-base font-black tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</h1>
            <h2 class="text-sm font-bold text-slate-700">{{ __('وزارة التربية الوطنية') }}</h2>
            <div class="flex items-center justify-between text-xs pt-2 font-semibold text-slate-600">
                <span>{{ __('مديرية التربية لولاية:') }} <strong>{{ $school?->state_name ?? '........................' }}</strong></span>
                <span>{{ __('المؤسسة:') }} <strong>{{ $school?->school_name ?? __('متوسطة سجِّل النموذجية') }}</strong></span>
                <span>{{ __('السنة الدراسية:') }} <strong>{{ $year }}</strong></span>
            </div>
        </header>

        {{-- Document Title --}}
        <div class="my-6 text-center space-y-1">
            <div class="inline-block px-4 py-1.5 bg-slate-900 text-white rounded-xl text-lg font-black tracking-wide shadow-sm">
                {{ __('رزنامة الفروض المحروسة والامتحانات الرسمية') }}
            </div>
            <div class="flex items-center justify-center gap-4 text-xs font-bold text-slate-700 pt-2">
                <span>{{ __('القسم:') }} <strong class="text-slate-950 font-black text-sm">{{ $classroom->name }} ({{ $classroom->level?->name }})</strong></span>
                <span>&bull;</span>
                <span>{{ __('الفترة:') }} <strong class="text-amber-800 font-black">{{ $period['name'] }}</strong></span>
                <span>&bull;</span>
                <span>{{ __('تاريخ الاستخراج:') }} {{ now()->format('Y/m/d') }}</span>
            </div>
        </div>

        {{-- Schedule Table --}}
        <div class="overflow-x-auto my-6">
            <table class="exam-table text-xs">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th class="w-36">{{ __('اليوم والتاريخ') }}</th>
                        <th class="w-28">{{ __('التوقيت') }}</th>
                        <th>{{ __('المادة التعليمية') }}</th>
                        <th class="w-28">{{ __('نوع التقييم') }}</th>
                        <th class="w-36">{{ __('الأستاذ المشرف') }}</th>
                        <th class="w-16">{{ __('العلامة') }}</th>
                        <th class="w-24">{{ __('ملاحظات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $index => $assessment)
                        @php
                            $dateCarbon = $assessment->date ? \Carbon\Carbon::parse($assessment->date) : null;
                            $dayName = $dateCarbon ? $dateCarbon->locale('ar')->translatedFormat('l') : '—';
                            $dateFormatted = $dateCarbon ? $dateCarbon->format('d/m/Y') : '—';
                            $startTime = $assessment->schedule?->start_time?->format('H:i') ?? '—';
                            $endTime = $assessment->schedule?->end_time?->format('H:i') ?? '—';
                        @endphp
                        <tr>
                            <td class="font-bold font-mono">{{ $index + 1 }}</td>
                            <td class="font-bold text-start">
                                <div>{{ $dayName }}</div>
                                <div class="font-mono text-slate-500 font-normal text-[11px]">{{ $dateFormatted }}</div>
                            </td>
                            <td class="font-mono font-bold">{{ $startTime }} - {{ $endTime }}</td>
                            <td class="font-black text-slate-900 text-start">
                                {{ $assessment->schedule?->subject?->name ?? '—' }}
                                @if ($assessment->schedule?->group)
                                    <span class="text-[10px] text-slate-500 block font-normal">({{ $assessment->schedule->group->name }})</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-semibold">{{ $assessment->type->label() }}</span>
                            </td>
                            <td class="text-start">
                                {{ $assessment->schedule?->user?->name ?? '—' }}
                            </td>
                            <td class="font-mono font-bold">/{{ (float) $assessment->max_score }}</td>
                            <td class="text-[10px] text-slate-500">
                                {{ $assessment->title !== $assessment->type->label() ? $assessment->title : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 font-semibold">
                                {{ __('لا توجد فروض أو اختبارات مبرمجة لهذا القسم في :period.', ['period' => $period['name']]) }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pedagogical & Ministerial Guidelines Notice --}}
        <div class="my-6 p-3.5 rounded-xl border border-amber-300 bg-amber-50/70 text-[11px] text-amber-950 space-y-1">
            <p class="font-bold flex items-center gap-1.5 text-amber-900">
                <span>🛡️</span>
                <span>{{ __('تنبيه وتوجيهات تنظيمية وزارية هامة:') }}</span>
            </p>
            <p class="leading-relaxed">
                {{ __('تطبيقاً للمناشير الوزارية المنظمة للتقويم البيداغوجي في مرحلة التعليم المتوسط، لا يجوز برمجة أكثر من فرضين محروسين في اليوم الواحد للقسم الواحد. يلتزم التلاميذ بالحضور الإلزامي في القاعة المحددة وإحضار الأدوات المدرسية والآلات الحاسبة المسموح بها.') }}
            </p>
        </div>

        {{-- Signatures & Stamp Block --}}
        <footer class="mt-8 pt-4 border-t border-slate-300">
            <div class="grid grid-cols-3 gap-4 text-center text-xs">
                <div>
                    <p class="font-bold text-slate-700">{{ __('الأستاذ(ة) المنسق(ة)') }}</p>
                    <div class="h-16 flex items-end justify-center text-[10px] text-slate-400">
                        {{ __('التوقيع') }}
                    </div>
                </div>
                <div>
                    <p class="font-bold text-slate-700">{{ __('مستشار(ة) التربية') }}</p>
                    <div class="h-16 flex items-end justify-center text-[10px] text-slate-400">
                        {{ __('التوقيع والتأشيرة') }}
                    </div>
                </div>
                <div>
                    <p class="font-bold text-slate-700">{{ __('مدير(ة) المتوسطة') }}</p>
                    <div class="h-16 flex items-end justify-center text-[10px] text-slate-400">
                        {{ __('الختم والتأشيرة الرسمية') }}
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>

