@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $mandateCode = 'MANDAT-'.$log->id.'-'.($log->date?->format('Ymd') ?? now()->format('Ymd'));
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استدعاء وتكليف بمهمة استخلاف بيداغوجي') }} — {{ $substituteTeacher->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        .print-sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            border: 2px solid #e2e8f0;
            position: relative;
        }

        .outer-border {
            border: 2px solid #0f172a;
            padding: 20px;
            border-radius: 6px;
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
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print max-w-3xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">📋</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('تكليف رسمي بمهمة استخلاف بيداغوجي') }}</h1>
                <p class="text-xs text-slate-400">{{ $substituteTeacher->name }} — {{ $classroom?->name }} ({{ $mandateCode }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة التكليف') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="print-sheet">
        <div class="outer-border">

            {{-- 1. Official Republic Header --}}
            <div class="text-center pb-3 border-b-2 border-slate-900 mb-6">
                <p class="text-sm font-bold tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ __('وزارة التربية الوطنية') }}</p>
                <div class="flex justify-between items-center text-xs font-semibold text-slate-700 mt-2 px-2">
                    <div>
                        <p>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</p>
                        <p class="font-bold text-slate-900">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                    </div>
                    <div class="text-center">
                        <span class="inline-block px-3 py-1 bg-slate-100 rounded border border-slate-300 font-mono text-[11px] font-bold">
                            {{ $mandateCode }}
                        </span>
                    </div>
                    <div class="text-left">
                        <p>{{ __('السنة الدراسية') }}: <span class="font-bold font-mono">{{ $year }}</span></p>
                        <p>{{ __('حرر بتاريخ') }}: <span class="font-mono">{{ now()->format('d/m/Y') }}</span></p>
                    </div>
                </div>
            </div>

            {{-- 2. Document Title --}}
            <div class="text-center my-6">
                <h2 class="text-xl font-black text-slate-950 uppercase tracking-wide inline-block pb-1 border-b-2 border-amber-500">
                    {{ __('استدعاء وتكليف بمهمة استخلاف بيداغوجي') }}
                </h2>
                <p class="text-xs text-slate-600 mt-1">
                    {{ __('ضماناً للاستمرارية البيداغوجية وحفاظاً على زمن التمدرس لتلاميذ المؤسسة') }}
                </p>
            </div>

            {{-- 3. Mandate Details Grid --}}
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-5">
                <h3 class="text-xs font-extrabold text-slate-900 mb-3 flex items-center gap-1.5 border-b border-slate-200 pb-2">
                    <span>📌</span> {{ __('بيانات الأستاذ المستخلف والمهمة المسندة') }}
                </h3>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="text-slate-500 mb-1">{{ __('الأستاذ المكلف بالاستخلاف') }}:</p>
                        <p class="text-sm font-black text-slate-900 bg-white p-2 rounded border border-slate-200">
                            {{ $substituteTeacher->name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">{{ __('تخصص الأستاذ المستخلف') }}:</p>
                        <p class="text-sm font-bold text-slate-800 bg-white p-2 rounded border border-slate-200">
                            {{ $substituteTeacher->primarySubject?->name ?? __('أستاذ التعليم المتوسط') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- 4. Absence Context Card --}}
            <div class="bg-amber-50/50 border border-amber-200 rounded-lg p-4 mb-5">
                <h3 class="text-xs font-extrabold text-amber-900 mb-3 flex items-center gap-1.5 border-b border-amber-200 pb-2">
                    <span>⏱️</span> {{ __('بيانات الحصة والشغور البيداغوجي') }}
                </h3>

                <div class="grid grid-cols-2 gap-y-3 gap-x-4 text-xs">
                    <div>
                        <span class="text-slate-600">{{ __('الأستاذ الأصلي المتغيب') }}:</span>
                        <strong class="text-slate-900">{{ $absentTeacher?->name }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-600">{{ __('سبب الغياب المعتمد') }}:</span>
                        <span class="font-bold text-amber-900">{{ $absence->reason->label() }}</span>
                        @if ($absence->reference_number)
                            <span class="font-mono text-[10px] text-slate-500">({{ $absence->reference_number }})</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-600">{{ __('تاريخ الحصة وتوقيتها') }}:</span>
                        <strong class="text-slate-900 font-mono">{{ $log->date?->format('d/m/Y') }}</strong>
                        <span class="text-slate-500 font-mono">({{ \Carbon\Carbon::parse($schedule?->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule?->end_time)->format('H:i') }})</span>
                    </div>
                    <div>
                        <span class="text-slate-600">{{ __('القسم المستهدف') }}:</span>
                        <strong class="text-slate-900 bg-amber-100 px-2 py-0.5 rounded border border-amber-300">
                            {{ $classroom?->name }} @if ($schedule?->group) ({{ $schedule->group->name }}) @endif
                        </strong>
                    </div>
                    <div class="col-span-2">
                        <span class="text-slate-600">{{ __('المادة المقررة') }}:</span>
                        <strong class="text-slate-900">{{ $subject?->name }}</strong>
                    </div>
                </div>
            </div>

            {{-- 5. Pedagogical Directives & Observations --}}
            @if ($log->observations)
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-5">
                    <h3 class="text-xs font-extrabold text-slate-800 mb-2 flex items-center gap-1.5">
                        <span>📝</span> {{ __('التعليمات والتوجيهات البيداغوجية للحصة') }}
                    </h3>
                    <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed bg-white p-2.5 rounded border border-slate-200 font-medium">
                        {{ $log->observations }}
                    </p>
                </div>
            @endif

            {{-- 6. Legal & Administrative Note --}}
            <div class="text-[11px] text-slate-600 leading-relaxed bg-slate-100 p-3 rounded mb-8 border border-slate-200">
                <p class="font-bold text-slate-800 mb-1">⚖️ {{ __('تذكير بالواجبات البيداغوجية والتنظيمية:') }}</p>
                <p>{{ __('بناءً على هذا التكليف، يُرجى من السيد(ة) الأستاذ(ة) المستخلف(ة) الالتحاق بالقسم في الموعد المحدد لتسيير الحصة، مراقبة انضباط وحضور التلاميذ، وتدوين موضوع الحصة والأنشطة المنجزة في دفتر النصوص الرسمي للقسم.') }}</p>
            </div>

            {{-- 7. Official Tripartite Signatures & Stamps --}}
            <div class="grid grid-cols-3 gap-4 text-center text-xs pt-4 border-t-2 border-slate-800">
                <div>
                    <p class="font-bold text-slate-800 mb-1">{{ __('الأستاذ المكلف بالاستخلاف') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('تأشيرة الاستلام والموافقة') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2"></div>
                </div>

                <div>
                    <p class="font-bold text-slate-800 mb-1">{{ __('مستشار التربية / ناظر الدروس') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('المعاينة والتسجيل بسجل المداومة') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2"></div>
                </div>

                <div>
                    <p class="font-bold text-slate-900 mb-1">{{ __('مدير المؤسسة') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('المصادقة والختم الرسمي') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2">
                        <span class="inline-block mt-4 text-[10px] text-slate-400 font-semibold border border-slate-300 rounded-full px-3 py-1">
                            {{ __('الختم الرسمي للمؤسسة') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center text-[10px] text-slate-400 font-mono">
                {{ __('منظومة سجل الرقمية لإدارة المتوسطة') }} · {{ $mandateCode }}
            </div>

        </div>
    </div>

</body>
</html>

