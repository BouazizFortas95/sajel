@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $printTitle = $printTitle ?? __('وثيقة رسمية');
    $printSubtitle = $printSubtitle ?? null;
    $printOrientation = $printOrientation ?? 'portrait';
    $printAcademicYear = $printAcademicYear ?? $schoolSetting->academic_year;
    $printReference = $printReference ?? __('وثيقة رسمية');
    $printMeta = $printMeta ?? null;
    $printAutoPrint = $printAutoPrint ?? ($autoPrint ?? false);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $printTitle }} — {{ $schoolSetting->school_name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 {{ $printOrientation }};
            margin: 8mm 12mm 6mm 12mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #ffffff;
            color: #0f172a;
            font-family: 'Amiri', 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
        }

        h1,
        h2,
        h3,
        h4,
        p {
            margin: 0;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 8px 16px;
            border-bottom: 1px solid #cbd5e1;
            background: #f8fafc;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        }

        .print-toolbar__inner {
            display: flex;
            max-width: 210mm;
            margin: 0 auto;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .print-toolbar__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .print-toolbar__mark {
            display: grid;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 6px;
            background: #1a237e;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }

        .print-toolbar__title {
            overflow: hidden;
            color: #1a237e;
            font-size: 13px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .print-toolbar__subtitle {
            overflow: hidden;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .print-button {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 8px;
            border: 1px solid #0d47a1;
            border-radius: 6px;
            background: #1a237e;
            padding: 6px 16px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
        }

        .print-button:hover {
            background: #0d47a1;
        }

        .document-sheet {
            position: relative;
            width: 210mm;
            max-width: 100%;
            margin: 0 auto 20px;
            padding: 6mm 8mm;
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            background-color: #ffffff;
            box-shadow: none;
        }

        .print-landscape .document-sheet {
            width: 277mm;
        }

        .print-landscape .print-toolbar__inner {
            max-width: 277mm;
        }

        .doc-header {
            overflow: hidden;
            border: 1px solid #1a237e;
            border-bottom: 2px double #1a237e;
            border-radius: 4px;
            background: #ffffff;
            box-shadow: none;
        }

        .doc-header-grid {
            display: grid;
            grid-template-columns: 1fr 1.35fr 1fr;
            gap: 0;
            align-items: stretch;
        }

        .doc-header-col {
            padding: 10px 12px;
        }

        .doc-header-col + .doc-header-col {
            border-inline-start: 1px solid #1a237e;
        }

        .doc-header-republic {
            color: #1a237e;
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.5;
        }

        .doc-header-republic .ministry {
            color: #334155;
            font-weight: 700;
        }

        .doc-header-republic .directorate {
            margin-top: 3px;
            color: #1a237e;
            font-size: 10.5pt;
            font-weight: 700;
        }

        .doc-header-school {
            text-align: center;
        }

        .doc-school-name {
            color: #1a237e;
            font-size: 13pt;
            font-weight: 700;
            line-height: 1.3;
        }

        .doc-school-meta {
            margin-top: 2px;
            color: #64748b;
            font-size: 8.5pt;
            font-weight: 600;
        }

        .doc-header-meta {
            color: #475569;
            font-size: 8.5pt;
            font-weight: 600;
            line-height: 1.6;
        }

        .doc-header-meta strong {
            display: block;
            color: #0f172a;
            font-size: 10pt;
        }

        .doc-title-banner {
            margin-top: 6px;
            border: 2px solid #1a237e;
            border-radius: 4px;
            background: #f0f4fa;
            padding: 4px 14px;
            color: #1a237e;
            text-align: center;
            font-size: 12.5pt;
            font-weight: 700;
            letter-spacing: 0;
            text-shadow: none;
        }

        .doc-subtitle {
            margin-top: 3px;
            color: #475569;
            font-size: 8.5pt;
            font-weight: 600;
        }

        .report-section {
            margin-top: 8px;
            break-inside: avoid;
            page-break-inside: avoid;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #ffffff;
            padding: 8px 10px;
            box-shadow: none;
        }

        .report-section--flow {
            break-inside: auto;
            page-break-inside: auto;
        }

        .section-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 6px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #1a237e;
            font-size: 10.5pt;
            font-weight: 700;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 14px;
            border-radius: 1px;
            background: #1a237e;
        }

        .identity-card {
            margin-top: 8px;
            border: 1px solid #1a237e;
            border-radius: 4px;
            background: #f8fafc;
            padding: 8px 10px;
        }

        .identity-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 6px;
        }

        .identity-item {
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            background: #ffffff;
            padding: 4px 8px;
        }

        .identity-label {
            color: #1a237e;
            font-size: 8pt;
            font-weight: 700;
        }

        .identity-value {
            margin-top: 1px;
            color: #0f172a;
            font-size: 9.5pt;
            font-weight: 700;
            line-height: 1.3;
            word-break: break-word;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 6px;
            text-align: center;
        }

        .kpi-card {
            border: 1px solid #475569;
            border-radius: 2px;
            background: #ffffff;
            padding: 4px 6px;
        }

        .kpi-card__label {
            color: #334155;
            font-size: 8pt;
            font-weight: 700;
        }

        .kpi-card__value {
            margin-top: 1px;
            color: #1a237e;
            font-size: 14pt;
            font-weight: 700;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .kpi-card__hint {
            margin-top: 1px;
            color: #64748b;
            font-size: 7.5pt;
            font-weight: 600;
        }

        .kpi--emerald {
            border-color: #475569;
            background: #f8fafc;
        }

        .kpi--emerald .kpi-card__value {
            color: #065f46;
        }

        .kpi--rose {
            border-color: #475569;
            background: #f8fafc;
        }

        .kpi--rose .kpi-card__value {
            color: #991b1b;
        }

        .kpi--amber {
            border-color: #475569;
            background: #f8fafc;
        }

        .kpi--amber .kpi-card__value {
            color: #92400e;
        }

        .kpi--sky {
            border-color: #475569;
            background: #f8fafc;
        }

        .kpi--sky .kpi-card__value {
            color: #0369a1;
        }

        .kpi--slate {
            border-color: #475569;
            background: #f8fafc;
        }

        .kpi--slate .kpi-card__value {
            color: #334155;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #475569;
            border-radius: 2px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            color: #0f172a;
            font-size: 8.5pt;
        }

        .report-table th {
            border: 1px solid #475569;
            background: #1a237e;
            padding: 4px 6px;
            color: #fff;
            text-align: start;
            font-size: 8.5pt;
            font-weight: 700;
            white-space: nowrap;
        }

        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .subject-card {
            margin-top: 6px;
            border: 1px solid #475569;
            border-radius: 2px;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .subject-card__head {
            background: #f1f5f9;
            border-bottom: 1px solid #475569;
            padding: 4px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .subject-card__title {
            color: #1a237e;
            font-size: 9.5pt;
            font-weight: 700;
        }

        .subject-card__average {
            color: #0f172a;
            font-size: 9pt;
            font-weight: 700;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-box {
            border: 1px solid #475569;
            border-radius: 2px;
            background: #ffffff;
            padding: 6px;
            text-align: center;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .signature-box strong {
            color: #1a237e;
            font-size: 8.5pt;
            font-weight: 700;
        }

        .signature-box span {
            color: #64748b;
            font-size: 7.5pt;
        }

        .signature-line {
            height: 24px;
        }

        .report-table tbody tr:nth-child(even) {
            background: rgba(255, 251, 235, .45);
        }

        .report-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .subject-card {
            margin-top: 8px;
            break-inside: avoid;
            overflow: hidden;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
        }

        .subject-card:first-of-type {
            margin-top: 0;
        }

        .subject-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: linear-gradient(90deg, rgba(255, 251, 235, .95), rgba(254, 243, 199, .55));
            padding: 7px 10px;
        }

        .subject-card__title {
            color: #78350f;
            font-size: 11px;
            font-weight: 900;
        }

        .subject-card__average {
            border-radius: 999px;
            background: #d97706;
            padding: 2px 9px;
            color: #fff;
            font-size: 9px;
            font-weight: 900;
        }

        .subject-card .report-table th {
            background: #92400e;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: 8px;
            font-weight: 800;
            line-height: 1.5;
            white-space: nowrap;
        }

        .badge-success,
        .badge-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .badge-danger,
        .badge-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #be123c;
        }

        .badge-warning,
        .badge-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #b45309;
        }

        .badge-info,
        .badge-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0369a1;
        }

        .badge-primary {
            border-color: #fcd34d;
            background: #fef3c7;
            color: #92400e;
        }

        .badge-gray {
            border-color: #e7e5e4;
            background: #f5f5f4;
            color: #57534e;
        }

        .honor-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 9px;
        }

        .honor-strip .badge {
            padding: 4px 9px;
            font-size: 9px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 2px solid #fbbf24;
            text-align: center;
            break-inside: avoid;
        }

        .signature-box {
            min-height: 64px;
            border: 1px dashed #d6d3d1;
            border-radius: 10px;
            background: rgba(255, 255, 255, .65);
            padding: 8px;
        }

        .signature-box strong {
            color: #78350f;
            font-size: 10px;
            font-weight: 900;
        }

        .signature-box span {
            display: block;
            margin-top: 2px;
            color: #78716c;
            font-size: 9px;
            font-weight: 600;
        }

        .signature-line {
            margin: 24px auto 0;
            width: 70%;
            border-top: 1px dotted #a8a29e;
        }

        .doc-footer {
            margin-top: 12px;
            border-top: 1px solid #e7e5e4;
            padding-top: 6px;
            color: #a8a29e;
            text-align: center;
            font-size: 8px;
            font-weight: 600;
        }

        .muted {
            color: #78716c;
        }

        .text-center {
            text-align: center;
        }

        .qr-card {
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            background: #fffbeb;
            padding: 10px;
        }

        .qr-card svg {
            width: 76px;
            height: 76px;
            flex: 0 0 auto;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            background: #fff;
            padding: 5px;
        }

        .qr-card__url {
            margin-top: 3px;
            color: #57534e;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 8px;
            line-height: 1.5;
            word-break: break-all;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .info-list {
            display: grid;
            gap: 4px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px dashed #e7e5e4;
            padding-bottom: 4px;
            font-size: 10px;
        }

        .info-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .info-row dt {
            color: #78716c;
            font-weight: 700;
        }

        .info-row dd {
            margin: 0;
            color: #1e293b;
            text-align: end;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .doc-header-grid {
                grid-template-columns: 1fr;
            }

            .doc-header-col + .doc-header-col {
                border-inline-start: 0;
                border-top: 1px dashed #fcd34d;
            }

            .doc-header-school {
                text-align: start;
            }

            .identity-grid,
            .data-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .kpi-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .signature-grid {
                grid-template-columns: 1fr;
            }

            .print-toolbar__inner {
                align-items: flex-start;
            }
        }

        @media print {
            html,
            body {
                background: #fffdf8 !important;
            }

            .print-toolbar,
            .no-print {
                display: none !important;
            }

            .document-sheet {
                width: auto !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fffdf8 !important;
                box-shadow: none !important;
            }

            .doc-header,
            .report-section,
            .identity-card,
            .subject-card,
            .signature-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .report-section--flow {
                break-inside: auto !important;
                page-break-inside: auto !important;
            }

            .report-section--flow .table-wrap {
                overflow: visible !important;
                break-inside: auto !important;
                page-break-inside: auto !important;
            }
            .deliberation-section,
            .table-wrapper,
            .report-section--flow .table-wrap {
                break-inside: auto !important;
                page-break-inside: auto !important;
                overflow: visible !important;
            }
            .deliberation-section thead,
            .report-section--flow thead {
                display: table-header-group !important;
            }
            .deliberation-section tr,
            .report-table tbody tr {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .doc-header-grid {
                grid-template-columns: 1fr 1.35fr 1fr !important;
            }

            .doc-header-school {
                text-align: center !important;
            }

            .identity-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }

            .kpi-strip {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }

            .signature-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="print-canvas {{ $printOrientation === 'landscape' ? 'print-landscape' : '' }}">
    <div class="print-toolbar no-print print:hidden">
        <div class="print-toolbar__inner">
            <div class="print-toolbar__brand">
                <span class="print-toolbar__mark">س</span>
                <div>
                    <div class="print-toolbar__title">{{ $printTitle }}</div>
                    <div class="print-toolbar__subtitle">{{ $schoolSetting->school_name }} · {{ $schoolSetting->academic_year }}</div>
                </div>
            </div>
            <button type="button" class="print-button" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.83h10.56M6.72 17.25h10.56M6.75 6.75h10.5v3.75H6.75V6.75Zm-1.5 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v5.25a1.5 1.5 0 0 1-1.5 1.5h-1.5v-3H6.75v3h-1.5a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z" />
                </svg>
                {{ __('طباعة / حفظ PDF') }}
            </button>
        </div>
    </div>

    <main class="document-sheet" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <header class="doc-header">
            <div class="doc-header-grid">
                <div class="doc-header-col doc-header-republic">
                    <p>{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                    <p class="ministry">{{ __('وزارة التربية الوطنية') }}</p>
                    <p class="directorate">{{ $schoolSetting->directorate_name }}</p>
                </div>

                <div class="doc-header-col doc-header-school">
                    <p class="doc-school-name">{{ $schoolSetting->school_name }}</p>
                    <p class="doc-school-meta">
                        {{ $schoolSetting->municipality_name ?: __('المؤسسة التربوية') }}
                        @if ($schoolSetting->school_code)
                            · {{ __('الرقم الميكانيكي') }}: <span dir="ltr">{{ $schoolSetting->school_code }}</span>
                        @endif
                    </p>
                    <div class="doc-title-banner">{{ $printTitle }}</div>
                    @if ($printSubtitle)
                        <p class="doc-subtitle">{{ $printSubtitle }}</p>
                    @endif
                </div>

                <div class="doc-header-col doc-header-meta">
                    <p>{{ __('السنة الدراسية') }}</p>
                    <strong>{{ $printAcademicYear }}</strong>
                    <p>{{ __('تاريخ الإصدار') }}</p>
                    <strong>{{ now(config('app.timezone', 'Africa/Algiers'))->format('Y-m-d') }}</strong>
                    <p>{{ $printReference }}</p>
                    @if ($printMeta)
                        <strong>{{ $printMeta }}</strong>
                    @endif
                </div>
            </div>
        </header>

        @if ($printAutoPrint)
            <script>
                window.addEventListener('load', () => {
                    window.setTimeout(() => window.print(), 350);
                });
            </script>
        @endif
