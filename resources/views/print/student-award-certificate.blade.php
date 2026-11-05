@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $certs = $certificates ?? [];
    if (empty($certs) && isset($student)) {
        $certs = [[
            'student' => $student,
            'classroom' => $classroom ?? $student->group?->classroom,
            'awardType' => $awardType,
            'trimester' => $trimester ?? 1,
            'period' => $period ?? ['name' => __('الفصل الأول')],
            'rank' => $rank ?? null,
            'rankLabel' => $rankLabel ?? null,
            'generalAverage' => $generalAverage ?? null,
            'citation' => $citation ?? '',
            'schoolSetting' => $school,
            'academicYear' => $year,
            'issueDate' => $issueDate ?? now(),
        ]];
    }
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادات التكريم والامتياز') }} — {{ $school?->school_name ?? config('app.name') }}</title>

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
            size: A4 landscape;
            margin: 6mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 10px;
        }

        .certificate-wrapper {
            max-width: 285mm;
            height: 195mm;
            margin: 0 auto 15px auto;
            background: #ffffff;
            position: relative;
            padding: 12mm 15mm;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .certificate-wrapper:last-child {
            page-break-after: auto;
            margin-bottom: 0;
        }

        /* Ornate Decorative Frames */
        .frame-outer {
            position: absolute;
            top: 5mm;
            bottom: 5mm;
            left: 5mm;
            right: 5mm;
            border: 3px double #d97706;
            pointer-events: none;
        }

        .frame-inner {
            position: absolute;
            top: 7mm;
            bottom: 7mm;
            left: 7mm;
            right: 7mm;
            border: 1px solid #f59e0b;
            pointer-events: none;
        }

        .corner-ornament {
            position: absolute;
            width: 28px;
            height: 28px;
            border: 2px solid #b45309;
        }
        .corner-tl { top: 6mm; left: 6mm; border-right: none; border-bottom: none; }
        .corner-tr { top: 6mm; right: 6mm; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 6mm; left: 6mm; border-right: none; border-top: none; }
        .corner-br { bottom: 6mm; right: 6mm; border-left: none; border-top: none; }

        /* Typography & Layout */
        .font-traditional {
            font-family: 'Amiri', 'Cairo', serif;
        }

        .award-banner-excellence {
            background: linear-gradient(135deg, #b45309 0%, #f59e0b 50%, #b45309 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }

        .award-banner-encouragement {
            background: linear-gradient(135deg, #0369a1 0%, #38bdf8 50%, #0369a1 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25);
        }

        .award-banner-honor_roll {
            background: linear-gradient(135deg, #4338ca 0%, #818cf8 50%, #4338ca 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .award-banner-good_conduct {
            background: linear-gradient(135deg, #047857 0%, #34d399 50%, #047857 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .badge-stat {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .certificate-wrapper {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 100vh !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print max-w-5xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">🏆</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('معاينة شهادات التكريم الرسمية') }}</h1>
                <p class="text-xs text-slate-400">{{ __('جاهزة للطباعة والتصدير بجودة A4 أفقية') }} ({{ count($certs) }} {{ __('شهادة') }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة فورية') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    {{-- Loop Through Certificates --}}
    @foreach ($certs as $cert)
        @php
            $certAwardType = $cert['awardType'] instanceof \App\Enums\StudentAwardType
                ? $cert['awardType']
                : \App\Enums\StudentAwardType::from((string) $cert['awardType']);
            $bannerClass = match ($certAwardType) {
                \App\Enums\StudentAwardType::Excellence => 'award-banner-excellence',
                \App\Enums\StudentAwardType::Encouragement => 'award-banner-encouragement',
                \App\Enums\StudentAwardType::HonorRoll => 'award-banner-honor_roll',
                \App\Enums\StudentAwardType::GoodConduct => 'award-banner-good_conduct',
            };
        @endphp

        <div class="certificate-wrapper">
            {{-- Decorative Outer & Inner Border --}}
            <div class="frame-outer"></div>
            <div class="frame-inner"></div>
            <div class="corner-ornament corner-tl"></div>
            <div class="corner-ornament corner-tr"></div>
            <div class="corner-ornament corner-bl"></div>
            <div class="corner-ornament corner-br"></div>

            {{-- 1. Official Republic Header --}}
            <div class="relative z-10 flex items-start justify-between border-b border-amber-200 pb-3">
                <div class="text-right">
                    <p class="text-[11px] font-bold text-slate-800 leading-tight">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                    <p class="text-[10px] text-slate-600 leading-tight">{{ __('وزارة التربية الوطنية') }}</p>
                    <p class="text-[10px] text-slate-600 leading-tight">
                        {{ __('مديرية التربية لولاية') }} {{ $cert['schoolSetting']?->wilaya ?? 'الجزائر' }}
                    </p>
                </div>

                <div class="text-center px-4">
                    <p class="text-xs font-extrabold text-amber-800 tracking-wide uppercase">{{ $cert['schoolSetting']?->school_name ?? __('المؤسسة التعليمية') }}</p>
                    <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                        {{ __('الموسم الدراسي') }}: <span class="text-slate-800 font-bold">{{ $cert['academicYear'] }}</span>
                    </p>
                </div>

                <div class="text-left">
                    <p class="text-[11px] font-bold text-slate-800">{{ $cert['classroom']?->name ?? '—' }}</p>
                    <p class="text-[10px] text-slate-600">{{ $cert['period']['name'] ?? __('الفصل الدراسي') }}</p>
                    <p class="text-[10px] text-slate-500">{{ $formatDate($cert['issueDate']) }}</p>
                </div>
            </div>

            {{-- 2. Award Title Banner --}}
            <div class="relative z-10 text-center my-3">
                <div class="inline-block px-10 py-2.5 rounded-full {{ $bannerClass }} border-2 border-white">
                    <h2 class="text-2xl font-black tracking-wide font-traditional">
                        {{ $certAwardType->certificateTitle() }}
                    </h2>
                </div>
            </div>

            {{-- 3. Student Dedication Body --}}
            <div class="relative z-10 text-center flex-1 flex flex-col justify-center px-6">
                <p class="text-sm text-slate-700 font-medium mb-2">
                    {{ __('يَسُرُّ إدارة المؤسسة والمجلس البيداغوجي لأساتذة القسم أن يمنحوا هذه الشهادة بكل فخر واعتزاز للتلميذ(ة):') }}
                </p>

                {{-- Honored Student Name --}}
                <div class="my-2">
                    <h1 class="text-3xl font-black text-slate-900 font-traditional tracking-normal border-b-2 border-amber-300 pb-2 inline-block px-8">
                        {{ $cert['student']->full_name }}
                    </h1>
                </div>

                {{-- Demographic Information --}}
                <p class="text-xs text-slate-600 font-semibold mb-3">
                    {{ __('المولود(ة) بتاريخ') }}: <span class="font-bold text-slate-800">{{ $formatDate($cert['student']->date_of_birth) }}</span>
                    @if ($cert['student']->registration_number)
                        &nbsp;•&nbsp; {{ __('رقم التسجيل') }}: <span class="font-bold text-slate-800 font-mono">{{ $cert['student']->registration_number }}</span>
                    @endif
                    &nbsp;•&nbsp; {{ __('القسم') }}: <span class="font-bold text-slate-800">{{ $cert['classroom']?->name }}</span>
                    @if ($cert['student']->group)
                        ({{ $cert['student']->group->name }})
                    @endif
                </p>

                {{-- Official Commendation Citation --}}
                <div class="max-w-3xl mx-auto bg-amber-50/60 border-r-4 border-l-4 border-amber-400 p-3 rounded-lg my-1">
                    <p class="text-sm font-semibold text-slate-800 leading-relaxed font-traditional">
                        {{ $cert['citation'] }}
                    </p>
                </div>

                {{-- Academic Merit Badges --}}
                @if ($cert['generalAverage'] !== null || $cert['rankLabel'])
                    <div class="flex items-center justify-center gap-3 mt-3">
                        @if ($cert['generalAverage'] !== null)
                            <span class="badge-stat">
                                <span>📊</span>
                                <span>{{ __('المعدل العام') }}:</span>
                                <strong class="text-base text-amber-950">{{ number_format($cert['generalAverage'], 2) }} / 20</strong>
                            </span>
                        @endif

                        @if ($cert['rankLabel'])
                            <span class="badge-stat">
                                <span>🏅</span>
                                <strong class="text-amber-950">{{ $cert['rankLabel'] }}</strong>
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- 4. Official Tripartite Approval Block --}}
            <div class="relative z-10 pt-3 border-t border-amber-200 grid grid-cols-3 gap-6 text-center">
                <div>
                    <p class="text-xs font-bold text-slate-700">{{ __('الأستاذ(ة) الرئيسي(ة)') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ __('المجلس البيداغوجي') }}</p>
                    <div class="h-8 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-700">{{ __('مستشار التربية / ناظر الدروس') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ __('مصلحة التمدرس') }}</p>
                    <div class="h-8 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-700">{{ __('مدير(ة) المؤسسة والختم الرسمي') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ $cert['schoolSetting']?->director_name ?? __('الإدارة التربوية') }}</p>
                    <div class="h-8 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
                </div>
            </div>
        </div>
    @endforeach

</body>
</html>

