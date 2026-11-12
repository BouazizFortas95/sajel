@php
    $isRtl = true;
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'التوزيع السنوي للسنة الأولى من التعليم المتوسط' }}</title>

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
            font-size: 8.2pt;
            line-height: 1.15;
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

        .no-print .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            font-size: 15.5pt;
            font-weight: 700;
            margin-bottom: 2px;
            font-family: 'Amiri', serif;
            line-height: 1.1;
        }

        .meta-grid {
            display: flex;
            justify-content: space-between;
            font-size: 8.8pt;
            font-weight: 700;
            margin-bottom: 3px;
            line-height: 1.25;
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
            font-size: 8.2pt;
            text-align: center;
        }

        .distribution-table th,
        .distribution-table td {
            border: 1px solid #000000;
            padding: 5.2px 2px;
            vertical-align: middle;
        }

        .distribution-table thead th {
            font-weight: 700;
            font-size: 8.5pt;
            background-color: #ffffff;
            color: #000000;
            padding: 3.5px 1px;
        }

        /* Vertical Rotated Text for Domain & Competence */
        .vertical-cell {
            vertical-align: middle;
            text-align: center;
            padding: 2px 0 !important;
            width: 28px;
            max-width: 32px;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            font-size: 8pt;
            font-weight: 700;
            display: inline-block;
            margin: 0 auto;
            line-height: 1;
        }

        .col-domain { width: 28px; }
        .col-competence { width: 34px; }
        .col-sequence { width: 85px; font-weight: 700; font-size: 8.2pt; }
        .col-lesson { text-align: center; font-size: 8.2pt; }
        .col-hours { width: 45px; font-size: 7.8pt; white-space: nowrap; }
        .col-date { width: 120px; font-size: 7.8pt; white-space: nowrap; }
        .col-week { width: 26px; font-size: 8pt; font-weight: 700; }

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
            font-size: 8pt;
            text-align: center;
            padding: 5.2px 2px !important;
        }

        .break-cell-exam {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 8pt;
            text-align: center;
            padding: 5.2px 2px !important;
        }

        /* Signatures Section */
        .signatures-grid {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
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
        <div>
            <strong>التوزيع السنوي للتعلمات</strong>
            <span style="opacity: 0.7; margin: 0 6px;">|</span>
            <span>{{ $level_name }} — {{ $subject_name }}</span>
        </div>

        <div class="btn-group">
            <button type="button" onclick="window.print()" class="btn btn-amber">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.83h10.56M6.72 17.25h10.56M6.75 6.75h10.5v3.75H6.75V6.75Zm-1.5 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v5.25a1.5 1.5 0 0 1-1.5 1.5h-1.5v-3H6.75v3h-1.5a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                طباعة A4
            </button>
            <a href="{{ route('filament.auth.pages.curriculum-progress') }}" class="btn btn-slate">
                العودة إلى تقدم المنهاج
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
                {{-- Domain 1: استخدام الحاسوب --}}
                @php
                    $d1 = $domains[0];
                    $d1Seq1 = $d1['sequences'][0];
                    $d1Seq2 = $d1['sequences'][1] ?? null;
                @endphp
                {{-- Row 1: First item of Seq 1 with Domain 1 & Competence 1 spanned --}}
                <tr>
                    <td rowspan="{{ $d1['rowspan'] }}" class="vertical-cell col-domain">
                        <span class="vertical-text">{{ $d1['name'] }}</span>
                    </td>
                    <td rowspan="{{ $d1['rowspan'] }}" class="vertical-cell col-competence">
                        <span class="vertical-text">{{ $d1['competence'] }}</span>
                    </td>
                    <td rowspan="{{ $d1Seq1['rowspan'] }}" class="col-sequence">{{ $d1Seq1['name'] }}</td>
                    <td class="col-lesson">{{ $d1Seq1['items'][0]['lesson'] }}</td>
                    <td class="col-hours">{{ $d1Seq1['items'][0]['hours'] }}</td>
                    <td class="col-date">{{ $d1Seq1['items'][0]['date'] }}</td>
                    <td class="col-week">{{ $d1Seq1['items'][0]['week'] }}</td>
                </tr>
                {{-- Rows 2 to 5 of Sequence 1 (بيئة التعامل مع الحاسوب) --}}
                @foreach (array_slice($d1Seq1['items'], 1) as $item)
                    <tr>
                        <td class="col-lesson">{{ $item['lesson'] }}</td>
                        <td class="col-hours">{{ $item['hours'] }}</td>
                        <td class="col-date">{{ $item['date'] }}</td>
                        <td class="col-week">{{ $item['week'] }}</td>
                    </tr>
                @endforeach

                {{-- Sequence 2: نظام التشغيل (contains مفهوم نظام التشغيل, break, remaining items) --}}
                @if ($d1Seq2)
                    <tr>
                        <td rowspan="{{ $d1Seq2['rowspan'] }}" class="col-sequence">{{ $d1Seq2['name'] }}</td>
                        <td class="col-lesson">{{ $d1Seq2['first_item']['lesson'] }}</td>
                        <td class="col-hours">{{ $d1Seq2['first_item']['hours'] }}</td>
                        <td class="col-date">{{ $d1Seq2['first_item']['date'] }}</td>
                        <td class="col-week">{{ $d1Seq2['first_item']['week'] }}</td>
                    </tr>
                    @if (!empty($d1Seq2['break']))
                        <tr>
                            <td colspan="4" class="break-cell-vacation">
                                {{ $d1Seq2['break']['text'] }}
                            </td>
                        </tr>
                    @endif
                    @foreach ($d1Seq2['remaining_items'] as $item)
                        <tr>
                            <td @class(['col-lesson', 'cell-exam-red' => $item['type'] === 'exam_red', 'cell-remedial-yellow' => $item['type'] === 'remedial_yellow'])>
                                {{ $item['lesson'] }}
                            </td>
                            <td class="col-hours">{{ $item['hours'] }}</td>
                            <td class="col-date">{{ $item['date'] }}</td>
                            <td class="col-week">{{ $item['week'] }}</td>
                        </tr>
                    @endforeach
                @endif

                {{-- Breaks & Remaining Sequences of Domain 1 --}}
                @foreach ($d1['breaks_and_sequences'] as $entry)
                    @if ($entry['type'] === 'break')
                        <tr>
                            <td colspan="5" class="{{ $entry['break_type'] === 'vacation' ? 'break-cell-vacation' : 'break-cell-exam' }}">
                                {{ $entry['text'] }}
                            </td>
                        </tr>
                    @else
                        {{-- Sequence entry --}}
                        <tr>
                            <td rowspan="{{ $entry['rowspan'] }}" class="col-sequence">{{ $entry['name'] }}</td>
                            <td @class(['col-lesson', 'cell-exam-red' => $entry['items'][0]['type'] === 'exam_red', 'cell-remedial-yellow' => $entry['items'][0]['type'] === 'remedial_yellow'])>
                                {{ $entry['items'][0]['lesson'] }}
                            </td>
                            <td class="col-hours">{{ $entry['items'][0]['hours'] }}</td>
                            <td class="col-date">{{ $entry['items'][0]['date'] }}</td>
                            <td class="col-week">{{ $entry['items'][0]['week'] }}</td>
                        </tr>
                        @foreach (array_slice($entry['items'], 1) as $item)
                            <tr>
                                <td @class(['col-lesson', 'cell-exam-red' => $item['type'] === 'exam_red', 'cell-remedial-yellow' => $item['type'] === 'remedial_yellow'])>
                                    {{ $item['lesson'] }}
                                </td>
                                <td class="col-hours">{{ $item['hours'] }}</td>
                                <td class="col-date">{{ $item['date'] }}</td>
                                <td class="col-week">{{ $item['week'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach

                {{-- Domain 2: معالج النصوص --}}
                @php
                    $d2 = $domains[1];
                    $d2Seq1 = $d2['sequences'][0];
                @endphp
                {{-- Row 1 of Domain 2 (Week 16) --}}
                <tr>
                    <td rowspan="{{ $d2['rowspan'] }}" class="vertical-cell col-domain">
                        <span class="vertical-text">{{ $d2['name'] }}</span>
                    </td>
                    <td rowspan="{{ $d2['rowspan'] }}" class="vertical-cell col-competence">
                        <span class="vertical-text">{{ $d2['competence'] }}</span>
                    </td>
                    <td rowspan="{{ $d2Seq1['rowspan'] }}" class="col-sequence">{{ $d2Seq1['name'] }}</td>
                    <td class="col-lesson">{{ $d2Seq1['items'][0]['lesson'] }}</td>
                    <td class="col-hours">{{ $d2Seq1['items'][0]['hours'] }}</td>
                    <td class="col-date">{{ $d2Seq1['items'][0]['date'] }}</td>
                    <td class="col-week">{{ $d2Seq1['items'][0]['week'] }}</td>
                </tr>

                {{-- Breaks & Remaining Sequences of Domain 2 --}}
                @foreach ($d2['breaks_and_sequences'] as $entry)
                    @if ($entry['type'] === 'break')
                        <tr>
                            <td colspan="5" class="{{ $entry['break_type'] === 'vacation' ? 'break-cell-vacation' : 'break-cell-exam' }}">
                                {{ $entry['text'] }}
                            </td>
                        </tr>
                    @else
                        {{-- Sequence entry --}}
                        <tr>
                            <td rowspan="{{ $entry['rowspan'] }}" class="col-sequence">{{ $entry['name'] }}</td>
                            <td @class(['col-lesson', 'cell-exam-red' => $entry['items'][0]['type'] === 'exam_red', 'cell-remedial-yellow' => $entry['items'][0]['type'] === 'remedial_yellow'])>
                                {{ $entry['items'][0]['lesson'] }}
                            </td>
                            <td class="col-hours">{{ $entry['items'][0]['hours'] }}</td>
                            <td class="col-date">{{ $entry['items'][0]['date'] }}</td>
                            <td class="col-week">{{ $entry['items'][0]['week'] }}</td>
                        </tr>
                        @foreach (array_slice($entry['items'], 1) as $item)
                            <tr>
                                <td @class(['col-lesson', 'cell-exam-red' => $item['type'] === 'exam_red', 'cell-remedial-yellow' => $item['type'] === 'remedial_yellow'])>
                                    {{ $item['lesson'] }}
                                </td>
                                <td class="col-hours">{{ $item['hours'] }}</td>
                                <td class="col-date">{{ $item['date'] }}</td>
                                <td class="col-week">{{ $item['week'] }}</td>
                            </tr>
                        @endforeach
                    @endif
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