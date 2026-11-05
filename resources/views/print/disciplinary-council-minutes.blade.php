@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $record->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $record->student;
    $classroom = $student?->group?->classroom;
    $councilAttendees = $attendees ?? ($record->attendees ?? [
        'مدير المؤسسة — رئيساً',
        'المستشار الرئيسي للتربية',
        'ناظر الدروس',
        'أستاذ(ة) ممثل عن مجلس القسم',
        'مندوب جمعية أولياء التلاميذ',
    ]);
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('محضر اجتماع مجلس التأديب') }} — {{ $student?->full_name }} — {{ $year }}</title>

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
            margin: 10mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 15px;
        }

        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12mm 15mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        .double-border {
            border: 3px double #0f172a;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .sheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen Actions Bar --}}
    <div class="no-print max-w-[210mm] mx-auto mb-4 flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-800">📜 {{ __('محضر جلسة مجلس التأديب الرسمي') }}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $record->session_number ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow transition flex items-center gap-1.5 cursor-pointer">
                🖨️ {{ __('طباعة المحضر (A4)') }}
            </button>
            <a href="{{ route('print.disciplinary.minutes.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📥 {{ __('تحميل كملف PDF') }}
            </a>
            <a href="{{ route('print.disciplinary.notification', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-rose-900 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📢 {{ __('إشعار الولي بالقرار') }}
            </a>
        </div>
    </div>

    {{-- Printable Document --}}
    <div class="sheet-container">

        {{-- Official Header --}}
        <div class="text-center pb-3 border-b border-slate-200">
            <h1 class="text-base font-extrabold text-slate-900 tracking-wide font-traditional">
                {{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}
            </h1>
            <h2 class="text-sm font-bold text-slate-800 mt-0.5">
                {{ __('وزارة التربية الوطنية') }}
            </h2>
            <div class="flex items-center justify-between text-xs text-slate-600 mt-2 px-2">
                <div class="text-start">
                    <p>{{ $schoolSetting?->directorate_name ?? __('مديرية التربية لولاية الجزائر وسط') }}</p>
                    <p class="font-extrabold text-slate-900">{{ $schoolSetting?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                </div>
                <div class="text-center font-mono">
                    <p>{{ __('السنة الدراسية') }}: <strong>{{ $year }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('جلسة رقم') }}: <strong>{{ $record->session_number ?? '—' }}</strong></p>
                </div>
                <div class="text-end font-mono">
                    <p>{{ __('تاريخ الانعقاد') }}: <strong>{{ $formatDate($record->session_date) }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('تاريخ الوقائع') }}: {{ $formatDate($record->incident_date) }}</p>
                </div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="my-5 text-center">
            <div class="inline-block px-8 py-2 rounded-xl double-border bg-slate-50">
                <h3 class="text-lg font-black text-slate-900 tracking-wider">
                    {{ __('مَحْضَرُ اجْتِمَاعِ مَجْلِسِ التَّأْدِيبِ') }}
                </h3>
                <p class="text-[10px] font-bold text-slate-600 tracking-widest uppercase mt-0.5">
                    Procès-Verbal de Réunion du Conseil de Discipline
                </p>
            </div>
        </div>

        {{-- Opening Intro & Council Members --}}
        <div class="text-xs text-slate-800 leading-relaxed font-traditional mb-4">
            <p>
                {{ __('في يوم :date، وعلى الساعة :time، اجتمع مجلس التأديب لمؤسسة :school برئاسة السيد(ة): :pres، وبحضور السادة الأعضاء القانونيين:', [
                    'date' => $record->session_date?->translatedFormat('l d F Y') ?? $formatDate($record->session_date),
                    'time' => $record->session_date?->format('H:i') ?? '10:00',
                    'school' => $schoolSetting?->school_name ?? __('المؤسسة'),
                    'pres' => $record->president?->name ?? ($schoolSetting?->director_name ?? __('مدير المؤسسة')),
                ]) }}
            </p>
            <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs font-sans text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                @foreach ($councilAttendees as $att)
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-600 font-bold">✓</span>
                        <span>{{ $att }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Student Identification Box --}}
        <div class="border border-slate-200 rounded-xl p-3.5 mb-4 bg-slate-50/60 text-xs">
            <h4 class="font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <span>👤</span> {{ __('هوية التلميذ(ة) المحال على المجلس:') }}
            </h4>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <span class="text-slate-500">{{ __('الاسم واللقب') }}:</span>
                    <strong class="text-sm font-extrabold text-slate-950 font-traditional">{{ $student?->full_name }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('القسم والفوج') }}:</span>
                    <strong class="text-amber-950 font-bold">{{ $classroom?->name ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('تاريخ الازدياد') }}:</span>
                    <strong class="font-mono">{{ $formatDate($student?->date_of_birth) }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('رقم القيد والتسجيل') }}:</span>
                    <strong class="font-mono">{{ $student?->matricule_number ?? $student?->registration_number ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('حضور الولي') }}:</span>
                    <strong class="{{ $record->guardian_attended ? 'text-emerald-700 font-bold' : 'text-rose-700 font-bold' }}">
                        {{ $record->guardian_attended ? __('نعم (حضر مع التلميذ)') : __('غائب (رغم التبليغ القانوني)') }}
                    </strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('التقرير المحال من طرف') }}:</span>
                    <strong>{{ $record->reportedBy?->name ?? __('المستشار الرئيسي للتربية') }}</strong>
                </div>
            </div>
        </div>

        {{-- Facts & Student Statement --}}
        <div class="space-y-3 mb-4 text-xs">
            <div class="border border-slate-200 rounded-xl p-3">
                <h4 class="font-bold text-slate-900 mb-1 flex items-center gap-1.5">
                    <span>⚠️</span> {{ __('1. عرض الوقائع والمخالفات المنسوبة:') }}
                </h4>
                <p class="font-traditional text-slate-800 leading-relaxed bg-slate-50 p-2.5 rounded border border-slate-100">
                    {{ $record->incident_description }}
                </p>
            </div>

            <div class="border border-slate-200 rounded-xl p-3">
                <h4 class="font-bold text-slate-900 mb-1 flex items-center gap-1.5">
                    <span>🗣️</span> {{ __('2. أقوال التلميذ(ة) ودفوعات ولي الأمر:') }}
                </h4>
                <p class="font-traditional text-slate-800 leading-relaxed bg-slate-50 p-2.5 rounded border border-slate-100 italic">
                    {{ $record->student_statement ?: __('استمع المجلس لتوضيحات التلميذ الذي أقرّ بالوقائع وقدم اعتذاره لمجلس التأديب.') }}
                </p>
            </div>
        </div>

        {{-- Secret Deliberation & Sanction Decision --}}
        <div class="border-2 border-slate-900 rounded-xl p-4 mb-5 bg-amber-50/20">
            <h4 class="font-extrabold text-sm text-slate-950 mb-2 flex items-center gap-1.5 font-traditional">
                <span>⚖️</span> {{ __('3. المداولة السرية وقرار مجلس التأديب:') }}
            </h4>
            <p class="text-xs text-slate-800 font-traditional leading-relaxed mb-3">
                {{ __('بعد مغادرة التلميذ وولي أمره لقاعة الاجتماع، تداول أعضاء المجلس سرياً وفق أحكام القرار الوزاري المنظم للمجالس التأديبية والنظام الداخلي، وصوّت الأعضاء بالأغلبية على اتخاذ القرار التالي:') }}
            </p>

            <div class="bg-white p-3 rounded-lg border border-slate-300 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 font-bold block">{{ __('العقوبة التأديبية المتخذة:') }}</span>
                    <strong class="text-base font-black text-rose-950 font-traditional">
                        {{ $record->decision?->label() ?? __('قيد المداولة') }}
                    </strong>
                </div>

                @if ($record->exclusion_days)
                    <div class="text-end text-xs font-mono">
                        <span class="bg-rose-100 text-rose-900 px-2.5 py-1 rounded-lg font-bold border border-rose-300">
                            {{ $record->exclusion_days }} {{ __('أيام إقصاء مؤقت') }}
                        </span>
                        <p class="text-[10px] text-slate-500 mt-1">
                            {{ __('من') }} {{ $formatDate($record->exclusion_start_date) }} {{ __('إلى') }} {{ $formatDate($record->exclusion_end_date) }}
                        </p>
                    </div>
                @endif

                @if ($record->compensation_amount)
                    <div class="text-end text-xs font-mono">
                        <span class="bg-purple-100 text-purple-900 px-2.5 py-1 rounded-lg font-bold border border-purple-300">
                            {{ number_format((float) $record->compensation_amount, 2) }} {{ __('دج تعويض مالي') }}
                        </span>
                    </div>
                @endif
            </div>

            @if ($record->notes)
                <p class="text-[11px] text-slate-600 mt-2 font-traditional">
                    <strong>{{ __('توجيهات إضافية') }}:</strong> {{ $record->notes }}
                </p>
            @endif
        </div>

        {{-- Signatures Block (Council Members Table) --}}
        <div class="border border-slate-300 rounded-xl overflow-hidden mb-4 text-xs">
            <table class="w-full text-center">
                <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300 text-[11px]">
                    <tr>
                        <th class="p-2 border-e border-slate-300 w-1/4">{{ __('رئيس المجلس (المدير)') }}</th>
                        <th class="p-2 border-e border-slate-300 w-1/4">{{ __('ناظر الدروس') }}</th>
                        <th class="p-2 border-e border-slate-300 w-1/4">{{ __('مستشار التربية') }}</th>
                        <th class="p-2 w-1/4">{{ __('ممثل الأساتذة والأولياء') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="h-16">
                        <td class="p-2 border-e border-slate-300 align-bottom font-bold text-[10px]">
                            {{ $schoolSetting?->director_name ?? __('المدير والختم') }}
                        </td>
                        <td class="p-2 border-e border-slate-300 align-bottom text-[10px] text-slate-400">
                            {{ __('التوقيع') }}
                        </td>
                        <td class="p-2 border-e border-slate-300 align-bottom text-[10px] text-slate-400">
                            {{ __('التوقيع') }}
                        </td>
                        <td class="p-2 align-bottom text-[10px] text-slate-400">
                            {{ __('التوقيع') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer Notice --}}
        <div class="flex items-center justify-between text-[9px] text-slate-400 font-mono pt-2 border-t border-slate-100">
            <span>{{ __('منظومة سجل الرقمية لإدارة المتوسطة — محضر جلسة مجلس التأديب') }}</span>
            <span>Ref: PV-CD-{{ $record->id }}</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>
