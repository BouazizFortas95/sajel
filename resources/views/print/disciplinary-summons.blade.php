@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $record->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $record->student;
    $classroom = $student?->group?->classroom;
    $levelName = $classroom?->level?->name ?? '—';
    $regNumber = $student?->matricule_number ?? $student?->registration_number ?? ($student?->id ? sprintf('%04d', $student->id) : '—');
    $guardianName = $student?->guardian_name ?: ($guardian?->full_name ?? __('ولي أمر التلميذ(ة)'));
    $guardianPhone = $student?->guardian_phone ?? '—';
    $degreeLabel = $record->infraction_degree?->label() ?? __('درجة ثانية');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استدعاء للمثول أمام مجلس التأديب') }} — {{ $student?->full_name }} — {{ $year }}</title>

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
            margin: 6mm 10mm 6mm 10mm !important;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 12px 0;
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        .font-cairo {
            font-family: 'Cairo', sans-serif;
        }

        .print-page {
            width: 100%;
            max-width: 194mm;
            margin: 0 auto;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .print-page--summons {
            page-break-after: always !important;
            break-after: page !important;
            min-height: 284mm;
            max-height: 284mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .print-page--receipt {
            page-break-before: always !important;
            break-before: page !important;
            min-height: 284mm;
            max-height: 284mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Screen Presentation Styling */
        @media screen {
            .print-page {
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 10mm 12mm !important;
                margin-bottom: 24px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            }
        }

        /* Print Media Styling */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .print-page {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 auto !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }

        /* Reusable Components */
        .doc-header-grid {
            display: grid;
            grid-template-columns: 1fr 1.4fr 1fr;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 4px;
            margin-bottom: 6px;
            align-items: center;
        }

        .doc-title-badge {
            display: inline-block;
            border: 2px solid #1e3a8a;
            background: #eff6ff;
            border-radius: 8px;
            padding: 4px 24px;
            text-align: center;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            border: 1.5px solid #1e3a8a;
            background: #ffffff;
            margin-bottom: 6px;
        }

        .info-table th {
            background: #f8fafc;
            color: #1e3a8a;
            font-weight: 700;
            padding: 3px 6px;
            border: 1px solid #cbd5e1;
            text-align: right;
            width: 18%;
        }

        .info-table td {
            padding: 3px 6px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-weight: 600;
        }

        .notice-card {
            border: 1.5px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 6px;
            background: #ffffff;
        }

        .notice-title {
            font-size: 8.5pt;
            font-weight: 800;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notice-body {
            font-size: 8pt;
            line-height: 1.4;
            color: #1e293b;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 6px;
            margin-bottom: 2px;
            text-align: center;
        }

        .signature-item {
            text-align: center;
        }

        .signature-role {
            font-size: 8.5pt;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0;
        }

        .signature-sub {
            font-size: 7.5pt;
            color: #0f172a;
            font-weight: 600;
            margin: 1px 0 0 0;
        }

        .signature-space {
            height: 48px;
        }

        .page-footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
            font-size: 7pt;
            color: #64748b;
            margin-top: auto;
        }

        /* Tear-off section styling */
        .tear-off-divider {
            border-top: 1.5px dashed #475569;
            padding-top: 4px;
            margin-top: 8px;
            margin-bottom: 6px;
        }

        .tear-off-guide {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7pt;
            font-weight: 700;
            color: #475569;
            margin-bottom: 4px;
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen Action Bar --}}
    <div class="no-print" style="max-width: 194mm; margin: 0 auto 12px auto; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 6px 14px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-family: 'Cairo', sans-serif;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 800; font-size: 9.5pt; color: #1e3a8a;">⚖️ {{ __('استدعاء رسمي للمثول أمام مجلس التأديب') }}</span>
            <span style="font-size: 7.5pt; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-family: monospace;">
                {{ $record->session_number ?? ('DISC-'.$record->id) }}
            </span>
            <span style="font-size: 7.5pt; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                {{ $student?->full_name }} — {{ $classroom?->name }}
            </span>
        </div>
        <div style="display: flex; gap: 6px;">
            <button onclick="window.print()" style="cursor: pointer; background: #1e3a8a; color: #ffffff; border: none; padding: 4px 12px; border-radius: 5px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                🖨️ {{ __('طباعة الوثيقة كاملة (A4)') }}
            </button>
            <a href="{{ route('print.disciplinary.summons.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" style="text-decoration: none; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 4px 12px; border-radius: 5px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                📥 {{ __('تحميل PDF') }}
            </a>
            <a href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" style="text-decoration: none; background: #fef3c7; color: #78350f; border: 1px solid #fde68a; padding: 4px 12px; border-radius: 5px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                📝 {{ __('محضر الجلسة') }}
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PAGE 1: Official Disciplinary Hearing Summons (استدعاء المثول أمام مجلس التأديب) --}}
    {{-- ========================================================================= --}}
    <div class="print-page print-page--summons">

        {{-- 1. Official National Republic Header --}}
        <div class="doc-header-grid">
            <div style="text-align: right; line-height: 1.3;">
                <p style="font-size: 7.5pt; color: #475569; margin: 0;">{{ $schoolSetting?->directorate_name ?? __('مديرية التربية') }}</p>
                <p style="font-size: 9.5pt; font-weight: 800; color: #1e3a8a; margin: 1px 0 0 0;">{{ $schoolSetting?->school_name ?? __('المتوسطة') }}</p>
                <p style="font-size: 7pt; color: #64748b; margin: 0;">{{ __('مصلحة التمدرس والامتحانات — أمانة مجلس التأديب') }}</p>
            </div>

            <div style="text-align: center; line-height: 1.25;">
                <p class="font-traditional" style="font-size: 11pt; font-weight: 700; color: #0f172a; margin: 0;">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p style="font-size: 8.5pt; font-weight: 700; color: #1e3a8a; margin: 1px 0 0 0;">{{ __('وزارة التربية الوطنية') }}</p>
            </div>

            <div style="text-align: left; font-size: 7pt; color: #475569; line-height: 1.35;">
                <p style="margin: 0;">{{ __('السنة الدراسية:') }} <strong style="color: #0f172a;">{{ $year }}</strong></p>
                <p style="margin: 0;">{{ __('جلسة رقم:') }} <strong style="color: #1e3a8a; font-family: monospace;">{{ $record->session_number ?? ('DISC-'.$record->id) }}</strong></p>
                <p style="margin: 0;">{{ __('تاريخ الإرسال:') }} <strong style="color: #0f172a;">{{ $formatDate($record->summons_sent_at ?? now()) }}</strong></p>
            </div>
        </div>

        {{-- 2. Document Title Banner --}}
        <div style="text-align: center; margin: 4px 0 8px 0;">
            <div class="doc-title-badge">
                <h1 style="font-size: 13pt; font-weight: 900; color: #1e3a8a; margin: 0; letter-spacing: 0.5px;">
                    {{ __('استدعاء للمثول أمام مجلس التأديب') }}
                </h1>
                <p style="font-size: 7pt; font-weight: 700; color: #475569; margin: 1px 0 0 0; text-transform: uppercase; letter-spacing: 1px;">
                    Convocation Devant le Conseil de Discipline
                </p>
            </div>
        </div>

        {{-- 3. Student & Guardian Information Table --}}
        <table class="info-table">
            <tr>
                <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
                <td style="width: 32%; font-size: 9pt; font-weight: 800; color: #1e3a8a;">{{ $student?->full_name }}</td>
                <th>{{ __('ولي الأمر المحترم') }}</th>
                <td style="font-weight: 700;">{{ $guardianName }}</td>
            </tr>
            <tr>
                <th>{{ __('القسم والفوج') }}</th>
                <td>{{ $classroom?->name ?? '—' }} ({{ $levelName }})</td>
                <th>{{ __('هاتف ولي الأمر') }}</th>
                <td style="direction: ltr; text-align: right; font-family: monospace;">{{ $guardianPhone }}</td>
            </tr>
            <tr>
                <th>{{ __('رقم القيد والتسجيل') }}</th>
                <td style="font-family: monospace;">{{ $regNumber }}</td>
                <th>{{ __('درجة المخالفة') }}</th>
                <td>
                    <span style="display: inline-block; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; font-size: 7.5pt; font-weight: 700; padding: 1px 8px; border-radius: 4px;">
                        ⚖️ {{ $degreeLabel }}
                    </span>
                </td>
            </tr>
        </table>

        {{-- 4. Session Appointment & Location Box --}}
        <div class="notice-card" style="border: 1.5px solid #2563eb; background: #eff6ff;">
            <div class="notice-title" style="color: #1e3a8a;">
                <span>📅</span> {{ __('الموعد ومكان انعقاد جلسة مجلس التأديب:') }}
            </div>
            <div class="notice-body">
                {{ __('يُطلب منكم الحضور الإلزامي رفقة ابنكم (ابنتكم) للمثول أمام أعضاء مجلس التأديب المنعقد بمقر المؤسسة (') }}<strong>{{ $schoolSetting?->school_name ?? __('المتوسطة') }}</strong>{{ __(' — مكتب السيد مدير المؤسسة)، وذلك يوم:') }}
                <strong style="color: #1e3a8a; font-size: 9pt;">{{ $formatDateTime($record->session_date) }}</strong>.
            </div>
        </div>

        {{-- 5. Incidents & Motive Description --}}
        <div class="notice-card" style="border-color: #cbd5e1; background: #ffffff;">
            <div class="notice-title" style="color: #991b1b; border-bottom: 1px dashed #cbd5e1; padding-bottom: 3px;">
                <span>⚠️</span> {{ __('موضوع الإحالة والوقائع المنسوبة أثناء الحصص الدراسية:') }}
            </div>
            <div class="notice-body font-traditional" style="font-size: 8.5pt; line-height: 1.45; white-space: pre-line; color: #0f172a; margin-top: 3px;">
                {{ $record->incident_description }}
            </div>
        </div>

        {{-- 6. Legal Safeguards & Regulatory Notice --}}
        <div class="notice-card" style="border: 1.5px solid #fde68a; background: #fffbeb;">
            <div class="notice-title" style="color: #92400e;">
                <span>📜</span> {{ __('الضمانات القانونية وحقوق الدفاع (وفقاً للنظام الداخلي للمؤسسة والتشريع المدرسي):') }}
            </div>
            <div class="notice-body" style="font-size: 7.5pt; line-height: 1.4; color: #78350f;">
                <p style="margin: 0 0 3px 0;">
                    • <strong>{{ __('حق الدفاع والمرافقة:') }}</strong> {{ __('إن حضور ولي الأمر إجباري لمرافقة التلميذ(ة) وضمان ممارسة حقه في الدفاع والإدلاء بأقواله وتوضيحاته بكل حرية أمام أعضاء المجلس، مع إمكانية الاطلاع المسبق على ملف الإحالة.') }}
                </p>
                <p style="margin: 0;">
                    • <strong>{{ __('مآل الغياب غير المبرر:') }}</strong> {{ __('في حالة الغياب غير المبرر لولي الأمر بعد التبليغ الرسمي القانوني في الآجال، فإن مجلس التأديب ينعقد في الموعد المحدد قانوناً ويبتّ في القضية حضورياً، وتكون قراراته نافذة وفقاً للتشريع المدرسي.') }}
                </p>
            </div>
        </div>

        {{-- 7. Official Signatures Area (Clean & Open for physical stamping) --}}
        <div class="signatures-grid">
            <div class="signature-item">
                <p class="signature-role">{{ __('المستشار الرئيسي للتربية') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->counselor_name ?: __('الاستشارة التربوية') }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-item">
                <p class="signature-role">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->principal_name ?? ($schoolSetting?->director_name ?? __('إدارة المؤسسة')) }}</p>
                <div class="signature-space"></div>
            </div>
        </div>

        {{-- Page 1 Footer --}}
        <div class="page-footer-bar">
            <span>{{ __('استدعاء للمثول أمام مجلس التأديب') }} — {{ $schoolSetting?->school_name }}</span>
            <span>{{ __('الصفحة 1 من 2') }}</span>
            <span dir="ltr">Ref: DISC-{{ $record->id }}</span>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- PAGE 2: Official Return Slip & Guardian Acknowledgment (وصل استلام ولي الأمر) --}}
    {{-- ========================================================================= --}}
    <div class="print-page print-page--receipt">

        {{-- 1. Official Header of Page 2 --}}
        <div class="doc-header-grid">
            <div style="text-align: right; line-height: 1.3;">
                <p style="font-size: 7.5pt; color: #475569; margin: 0;">{{ $schoolSetting?->directorate_name ?? __('مديرية التربية') }}</p>
                <p style="font-size: 9.5pt; font-weight: 800; color: #1e3a8a; margin: 1px 0 0 0;">{{ $schoolSetting?->school_name ?? __('المتوسطة') }}</p>
                <p style="font-size: 7pt; color: #64748b; margin: 0;">{{ __('مصلحة التمدرس والاستشارة التربوية — أمانة مجلس التأديب') }}</p>
            </div>

            <div style="text-align: center; line-height: 1.25;">
                <p class="font-traditional" style="font-size: 11pt; font-weight: 700; color: #0f172a; margin: 0;">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p style="font-size: 8.5pt; font-weight: 700; color: #1e3a8a; margin: 1px 0 0 0;">{{ __('وزارة التربية الوطنية') }}</p>
            </div>

            <div style="text-align: left; font-size: 7pt; color: #475569; line-height: 1.35;">
                <p style="margin: 0;">{{ __('السنة الدراسية:') }} <strong style="color: #0f172a;">{{ $year }}</strong></p>
                <p style="margin: 0;">{{ __('رقم الملف:') }} <strong style="color: #1e3a8a; font-family: monospace;">DISC-{{ $record->id }}</strong></p>
                <p style="margin: 0;">{{ __('تاريخ الجلسة:') }} <strong style="color: #0f172a;">{{ $formatDate($record->session_date) }}</strong></p>
            </div>
        </div>

        {{-- 2. Document Title Banner for Receipt Document --}}
        <div style="text-align: center; margin: 4px 0 8px 0;">
            <div class="doc-title-badge" style="background: #f8fafc; border-color: #0f172a;">
                <h2 style="font-size: 12pt; font-weight: 900; color: #0f172a; margin: 0;">
                    {{ __('وصل استلام وتبليغ استدعاء ولي الأمر للمثول أمام مجلس التأديب') }}
                </h2>
                <p style="font-size: 7pt; font-weight: 700; color: #475569; margin: 1px 0 0 0; text-transform: uppercase;">
                    Récépissé de Notification de Convocation au Conseil de Discipline
                </p>
            </div>
        </div>

        {{-- 3. Guardian Instructions & Legal Return Notice --}}
        <div class="notice-card" style="border: 1.5px solid #93c5fd; background: #f0fdf4;">
            <div class="notice-title" style="color: #166534;">
                <span>📌</span> {{ __('تعليمات وإجراءات تسليم واسترجاع الوصل (مصلحة التمدرس والاستشارة التربوية):') }}
            </div>
            <div class="notice-body" style="font-size: 7.5pt; line-height: 1.4; color: #14532d;">
                <p style="margin: 0 0 3px 0;">
                    1. <strong>{{ __('إلزامية الإرجاع الفوري:') }}</strong> {{ __('يُسلّم الاستدعاء الرسمي لولي أمر التلميذ(ة)، ويجب عليه توقيع هذا الوصل وإعادته فوراً لمصلحة التمدرس والاستشارة التربوية بالمؤسسة لإثبات التبليغ القانوني قبل انعقاد الجلسة بثلاثة (03) أيام على الأقل.') }}
                </p>
                <p style="margin: 0 0 3px 0;">
                    2. <strong>{{ __('حفظ الوصل في الملف التأديبي:') }}</strong> {{ __('يُودع هذا الوصل بعد استرجاعه في الملف التأديبي للتلميذ كوثيقة رسمية مثبتة للتبليغ القانوني.') }}
                </p>
                <p style="margin: 0;">
                    3. <strong>{{ __('الوثائق المطلوبة عند الحضور:') }}</strong> {{ __('يُطلب من ولي الأمر إحضار بطاقة التعريف الوطنية الخاصة به وكراس المراسلة للتلميذ عند الحضور للجلسة.') }}
                </p>
            </div>
        </div>

        {{-- 4. Case & Student Summary Card --}}
        <table class="info-table" style="margin-bottom: 10px;">
            <tr>
                <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
                <td style="width: 32%; font-size: 9pt; font-weight: 800; color: #1e3a8a;">{{ $student?->full_name }}</td>
                <th>{{ __('القسم والفوج') }}</th>
                <td style="font-weight: 700;">{{ $classroom?->name ?? '—' }} ({{ $levelName }})</td>
            </tr>
            <tr>
                <th>{{ __('ولي الأمر المخاطب') }}</th>
                <td style="font-weight: 700;">{{ $guardianName }}</td>
                <th>{{ __('هاتف ولي الأمر') }}</th>
                <td style="direction: ltr; text-align: right; font-family: monospace;">{{ $guardianPhone }}</td>
            </tr>
            <tr>
                <th>{{ __('رقم جلسة المجلس') }}</th>
                <td style="font-family: monospace; font-weight: 700; color: #1e3a8a;">{{ $record->session_number ?? ('DISC-'.$record->id) }}</td>
                <th>{{ __('موعد انعقاد الجلسة') }}</th>
                <td style="font-weight: 800; color: #991b1b;">{{ $formatDateTime($record->session_date) }}</td>
            </tr>
        </table>

        {{-- 5. Detachable Return Slip Section --}}
        <div class="tear-off-divider">
            <div class="tear-off-guide">
                <span>✂️ {{ __('يقتطع هذا الوصل بعد توقيعه ويعاد فورا إلى مصلحة التمدرس والاستشارة التربوية لحفظه بالملف التأديبي') }}</span>
                <span style="font-family: monospace; color: #1e3a8a;">Ref: DISC-{{ $record->id }}</span>
            </div>

            <div style="border: 1.5px solid #1e3a8a; border-radius: 6px; padding: 6px 10px; background: #ffffff;">

                {{-- Slip Official Mini Header --}}
                <div style="display: grid; grid-template-columns: 1.2fr 1.6fr 1.2fr; gap: 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin-bottom: 4px; align-items: center;">
                    <div style="text-align: right; line-height: 1.25;">
                        <p style="font-size: 7.5pt; font-weight: 700; color: #0f172a; margin: 0;">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                        <p style="font-size: 6.5pt; font-weight: 600; color: #1e3a8a; margin: 0;">{{ __('وزارة التربية الوطنية') }}</p>
                        <p style="font-size: 6.5pt; color: #475569; margin: 0;">{{ $schoolSetting?->directorate_name ?: __('مديرية التربية') }}</p>
                    </div>

                    <div style="text-align: center; line-height: 1.25;">
                        <p style="font-size: 8pt; font-weight: 800; color: #1e3a8a; margin: 0;">{{ $schoolSetting?->school_name }}</p>
                        <p style="font-size: 6.5pt; color: #475569; margin: 0;">{{ __('مصلحة التمدرس والاستشارة التربوية') }}</p>
                        <span style="display: inline-block; background: #1e3a8a; color: #ffffff; font-size: 7.5pt; font-weight: 700; padding: 1px 10px; border-radius: 3px; margin-top: 1px;">
                            {{ __('وصل استلام استدعاء مجلس التأديب') }}
                        </span>
                    </div>

                    <div style="text-align: left; font-size: 6.5pt; color: #475569; line-height: 1.25;">
                        <p style="margin: 0;">{{ __('السنة الدراسية:') }} {{ $year }}</p>
                        <p style="margin: 0;">{{ __('رقم الملف:') }} DISC-{{ $record->id }}</p>
                        <p style="margin: 0;">{{ __('موعد الجلسة:') }} {{ $formatDate($record->session_date) }}</p>
                    </div>
                </div>

                {{-- Guardian Formal Acknowledgment Text --}}
                <div style="margin: 6px 0;">
                    <p style="font-size: 7.5pt; font-weight: 700; line-height: 1.45; color: #0f172a; margin: 0;">
                        {{ __('أنا الموقع أسفله، السيد(ة): ............................................................. ولي أمر التلميذ(ة): :name (المسجل بقسم: :classroom)، أشهد بأنني تسلمت الاستدعاء الرسمي الموجه لي للمثول أمام مجلس التأديب المبرمج بتاريخ :date بمقر المؤسسة التعليمية، وألتزم بالحضور في الموعد والمكان المحددين لمرافقة التلميذ(ة).', [
                            'name' => $student?->full_name,
                            'classroom' => $classroom?->name ?? '—',
                            'date' => $formatDateTime($record->session_date),
                        ]) }}
                    </p>
                </div>

                {{-- Dual Signature Blocks: Guardian + School Administration --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; border-top: 1px dotted #cbd5e1; padding-top: 6px; margin-top: 4px;">
                    <div style="border-inline-end: 1px solid #e2e8f0; padding-inline-end: 8px;">
                        <p style="font-size: 7pt; color: #475569; margin: 0;">{{ __('تاريخ الاستلام:') }} ...... / ...... / 2026</p>
                        <p style="font-size: 7.5pt; font-weight: 700; color: #0f172a; margin: 4px 0 0 0;">{{ __('توقيع وبصمة ولي الأمر:') }}</p>
                        <div style="height: 36px;"></div>
                    </div>

                    <div>
                        <p style="font-size: 7pt; color: #475569; margin: 0;">{{ __('تاريخ استرجاع الوصل:') }} ...... / ...... / 2026</p>
                        <p style="font-size: 7.5pt; font-weight: 700; color: #1e3a8a; margin: 4px 0 0 0;">{{ __('تأشيرة وختم مصلحة الاستشارة التربوية:') }}</p>
                        <div style="height: 36px;"></div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Page 2 Footer --}}
        <div class="page-footer-bar">
            <span>{{ __('وصل استلام استدعاء مجلس التأديب') }} — {{ $schoolSetting?->school_name }}</span>
            <span>{{ __('الصفحة 2 من 2') }}</span>
            <span dir="ltr">Ref: DISC-{{ $record->id }}</span>
        </div>

    </div>

</body>
</html>
