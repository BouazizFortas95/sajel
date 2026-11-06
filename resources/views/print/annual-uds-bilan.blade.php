@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $overview['academic_year'] ?? ($school?->academic_year ?? '2026/2027');
    $bilanCode = 'BIL-UDS-'.str_replace('/', '', $yearStr);
    $levels = $overview['level_breakdown'] ?? [];
    $chronicByType = $overview['chronic_by_type'] ?? [];
    $epsByStatus = $overview['eps_by_status'] ?? [];
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة السنوية لنشاط وحدة الكشف والمتابعة الصحية') }} — {{ $yearStr }}</title>

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
            margin: 3.5mm 5mm 3.5mm 5mm;
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
        .btn-emerald { background: #059669; color: #ffffff; }
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
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

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
        .traditional-header .ministries {
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
        }

        /* 3-Column Metadata Table */
        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 2.5px;
            margin-bottom: 3px;
            font-size: 8.5px;
        }
        .header-col-school {
            display: table-cell !important;
            width: 34% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.25;
        }
        .header-col-school .school-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 9.5px;
        }

        .header-col-title {
            display: table-cell !important;
            width: 42% !important;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 8px;
        }
        .header-col-title .main-title {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
        }
        .header-col-title .sub-title {
            font-size: 9px;
            font-weight: 700;
            color: #059669;
            line-height: 1.15;
        }

        .header-col-date {
            display: table-cell !important;
            width: 24% !important;
            text-align: end;
            vertical-align: middle;
            font-size: 8.5px;
            line-height: 1.25;
            font-family: monospace;
        }

        /* 6 KPI Cards Table strictly in 1 Row */
        .kpi-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 3px 0 !important;
            margin-bottom: 3px !important;
        }
        .kpi-cell {
            display: table-cell !important;
            width: 16.666% !important;
            vertical-align: middle !important;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2.5px 4px;
            text-align: center;
            background: #f8fafc;
            box-sizing: border-box;
        }
        .kpi-cell.emerald { background: #ecfdf5; border-color: #a7f3d0; }
        .kpi-cell.amber { background: #fffbeb; border-color: #fde68a; }
        .kpi-cell.rose { background: #fff1f2; border-color: #fecdd3; }
        .kpi-cell.blue { background: #eff6ff; border-color: #bfdbfe; }

        .kpi-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #64748b;
            line-height: 1.1;
            white-space: nowrap;
        }
        .kpi-value {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
            margin-top: 1px;
        }

        /* Data Tables */
        table.uds-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 3px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        table.uds-table th, table.uds-table td {
            border: 1px solid #94a3b8;
            padding: 1.8px 3px;
            text-align: center;
            line-height: 1.15;
        }
        table.uds-table thead th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
            font-size: 8px;
        }
        table.uds-table tfoot td {
            font-weight: 800;
            background-color: #f8fafc;
        }

        .section-heading {
            font-size: 8.5px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 2-Column Side-by-Side Table */
        .two-col-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 5px 0 !important;
            margin-bottom: 3px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .two-col-cell {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top !important;
        }

        /* 3 Official Signatures Table */
        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.2px solid #0f172a !important;
            padding-top: 2.5px !important;
            margin-top: 2.5px !important;
            text-align: center !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top !important;
            padding: 1px 6px !important;
        }
        .sig-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .sig-stamp {
            font-size: 7.5px;
            color: #64748b;
        }

        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 2px;
        }

        @media print {
            .no-print { display: none !important; }
            html, body {
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                height: 100% !important;
                max-height: 202mm !important;
                overflow: hidden !important;
            }
            .sheet-container {
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        <div class="no-print">
            <div>
                <strong>{{ __('الحصيلة السنوية لنشاط وحدة الكشف والمتابعة الصحية المدرسية (UDS)') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8;">{{ $school?->school_name }} — {{ $yearStr }}</span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button onclick="window.print()" class="btn btn-emerald">
                    🖨️ {{ __('طباعة الحصيلة كاملة') }}
                </button>
                <button onclick="window.history.back()" class="btn btn-slate">
                    {{ __('رجوع') }}
                </button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Official Algerian Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministries">{{ __('وزارة التربية الوطنية') }} — {{ __('وزارة الصحة والسكان وإصلاح المستشفيات') }}</div>
        </div>

        {{-- Meta Strip --}}
        <div class="header-meta-table">
            <div class="header-col-school">
                <div>{{ $school?->directorate_name ?? 'مديرية التربية لولاية الوادي' }}</div>
                <div class="school-name">{{ $school?->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</div>
                <div style="color: #059669; font-weight: 700;">{{ __('وحدة الكشف والمتابعة الصحية المدرسية (UDS)') }}</div>
            </div>
            <div class="header-col-title">
                <div class="main-title">{{ __('الحصيلة الإحصائية السنوية للصحة المدرسية (UDS)') }}</div>
                <div class="sub-title">{{ __('الموسم الدراسي') }}: {{ $yearStr }}</div>
            </div>
            <div class="header-col-date">
                <div><strong>Réf:</strong> {{ $bilanCode }}</div>
                <div><strong>{{ __('حرر بتاريخ') }}:</strong> {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>

        {{-- 6 KPI Cards Table strictly in 1 Row --}}
        <div class="kpi-table">
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('تعداد التلاميذ الإجمالي') }}</div>
                <div class="kpi-value">{{ $overview['total_students'] }}</div>
            </div>
            <div class="kpi-cell emerald">
                <div class="kpi-label">{{ __('المفحوصون طبياً') }}</div>
                <div class="kpi-value" style="color: #047857;">{{ $overview['screened_count'] }} <span style="font-size: 8px; font-weight: normal;">({{ $overview['coverage_rate'] }}%)</span></div>
            </div>
            <div class="kpi-cell rose">
                <div class="kpi-label">{{ __('الأمراض المزمنة (PAI)') }}</div>
                <div class="kpi-value" style="color: #b91c1c;">{{ $overview['chronic_count'] }} <span style="font-size: 8px; font-weight: normal;">({{ $overview['emergency_med_count'] }} {{ __('دواء') }})</span></div>
            </div>
            <div class="kpi-cell amber">
                <div class="kpi-label">{{ __('إعفاءات التربية البدنية') }}</div>
                <div class="kpi-value" style="color: #b45309;">{{ $overview['eps_exempt_count'] }} <span style="font-size: 8px; font-weight: normal;">({{ $overview['eps_exempt_rate'] }}%)</span></div>
            </div>
            <div class="kpi-cell blue">
                <div class="kpi-label">{{ __('حاملو النظارات الطبية') }}</div>
                <div class="kpi-value" style="color: #1d4ed8;">{{ $overview['glasses_count'] }}</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('الأسنان المنخورة') }}</div>
                <div class="kpi-value" style="color: #be123c;">{{ $overview['caries_total'] }}</div>
            </div>
        </div>

        {{-- Section 1: Level Breakdown Table --}}
        <div class="section-heading">
            <span>{{ __('1. جدول التغطية الصحية والفحوصات الطبية حسب المستويات التعليمية (1AM - 4AM)') }}</span>
            <span style="font-size: 7.5px; font-weight: normal; color: #64748b;">{{ __('الطب المدرسي الوقائي') }}</span>
        </div>
        <table class="uds-table">
            <thead>
                <tr>
                    <th style="width: 25%; text-align: start; padding-inline-start: 6px;">{{ __('المستوى التعليمي') }}</th>
                    <th style="width: 15%;">{{ __('إجمالي المتمدرسين') }}</th>
                    <th style="width: 15%; background: #ecfdf5; color: #065f46;">{{ __('المفحوصون سريرياً') }}</th>
                    <th style="width: 15%; background: #ecfdf5; color: #065f46;">{{ __('نسبة التغطية') }}</th>
                    <th style="width: 15%; background: #fff1f2; color: #9f1239;">{{ __('حالات الأمراض المزمنة') }}</th>
                    <th style="width: 15%; background: #fffbeb; color: #92400e;">{{ __('معفـون من الرياضة') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($levels as $code => $lvl)
                    <tr>
                        <td style="font-weight: 700; text-align: start; padding-inline-start: 6px;">{{ $lvl['name'] }}</td>
                        <td>{{ $lvl['total'] }}</td>
                        <td style="font-weight: 700; color: #047857;">{{ $lvl['screened'] }}</td>
                        <td style="font-weight: 800; background: #f0fdf4; color: #166534;">{{ $lvl['coverage_rate'] }}%</td>
                        <td style="font-weight: 700; color: #b91c1c;">{{ $lvl['chronic'] }}</td>
                        <td style="font-weight: 700; color: #b45309;">{{ $lvl['eps_exempt'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="color: #94a3b8; padding: 4px;">{{ __('لم تسجل فحوصات طبية للمستويات بعد') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: start; padding-inline-start: 6px;">{{ __('المجموع الإجمالي للمؤسسة:') }}</td>
                    <td>{{ $overview['total_students'] }}</td>
                    <td style="color: #047857;">{{ $overview['screened_count'] }}</td>
                    <td style="background: #dcfce7; color: #14532d;">{{ $overview['coverage_rate'] }}%</td>
                    <td style="color: #b91c1c;">{{ $overview['chronic_count'] }}</td>
                    <td style="color: #b45309;">{{ $overview['eps_exempt_count'] }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Section 2: Two-Column Side-by-Side Tables --}}
        <div class="two-col-table">
            <div class="two-col-cell">
                <div class="section-heading">{{ __('2. تصنيف الأمراض المزمنة والحالات الخاصة (PAI)') }}</div>
                <table class="uds-table">
                    <thead>
                        <tr>
                            <th style="text-align: start; padding-inline-start: 6px;">{{ __('المرض / الحالة الخاصة') }}</th>
                            <th style="width: 25%;">{{ __('الحالات المسجلة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chronicByType as $item)
                            <tr>
                                <td style="text-align: start; padding-inline-start: 6px;">
                                    <span>{{ $item['icon'] }}</span>
                                    <span style="font-weight: 700;">{{ $item['label'] }}</span>
                                </td>
                                <td style="font-weight: 800; color: #b91c1c;">{{ $item['count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="color: #94a3b8;">{{ __('لا توجد أمراض مزمنة مسجلة') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="two-col-cell">
                <div class="section-heading">{{ __('3. إحصائيات الإعفاء من التربية البدنية والرياضية (EPS)') }}</div>
                <table class="uds-table">
                    <thead>
                        <tr>
                            <th style="text-align: start; padding-inline-start: 6px;">{{ __('نوع وحجم الإعفاء الممنوح') }}</th>
                            <th style="width: 25%;">{{ __('عدد التلاميذ') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($epsByStatus as $item)
                            <tr>
                                <td style="text-align: start; padding-inline-start: 6px; font-weight: 700;">{{ $item['label'] }}</td>
                                <td style="font-weight: 800; color: #b45309;">{{ $item['count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="color: #94a3b8;">{{ __('جميع التلاميذ لائقون بدنياً') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tripartite Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('طبيب وحدة الكشف والمتابعة (UDS)') }}</div>
                <div class="sig-stamp">({{ __('الختم الطبي والتوقيع') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مستشار التربية / ناظر المؤسسة') }}</div>
                <div class="sig-stamp">({{ __('تأشيرة المتابعة والاطلاع') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة التعليمية') }}</div>
                <div class="sig-stamp">({{ __('المصادقة والتوجيه للوصاية') }})</div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — الحصيلة السنوية للصحة المدرسية') }} · {{ $bilanCode }}
        </div>
    </div>

</body>
</html>
