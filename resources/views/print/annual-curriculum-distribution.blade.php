@php
    $isRtl = true;
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'التوزيع السنوي لمادة المعلوماتية' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

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
            margin: 5mm 6mm 5mm 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: 'Amiri', 'Cairo', serif;
            font-size: 8pt;
            line-height: 1.1;
            direction: rtl;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 198mm;
            margin: 4px auto 8px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
            font-size: 10.5px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .level-switcher {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-print .btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: 'Cairo', sans-serif;
        }

        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-emerald { background: #10b981; color: #ffffff; }
        .btn-emerald:hover { background: #059669; }
        .btn-slate { background: #334155; color: #f1f5f9; }
        .btn-slate:hover { background: #475569; }

        .sheet {
            width: 100%;
            max-width: 198mm;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
            background: #ffffff;
        }

        /* Document Header */
        .doc-title {
            text-align: center;
            font-size: 14.5pt;
            font-weight: 700;
            margin-bottom: 2px;
            font-family: 'Amiri', serif;
            line-height: 1.1;
        }

        .meta-grid {
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
            font-weight: 700;
            margin-bottom: 2px;
            line-height: 1.2;
            padding: 0 4px;
        }

        .meta-col {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .meta-col-right {
            text-align: right;
        }

        .meta-col-left {
            text-align: left;
        }

        /* The Main Table */
        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000000;
            font-size: 8pt;
            text-align: center;
        }

        .distribution-table th,
        .distribution-table td {
            border: 1px solid #000000;
            padding: 3.2px 2px;
            vertical-align: middle;
        }

        .distribution-table thead th {
            font-weight: 700;
            font-size: 8.2pt;
            background-color: #ffffff;
            color: #000000;
            padding: 2.5px 1px;
        }

        /* Vertical Rotated Text for Domain & Competence */
        .vertical-cell {
            vertical-align: middle;
            text-align: center;
            padding: 2px 1px !important;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 7.2pt;
            font-weight: 700;
            display: inline-block;
            margin: 0 auto;
            line-height: 1.1;
        }

        .col-domain {
            width: 28px;
        }

        .col-domain .vertical-text {
            white-space: nowrap;
            font-size: 8pt;
        }

        .col-competence {
            width: 36px;
            max-width: 38px;
        }

        .col-competence .vertical-text {
            font-size: 6.8pt;
            line-height: 1.05;
        }

        .col-sequence { width: 85px; font-weight: 700; font-size: 8pt; }
        .col-lesson { text-align: center; font-size: 8pt; }
        .col-hours { width: 45px; font-size: 7.5pt; white-space: nowrap; }
        .col-date { width: 120px; font-size: 7.5pt; white-space: nowrap; }
        .col-week { width: 26px; font-size: 7.8pt; font-weight: 700; }

        /* Highlighted Lesson Cells */
        .cell-exam-red {
            background-color: #c00000 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        .cell-remedial-yellow {
            background-color: #facc15 !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }

        .break-cell-vacation {
            color: #c00000;
            font-weight: 700;
            font-size: 7.8pt;
            text-align: center;
            padding: 3px 2px !important;
        }

        .break-cell-exam {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 7.8pt;
            text-align: center;
            padding: 3px 2px !important;
        }

        /* Signatures Section */
        .signatures-grid {
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            font-weight: 700;
            padding: 0 15px;
        }

        .signature-col {
            text-align: center;
            width: 140px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body, html {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .sheet {
                padding: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="level-switcher">
            <span style="opacity: 0.8; margin-left: 4px;">{{ __('المستوى:') }}</span>
            @foreach ($available_levels as $lvl => $label)
                <a
                    href="{{ route('print.annual-curriculum-distribution', ['locale' => app()->getLocale(), 'level' => $lvl, 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                    class="btn {{ ($level_number ?? 1) == $lvl ? 'btn-amber' : 'btn-slate' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="btn-group">
            <a
                href="{{ route('print.annual-curriculum-distribution.pdf', ['locale' => app()->getLocale(), 'level' => $level_number ?? 1, 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                class="btn btn-emerald"
            >
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('تحميل PDF') }}
            </a>
            <button type="button" onclick="window.print()" class="btn btn-amber">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.83h10.56M6.72 17.25h10.56M6.75 6.75h10.5v3.75H6.75V6.75Zm-1.5 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v5.25a1.5 1.5 0 0 1-1.5 1.5h-1.5v-3H6.75v3h-1.5a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                {{ __('طباعة A4') }}
            </button>
            <a href="{{ route('filament.auth.pages.curriculum-progress') }}" class="btn btn-slate">
                {{ __('العودة إلى تقدم المنهاج') }}
            </a>
        </div>
    </div>

    <div class="sheet">
        {{-- Document Header --}}
        <div class="doc-title">{{ $title }}</div>

        <div class="meta-grid">
            <div class="meta-col meta-col-right">
                <div>{{ $school_name }}</div>
                <div>{{ $subject_name }}</div>
                <div>الحجم الساعي: {{ $weekly_hours }}</div>
            </div>

            <div class="meta-col meta-col-left">
                <div>الأستاذ: {{ $teacher_name }}</div>
                <div>السنة الدراسية: {{ $academic_year }}</div>
                <div>معامل المادة: {{ $coefficient }}</div>
            </div>
        </div>

        {{-- Main Annual Progression Table --}}
        <table class="distribution-table">
            <thead>
                <tr>
                    <th class="col-domain">الميدان</th>
                    <th class="col-competence">الكفاءة الختامية</th>
                    <th class="col-sequence">المقطع التعلمي</th>
                    <th class="col-lesson">الوضعية التعلمية (الدرس)</th>
                    <th class="col-hours">الحجم الساعي</th>
                    <th class="col-date">التاريخ</th>
                    <th class="col-week">الأسابيع</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($domains as $domain)
                    @foreach ($domain['rows'] as $index => $row)
                        <tr>
                            @if ($index === 0)
                                <td rowspan="{{ $domain['rowspan'] }}" class="vertical-cell col-domain">
                                    <span class="vertical-text">{{ $domain['name'] }}</span>
                                </td>
                                <td rowspan="{{ $domain['rowspan'] }}" class="vertical-cell col-competence">
                                    <span class="vertical-text">{!! $domain['competence'] !!}</span>
                                </td>
                            @endif

                            @if (!empty($row['is_break']))
                                <td colspan="{{ $row['colspan'] ?? 5 }}" class="{{ ($row['break_type'] ?? '') === 'vacation' ? 'break-cell-vacation' : 'break-cell-exam' }}">
                                    {{ $row['text'] }}
                                </td>
                            @else
                                @if (!empty($row['sequence']))
                                    <td rowspan="{{ $row['sequence']['rowspan'] }}" class="col-sequence">
                                        {{ $row['sequence']['name'] }}
                                    </td>
                                @endif
                                <td @class([
                                    'col-lesson',
                                    'cell-exam-red' => ($row['type'] ?? '') === 'exam_red',
                                    'cell-remedial-yellow' => ($row['type'] ?? '') === 'remedial_yellow',
                                ])>
                                    {{ $row['lesson'] }}
                                </td>
                                <td class="col-hours">{{ $row['hours'] }}</td>
                                <td class="col-date">{{ $row['date'] }}</td>
                                <td class="col-week">{{ $row['week'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        {{-- Signatures Footer --}}
        <div class="signatures-grid">
            <div class="signature-col">
                <div>المفتش</div>
            </div>
            <div class="signature-col">
                <div>المدير</div>
            </div>
            <div class="signature-col">
                <div>أستاذ المادة</div>
            </div>
        </div>
    </div>
</body>
</html>