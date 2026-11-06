@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $sheetCode = 'TB-CLASS-'.$classroom->id.'-'.date('Y');
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('سجل تسليم واسترجاع الكتب المدرسية للقسم') }} — {{ $classroom->name }}</title>

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

        /* Traditional Header */
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

        /* 3-Column Meta Strip */
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

        /* Table */
        .sheet-table-wrap {
            width: 100%;
            margin-bottom: 5px;
        }
        .sheet-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            table-layout: auto;
        }
        .sheet-table th,
        .sheet-table td {
            border: 1px solid #94a3b8;
            padding: 2.5px 4px;
            line-height: 1.2;
        }
        .sheet-table thead th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            font-size: 8.5px;
        }
        .sheet-table td.center { text-align: center; }
        .sheet-table td.mono { font-family: monospace; font-size: 9px; }
        .sheet-table tbody tr:nth-child(even) { background-color: #fcfdfe; }

        /* Badges */
        .badge-cleared {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 800;
            background: #dcfce7;
            color: #166534;
        }
        .badge-borrowed {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 800;
            background: #fef3c7;
            color: #92400e;
        }

        /* Legend & Signatures Strip */
        .bottom-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1.5px solid #0f172a;
            padding-top: 4px;
            margin-top: 4px;
            font-size: 8.5px;
            color: #475569;
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

    {{-- Screen Floating Action Bar --}}
    <div class="no-print">
        <div class="title-group">
            <span style="font-size: 16px;">📋</span>
            <div>
                <strong>{{ __('استمارة تسليم واسترجاع الكتب المدرسية للقسم') }}</strong>
                <div style="font-size: 9.5px; color: #94a3b8;">{{ $classroom->name }} — {{ $yearStr }} ({{ $sheetCode }})</div>
            </div>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-amber">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الاستمارة') }}
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
            <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
        </div>

        {{-- 2. 3-Column Metadata Strip --}}
        <div class="header-meta-grid">
            <div class="col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
            </div>
            <div class="col-title">
                <div class="main-title">{{ __('استمارة تسليم واسترجاع الكتب المدرسية للقسم') }}</div>
                <div class="sub-title">{{ __('القسم') }}: <span style="background: #fef3c7; padding: 1px 6px; border-radius: 3px;">{{ $classroom->name }}</span></div>
            </div>
            <div class="col-date">
                <div>{{ __('السنة الدراسية') }}: <span class="date-val">{{ $yearStr }}</span></div>
                <div class="code-val">{{ $sheetCode }}</div>
            </div>
        </div>

        {{-- 3. Matrix Table --}}
        @if ($students->isNotEmpty() && $textbooks->isNotEmpty())
            <div class="sheet-table-wrap">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;">#</th>
                            <th style="width: 140px; text-align: start; padding-inline-start: 6px;">{{ __('اسم ولقب التلميذ') }}</th>
                            <th style="width: 50px;">{{ __('الفوج') }}</th>
                            @foreach ($textbooks as $tb)
                                <th style="font-size: 8px;" title="{{ $tb->title }}">{{ $tb->subject?->name }}</th>
                            @endforeach
                            <th style="width: 55px;">{{ __('المجموع') }}</th>
                            <th style="width: 55px;">{{ __('الوضعية') }}</th>
                            <th style="width: 65px;">{{ __('توقيع الولي') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $index => $st)
                            @php
                                $studentLoans = $loans->get($st->id, collect());
                                $borrowedCount = $studentLoans->count();
                                $returnedCount = $studentLoans->whereNotNull('returned_at')->count();
                                $allReturned = $borrowedCount > 0 && $returnedCount === $borrowedCount;
                            @endphp
                            <tr>
                                <td class="center font-bold" style="font-weight: 700; color: #475569;">{{ $index + 1 }}</td>
                                <td style="font-weight: 700; color: #0f172a; padding-inline-start: 6px;">{{ $st->full_name }}</td>
                                <td class="center" style="font-size: 8px; color: #475569;">{{ $st->group?->name ?? '—' }}</td>
                                @foreach ($textbooks as $tb)
                                    @php
                                        $loan = $studentLoans->firstWhere('textbook_id', $tb->id);
                                    @endphp
                                    <td class="center">
                                        @if ($loan)
                                            @if ($loan->returned_at)
                                                <span style="color: #047857; font-weight: 800;">✓</span>
                                            @elseif ($loan->distribution_type->value === 'solidarity')
                                                <span style="color: #b45309; font-weight: 800; font-size: 8px;">تضامن</span>
                                            @else
                                                <span style="color: #475569; font-weight: 800;">●</span>
                                            @endif
                                        @else
                                            <span style="color: #cbd5e1;">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="center mono" style="font-weight: 800; color: #0f172a;">
                                    {{ $returnedCount }} / {{ $borrowedCount }}
                                </td>
                                <td class="center">
                                    @if ($allReturned)
                                        <span class="badge-cleared">✓ بريء</span>
                                    @elseif ($borrowedCount > 0)
                                        <span class="badge-borrowed">معار</span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 8px;">—</span>
                                    @endif
                                </td>
                                <td class="center"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 20px; text-align: center; color: #64748b; font-weight: 600; border: 1px dashed #cbd5e1; border-radius: 6px; margin: 8px 0;">
                {{ __('لم يتم العثور على تلاميذ أو كتب مسجلة لهذا القسم.') }}
            </div>
        @endif

        {{-- 4. Legend & Signatures --}}
        <div class="bottom-bar">
            <div>
                <strong>{{ __('دليل الرموز:') }}</strong> ● {{ __('مستلم (معار)') }} &nbsp;•&nbsp; <span style="color: #047857; font-weight: 800;">✓</span> {{ __('مسترجع بحالة حسنة') }} &nbsp;•&nbsp; <strong style="color: #b45309;">{{ __('تضامن') }}</strong>: {{ __('حصة التضامن الوطني المجانية') }}
            </div>
            <div>
                <span>{{ __('مستشار التربية') }}: ........................</span> &nbsp;&nbsp;&nbsp;&nbsp;
                <span>{{ __('مسؤول المخزن') }}: ........................</span>
            </div>
        </div>

    </div>

</body>
</html>
