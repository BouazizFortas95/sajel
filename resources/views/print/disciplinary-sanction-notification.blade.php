@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $record->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $record->student;
    $classroom = $student?->group?->classroom;
    $guardian = $student?->primaryGuardian ?? $student?->guardians?->first();
    $guardianName = $guardian?->full_name ?? ($student?->guardian_name ?? 'ولي أمر التلميذ(ة)');
    $guardianPhone = $guardian?->phone ?? ($student?->guardian_phone ?? '—');
    $guardianAddress = $guardian?->address ?? ($student?->address ?? 'العنوان المسجل بالملف المدرسي');
    $regNumber = $student?->national_id ?? $student?->registration_number ?? ($student?->id ? sprintf('%04d', $student->id) : '—');
    $degreeName = $record->degree?->getLabel() ?? ($record->infraction_degree?->label() ?? 'مخالفة انضباطية');
    $sanctionName = $record->sanction?->getLabel() ?? 'لم تحدد عقوبة بعد';
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('إشعار رسمي بقرار مجلس التأديب') }} — {{ $student?->full_name }} — {{ $year }}</title>

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
            margin: 4mm 8mm 4mm 8mm !important;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 8px 0;
            line-height: 1.35;
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        .sheet-container {
            width: 100%;
            max-width: 196mm;
            margin: 0 auto;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        @media screen {
            .sheet-container {
                border: 1px solid #cbd5e1;
                padding: 6mm 10mm !important;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            }
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
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .no-print {
                display: none !important;
            }
        }

        /* Header Grid */
        .header-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.4fr 1.1fr;
            border-bottom: 1.5px solid #991b1b;
            padding-bottom: 3px;
            margin-bottom: 4px;
            align-items: center;
        }

        /* Title Badge */
        .title-badge-wrap {
            text-align: center;
            margin: 3px 0 5px 0;
        }

        .title-badge {
            display: inline-block;
            border: 1.5px solid #991b1b;
            background: #fef2f2;
            border-radius: 6px;
            padding: 2px 22px;
            text-align: center;
        }

        .title-main {
            font-size: 11.5pt;
            font-weight: 900;
            color: #991b1b;
            margin: 0;
            line-height: 1.25;
        }

        .title-sub {
            font-size: 6.5pt;
            font-weight: 700;
            color: #7f1d1d;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Identity Tables */
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            border: 1.5px solid #991b1b;
            background: #ffffff;
            margin-bottom: 4px;
        }

        .identity-table th {
            background: #fff5f5;
            color: #991b1b;
            font-weight: 700;
            padding: 2.5px 5px;
            border: 1px solid #fed7d7;
            text-align: right;
            width: 16%;
        }

        .identity-table td {
            padding: 2.5px 5px;
            border: 1px solid #fed7d7;
            color: #0f172a;
            font-weight: 600;
        }

        /* Section Cards */
        .section-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 6px;
            margin-bottom: 4px;
            background: #ffffff;
        }

        .section-title {
            font-size: 7.5pt;
            font-weight: 800;
            color: #991b1b;
            margin-bottom: 1px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .section-content {
            font-size: 7.5pt;
            line-height: 1.35;
            color: #0f172a;
            white-space: pre-line;
            background: #f8fafc;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #f1f5f9;
        }

        /* Sanction Box */
        .sanction-box {
            border: 1.5px solid #991b1b;
            background: #fff5f5;
            border-radius: 5px;
            padding: 4px 8px;
            margin-bottom: 4px;
        }

        .sanction-header {
            font-size: 8pt;
            font-weight: 800;
            color: #991b1b;
            border-bottom: 1px dashed #fca5a5;
            padding-bottom: 2px;
            margin-bottom: 3px;
            text-align: center;
        }

        .sanction-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 3px 8px;
        }

        /* Legal Appeal Rights Box */
        .appeal-box {
            border: 1px solid #93c5fd;
            background: #eff6ff;
            border-radius: 4px;
            padding: 3px 6px;
            margin-bottom: 4px;
            font-size: 7pt;
            line-height: 1.3;
            color: #1e3a8a;
        }

        .appeal-title {
            font-weight: 800;
            color: #1d4ed8;
            margin-bottom: 1px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Signatures Grid */
        .signatures-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 2px;
            margin-bottom: 2px;
            text-align: center;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signature-col {
            text-align: center;
        }

        .signature-role {
            font-size: 8pt;
            font-weight: 800;
            color: #991b1b;
            margin: 0;
            line-height: 1.2;
        }

        .signature-sub {
            font-size: 7pt;
            color: #475569;
            font-weight: 600;
            margin: 1px 0 0 0;
        }

        .signature-space {
            height: 38px;
        }

        /* Tear-Off Divider */
        .tear-off-divider {
            margin: 4px 0 3px 0;
            text-align: center;
            position: relative;
            border-top: 1.5px dashed #64748b;
            padding-top: 2px;
        }

        .tear-off-badge {
            display: inline-block;
            background: #ffffff;
            position: relative;
            top: -9px;
            padding: 0 10px;
            font-size: 6.8pt;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.3px;
        }

        /* Detachable Receipt Box */
        .receipt-card {
            border: 1.2px solid #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
            padding: 3px 6px;
            font-size: 7pt;
            line-height: 1.35;
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 2px;
            font-size: 6.8pt;
            font-weight: 700;
            color: #334155;
        }

        .receipt-signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6.8pt;
        }

        .receipt-stamp-space {
            height: 24px;
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    {{-- Action Toolbar for Browser Screen --}}
    <div class="no-print max-w-[196mm] mx-auto mb-3 flex items-center justify-between bg-white border border-slate-200 px-4 py-2.5 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-rose-600"></span>
            <span class="font-bold text-xs text-slate-700">{{ __('إشعار رسمي بقرار مجلس التأديب (مع حق الطعن القانوني)') }}</span>
            <span class="text-[11px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $record->session_number ?? 'CD-'.$record->id }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('print.disciplinary.notification.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-rose-700 text-white hover:bg-rose-800 transition">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('تحميل PDF رسمي') }}
            </a>
            <a href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 transition">
                {{ __('محضر الجلسة') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-slate-800 text-white hover:bg-slate-900 transition">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- Official Algerian Ministry Header --}}
        <div class="header-grid font-traditional">
            <div class="text-right">
                <p class="text-[8.5pt] font-bold leading-tight">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-[7.5pt] font-semibold text-slate-700 leading-tight">{{ __('وزارة التربية الوطنية') }}</p>
                <p class="text-[7pt] text-slate-600 font-sans mt-0.5">
                    <span>{{ __('مديرية التربية لولاية') }}:</span>
                    <strong class="text-slate-900">{{ $schoolSetting?->directorate ?? '................................' }}</strong>
                </p>
            </div>
            <div class="text-center">
                <p class="text-[10pt] font-black text-slate-900 leading-tight">
                    <span>{{ __('متوسطة') }}:</span>
                    <span>{{ $schoolSetting?->school_name ?? '................................' }}</span>
                </p>
                <p class="text-[7.5pt] text-slate-600 font-sans mt-0.5">
                    <span>{{ __('مصلحة الاستشارة التربوية') }}</span>
                </p>
            </div>
            <div class="text-left font-sans text-[7pt] text-slate-700 leading-tight">
                <p>
                    <span>{{ __('السنة الدراسية') }}:</span>
                    <strong class="font-mono text-slate-900">{{ $year }}</strong>
                </p>
                <p class="mt-0.5">
                    <span>{{ __('رقم الإشعار / المرجع') }}:</span>
                    <strong class="font-mono text-slate-900">{{ $record->session_number ?? 'قرار-CD-'.$record->id }}</strong>
                </p>
                <p class="mt-0.5">
                    <span>{{ $schoolSetting?->city ?? '................' }} {{ __('في') }}:</span>
                    <strong class="font-mono text-slate-900">{{ $formatDate($record->deliberated_at ?? $record->hearing_date ?? now()) }}</strong>
                </p>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="title-badge-wrap">
            <div class="title-badge">
                <h1 class="title-main">{{ __('إشعار رسمي بقرار مجلس التأديب') }}</h1>
                <p class="title-sub">NOTIFICATION OFFICIELLE DE DECISION DU CONSEIL DE DISCIPLINE</p>
            </div>
        </div>

        {{-- Recipient & Student Identity Table --}}
        <table class="identity-table">
            <tbody>
                <tr>
                    <th>{{ __('إلى السيد(ة) الولي') }}</th>
                    <td style="font-weight: 800; color: #991b1b; font-size: 8pt;">{{ $guardianName }}</td>
                    <th>{{ __('الهاتف / العنوان') }}</th>
                    <td>
                        @if($guardianPhone !== '—') <span class="font-mono font-bold">{{ $guardianPhone }}</span> — @endif
                        <span>{{ $guardianAddress }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
                    <td style="font-weight: 800; color: #0f172a; font-size: 8.5pt;">{{ $student?->full_name ?? '—' }}</td>
                    <th>{{ __('رقم القيد والتسجيل') }}</th>
                    <td dir="ltr" class="font-mono font-bold text-right">{{ $regNumber }}</td>
                </tr>
                <tr>
                    <th>{{ __('القسم والفوج التربوي') }}</th>
                    <td><strong>{{ $classroom?->name ?? '—' }}</strong> @if($student?->group?->name) <span class="text-slate-600">({{ $student->group->name }})</span> @endif</td>
                    <th>{{ __('تاريخ ومكان الميلاد') }}</th>
                    <td>{{ $formatDate($student?->birth_date) }} {{ __('بـ') }} {{ $student?->birth_place ?? '—' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Legal Preamble & Incident Facts --}}
        <div class="section-card">
            <div class="text-justify text-[7.3pt] leading-relaxed text-slate-800">
                <span>{{ __('ينهي السيد مدير المتوسطة إلى علمكم أنه بمقتضى النصوص التشريعية والتنظيمية المعمول بها، لا سيما القرار الوزاري المنظم لمجالس التأديب في مؤسسات التعليم المتوسط، اجتمع مجلس التأديب بتاريخ') }}</span>
                <strong class="font-mono text-slate-900">{{ $formatDate($record->hearing_date) }}</strong>
                <span>{{ __('للنظر في الوقائع والمخالفات المنسوبة للتلميذ(ة) المذكور(ة) أعلاه والمصنفة ضمن:') }}</span>
                <strong class="text-rose-900 font-bold">« {{ $degreeName }} »</strong>.
            </div>
            <div class="mt-1 p-1.5 bg-amber-50/80 border-r-2 border-amber-600 text-[7.2pt] text-amber-950 font-medium">
                <strong>{{ __('بيان الواقعة المنسوبة:') }}</strong>
                <span>{{ $record->incident_description }}</span>
            </div>
        </div>

        {{-- Formal Sanction Decision Box --}}
        <div class="sanction-box">
            <div class="sanction-header">
                {{ __('مقرر العقوبة المتخذة طبقا للمداولة القانونية للمجلس') }}
            </div>

            <div class="sanction-inner">
                <div>
                    <span class="text-slate-600 text-[7pt] block">{{ __('العقوبة المصادق عليها رسميا:') }}</span>
                    <span class="text-[9.5pt] font-black text-rose-800">{{ $sanctionName }}</span>
                </div>
                @if($record->exclusion_days)
                    <div class="bg-rose-100 text-rose-900 font-bold px-2.5 py-1 rounded border border-rose-300 text-[7pt] text-center">
                        <div>{{ __('مدة الإقصاء المؤقت:') }}</div>
                        <div class="text-[8.5pt] font-black">{{ $record->exclusion_days }} {{ __('أيام دراسة فعلية') }}</div>
                    </div>
                @endif
                @if($record->exclusion_start_date || $record->exclusion_end_date)
                    <div class="text-[7pt] text-slate-700 bg-rose-50 px-2 py-1 rounded border border-rose-200">
                        <div>
                            <span>{{ __('سريان الإقصاء:') }}</span>
                            <strong class="font-mono">{{ $formatDate($record->exclusion_start_date) }}</strong>
                        </div>
                        <div>
                            <span>{{ __('استئناف الدراسة:') }}</span>
                            <strong class="font-mono text-emerald-800">{{ $record->exclusion_end_date ? \Carbon\Carbon::parse($record->exclusion_end_date)->addDay()->format('d/m/Y') : '—' }}</strong>
                        </div>
                    </div>
                @endif
            </div>

            @if($record->restitution_amount && $record->restitution_amount > 0)
                <div class="mt-1 text-[7pt] bg-amber-100/70 px-2 py-1 rounded border border-amber-300 flex justify-between items-center text-amber-950">
                    <span class="font-bold">{{ __('التعويض المالي عن الإتلاف المادي المسجل:') }}</span>
                    <span class="font-mono font-black text-[7.5pt]">{{ number_format($record->restitution_amount, 2) }} {{ __('د.ج') }}</span>
                </div>
            @endif

            @if($record->decision_notes)
                <div class="mt-1 text-[7pt] text-slate-700 bg-white px-2 py-1 rounded border border-slate-200">
                    <strong>{{ __('توجيهات وملاحظات المجلس:') }}</strong>
                    <span>{{ $record->decision_notes }}</span>
                </div>
            @endif
        </div>

        {{-- Legal Appeal Rights Notice --}}
        <div class="appeal-box">
            <div class="appeal-title">
                <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ __('مقتضى حق الطعن القانوني (القرار الوزاري المنظم لمجالس التأديب):') }}</span>
            </div>
            <p class="m-0">
                {{ __('يخطر السيد(ة) الولي الشرعي بأن له الحق في الطعن في هذا القرار أمام «لجنة الطعن الولائية» المعتمدة بمديرية التربية، وذلك في أجل أقصاه ثمانية (08) أيام تسري ابتداء من تاريخ استلامه هذا الإشعار، مع تقديم عريضة طعن معللة. وفي حال عدم الطعن داخل الأجل المحدد يعتبر القرار نافذا ونهائيا ويثبت في الملف المدرسي للتلميذ.') }}
            </p>
        </div>

        {{-- Administrative Signatures --}}
        <div class="signatures-row">
            <div class="signature-col">
                <p class="signature-role">{{ __('مستشار(ة) التربية / الاستشارة التربوية') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->counselor_name ?? 'مستشار التربية' }}</p>
                <div class="signature-space"></div>
            </div>
            <div class="signature-col">
                <p class="signature-role">{{ __('مدير(ة) المؤسسة — رئيس المجلس') }}</p>
                <p class="signature-sub">{{ $record->president?->name ?? ($schoolSetting?->principal_name ?? 'المدير') }}</p>
                <div class="signature-space"></div>
            </div>
        </div>

        {{-- Scissor Tear-Off Divider --}}
        <div class="tear-off-divider">
            <span class="tear-off-badge">
                ✂ {{ __('يقتطع هذا الوصل ويعاد فورا إلى مصلحة الاستشارة التربوية بعد توقيعه') }} ✂
            </span>
        </div>

        {{-- Detachable Receipt Slip for School Archives --}}
        <div class="receipt-card">
            {{-- Institutional Mini Header on the slip --}}
            <div class="receipt-header">
                <div>
                    <span>{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</span> — <span>{{ __('وزارة التربية الوطنية') }}</span>
                </div>
                <div>
                    <span>{{ __('متوسطة') }}: </span><strong>{{ $schoolSetting?->school_name ?? '................................' }}</strong>
                </div>
                <div>
                    <span>Ref: REC-CD-{{ $record->id }}</span> | <span class="font-mono">{{ $year }}</span>
                </div>
            </div>

            {{-- Title of Slip --}}
            <div class="text-center my-0.5">
                <strong class="text-[7.5pt] text-slate-900 border-b border-slate-400 pb-0.5 inline-block">
                    {{ __('وصل استلام إشعار رسمي بقرار مجلس التأديب') }}
                </strong>
            </div>

            {{-- Guardian Acknowledgment Statement --}}
            <p class="m-0 text-justify">
                {{ __('أنا الموقع أسفله السيد(ة):') }} ................................................................
                {{ __('بصفتي ولي أمر التلميذ(ة):') }} <strong class="text-slate-900 font-bold">{{ $student?->full_name }}</strong>
                {{ __('المتمدرس(ة) بقسم:') }} <strong class="font-mono text-slate-900">{{ $classroom?->name }}</strong>
                @if($student?->group?->name) ({{ $student->group->name }}) @endif
            </p>
            <p class="mt-0.5 mb-0 text-justify">
                {{ __('أشهد أني تسلمت نسخة رسمية من الإشعار بقرار مجلس التأديب المنعقد بتاريخ') }}
                <strong class="font-mono text-slate-900">{{ $formatDate($record->hearing_date) }}</strong>
                {{ __('والمتضمن عقوبة:') }} <strong class="text-rose-800 font-bold">« {{ $sanctionName }} »</strong>
                {{ __('وصرحت بأني اطلعت على أحكام وتفاصيل حق الطعن القانوني المنصوص عليها قانونا.') }}
            </p>

            {{-- Signatures on Receipt --}}
            <div class="receipt-signatures-grid">
                <div>
                    <span class="text-slate-600 block">{{ __('حرر بـ') }}: .............................</span>
                    <span class="text-slate-600 block mt-0.5">{{ __('في تاريخ') }}: ..... / ..... / 202...</span>
                </div>
                <div>
                    <strong class="text-slate-900 block">{{ __('إمضاء وبصمة ولي الأمر:') }}</strong>
                    <div class="receipt-stamp-space"></div>
                </div>
                <div>
                    <strong class="text-slate-900 block">{{ __('تأشيرة مصلحة الاستشارة التربوية:') }}</strong>
                    <div class="receipt-stamp-space"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
