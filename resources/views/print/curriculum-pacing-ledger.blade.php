@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $auditCode = 'PAC-AUDIT-'.date('Y');
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('كشف متابعة وتيرة تنفيذ المنهاج الدراسي') }} — {{ $yearStr }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 landscape;
            margin: 4mm 6mm 4mm 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 8.5px;
            line-height: 1.2;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 285mm;
            margin: 4px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-print .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-slate { background: #334155; color: #f1f5f9; }

        .sheet-container {
            width: 100%;
            max-width: 287mm;
            margin: 0 auto;
            padding: 1.5mm 2.5mm;
            background: #ffffff;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        /* Traditional Header */
        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 2px;
            line-height: 1.15;
        }
        .traditional-header .country {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
        }

        /* 3-Column Meta Table */
        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 2.5px;
            margin-bottom: 3px;
            font-size: 8.5px;
        }
        .header-meta-col-school {
            display: table-cell !important;
            width: 33% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.25;
        }
        .header-meta-col-school .school-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 9.5px;
        }
        .header-meta-col-title {
            display: table-cell !important;
            width: 44% !important;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 8px;
        }
        .header-meta-col-title .main-title {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
        }
        .header-meta-col-title .sub-title {
            font-size: 9px;
            font-weight: 700;
            color: #92400e;
            margin-top: 1px;
        }
        .header-meta-col-date {
            display: table-cell !important;
            width: 23% !important;
            text-align: end;
            vertical-align: middle;
            font-size: 8.5px;
            line-height: 1.25;
        }

        /* KPI Table */
        .kpi-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 3.5px 0 !important;
            margin-bottom: 3px !important;
        }
        .kpi-cell {
            display: table-cell !important;
            width: 25% !important;
            vertical-align: middle !important;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 5px;
            text-align: center;
            background: #f8fafc;
        }
        .kpi-cell.emerald { background: #ecfdf5; border-color: #a7f3d0; }
        .kpi-cell.amber { background: #fffbeb; border-color: #fde68a; }
        .kpi-cell.blue { background: #eff6ff; border-color: #bfdbfe; }
        .kpi-label { font-size: 7.5px; font-weight: 700; color: #64748b; margin-bottom: 1px; }
        .kpi-cell.emerald .kpi-label { color: #047857; }
        .kpi-cell.amber .kpi-label { color: #b45309; }
        .kpi-cell.blue .kpi-label { color: #1d4ed8; }
        .kpi-value { font-size: 11.5px; font-weight: 900; font-family: monospace; color: #0f172a; }
        .kpi-cell.emerald .kpi-value { color: #065f46; }
        .kpi-cell.amber .kpi-value { color: #78350f; }
        .kpi-cell.blue .kpi-value { color: #1e3a8a; }

        /* Pacing Table */
        .pacing-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 3px;
        }
        .pacing-table th, .pacing-table td {
            border: 1px solid #94a3b8;
            padding: 2px 4px;
        }
        .pacing-table thead th {
            background: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }
        .pacing-table td.center { text-align: center; }
        .pacing-table td.mono { font-family: monospace; font-size: 8.5px; }

        /* Signatures */
        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.2px solid #0f172a !important;
            padding-top: 2.5px !important;
            margin-top: 2.5px !important;
            text-align: center !important;
            page-break-inside: avoid !important;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top !important;
            padding: 1px 5px !important;
        }
        .sig-title { font-size: 8.5px; font-weight: 800; color: #0f172a; }
        .sig-box { height: 28px; border-bottom: 1px dashed #94a3b8; margin-top: 2px; }

        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 2px;
        }

        @media print {
            .no-print { display: none !important; }
            .sheet-container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        <div class="no-print">
            <div>
                <strong>{{ __('كشف متابعة وتيرة تنفيذ المنهاج الدراسي ومطابقة التدرج البيداغوجي') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8; margin-inline-start: 8px;">{{ $yearStr }}</span>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-amber">{{ __('طباعة الكشف') }}</button>
                <button onclick="window.history.back()" class="btn btn-slate">{{ __('رجوع') }}</button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }} — {{ __('المفتشية العامة للتعليم المتوسط') }}</div>
        </div>

        {{-- Meta Table --}}
        <div class="header-meta-table">
            <div class="header-meta-col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                <div>{{ __('المجال البيداغوجي') }}: <strong>{{ $subject?->name ?? __('كافة المواد التعليمية') }}</strong></div>
            </div>
            <div class="header-meta-col-title">
                <div class="main-title">{{ __('كشف متابعة وتيرة تنفيذ المنهاج وتدرج التعلمات') }}</div>
                <div class="sub-title">{{ __('الموسم الدراسي') }}: {{ $yearStr }}</div>
            </div>
            <div class="header-meta-col-date">
                <div>{{ __('حرر بتاريخ') }}: <span style="font-family: monospace; font-weight: 800;">{{ now()->format('d/m/Y') }}</span></div>
                <div style="font-family: monospace; color: #64748b;">{{ $auditCode }}</div>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="kpi-table">
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('إجمالي الحصص المخططة') }}</div>
                <div class="kpi-value">{{ number_format($overallTotal) }} {{ __('حصة') }}</div>
            </div>
            <div class="kpi-cell emerald">
                <div class="kpi-label">{{ __('الحصص المنجزة فعلياً') }}</div>
                <div class="kpi-value">{{ number_format($overallCompleted) }} {{ __('حصة') }}</div>
            </div>
            <div class="kpi-cell blue">
                <div class="kpi-label">{{ __('نسبة الإنجاز الإجمالية للمؤسسة') }}</div>
                <div class="kpi-value">{{ $overallRate }}%</div>
            </div>
            <div class="kpi-cell amber">
                <div class="kpi-label">{{ __('التقييم البيداغوجي للوتيرة') }}</div>
                <div class="kpi-value" style="font-size: 9.5px;">{{ $overallRate >= 60 ? __('متقدم / متوافق مع المخطط') : __('يستدعي المعالجة والتكثيف') }}</div>
            </div>
        </div>

        {{-- Pacing Table --}}
        @if ($rows->isNotEmpty())
            <table class="pacing-table">
                <thead>
                    <tr>
                        <th style="width: 25px;">#</th>
                        <th style="text-align: right;">{{ __('المادة التعليمية') }}</th>
                        <th style="width: 80px;">{{ __('المستوى التعليمي') }}</th>
                        <th style="width: 70px;">{{ __('الحصص المخططة') }}</th>
                        <th style="width: 70px;">{{ __('الحصص المنجزة') }}</th>
                        <th style="width: 75px;">{{ __('نسبة الإنجاز') }}</th>
                        <th style="width: 75px;">{{ __('النسبة المعيارية') }}</th>
                        <th style="width: 65px;">{{ __('الفارق') }}</th>
                        <th style="width: 95px;">{{ __('الحالة البيداغوجية') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $r)
                        <tr>
                            <td class="center font-bold">{{ $index + 1 }}</td>
                            <td style="font-weight: 700; color: #0f172a;">{{ $r['subject']?->name }}</td>
                            <td class="center">{{ $r['level']?->name }}</td>
                            <td class="center mono">{{ $r['totalLessons'] }}</td>
                            <td class="center mono" style="font-weight: 700; color: #047857;">{{ $r['completedLessons'] }}</td>
                            <td class="center mono" style="font-weight: 800;">{{ $r['completionRate'] }}%</td>
                            <td class="center mono" style="color: #64748b;">{{ $r['expectedRate'] }}%</td>
                            <td class="center mono" style="font-weight: 700; color: {{ $r['lag'] >= 0 ? '#047857' : ($r['lag'] >= -15 ? '#b45309' : '#b91c1c') }};">
                                {{ $r['lag'] > 0 ? '+'.$r['lag'] : $r['lag'] }}%
                            </td>
                            <td class="center" style="font-weight: 700;">
                                @if ($r['pacingStatus'] === 'on_track')
                                    <span style="color: #047857;">{{ __('على المسار الطبيعي') }}</span>
                                @elseif ($r['pacingStatus'] === 'slight_delay')
                                    <span style="color: #b45309;">{{ __('تأخر طفيف') }}</span>
                                @else
                                    <span style="color: #b91c1c;">{{ __('تأخر مقلق (معالجة)') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="padding: 12px; text-align: center; color: #64748b; font-weight: 600; border: 1px dashed #cbd5e1; border-radius: 6px; margin: 6px 0;">
                {{ __('لا توجد سجلات حصص مسجلة حتى الآن.') }}
            </div>
        @endif

        {{-- Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('أستاذ منسق المادة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('مطابقة دفاتر النصوص') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مستشار التربية') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('مراقبة تنفيذ جداول التوقيت') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('التأشيرة والاعتماد الإداري') }}</div>
                <div class="sig-box"></div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — كشف متابعة وتيرة تنفيذ المنهاج وتدرج التعلمات') }} · {{ $auditCode }}
        </div>
    </div>
</body>
</html>

