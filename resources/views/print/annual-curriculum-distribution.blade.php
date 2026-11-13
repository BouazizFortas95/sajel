@php
    $isRtl = true;
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($mode ?? 'annual') === 'monthly' ? __('المخططات الشهرية للتعلمات') : ($title ?? 'التوزيع السنوي لمادة المعلوماتية') }} — سجِّل</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

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
            margin: {{ ($mode ?? 'annual') === 'monthly' ? '2.5mm 4.5mm 2.5mm 4.5mm' : '4mm 6mm 4mm 6mm' }};
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 7.8pt;
            line-height: 1.15;
            direction: rtl;
            -webkit-font-smoothing: antialiased;
        }

        body.mode-monthly {
            font-size: 6.8pt;
            line-height: 1.1;
        }

        body.mode-monthly .header-grid {
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        body.mode-monthly .title-badge {
            padding: 1px 10px;
        }

        body.mode-monthly .title-main {
            font-size: 10pt;
            line-height: 1.15;
        }

        body.mode-monthly .title-sub {
            font-size: 7pt;
        }

        body.mode-monthly .distribution-table {
            font-size: 6.8pt;
        }

        body.mode-monthly .distribution-table th,
        body.mode-monthly .distribution-table td {
            padding: 1.2px 1.5px;
        }

        body.mode-monthly .distribution-table thead th {
            font-size: 7.2pt;
            padding: 1.5px 1px;
        }

        body.mode-monthly .col-sequence {
            font-size: 6.8pt;
            width: 78px;
        }

        body.mode-monthly .col-lesson {
            font-size: 6.8pt;
        }

        body.mode-monthly .col-hours {
            font-size: 6.5pt;
            width: 40px;
        }

        body.mode-monthly .col-date {
            font-size: 6.5pt;
            width: 110px;
        }

        body.mode-monthly .col-week {
            font-size: 6.8pt;
            width: 22px;
        }

        body.mode-monthly .vertical-text {
            font-size: 6.2pt;
        }

        body.mode-monthly .col-domain .vertical-text {
            font-size: 6.8pt;
        }

        body.mode-monthly .col-competence .vertical-text {
            font-size: 5.8pt;
        }

        body.mode-monthly .break-cell-vacation,
        body.mode-monthly .break-cell-exam {
            font-size: 6.5pt;
            padding: 1.2px 2px !important;
        }

        body.mode-monthly .signatures-grid {
            margin-top: 4px;
        }

        body.mode-monthly .signature-title {
            font-size: 7.5pt;
        }

        .no-print {
            max-width: 198mm;
            margin: 4px auto 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .level-switcher, .mode-switcher {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .month-bar {
            max-width: 198mm;
            margin: 0 auto 6px auto;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 4px;
            background: #1e293b;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 9.5px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 9.5px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: 'Cairo', sans-serif;
            transition: all 0.15s ease;
        }

        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-emerald { background: #10b981; color: #ffffff; }
        .btn-emerald:hover { background: #059669; }
        .btn-sky { background: #0284c7; color: #ffffff; }
        .btn-sky:hover { background: #0369a1; }
        .btn-slate { background: #334155; color: #f1f5f9; }
        .btn-slate:hover { background: #475569; }

        .sheet {
            width: 100%;
            max-width: 198mm;
            margin: 0 auto;
            padding: 1mm 2mm;
            box-sizing: border-box;
            background: #ffffff;
        }

        @media screen {
            .sheet {
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
                margin: 0 auto 16px auto;
            }
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        /* Institutional National Header */
        .header-grid {
            display: grid;
            grid-template-columns: 1.15fr 1.35fr 1.15fr;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 3px;
            margin-bottom: 4px;
            align-items: center;
        }

        .header-col-right {
            text-align: right;
            font-size: 7.2pt;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.25;
        }

        .header-col-center {
            text-align: center;
        }

        .title-badge {
            display: inline-block;
            border: 1.5px solid #1e3a8a;
            background: #f0f7ff;
            border-radius: 5px;
            padding: 2px 12px;
            text-align: center;
        }

        .title-main {
            font-size: 11pt;
            font-weight: 900;
            color: #1e3a8a;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.2px;
        }

        .title-sub {
            font-size: 7.8pt;
            font-weight: 800;
            color: #b45309;
            margin-top: 1px;
        }

        .header-col-left {
            text-align: left;
            font-size: 7.2pt;
            font-weight: 700;
            color: #334155;
            line-height: 1.25;
        }

        /* Main Table */
        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #0f172a;
            font-size: 7.7pt;
            text-align: center;
        }

        .distribution-table th,
        .distribution-table td {
            border: 1px solid #334155;
            padding: 2.8px 2px;
            vertical-align: middle;
        }

        .distribution-table thead th {
            font-weight: 800;
            font-size: 8pt;
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 2.5px 1px;
            border-color: #1e3a8a;
        }

        /* Level Banner Row */
        .level-banner-row td {
            background: linear-gradient(90deg, #1e3a8a 0%, #2563eb 100%) !important;
            color: #ffffff !important;
            padding: 1.5px 6px !important;
            text-align: right !important;
            border-top: 1.2px solid #0f172a !important;
            border-bottom: 1.2px solid #0f172a !important;
        }

        .level-banner-content {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .level-tag {
            background: #f59e0b;
            color: #0f172a;
            font-size: 6.5pt;
            font-weight: 900;
            padding: 0 5px;
            border-radius: 2px;
        }

        .level-title {
            font-size: 7.8pt;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.1px;
        }

        /* Vertical Rotated Text */
        .vertical-cell {
            vertical-align: middle;
            text-align: center;
            padding: 2px 1px !important;
            background-color: #fbfcfe;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 7pt;
            font-weight: 800;
            display: inline-block;
            margin: 0 auto;
            line-height: 1.05;
        }

        .col-domain {
            width: 27px;
        }

        .col-domain .vertical-text {
            white-space: nowrap;
            font-size: 7.8pt;
            color: #1e3a8a;
        }

        .col-competence {
            width: 35px;
            max-width: 37px;
        }

        .col-competence .vertical-text {
            font-size: 6.6pt;
            line-height: 1.05;
            color: #334155;
        }

        .col-sequence {
            width: 82px;
            font-weight: 800;
            font-size: 7.6pt;
            background-color: #fbfcfe;
        }

        .col-lesson {
            text-align: center;
            font-size: 7.7pt;
            font-weight: 600;
        }

        .col-hours {
            width: 44px;
            font-size: 7.2pt;
            white-space: nowrap;
            color: #334155;
        }

        .col-date {
            width: 118px;
            font-size: 7.2pt;
            white-space: nowrap;
            color: #334155;
        }

        .col-week {
            width: 25px;
            font-size: 7.6pt;
            font-weight: 800;
            background-color: #fbfcfe;
        }

        /* Highlights */
        .cell-exam-red {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
        }

        .cell-remedial-yellow {
            background-color: #fef08a !important;
            color: #854d0e !important;
            font-weight: 800 !important;
        }

        .break-cell-vacation {
            color: #b91c1c;
            background-color: #fef2f2 !important;
            font-weight: 800;
            font-size: 7.4pt;
            text-align: center;
            padding: 2.2px 2px !important;
        }

        .break-cell-exam {
            color: #1e40af;
            background-color: #eff6ff !important;
            font-weight: 800;
            font-size: 7.4pt;
            text-align: center;
            padding: 2.2px 2px !important;
        }

        /* Signatures Section */
        .signatures-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            padding: 2px 10px 0 10px;
        }

        .signature-col {
            text-align: center;
        }

        .signature-title {
            font-size: 8.5pt;
            font-weight: 800;
            color: #1e3a8a;
        }

        @media print {
            .no-print { display: none !important; }
            body, html {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .sheet {
                padding: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="mode-{{ $mode ?? 'annual' }}">
    {{-- Navigation & Print Toolbar --}}
    <div class="no-print">
        <div class="toolbar-group">
            <div class="level-switcher">
                <span style="opacity: 0.8; margin-left: 2px;">{{ __('المستوى:') }}</span>
                @foreach ($available_levels as $lvl => $label)
                    @php
                        $isAll = (string) $lvl === 'all';
                        $isActive = $isAll
                            ? (($selected_level ?? null) === 'all' || (($mode ?? 'annual') === 'monthly' && ($selected_level ?? null) === 'all'))
                            : (($selected_level ?? null) !== 'all' && ($level_number ?? 1) == $lvl);
                    @endphp
                    @if (($mode ?? 'annual') === 'annual' && $isAll)
                        {{-- Skip 'all' button in annual mode since annual is 35 weeks per level --}}
                    @else
                        <a
                            href="{{ route('print.annual-curriculum-distribution', ['locale' => app()->getLocale(), 'level' => $lvl, 'mode' => $mode ?? 'annual', 'month' => $selected_month ?? 'all', 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                            class="btn {{ $isActive ? 'btn-amber' : 'btn-slate' }}"
                        >
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mode-switcher">
                <span style="opacity: 0.8; margin-left: 2px;">{{ __('المخطط:') }}</span>
                <a
                    href="{{ route('print.annual-curriculum-distribution', ['locale' => app()->getLocale(), 'level' => ($selected_level ?? 1) === 'all' ? 1 : ($selected_level ?? 1), 'mode' => 'annual', 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                    class="btn {{ ($mode ?? 'annual') === 'annual' ? 'btn-sky' : 'btn-slate' }}"
                >
                    {{ __('التوزيع السنوي') }}
                </a>
                <a
                    href="{{ route('print.annual-curriculum-distribution', ['locale' => app()->getLocale(), 'level' => $selected_level ?? 'all', 'mode' => 'monthly', 'month' => $selected_month ?? 'all', 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                    class="btn {{ ($mode ?? 'annual') === 'monthly' ? 'btn-sky' : 'btn-slate' }}"
                >
                    {{ __('المخططات الشهرية') }}
                </a>
            </div>
        </div>

        <div class="btn-group">
            <a
                href="{{ route('print.annual-curriculum-distribution.pdf', ['locale' => app()->getLocale(), 'level' => $selected_level ?? ($level_number ?? 1), 'mode' => $mode ?? 'annual', 'month' => $selected_month ?? 'all', 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
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

    {{-- Month selector bar when in Monthly mode --}}
    @if (($mode ?? 'annual') === 'monthly' && !empty($available_months))
        <div class="no-print month-bar">
            <span style="opacity: 0.8; margin-left: 6px;">{{ __('الشهر المطلوب:') }}</span>
            @foreach ($available_months as $mKey => $mLabel)
                <a
                    href="{{ route('print.annual-curriculum-distribution', ['locale' => app()->getLocale(), 'level' => $selected_level ?? ($level_number ?? 1), 'mode' => 'monthly', 'month' => $mKey, 'subject' => $selected_subject_id ?? request('subject'), 'classroom' => $selected_classroom_id ?? request('classroom')]) }}"
                    class="btn {{ ($selected_month ?? 'all') === (string) $mKey ? 'btn-amber' : 'btn-slate' }}"
                >
                    {{ $mLabel }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Render Printable Content --}}
    @if (($mode ?? 'annual') === 'monthly')
        {{-- Monthly Plans Render Loop --}}
        @foreach ($monthly_plans as $plan)
            <div class="sheet {{ $loop->last ? '' : 'page-break' }}">
                {{-- National Institutional Header --}}
                <div class="header-grid">
                    <div class="header-col-right">
                        <div>الجمهورية الجزائرية الديمقراطية الشعبية</div>
                        <div>وزارة التربية الوطنية</div>
                        <div>مديرية التربية لولاية {{ $directorate_name ?? 'المغير' }}</div>
                        <div>{{ $school_name }}</div>
                    </div>

                    <div class="header-col-center">
                        <div class="title-badge">
                            <h1 class="title-main">المخطط الشهري للتعلمات</h1>
                            <div class="title-sub">
                                {{ (str_contains($plan['month_name'], 'و') || str_contains($plan['month_name'], '—')) ? 'شهري' : 'شهر' }} {{ $plan['month_name'] }} ({{ $plan['term'] }})
                                @if (!empty($plan['level_sections']) && count($plan['level_sections']) === 1)
                                    — {{ $plan['level_sections'][0]['level_name'] }}
                                @elseif (($selected_level ?? null) !== 'all' && count($selected_levels ?? []) === 1)
                                    — {{ $level_name }}
                                @endif
                                — مادة المعلوماتية
                            </div>
                        </div>
                    </div>

                    <div class="header-col-left">
                        <div>السنة الدراسية: {{ $academic_year }}</div>
                        <div>الحجم الساعي: {{ $weekly_hours }}</div>
                        <div>معامل المادة: {{ $coefficient }}</div>
                        <div>الأستاذ: {{ $teacher_name }}</div>
                    </div>
                </div>

                {{-- Unified Monthly Table --}}
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
                        @if (!empty($plan['level_sections']))
                            @foreach ($plan['level_sections'] as $section)
                                @if (count($plan['level_sections']) > 1)
                                    <tr class="level-banner-row">
                                        <td colspan="7">
                                            <div class="level-banner-content">
                                                <span class="level-tag">المستوى:</span>
                                                <span class="level-title">{{ $section['level_name'] }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($section['domains'] as $domain)
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
                            @endforeach
                        @else
                            @foreach ($plan['domains'] as $domain)
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
                        @endif
                    </tbody>
                </table>

                {{-- Institutional Signatures Footer --}}
                <div class="signatures-grid">
                    <div class="signature-col">
                        <div class="signature-title">أستاذ(ة) المادة</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">مدير(ة) المؤسسة</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">مفتش(ة) مادة المعلوماتية</div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{-- Annual Distribution View (Full 35 Weeks) --}}
        <div class="sheet">
            {{-- National Institutional Header --}}
            <div class="header-grid">
                <div class="header-col-right">
                    <div>الجمهورية الجزائرية الديمقراطية الشعبية</div>
                    <div>وزارة التربية الوطنية</div>
                    <div>مديرية التربية لولاية {{ $directorate_name ?? 'المغير' }}</div>
                    <div>{{ $school_name }}</div>
                </div>

                <div class="header-col-center">
                    <div class="title-badge">
                        <h1 class="title-main">{{ $title ?? 'التوزيع السنوي للتعلمات' }}</h1>
                        <div class="title-sub">{{ $level_name }} — مادة المعلوماتية</div>
                    </div>
                </div>

                <div class="header-col-left">
                    <div>السنة الدراسية: {{ $academic_year }}</div>
                    <div>الحجم الساعي: {{ $weekly_hours }}</div>
                    <div>معامل المادة: {{ $coefficient }}</div>
                    <div>الأستاذ: {{ $teacher_name }}</div>
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

            {{-- Institutional Signatures Footer --}}
            <div class="signatures-grid">
                <div class="signature-col">
                    <div class="signature-title">أستاذ(ة) المادة</div>
                </div>
                <div class="signature-col">
                    <div class="signature-title">مدير(ة) المؤسسة</div>
                </div>
                <div class="signature-col">
                    <div class="signature-title">مفتش(ة) مادة المعلوماتية</div>
                </div>
            </div>
        </div>
    @endif
</body>
</html>
