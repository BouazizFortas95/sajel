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

    $rows = $matrix['rows'] ?? [];
    $subjects = $matrix['subjects'] ?? [];
    $studentsCount = $matrix['students_count'] ?? count($rows);
    $gradedCount = $matrix['graded_count'] ?? 0;
    $passCount = $matrix['pass_count'] ?? 0;
    $passRate = $matrix['pass_rate'] ?? 0.0;
    $highestAvg = $matrix['highest_average'] ?? null;
    $lowestAvg = $matrix['lowest_average'] ?? null;
    $classGeneralAvg = $matrix['class_general_average'] ?? null;
    $honorCount = $matrix['honor_count'] ?? 0;
    $brackets = $matrix['brackets'] ?? [];
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('محضر مداولات مجلس القسم الرسمي') }} — {{ $classroom->name }} — {{ $termLabel }}</title>

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
            margin: 6mm 6mm 6mm 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 8px;
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
            max-width: 285mm;
            margin: 0 auto;
            padding: 2mm 0;
            background: #ffffff;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        .traditional-header .country {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
        }
        .traditional-header .direction {
            font-size: 9px;
            color: #475569;
        }

        .doc-title-box {
            border: 1.5px solid #0f172a;
            border-radius: 5px;
            padding: 4px 10px;
            margin: 4px 0 6px 0;
            text-align: center;
            background: #f8fafc;
        }
        .doc-title {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
        }
        .doc-subtitle {
            font-size: 9px;
            font-weight: 700;
            color: #2563eb;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
            margin-bottom: 6px;
            text-align: center;
        }
        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 4px;
            background: #ffffff;
        }
        .kpi-card .label {
            font-size: 7.5px;
            color: #64748b;
            font-weight: 600;
            display: block;
        }
        .kpi-card .val {
            font-size: 10.5px;
            font-weight: 900;
            color: #0f172a;
            font-family: monospace;
        }

        table.deliberation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-bottom: 6px;
        }
        table.deliberation-table th,
        table.deliberation-table td {
            border: 1px solid #475569;
            padding: 2.5px 3px;
            line-height: 1.15;
        }
        table.deliberation-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
        }
        table.deliberation-table td.center {
            text-align: center;
        }
        table.deliberation-table td.num {
            font-family: monospace;
            font-size: 7.5px;
        }
        table.deliberation-table tr:nth-child(even) td {
            background: #fafafa;
        }

        .decision-tag {
            font-size: 7px;
            font-weight: 700;
            padding: 1px 3px;
            border-radius: 2px;
            display: inline-block;
        }
        .tag-excellence { background: #dcfce7; color: #166534; }
        .tag-encouragement { background: #e0f2fe; color: #075985; }
        .tag-honor_roll { background: #fef3c7; color: #92400e; }
        .tag-passed { background: #f1f5f9; color: #334155; }
        .tag-warning { background: #ffedd5; color: #9a3412; }
        .tag-blame { background: #ffe4e6; color: #9f1239; }
        .tag-pending { background: #f3f4f6; color: #6b7280; }

        .brackets-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 8px;
            margin-bottom: 8px;
            font-size: 8px;
        }
        .brackets-bar .item strong {
            color: #0f172a;
            font-family: monospace;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 6px;
            page-break-inside: avoid;
        }
        .sig-box {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 5px 6px;
            min-height: 55px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 7.5px;
        }
        .sig-title {
            font-size: 8px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }
        .sig-sub {
            font-size: 7px;
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
                <strong>{{ __('معاينة الطباعة الرسمية — محضر مداولات مجلس القسم (الرقمنة الوزارية)') }}</strong>
                <span style="opacity: 0.7; margin: 0 6px;">|</span>
                <span>{{ $classroom->name }} — {{ $termLabel }} ({{ $yearStr }})</span>
            </div>
            <div class="btn-group">
                <a href="{{ route('print.rakmana.deliberation-sheet.pdf', ['locale' => app()->getLocale(), 'classroom' => $classroom->id, 'trimester' => $trimester, 'academic_year' => $yearStr]) }}" class="btn btn-emerald">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>{{ __('تحميل PDF') }}</span>
                </a>
                <button onclick="window.print()" class="btn btn-slate">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>{{ __('طباعة المحضر') }}</span>
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
            <h1 class="doc-title">{{ __('محضر مداولات مجلس القسم الفصلي الرسمي') }} — {{ $classroom->name }}</h1>
            <div class="doc-subtitle">{{ __('منظومة الرقمنة الوزارية لقطاع التربية الوطنية') }} (amatti.education.gov.dz) — {{ $termLabel }} ({{ $yearStr }})</div>
        </div>

        {{-- Pedagogical KPI Strip --}}
        <div class="kpi-strip">
            <div class="kpi-card">
                <span class="label">{{ __('إجمالي التلاميذ') }}</span>
                <span class="val">{{ $studentsCount }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('المرصودون') }}</span>
                <span class="val">{{ $gradedCount }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('الناجحون (>=10)') }}</span>
                <span class="val" style="color: #059669;">{{ $passCount }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('نسبة النجاح') }}</span>
                <span class="val" style="color: #059669;">{{ number_format($passRate, 1) }}%</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('معدل القسم العام') }}</span>
                <span class="val">{{ $classGeneralAvg !== null ? number_format($classGeneralAvg, 2) : '—' }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('أعلى معدل') }}</span>
                <span class="val" style="color: #059669;">{{ $highestAvg !== null ? number_format($highestAvg, 2) : '—' }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('أدنى معدل') }}</span>
                <span class="val" style="color: #e11d48;">{{ $lowestAvg !== null ? number_format($lowestAvg, 2) : '—' }}</span>
            </div>
            <div class="kpi-card">
                <span class="label">{{ __('لوحة الشرف') }}</span>
                <span class="val" style="color: #d97706;">{{ $honorCount }}</span>
            </div>
        </div>

        {{-- Comprehensive Deliberation Table --}}
        <table class="deliberation-table">
            <thead>
                <tr>
                    <th style="width: 20px;">{{ __('رتبة') }}</th>
                    <th style="width: 18px;">#</th>
                    <th style="width: 75px;">{{ __('رقم التعريف (NIN)') }}</th>
                    <th style="min-width: 90px;">{{ __('اللقب والاسم') }}</th>
                    <th style="width: 48px;">{{ __('تاريخ الميلاد') }}</th>
                    <th style="width: 35px;">{{ __('الصفة') }}</th>
                    @foreach($subjects as $subj)
                        <th style="width: 32px;" title="{{ $subj['name'] }}">
                            <div>{{ mb_substr($subj['name'], 0, 7) }}</div>
                            <div style="font-size: 6.5px; opacity: 0.8;">({{ $subj['coefficient'] }})</div>
                        </th>
                    @endforeach
                    <th style="width: 38px;">{{ __('المجموع') }}</th>
                    <th style="width: 38px; background: #e2e8f0;">{{ __('المعدل') }}</th>
                    <th style="width: 70px;">{{ __('قرار المجلس') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="center num font-bold">{{ $row['rank'] }}</td>
                        <td class="center num text-gray-500">{{ $row['order'] }}</td>
                        <td class="center num">{{ $row['national_id_number'] }}</td>
                        <td style="font-weight: 700;">
                            {{ $row['full_name'] }}
                            @if($row['is_repeater'])
                                <span style="font-size: 6.5px; color: #dc2626; font-weight: 800;">({{ __('معيد') }})</span>
                            @endif
                        </td>
                        <td class="center num">{{ $row['date_of_birth'] }}</td>
                        <td class="center" style="font-size: 7px;">{{ $row['regime'] }}</td>

                        @foreach($subjects as $subj)
                            @php
                                $sc = $row['subject_grades'][$subj['id']]['score'] ?? null;
                            @endphp
                            <td class="center num" style="{{ $sc !== null ? ($sc < 10 ? 'color: #dc2626;' : 'font-weight: 600;') : 'color: #94a3b8;' }}">
                                {{ $sc !== null ? number_format((float) $sc, 2) : '—' }}
                            </td>
                        @endforeach

                        <td class="center num font-bold">
                            {{ $row['total_points'] !== null ? number_format((float) $row['total_points'], 2) : '—' }}
                        </td>
                        <td class="center num font-black" style="background: #f8fafc; font-size: 8px; {{ $row['general_average'] !== null ? ($row['general_average'] >= 10 ? 'color: #166534;' : 'color: #991b1b;') : '' }}">
                            {{ $row['general_average'] !== null ? number_format((float) $row['general_average'], 2) : '—' }}
                        </td>
                        <td class="center">
                            <span class="decision-tag tag-{{ $row['decision_class'] }}">{{ $row['decision'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 9 + count($subjects) }}" class="center" style="padding: 20px; color: #94a3b8;">
                            {{ __('لا يوجد تلاميذ مسجلين في هذا القسم.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Brackets Breakdown Bar --}}
        <div class="brackets-bar">
            <span class="item">{{ __('تهنئة (>=16)') }}: <strong>{{ $brackets['excellence'] ?? 0 }}</strong></span>
            <span class="item">{{ __('تشجيع (14-15.99)') }}: <strong>{{ $brackets['encouragement'] ?? 0 }}</strong></span>
            <span class="item">{{ __('لوحة شرف (12-13.99)') }}: <strong>{{ $brackets['honor_roll'] ?? 0 }}</strong></span>
            <span class="item">{{ __('متوسط (10-11.99)') }}: <strong>{{ $brackets['average'] ?? 0 }}</strong></span>
            <span class="item">{{ __('تنبيه (8-9.99)') }}: <strong>{{ $brackets['warning'] ?? 0 }}</strong></span>
            <span class="item">{{ __('توبيخ (<8)') }}: <strong>{{ $brackets['blame'] ?? 0 }}</strong></span>
        </div>

        {{-- Council Quadruple Signatures & Stamps --}}
        <div class="signatures-grid">
            <div class="sig-box">
                <div class="sig-title">{{ __('الأستاذ(ة) الرئيسي(ة) للقسم') }}</div>
                <div class="sig-sub">{{ __('الاسم واللقب') }}: ……………………………</div>
                <div class="sig-sub">{{ __('التوقيع') }}:</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">{{ __('مستشار(ة) التربية') }}</div>
                <div class="sig-sub">{{ __('الاسم واللقب') }}: ……………………………</div>
                <div class="sig-sub">{{ __('التوقيع والتأشيرة') }}:</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">{{ __('مستشار(ة) التوجيه والإرشاد') }}</div>
                <div class="sig-sub">{{ __('الاسم واللقب') }}: ……………………………</div>
                <div class="sig-sub">{{ __('التوقيع') }}:</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">{{ __('رئيس المجلس — مدير المؤسسة') }}</div>
                <div class="sig-sub">{{ __('المصادقة والاعتماد النهائي') }}</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <span class="sig-sub">{{ __('الختم والتوقيع') }}:</span>
                    <span class="sig-sub">{{ $generatedAt->format('Y/m/d') }}</span>
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
