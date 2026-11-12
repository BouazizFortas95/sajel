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
            margin: 4mm 8mm 4mm 8mm !important;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 8px 0;
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
            }
            .no-print {
                display: none !important;
            }
        }

        /* Header Grid */
        .header-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.4fr 1.1fr;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 3px;
            margin-bottom: 4px;
            align-items: center;
        }

        /* Title Badge */
        .title-badge-wrap {
            text-align: center;
            margin: 2px 0 5px 0;
        }

        .title-badge {
            display: inline-block;
            border: 1.5px solid #1e3a8a;
            background: #eff6ff;
            border-radius: 6px;
            padding: 2px 20px;
            text-align: center;
        }

        .title-main {
            font-size: 11.5pt;
            font-weight: 900;
            color: #1e3a8a;
            margin: 0;
            line-height: 1.25;
        }

        .title-sub {
            font-size: 6.5pt;
            font-weight: 700;
            color: #475569;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Opening text & Attendees */
        .intro-box {
            font-size: 7.5pt;
            line-height: 1.35;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .attendees-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 6px;
            margin-top: 2px;
            font-size: 7pt;
        }

        /* Identity Table */
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            border: 1.5px solid #1e3a8a;
            background: #ffffff;
            margin-bottom: 4px;
        }

        .identity-table th {
            background: #f8fafc;
            color: #1e3a8a;
            font-weight: 700;
            padding: 2px 5px;
            border: 1px solid #cbd5e1;
            text-align: right;
            width: 16%;
        }

        .identity-table td {
            padding: 2px 5px;
            border: 1px solid #cbd5e1;
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
            color: #1e3a8a;
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

        /* Decision Box */
        .decision-box {
            border: 1.5px solid #1e3a8a;
            background: #fffbeb;
            border-radius: 4px;
            padding: 4px 6px;
            margin-bottom: 4px;
        }

        .decision-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 3px 8px;
            margin-top: 2px;
        }

        /* Signatures 4 Roles Grid */
        .signatures-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 3px;
            margin-bottom: 2px;
            text-align: center;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signature-col {
            text-align: center;
        }

        .signature-role {
            font-size: 7.5pt;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0;
            line-height: 1.2;
        }

        .signature-sub {
            font-size: 6.5pt;
            color: #475569;
            font-weight: 600;
            margin: 1px 0 0 0;
        }

        .signature-space {
            height: 38px;
        }

        /* Footer */
        .footer-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 2px;
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 2px;
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen Action Bar --}}
    <div class="no-print" style="max-width: 196mm; margin: 0 auto 10px auto; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 6px 14px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-family: 'Cairo', sans-serif;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 800; font-size: 9pt; color: #1e3a8a;">📜 {{ __('محضر جلسة مجلس التأديب الرسمي') }}</span>
            <span style="font-size: 7.5pt; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 3px; font-weight: 700; font-family: monospace;">
                {{ $record->session_number ?? '—' }}
            </span>
            <span style="font-size: 7.5pt; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 3px; font-weight: 700;">
                {{ $student?->full_name }} — {{ $classroom?->name }}
            </span>
        </div>
        <div style="display: flex; gap: 6px;">
            <button onclick="window.print()" style="cursor: pointer; background: #1e3a8a; color: #ffffff; border: none; padding: 3px 12px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                🖨️ {{ __('طباعة المحضر (A4)') }}
            </button>
            <a href="{{ route('print.disciplinary.minutes.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" style="text-decoration: none; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 3px 12px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                📥 {{ __('تحميل PDF') }}
            </a>
            <a href="{{ route('print.disciplinary.notification', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" style="text-decoration: none; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 12px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                📢 {{ __('إشعار الولي بالقرار') }}
            </a>
        </div>
    </div>

    {{-- Printable A4 Sheet --}}
    <div class="sheet-container">

        {{-- 1. Official National Republic Header --}}
        <div class="header-grid">
            <div style="text-align: right; line-height: 1.25;">
                <p style="font-size: 7pt; color: #475569; margin: 0;">{{ $schoolSetting?->directorate_name ?? __('مديرية التربية') }}</p>
                <p style="font-size: 9pt; font-weight: 800; color: #1e3a8a; margin: 1px 0 0 0;">{{ $schoolSetting?->school_name ?? __('المتوسطة') }}</p>
                <p style="font-size: 6.5pt; color: #64748b; margin: 0;">{{ __('أمانة مجلس التأديب') }}</p>
            </div>

            <div style="text-align: center; line-height: 1.2;">
                <p class="font-traditional" style="font-size: 10.5pt; font-weight: 700; color: #0f172a; margin: 0;">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p style="font-size: 8pt; font-weight: 700; color: #1e3a8a; margin: 1px 0 0 0;">{{ __('وزارة التربية الوطنية') }}</p>
            </div>

            <div style="text-align: left; font-size: 6.5pt; color: #475569; line-height: 1.3;">
                <p style="margin: 0;">{{ __('السنة الدراسية:') }} <strong style="color: #0f172a;">{{ $year }}</strong></p>
                <p style="margin: 0;">{{ __('جلسة رقم:') }} <strong style="color: #1e3a8a; font-family: monospace;">{{ $record->session_number ?? '—' }}</strong></p>
                <p style="margin: 0;">{{ __('تاريخ الانعقاد:') }} <strong style="color: #0f172a;">{{ $formatDate($record->session_date) }}</strong></p>
            </div>
        </div>

        {{-- 2. Document Title Banner (Clean, no diacritics / tashkeel) --}}
        <div class="title-badge-wrap">
            <div class="title-badge">
                <h1 class="title-main">
                    {{ __('محضر اجتماع مجلس التأديب') }}
                </h1>
                <p class="title-sub">
                    Procès-Verbal de Réunion du Conseil de Discipline
                </p>
            </div>
        </div>

        {{-- 3. Opening Formula & Council Attendees --}}
        <div class="intro-box">
            <p style="margin: 0 0 2px 0;">
                {{ __('في يوم :date، وعلى الساعة :time، اجتمع مجلس التأديب لمؤسسة :school برئاسة السيد(ة): :pres، وبحضور السادة الأعضاء القانونيين:', [
                    'date' => $record->session_date?->translatedFormat('l d F Y') ?? $formatDate($record->session_date),
                    'time' => $record->session_date?->format('H:i') ?? '10:00',
                    'school' => $schoolSetting?->school_name ?? __('المؤسسة'),
                    'pres' => $record->president?->name ?? ($schoolSetting?->director_name ?? __('مدير المؤسسة')),
                ]) }}
            </p>
            <div class="attendees-grid">
                @foreach ($councilAttendees as $att)
                    <div style="display: flex; align-items: center; gap: 3px;">
                        <span style="color: #059669; font-weight: 800;">✓</span>
                        <span>{{ $att }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. Student Identification Box --}}
        <table class="identity-table">
            <tr>
                <th>{{ __('الاسم واللقب') }}</th>
                <td style="width: 25%; font-weight: 800; color: #1e3a8a;">{{ $student?->full_name }}</td>
                <th>{{ __('القسم والفوج') }}</th>
                <td style="font-weight: 700;">{{ $classroom?->name ?? '—' }}</td>
                <th>{{ __('تاريخ الازدياد') }}</th>
                <td style="font-family: monospace;">{{ $formatDate($student?->date_of_birth) }}</td>
            </tr>
            <tr>
                <th>{{ __('رقم القيد والتسجيل') }}</th>
                <td style="font-family: monospace;">{{ $student?->matricule_number ?? $student?->registration_number ?? '—' }}</td>
                <th>{{ __('حضور ولي الأمر') }}</th>
                <td>
                    <span style="font-weight: 700; color: {{ $record->guardian_attended ? '#059669' : '#dc2626' }};">
                        {{ $record->guardian_attended ? __('✅ نعم (حضر مع التلميذ)') : __('❌ غائب (رغم التبليغ القانوني)') }}
                    </span>
                </td>
                <th>{{ __('التقرير المحال من طرف') }}</th>
                <td>{{ $record->reportedBy?->name ?? __('المستشار الرئيسي للتربية') }}</td>
            </tr>
        </table>

        {{-- 5. Facts & Incident Motives --}}
        <div class="section-card">
            <div class="section-title">
                <span>⚠️</span> {{ __('1. عرض الوقائع والمخالفات المنسوبة أثناء الحصة:') }}
            </div>
            <div class="section-content font-traditional">
                {{ $record->incident_description }}
            </div>
        </div>

        {{-- 6. Student & Guardian Statements --}}
        <div class="section-card">
            <div class="section-title">
                <span>🗣️</span> {{ __('2. أقوال التلميذ(ة) ودفوعات ولي الأمر:') }}
            </div>
            <div class="section-content font-traditional" style="font-style: italic;">
                {{ $record->student_statement ?: __('استمع المجلس لتوضيحات التلميذ(ة) الذي أقرّ بالوقائع وقدم اعتذاره لأعضاء مجلس التأديب.') }}
            </div>
        </div>

        {{-- 7. Secret Deliberation & Sanction Decision --}}
        <div class="decision-box">
            <div class="section-title" style="color: #0f172a;">
                <span>⚖️</span> {{ __('3. المداولة السرية وقرار مجلس التأديب:') }}
            </div>
            <p style="font-size: 7pt; color: #334155; margin: 0 0 3px 0; line-height: 1.3;">
                {{ __('بعد مغادرة التلميذ وولي أمره، تداول أعضاء المجلس سرياً وفق أحكام القرار الوزاري والنظام الداخلي، وصوّت الأعضاء بالأغلبية على اتخاذ القرار التالي:') }}
            </p>

            <div class="decision-inner">
                <div>
                    <span style="font-size: 6.5pt; color: #64748b; font-weight: 700; display: block;">{{ __('العقوبة التأديبية المقررة:') }}</span>
                    <strong style="font-size: 9.5pt; font-weight: 900; color: #991b1b;">
                        {{ $record->decision?->label() ?? __('قيد المداولة') }}
                    </strong>
                </div>

                @if ($record->exclusion_days)
                    <div style="text-align: left; font-size: 7pt; font-family: monospace;">
                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid #fca5a5;">
                            {{ $record->exclusion_days }} {{ __('أيام إقصاء مؤقت') }}
                        </span>
                        <div style="font-size: 6.5pt; color: #64748b; margin-top: 1px;">
                            {{ __('من:') }} {{ $formatDate($record->exclusion_start_date) }} {{ __('إلى:') }} {{ $formatDate($record->exclusion_end_date) }}
                        </div>
                    </div>
                @endif

                @if ($record->compensation_amount)
                    <div style="text-align: left; font-size: 7pt; font-family: monospace;">
                        <span style="background: #f3e8ff; color: #6b21a8; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid #d8b4fe;">
                            {{ number_format((float) $record->compensation_amount, 2) }} {{ __('دج تعويض مالي') }}
                        </span>
                    </div>
                @endif
            </div>

            @if ($record->notes)
                <div style="font-size: 6.5pt; color: #475569; margin-top: 2px;">
                    <strong>{{ __('ملاحظات وتوجيهات إضافية:') }}</strong> {{ $record->notes }}
                </div>
            @endif
        </div>

        {{-- 8. Official Signatures (4 Council Roles, Open and Clean without Boxes) --}}
        <div class="signatures-row">
            <div class="signature-col">
                <p class="signature-role">{{ __('رئيس المجلس (المدير)') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->principal_name ?? ($schoolSetting?->director_name ?? __('المدير والختم الرسمي')) }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-col">
                <p class="signature-role">{{ __('ناظر(ة) الدروس') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->censor_name ?? __('نظارة الدروس') }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-col">
                <p class="signature-role">{{ __('مستشار(ة) التربية') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->counselor_name ?? __('الاستشارة التربوية') }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-col">
                <p class="signature-role">{{ __('ممثل الأساتذة والأولياء') }}</p>
                <p class="signature-sub">{{ __('أعضاء المجلس') }}</p>
                <div class="signature-space"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer-line">
            <span>{{ __('منظومة سجل الرقمية — محضر جلسة مجلس التأديب') }} | {{ $schoolSetting?->school_name }}</span>
            <span>Ref: PV-CD-{{ $record->id }}</span>
            <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>
