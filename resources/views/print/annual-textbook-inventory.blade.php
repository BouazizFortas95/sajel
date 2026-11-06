@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $inventoryCode = 'ONPS-INV-'.date('Y');
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة السنوية وجرد الكتاب المدرسي') }} — {{ $yearStr }}</title>

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
            margin: 5mm 6mm 5mm 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 9px;
            line-height: 1.25;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 285mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 11px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-print .title-group {
            display: flex;
            align-items: center;
            gap: 10px;
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
            padding: 5px 12px;
            font-size: 10.5px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }
        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-slate { background: #334155; color: #f1f5f9; }
        .btn-slate:hover { background: #475569; }

        .sheet-container {
            width: 100%;
            max-width: 285mm;
            margin: 0 auto;
            padding: 2mm 3mm;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        /* Traditional Algerian Republic Header */
        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 3px;
        }
        .traditional-header .country {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 10.5px;
            font-weight: 600;
            color: #334155;
        }

        /* 3-Column Metadata Header Grid */
        .header-meta-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 5px;
            font-size: 9.5px;
        }
        .header-meta-grid .col-school {
            text-align: start;
            flex: 1;
            line-height: 1.3;
        }
        .header-meta-grid .col-school .school-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 10px;
        }
        .header-meta-grid .col-school .crddp {
            font-size: 8.5px;
            color: #64748b;
        }

        .header-meta-grid .col-title {
            text-align: center;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 4px 12px;
            flex: 1.3;
        }
        .header-meta-grid .col-title .main-title {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header-meta-grid .col-title .sub-title {
            font-size: 10px;
            font-weight: 700;
            color: #92400e;
            margin-top: 1px;
        }

        .header-meta-grid .col-date {
            text-align: end;
            flex: 1;
            font-size: 9px;
            line-height: 1.3;
        }
        .header-meta-grid .col-date .date-val {
            font-weight: 800;
            font-family: monospace;
            font-size: 9.5px;
        }
        .header-meta-grid .col-date .code-val {
            font-family: monospace;
            font-size: 8.5px;
            color: #64748b;
        }

        /* 4 KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
            margin-bottom: 5px;
        }
        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 6px;
            text-align: center;
            background: #f8fafc;
        }
        .kpi-card.emerald { background: #ecfdf5; border-color: #a7f3d0; }
        .kpi-card.amber { background: #fffbeb; border-color: #fde68a; }
        .kpi-card.blue { background: #eff6ff; border-color: #bfdbfe; }

        .kpi-label {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 1px;
        }
        .kpi-card.emerald .kpi-label { color: #047857; }
        .kpi-card.amber .kpi-label { color: #b45309; }
        .kpi-card.blue .kpi-label { color: #1d4ed8; }

        .kpi-value {
            font-size: 11px;
            font-weight: 900;
            font-family: monospace;
            color: #0f172a;
        }
        .kpi-card.emerald .kpi-value { color: #065f46; }
        .kpi-card.amber .kpi-value { color: #78350f; }
        .kpi-card.blue .kpi-value { color: #1e3a8a; }

        /* Inventory Table */
        .inv-table-wrap {
            width: 100%;
            margin-bottom: 5px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            table-layout: auto;
        }
        .inv-table th,
        .inv-table td {
            border: 1px solid #94a3b8;
            padding: 2.5px 4px;
            line-height: 1.2;
        }
        .inv-table thead th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            font-size: 8.5px;
        }
        .inv-table td.center { text-align: center; }
        .inv-table td.mono { font-family: monospace; font-size: 9px; }
        .inv-table tbody tr:nth-child(even) { background-color: #fcfdfe; }

        .inv-table tfoot tr {
            background-color: #f1f5f9;
            font-weight: 800;
            border-top: 1.5px solid #0f172a;
        }
        .inv-table tfoot td {
            padding: 3px 4px;
            font-size: 8.5px;
        }

        /* 3 Official Signatures Block */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            border-top: 1.5px solid #0f172a;
            padding-top: 4px;
            margin-top: 4px;
            text-align: center;
        }
        .sig-col {
            padding: 2px 6px;
        }
        .sig-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 0.5px;
        }
        .sig-box {
            height: 38px;
            border-bottom: 1px dashed #94a3b8;
            margin-top: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sig-stamp-hint {
            font-size: 8px;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1px 8px;
        }

        .footer-note {
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 3px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                max-width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Preview Floating Action Bar --}}
    <div class="no-print">
        <div class="title-group">
            <span style="font-size: 16px;">📚</span>
            <div>
                <strong>{{ __('الحصيلة السنوية الشاملة لتسيير وحركة الكتاب المدرسي (ONPS / CRDDP)') }}</strong>
                <div style="font-size: 9.5px; color: #94a3b8;">{{ $school?->school_name }} — {{ $yearStr }} ({{ $inventoryCode }})</div>
            </div>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-amber">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الحصيلة كاملة') }}
            </button>
            <button onclick="window.history.back()" class="btn btn-slate">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- 1. Official Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }} — {{ __('الديوان الوطني للمطبوعات المدرسية (ONPS)') }}</div>
        </div>

        {{-- 2. 3-Column Metadata Strip --}}
        <div class="header-meta-grid">
            <div class="col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                <div class="crddp">{{ __('مركز التوزيع والتوثيق البيداغوجي (CRDDP)') }}</div>
            </div>
            <div class="col-title">
                <div class="main-title">{{ __('الحصيلة السنوية الشاملة وجرد حركة الكتاب المدرسي') }}</div>
                <div class="sub-title">{{ __('الموسم الدراسي') }}: {{ $yearStr }}</div>
            </div>
            <div class="col-date">
                <div>{{ __('حرر بتاريخ') }}: <span class="date-val">{{ now()->format('d/m/Y') }}</span></div>
                <div class="code-val">{{ $inventoryCode }}</div>
            </div>
        </div>

        {{-- 3. 4 KPI Cards Strip --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">{{ __('إجمالي النسخ المستلمة') }}</div>
                <div class="kpi-value">{{ number_format($totalReceived) }} {{ __('نسخة') }}</div>
            </div>
            <div class="kpi-card emerald">
                <div class="kpi-label">{{ __('الموزعة (تضامن + بيع)') }}</div>
                <div class="kpi-value">{{ number_format($totalDistributed) }} ({{ number_format($totalSolidarity) }} {{ __('مجاني') }})</div>
            </div>
            <div class="kpi-card amber">
                <div class="kpi-label">{{ __('الرصيد المتبقي بالمخزن') }}</div>
                <div class="kpi-value">{{ number_format($totalInStock) }} {{ __('نسخة') }}</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-label">{{ __('القيمة المالية الإجمالية') }}</div>
                <div class="kpi-value">{{ number_format($totalFinancialValue, 2) }} {{ __('دج') }}</div>
            </div>
        </div>

        {{-- 4. Detailed Inventory Table --}}
        @if ($rows->isNotEmpty())
            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;">#</th>
                            <th style="text-align: right;">{{ __('عنوان الكتاب المقرر') }}</th>
                            <th style="width: 60px;">{{ __('المستوى') }}</th>
                            <th style="width: 80px;">{{ __('المادة') }}</th>
                            <th style="width: 50px;">{{ __('المستلم') }}</th>
                            <th style="width: 50px;">{{ __('الموزع') }}</th>
                            <th style="width: 55px;">{{ __('التضامن') }}</th>
                            <th style="width: 45px;">{{ __('البيع') }}</th>
                            <th style="width: 50px;">{{ __('المسترجع') }}</th>
                            <th style="width: 55px;">{{ __('بالمخزن') }}</th>
                            <th style="width: 55px;">{{ __('السعر (دج)') }}</th>
                            <th style="width: 75px;">{{ __('المجموع المالي') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $index => $r)
                            <tr>
                                <td class="center font-bold" style="font-weight: 700; color: #475569;">{{ $index + 1 }}</td>
                                <td style="font-weight: 700; color: #0f172a;">{{ $r['textbook']?->title }}</td>
                                <td class="center" style="font-size: 8px; color: #475569;">{{ $r['level'] }}</td>
                                <td style="font-size: 8px; color: #475569;">{{ $r['subject'] }}</td>
                                <td class="center mono" style="font-weight: 700;">{{ $r['received'] }}</td>
                                <td class="center mono" style="font-weight: 700; color: #047857;">{{ $r['distributed'] }}</td>
                                <td class="center mono" style="color: #065f46;">{{ $r['solidarity'] }}</td>
                                <td class="center mono" style="color: #334155;">{{ $r['sold'] }}</td>
                                <td class="center mono" style="color: #1d4ed8;">{{ $r['returned'] }}</td>
                                <td class="center mono" style="font-weight: 700; color: #b45309;">{{ $r['in_stock'] }}</td>
                                <td class="center mono">{{ number_format((float) $r['unit_price'], 2) }}</td>
                                <td class="center mono" style="font-weight: 700;">{{ number_format($r['financial_value'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: start; font-weight: 800; padding-inline-start: 6px;">{{ __('المجموع الإجمالي العام:') }}</td>
                            <td class="center mono" style="font-weight: 800;">{{ number_format($totalReceived) }}</td>
                            <td class="center mono" style="font-weight: 800; color: #047857;">{{ number_format($totalDistributed) }}</td>
                            <td class="center mono" style="font-weight: 800; color: #065f46;">{{ number_format($totalSolidarity) }}</td>
                            <td class="center mono" style="font-weight: 800;">{{ number_format($totalSold) }}</td>
                            <td class="center mono" style="font-weight: 800; color: #1d4ed8;">{{ number_format($totalReturned) }}</td>
                            <td class="center mono" style="font-weight: 800; color: #b45309;">{{ number_format($totalInStock) }}</td>
                            <td class="center">—</td>
                            <td class="center mono" style="font-weight: 800; color: #1e3a8a;">{{ number_format($totalFinancialValue, 2) }} {{ __('دج') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div style="padding: 20px; text-align: center; color: #64748b; font-weight: 600; border: 1px dashed #cbd5e1; border-radius: 6px; margin: 8px 0;">
                {{ __('لم يتم تسجيل أية كتب في المخزن بعد.') }}
            </div>
        @endif

        {{-- 5. Official Tripartite Signatures Block --}}
        <div class="signatures-grid">
            <div class="sig-col">
                <div class="sig-title">{{ __('المسؤول عن مخزن الكتب (المسير)') }}</div>
                <div class="sig-sub">{{ __('الجرد الفعلي والمطابقة العينية') }}</div>
                <div class="sig-box">
                    <span class="sig-stamp-hint">({{ __('التوقيع والمصادقة') }})</span>
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-title">{{ __('المقتصد / مسير المصالح المالية') }}</div>
                <div class="sig-sub">{{ __('التدقيق المالي والمحاسبي') }}</div>
                <div class="sig-box">
                    <span class="sig-stamp-hint">({{ __('التأشيرة والختم المالي') }})</span>
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                <div class="sig-sub">{{ __('المصادقة والتوجيه للديوان (ONPS)') }}</div>
                <div class="sig-box">
                    <span class="sig-stamp-hint">({{ __('الختم الإداري والتوقيع') }})</span>
                </div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — الحصيلة السنوية لجرد الكتاب المدرسي') }} · {{ $inventoryCode }}
        </div>

    </div>

</body>
</html>
