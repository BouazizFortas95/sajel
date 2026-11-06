@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('Y/m/d') : '—';
    $school = \App\Models\SchoolSetting::current();
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('جدول الترقية في الدرجات السنوي') }} — {{ $year }}</title>

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
            font-size: 8px;
            line-height: 1.25;
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
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary { background: #0284c7; color: #ffffff; }
        .btn-primary:hover { background: #0369a1; }
        .btn-secondary { background: #334155; color: #ffffff; border-color: #475569; }
        .btn-secondary:hover { background: #475569; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
        }

        .sheet-container {
            max-width: 285mm;
            margin: 0 auto;
            border: 1.5px solid #0f172a;
            padding: 6px 8px;
            background: #ffffff;
        }

        /* Header */
        .header-section {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .republic-title {
            font-family: 'Amiri', serif;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .ministry-title {
            font-size: 10px;
            font-weight: 700;
            color: #1e293b;
        }

        .sub-header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 3px;
            font-size: 8.5px;
            font-weight: 600;
            color: #334155;
            text-align: center;
        }

        /* Title Box */
        .doc-title-box {
            background: #f8fafc;
            border: 1.5px solid #0f172a;
            padding: 4px 10px;
            margin: 6px 0;
            text-align: center;
            border-radius: 4px;
        }

        .doc-main-title {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }

        .doc-sub-title {
            font-size: 8px;
            color: #475569;
            font-weight: 600;
        }

        /* KPI Strip */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 6px;
        }

        .kpi-card {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            border-radius: 4px;
            text-align: center;
        }

        .kpi-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #475569;
        }

        .kpi-val {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
        }

        /* Ledger Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-bottom: 6px;
        }

        .ledger-table th, .ledger-table td {
            border: 1px solid #64748b;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
        }

        .ledger-table th {
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
        }

        .ledger-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .name-cell {
            text-align: right !important;
            font-weight: 700;
            white-space: nowrap;
        }

        .score-cell {
            font-weight: 800;
            color: #0f172a;
        }

        .badge-rhythm {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: 800;
            color: #ffffff;
        }

        .badge-min { background: #16a34a; }
        .badge-med { background: #0284c7; }
        .badge-max { background: #d97706; }

        /* Committee Visa Block */
        .visa-section {
            border-top: 1.5px solid #0f172a;
            padding-top: 6px;
            margin-top: 4px;
        }

        .visa-title {
            text-align: center;
            font-size: 9px;
            font-weight: 800;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .visa-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            text-align: center;
        }

        .visa-box {
            border: 1px dashed #94a3b8;
            padding: 4px;
            min-height: 55px;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .visa-role {
            font-size: 8.5px;
            font-weight: 800;
            color: #1e293b;
        }

        .visa-placeholder {
            font-size: 7px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <strong>{{ __('جدول الترقية في الدرجات السنوي — اللجنة الإدارية المتساوية الأعضاء (CAP)') }}</strong>
            <span style="opacity: 0.8; margin-right: 8px; font-size: 10px;">({{ $year }})</span>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                {{ __('طباعة الجدول (Print)') }}
            </button>
            <a href="{{ route('print.staff-career.advancement-ledger.pdf', ['locale' => app()->getLocale(), 'year' => $year]) }}" class="btn btn-secondary">
                {{ __('تحميل PDF') }}
            </a>
            <a href="{{ route('staff-career.advancements.export-csv', ['locale' => app()->getLocale(), 'year' => $year]) }}" class="btn btn-secondary">
                {{ __('تصدير CSV') }}
            </a>
        </div>
    </div>

    <div class="sheet-container">
        <!-- Header -->
        <header class="header-section">
            <div class="republic-title">الجمهـورية الجزائريـة الديمقراطيـة الشعبيـة</div>
            <div class="ministry-title">وزارة التـربيـة الوطنيـة</div>
            <div class="sub-header-grid">
                <div>{{ $directorate ?? ($school?->directorate_name ?? 'مديرية التربية الوطنية') }}</div>
                <div>{{ $school_name ?? ($school?->school_name ?? 'متوسطة ساجل النموذجية') }}</div>
            </div>
        </header>

        <!-- Document Title -->
        <div class="doc-title-box">
            <div class="doc-main-title">{{ __('جدول الترقية في الدرجات لمستخدمي المؤسسة برسم سنة') }} {{ $year }}</div>
            <div class="doc-sub-title">
                {{ __('تطبيقاً لأحكام الأمر رقم 06-03 المتضمن القانون الأساسي العام للوظيفة العمومية والمرسوم التنفيذي 08-315') }}
                — {{ __('المعروض على أنظار اللجنة الإدارية المتساوية الأعضاء المختصة (CAP)') }}
            </div>
        </div>

        <!-- KPI Strip -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-label">{{ __('إجمالي المترشحين للترقية') }}</div>
                <div class="kpi-val">{{ $total_count }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">{{ __('الوتيرة الدنيا (سنتان ونصف)') }}</div>
                <div class="kpi-val" style="color: #16a34a;">{{ $min_count }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">{{ __('الوتيرة المتوسطة (3 سنوات)') }}</div>
                <div class="kpi-val" style="color: #0284c7;">{{ $med_count }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">{{ __('الوتيرة القصوى (3 سنوات ونصف)') }}</div>
                <div class="kpi-val" style="color: #d97706;">{{ $max_count }}</div>
            </div>
        </div>

        <!-- Ledger Table -->
        <table class="ledger-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">{{ __('رقم') }}</th>
                    <th rowspan="2" style="width: 14%;">{{ __('اللقب والاسم') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('رقم الوظيفة') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('الميلاد') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('أول تعيين') }}</th>
                    <th rowspan="2" style="width: 10%;">{{ __('الرتبة القانونية') }}</th>
                    <th colspan="2">{{ __('الدرجة الحالية') }}</th>
                    <th rowspan="2" style="width: 4%;">{{ __('الدرجة المقترحة') }}</th>
                    <th colspan="3">{{ __('التنقيط /20') }}</th>
                    <th colspan="3">{{ __('تواريخ الاستحقاق القانونية') }}</th>
                    <th rowspan="2" style="width: 6%;">{{ __('الوتيرة المعتمدة') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('تاريخ السريان') }}</th>
                    <th rowspan="2" style="width: 8%;">{{ __('ملاحظات وقرار اللجنة') }}</th>
                </tr>
                <tr>
                    <th style="width: 3%;">{{ __('الرقم') }}</th>
                    <th style="width: 6%;">{{ __('تاريخ المفعول') }}</th>
                    <th style="width: 4%;">{{ __('إدارية') }}</th>
                    <th style="width: 4%;">{{ __('تربوية') }}</th>
                    <th style="width: 4%;">{{ __('المجموع') }}</th>
                    <th style="width: 6%;">{{ __('دنيا (2.5)') }}</th>
                    <th style="width: 6%;">{{ __('متوسطة (3)') }}</th>
                    <th style="width: 6%;">{{ __('قصوى (3.5)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($advancements as $idx => $adv)
                    @php
                        $user = $adv->user;
                        $eval = $user?->latestEvaluation;
                        $rhythmClass = match($adv->advancement_rhythm?->value) {
                            'minimum' => 'badge-min',
                            'maximum' => 'badge-max',
                            default => 'badge-med',
                        };
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="name-cell">{{ $user?->name ?? '—' }}</td>
                        <td>{{ $user?->registration_number ?? '—' }}</td>
                        <td>{{ $formatDate($user?->birth_date) }}</td>
                        <td>{{ $formatDate($user?->recruitment_date) }}</td>
                        <td>{{ $user?->statutory_grade ?? ($user?->role?->label() ?? '—') }}</td>
                        <td style="font-weight: 800;">{{ $adv->current_step }}</td>
                        <td>{{ $formatDate($adv->current_step_date) }}</td>
                        <td style="font-weight: 900; color: #0284c7; background: #e0f2fe;">{{ $adv->next_step }}</td>
                        <td class="score-cell">{{ $eval ? number_format($eval->administrative_score, 2) : '—' }}</td>
                        <td class="score-cell">{{ $eval && $eval->pedagogical_score !== null ? number_format($eval->pedagogical_score, 2) : '—' }}</td>
                        <td class="score-cell" style="color: #0284c7; font-weight: 900;">{{ $eval ? number_format($eval->composite_score, 2) : '—' }}</td>
                        <td>{{ $formatDate($adv->eligible_at_minimum) }}</td>
                        <td>{{ $formatDate($adv->eligible_at_medium) }}</td>
                        <td>{{ $formatDate($adv->eligible_at_maximum) }}</td>
                        <td>
                            <span class="badge-rhythm {{ $rhythmClass }}">
                                {{ $adv->advancement_rhythm?->shortLabel() ?? 'المتوسطة' }}
                            </span>
                        </td>
                        <td style="font-weight: 700;">{{ $formatDate($adv->effective_date) }}</td>
                        <td style="font-size: 7px; color: #475569;">
                            {{ $adv->status_label }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" style="padding: 12px; text-align: center; color: #64748b;">
                            {{ __('لا يوجد مترشحون مسجلون في جدول الترقية لهذه السنة.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Committee Visas -->
        <div class="visa-section">
            <div class="visa-title">{{ __('تأشيرات ومصادقة أعضاء اللجنة الإدارية المتساوية الأعضاء المختصة (CAP)') }}</div>
            <div class="visa-grid">
                <div class="visa-box">
                    <div class="visa-role">{{ __('ممثلو الإدارة') }}</div>
                    <div class="visa-placeholder">{{ __('(الأسماء والتوقيعات)') }}</div>
                </div>
                <div class="visa-box">
                    <div class="visa-role">{{ __('ممثلو الموظفين المنتخبون') }}</div>
                    <div class="visa-placeholder">{{ __('(الأسماء والتوقيعات)') }}</div>
                </div>
                <div class="visa-box">
                    <div class="visa-role">{{ __('رئيس اللجنة / مدير المؤسسة') }}</div>
                    <div class="visa-placeholder">{{ __('(الختم الرسمي والتأشيرة)') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
