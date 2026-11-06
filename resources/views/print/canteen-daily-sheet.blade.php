@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $sheetDateStr = $formatDate($sheet->sheet_date);
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ورقة الاستهلاك اليومي للمطعم المدرسي') }} — {{ $sheetDateStr }}</title>

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

        /* Meta Table */
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
            width: 32% !important;
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
            font-size: 11.5px;
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
            width: 24% !important;
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

        /* Menu Strip */
        .menu-strip {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 2.5px 6px;
            margin-bottom: 3px;
            font-size: 8px;
            line-height: 1.3;
        }

        /* Food Items Table */
        .food-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 3px;
        }
        .food-table th, .food-table td {
            border: 1px solid #94a3b8;
            padding: 2px 4px;
        }
        .food-table thead th {
            background: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }
        .food-table td.center { text-align: center; }
        .food-table td.mono { font-family: monospace; font-size: 8.5px; }

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
                <strong>{{ __('ورقة الاستهلاك اليومي للمطعم المدرسي (FJC)') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8; margin-inline-start: 8px;">{{ $sheet->reference_number }} · {{ $sheetDateStr }}</span>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-amber">{{ __('طباعة الورقة') }}</button>
                <button onclick="window.history.back()" class="btn btn-slate">{{ __('رجوع') }}</button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }} — {{ __('المصالح الاقتصادية والتغذية المدرسية') }}</div>
        </div>

        {{-- Meta Table --}}
        <div class="header-meta-table">
            <div class="header-meta-col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                <div>{{ __('مصلحة المقتصدية') }} · {{ __('المطعم المدرسي') }}</div>
            </div>
            <div class="header-meta-col-title">
                <div class="main-title">{{ __('ورقة الاستهلاك اليومي للمواد الغذائية') }}</div>
                <div class="sub-title">FEUILLE JOURNALIÈRE DE CONSOMMATION (FJC) · {{ $yearStr }}</div>
            </div>
            <div class="header-meta-col-date">
                <div>{{ __('يوم') }}: <strong>{{ $sheetDateStr }}</strong></div>
                <div style="font-family: monospace; font-weight: 800; color: #1e3a8a;">{{ $sheet->reference_number }}</div>
                <div style="color: #64748b;">{{ __('الحالة:') }} {{ $sheet->status->label() }}</div>
            </div>
        </div>

        {{-- 4 KPI Cards --}}
        <div class="kpi-table">
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('التلاميذ نصف الداخليين الحاضرين') }}</div>
                <div class="kpi-value">{{ number_format($sheet->half_board_students_present) }} {{ __('تلميذ') }}</div>
            </div>
            <div class="kpi-cell emerald">
                <div class="kpi-label">{{ __('المستفيدون (تلاميذ + موظفون + عمال)') }}</div>
                <div class="kpi-value">{{ number_format($sheet->total_beneficiaries_count) }} {{ __('وجبة') }}</div>
            </div>
            <div class="kpi-cell blue">
                <div class="kpi-label">{{ __('التكلفة المالية الإجمالية لليوم') }}</div>
                <div class="kpi-value">{{ number_format((float) $sheet->total_cost, 2) }} {{ __('دج') }}</div>
            </div>
            <div class="kpi-cell amber">
                <div class="kpi-label">{{ __('تكلفة الوجبة الفردية') }}</div>
                <div class="kpi-value">{{ number_format((float) $sheet->cost_per_meal, 2) }} {{ __('دج') }}</div>
            </div>
        </div>

        {{-- Menu & Dietary Alerts --}}
        <div class="menu-strip">
            <strong>🍽️ {{ __('قائمة وجبة اليوم:') }}</strong> {{ $sheet->menu_description ?? __('طبق رئيسي متكامل، سلطة خضراء، فاكهة موسمية، وخبز.') }}
            @if ($sheet->dietary_allergy_alerts)
                <div style="margin-top: 1px; color: #b91c1c;">
                    <strong>⚠️ {{ __('تنبيه الحساسيات الغذائية (PAI):') }}</strong> {{ $sheet->dietary_allergy_alerts }}
                </div>
            @endif
        </div>

        {{-- Food Items Consumed Table --}}
        @if ($sheet->entries->isNotEmpty())
            <table class="food-table">
                <thead>
                    <tr>
                        <th style="width: 25px;">#</th>
                        <th style="width: 110px;">{{ __('الصنف الغذائي') }}</th>
                        <th style="text-align: right;">{{ __('اسم المادة الغذائية والمؤونة المخرجة من المخزن') }}</th>
                        <th style="width: 60px;">{{ __('الوحدة') }}</th>
                        <th style="width: 75px;">{{ __('الكمية المستهلكة') }}</th>
                        <th style="width: 75px;">{{ __('سعر الوحدة (دج)') }}</th>
                        <th style="width: 90px;">{{ __('المبلغ الإجمالي (دج)') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet->entries as $idx => $entry)
                        <tr>
                            <td class="center font-bold">{{ $idx + 1 }}</td>
                            <td class="center" style="color: #475569; font-size: 7.5px;">{{ $entry->foodItem?->category?->label() }}</td>
                            <td style="font-weight: 700; color: #0f172a;">{{ $entry->foodItem?->name }}</td>
                            <td class="center">{{ $entry->foodItem?->unit }}</td>
                            <td class="center mono" style="font-weight: 800; color: #047857;">{{ number_format((float) $entry->quantity_consumed, 2) }}</td>
                            <td class="center mono">{{ number_format((float) $entry->unit_price, 2) }}</td>
                            <td class="center mono" style="font-weight: 800; color: #1e3a8a;">{{ number_format((float) $entry->total_item_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 800;">
                        <td colspan="4" style="text-align: start; padding-inline-start: 6px;">{{ __('المجموع العام لمصاريف الإطعام اليومي:') }}</td>
                        <td class="center mono" style="color: #047857;">{{ number_format((float) $sheet->entries->sum('quantity_consumed'), 2) }}</td>
                        <td class="center">—</td>
                        <td class="center mono" style="color: #1e3a8a; font-size: 9.5px;">{{ number_format((float) $sheet->total_cost, 2) }} {{ __('دج') }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div style="padding: 12px; text-align: center; color: #64748b; font-weight: 600; border: 1px dashed #cbd5e1; border-radius: 6px; margin: 6px 0;">
                {{ __('لم يتم تسجيل أية مواد مستهلكة في هذه الورقة بعد.') }}
            </div>
        @endif

        {{-- Signatures Table --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('مسير مخزن المطعم (المخزني)') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('الإخراج الفعلي والمطابقة المادية') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('المقتصد / مسير المصالح المالية') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('المراقبة المحاسبية وحساب تكلفة الوجبة') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('الأمر بالصرف والمصادقة الإدارية') }}</div>
                <div class="sig-box"></div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — ورقة الاستهلاك اليومي للمطعم المدرسي') }} · {{ $sheet->reference_number }}
        </div>
    </div>
</body>
</html>
