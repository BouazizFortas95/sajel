@php
    $isRtl = app()->getLocale() === 'ar';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $trimester = $trimester ?? 1;
    $termLabel = match ($trimester) {
        2 => __('الفصل الثاني'),
        3 => __('الفصل الثالث'),
        default => __('الفصل الأول'),
    };

    // Calculate quick metrics
    $scores = array_filter(array_map(fn ($r) => $r['average'] !== '' ? (float) $r['average'] : null, $rows));
    $gradedCount = count($scores);
    $subjectAvg = $gradedCount > 0 ? round(array_sum($scores) / $gradedCount, 2) : null;
    $maxScore = $gradedCount > 0 ? max($scores) : null;
    $minScore = $gradedCount > 0 ? min($scores) : null;
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('بطاقة حجز النقاط الرسمية للمادة') }} — {{ $classroom->name }} — {{ $subject->name }}</title>

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
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 9.5px;
            line-height: 1.3;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 195mm;
            margin: 8px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 6px 14px;
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
        .btn-emerald { background: #059669; color: #ffffff; }
        .btn-emerald:hover { background: #047857; }
        .btn-slate { background: #334155; color: #f1f5f9; }

        .sheet-container {
            width: 100%;
            max-width: 195mm;
            margin: 0 auto;
            padding: 2mm 0;
            background: #ffffff;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 6px;
            line-height: 1.25;
        }
        .traditional-header .country {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
        }
        .traditional-header .direction {
            font-size: 10px;
            color: #475569;
        }

        .doc-title-box {
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 6px 10px;
            margin: 6px 0 10px 0;
            text-align: center;
            background: #f8fafc;
            position: relative;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .doc-subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 2px;
        }

        .meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .meta-item strong {
            color: #334155;
        }
        .meta-item span {
            font-weight: 800;
            color: #0f172a;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-bottom: 8px;
            text-align: center;
        }
        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            background: #ffffff;
        }
        .kpi-card .label {
            font-size: 8px;
            color: #64748b;
            font-weight: 600;
            display: block;
        }
        .kpi-card .val {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            font-family: monospace;
        }

        table.sheet-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 12px;
        }
        table.sheet-table th,
        table.sheet-table td {
            border: 1px solid #64748b;
            padding: 4px 5px;
            line-height: 1.2;
        }
        table.sheet-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
        }
        table.sheet-table td.center {
            text-align: center;
        }
        table.sheet-table td.num {
            font-family: monospace;
            font-size: 8.5px;
        }
        table.sheet-table tr:nth-child(even) td {
            background: #fafafa;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-box {
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 8px 10px;
            min-height: 75px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .sig-sub {
            font-size: 8px;
            color: #64748b;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    @if(!isset($inlineCss) || !$inlineCss)
        <div class="no-print">
            <div>
                <strong>{{ __('معاينة الطباعة الرسمية — بطاقة حجز النقاط (الرقمنة الوزارية)') }}</strong>
                <span style="opacity: 0.7; margin: 0 6px;">|</span>
                <span>{{ $classroom->name }} — {{ $subject->name }}</span>
            </div>
            <div class="btn-group">
                <a href="{{ route('print.rakmana.grade-sheet.pdf', ['locale' => app()->getLocale(), 'classroom' => $classroom->id, 'subject' => $subject->id, 'trimester' => $trimester, 'academic_year' => $yearStr]) }}" class="btn btn-emerald">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>{{ __('تحميل PDF') }}</span>
                </a>
                <button onclick="window.print()" class="btn btn-slate">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>{{ __('طباعة الورقة') }}</span>
                </button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Algerian State Header --}}
        <header class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
            <div class="direction">
                {{ __('مديرية التربية لولاية') }}: {{ $school?->directorate_name ?: '……………………' }}
                &nbsp;|&nbsp;
                {{ __('المؤسسة') }}: {{ $school?->school_name ?: 'متوسطة ……………………' }}
            </div>
        </header>

        {{-- Document Title Box --}}
        <div class="doc-title-box">
            <h1 class="doc-title">{{ __('بطاقة حجز النقاط الرسمية الموحدة للمادة') }}</h1>
            <div class="doc-subtitle">{{ __('منظومة الرقمنة الوزارية — قطاع التربية الوطنية') }} (amatti.education.gov.dz)</div>
        </div>

        {{-- Metadata Strip --}}
        <div class="meta-strip">
            <div class="meta-item">
                <strong>{{ __('السنة الدراسية') }}:</strong>
                <span>{{ $yearStr }}</span>
            </div>
            <div class="meta-item">
                <strong>{{ __('الفصل الدراسي') }}:</strong>
                <span>{{ $termLabel }}</span>
            </div>
            <div class="meta-item">
                <strong>{{ __('القسم / الفوج') }}:</strong>
                <span>{{ $classroom->name }}</span>
            </div>
            <div class="meta-item">
                <strong>{{ __('المادة التعليمية') }}:</strong>
                <span>{{ $subject->name }}</span>
            </div>
        </div>

        {{-- Pedagogical KPI Strip --}}
        <div class="kpi-strip">
            <div class="kpi-card">
                <span class="label">{{ __('إجمالي التلاميذ') }}</span>
                <span class="val">{{ $students_count }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('التلاميذ المرصودون') }}</span>
                <span class="val">{{ $gradedCount }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('معدل المادة في القسم') }}</span>
                <span class="val">{{ $subjectAvg !== null ? number_format($subjectAvg, 2) : '—' }}/20</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('أعلى علامة') }}</span>
                <span class="val">{{ $maxScore !== null ? number_format($maxScore, 2) : '—' }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('أدنى علامة') }}</span>
                <span class="val">{{ $minScore !== null ? number_format($minScore, 2) : '—' }}</span>
            </div>
        </div>

        {{-- Students Score Table --}}
        <table class="sheet-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 110px;">{{ __('رقم التعريف الوطني (NIN)') }}</th>
                    <th style="width: 65px;">{{ __('رقم التسجيل') }}</th>
                    <th>{{ __('اللقب والاسم') }}</th>
                    <th style="width: 65px;">{{ __('تاريخ الميلاد') }}</th>
                    <th style="width: 48px;">{{ __('التقويم') }}</th>
                    <th style="width: 48px;">{{ __('الفرض') }}</th>
                    <th style="width: 48px;">{{ __('الاختبار') }}</th>
                    <th style="width: 52px;">{{ __('المعدل') }}</th>
                    <th style="width: 75px;">{{ __('الملاحظة') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="center num">{{ $row['order'] }}</td>
                        <td class="center num font-bold">{{ $row['national_id_number'] }}</td>
                        <td class="center num">{{ $row['registration_number'] }}</td>
                        <td style="font-weight: 700;">{{ $row['full_name'] }}</td>
                        <td class="center num">{{ $row['date_of_birth'] }}</td>
                        <td class="center num">{{ $row['continuous'] ?: '—' }}</td>
                        <td class="center num">{{ $row['midterm'] ?: '—' }}</td>
                        <td class="center num">{{ $row['exam'] ?: '—' }}</td>
                        <td class="center num" style="font-weight: 900; background: #f8fafc;">
                            {{ $row['average'] !== '' ? number_format((float) $row['average'], 2) : '—' }}
                        </td>
                        <td class="center" style="font-size: 8px;">{{ $row['appreciation'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center" style="padding: 20px; color: #94a3b8;">
                            {{ __('لا يوجد تلاميذ مسجلين في هذا القسم.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures & Legal Visas --}}
        <div class="signatures-grid">
            <div class="sig-box">
                <div>
                    <div class="sig-title">{{ __('أستاذ(ة) المادة') }}</div>
                    <div class="sig-sub">{{ __('الاسم واللقب') }}: {{ $teacher?->name ?: '……………………………' }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <span class="sig-sub">{{ __('التوقيع والتأشيرة') }}:</span>
                    <span class="sig-sub">{{ __('حرر بتاريخ') }}: {{ $generatedAt->format('Y/m/d') }}</span>
                </div>
            </div>

            <div class="sig-box">
                <div>
                    <div class="sig-title">{{ __('مدير المؤسسة — المصادقة والاعتماد') }}</div>
                    <div class="sig-sub">{{ __('الاسم واللقب والصفة') }}: ……………………………</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <span class="sig-sub">{{ __('الختم والتوقيع الرسمي') }}:</span>
                    <span class="sig-sub">amatti.education.gov.dz</span>
                </div>
            </div>
        </div>
    </div>

    @if(isset($autoPrint) && $autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
