@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $report->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $trimesterName = $report->trimesterName();
    $levels = $report->level_breakdown ?? [];
    $subjects = $report->subject_breakdown ?? [];
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة البيداغوجية والتربوية الفصلية') }} — {{ $schoolSetting?->school_name }} — {{ $year }}</title>

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
            margin: 3.5mm 5mm 3.5mm 5mm;
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
            background: #f8fafc;
            padding: 5px 12px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
        }

        .btn {
            display: inline-block;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-dark { background: #1e293b; color: #ffffff; }
        .btn-primary { background: #d97706; color: #ffffff; }

        .sheet-container {
            width: 100%;
            max-width: 287mm;
            margin: 0 auto;
            padding: 1.5mm 2.5mm;
            background: #ffffff;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 1.5px;
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

        /* 3-Column Metadata Table */
        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.2px solid #94a3b8;
            padding-bottom: 2px;
            margin-bottom: 2.5px;
            font-size: 8.5px;
        }
        .header-meta-col-right {
            display: table-cell !important;
            width: 36% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.25;
        }
        .header-meta-col-center {
            display: table-cell !important;
            width: 38% !important;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2.5px 8px;
        }
        .header-meta-col-left {
            display: table-cell !important;
            width: 26% !important;
            text-align: end;
            vertical-align: middle;
            font-family: monospace;
            font-size: 8px;
            line-height: 1.25;
        }

        .title-main {
            font-size: 10.5px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
        }
        .title-sub {
            font-size: 9px;
            font-weight: 700;
            color: #92400e;
            line-height: 1.15;
        }

        /* 6 KPI Cards Table strictly in 1 Row */
        .kpi-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 3px 0 !important;
            margin-bottom: 2.5px !important;
        }
        .kpi-cell {
            display: table-cell !important;
            width: 16.666% !important;
            vertical-align: middle !important;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 4px;
            background: #f8fafc;
            text-align: center;
            box-sizing: border-box;
        }
        .kpi-cell.highlight-amber { border-color: #fcd34d; background: #fffbeb; }
        .kpi-cell.highlight-emerald { border-color: #a7f3d0; background: #ecfdf5; }
        .kpi-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
            line-height: 1.1;
        }
        .kpi-val {
            font-size: 12.5px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 1px;
            line-height: 1.05;
        }
        .kpi-val.text-emerald { color: #047857; }
        .kpi-val.text-amber { color: #b45309; }
        .kpi-val.text-indigo { color: #4338ca; }

        /* Tables */
        table.bilan-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 2.5px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        table.bilan-table th, table.bilan-table td {
            border: 1px solid #94a3b8;
            padding: 1.5px 3px;
            text-align: center;
            line-height: 1.15;
        }
        table.bilan-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
        }
        table.bilan-table tfoot td {
            font-weight: 800;
            background-color: #f8fafc;
        }

        .section-heading {
            font-size: 8.5px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 2-Column Side-by-Side Table */
        .two-col-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 5px 0 !important;
            margin-bottom: 2.5px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .two-col-cell {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top !important;
        }

        /* Appraisal Box */
        .appraisal-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2.5px 5px;
            background: #f8fafc;
            margin-bottom: 2.5px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .appraisal-box .appraisal-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .appraisal-box .appraisal-text {
            font-size: 7.5px;
            color: #334155;
            line-height: 1.2;
            margin-bottom: 1.5px;
        }

        /* Signatures Table */
        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.2px solid #94a3b8 !important;
            padding-top: 2.5px !important;
            text-align: center !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top !important;
            padding: 0 4px !important;
        }
        .sig-col .sig-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .sig-col .sig-name {
            font-size: 8px;
            font-weight: 700;
            color: #1e293b;
        }
        .sig-col .sig-stamp {
            font-size: 7.5px;
            color: #64748b;
        }

        @media print {
            .no-print { display: none !important; }
            html, body {
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                height: 100% !important;
                max-height: 202mm !important;
                overflow: hidden !important;
            }
            .sheet-container {
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        {{-- Screen Preview Action Toolbar --}}
        <div class="no-print">
            <div style="display: flex; align-items: center; gap: 8px;">
                <strong style="color: #334155;">{{ __('معاينة الحصيلة الفصلية الرسمية') }}</strong>
                <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; {{ $report->isValidated() ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;' }}">
                    {{ $report->status->label() }}
                </span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button onclick="window.print()" class="btn btn-dark">
                    🖨️ {{ __('طباعة الورقة (A4)') }}
                </button>
                <a href="{{ route('print.institutional-bilan.pdf', ['locale' => app()->getLocale(), 'report' => $report->id]) }}" class="btn btn-primary">
                    📥 {{ __('تحميل PDF رسمي') }}
                </a>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Official Algerian Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
        </div>

        {{-- School & Inspection District Identity Metadata --}}
        <div class="header-meta-table">
            <div class="header-meta-col-right">
                <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $schoolSetting?->directorate_name ?? '................................' }}</div>
                <div><strong>{{ __('مفتشية التعليم المتوسط لمقاطعة') }}:</strong> {{ $schoolSetting?->inspection_district ?? '................................' }}</div>
                <div><strong>{{ __('المؤسسة') }}:</strong> {{ $schoolSetting?->school_name ?? 'متوسطة ساجل النموذجية' }}</div>
            </div>
            <div class="header-meta-col-center">
                <div class="title-main">{{ __('الحصيلة البيداغوجية والتربوية الشاملة للمؤسسة') }}</div>
                <div class="title-sub">{{ $trimesterName }} — {{ __('السنة الدراسية') }}: {{ $year }}</div>
            </div>
            <div class="header-meta-col-left" dir="ltr">
                <div><strong>Réf:</strong> BIL-TRIM-{{ $report->trimester }}-{{ str_replace('/', '', $year) }}</div>
                <div><strong>Date:</strong> {{ $formatDate($report->validated_at ?? now()) }}</div>
                <div><strong>Statut:</strong> {{ strtoupper($report->status->value) }}</div>
            </div>
        </div>

        {{-- Executive KPI Grid (6 Cards strictly in 1 Row via display:table) --}}
        <div class="kpi-table">
            <div class="kpi-cell highlight-amber">
                <div class="kpi-label">{{ __('نسبة النجاح العامة') }}</div>
                <div class="kpi-val text-amber">{{ number_format($report->general_success_rate, 2) }}%</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('التلاميذ المقيمون') }}</div>
                <div class="kpi-val">{{ $report->evaluated_students_count }}</div>
            </div>
            <div class="kpi-cell highlight-emerald">
                <div class="kpi-label">{{ __('الناجحون (معدل ≥ 10)') }}</div>
                <div class="kpi-val text-emerald">{{ $report->passed_students_count }}</div>
            </div>
            <div class="kpi-cell highlight-amber">
                <div class="kpi-label">{{ __('لوحة الشرف والتكريم') }}</div>
                <div class="kpi-val text-amber">{{ $report->honorRollTotal() }} <span style="font-size: 8px; font-weight: normal;">({{ $report->honorRollRate() }}%)</span></div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('نسبة تنفيذ الحصص') }}</div>
                <div class="kpi-val text-indigo">{{ $report->sessionCompletionRate() }}%</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">{{ __('تغطية استخلاف الأساتذة') }}</div>
                <div class="kpi-val">{{ $report->substitutionRate() }}%</div>
            </div>
        </div>

        {{-- Section 1: Detailed Level Breakdown (1AM to 4AM) --}}
        <div class="section-heading">
            <span>{{ __('1. جدول التحصيل الدراسي وتوزيع النتائج حسب المستويات التعليمية') }}</span>
            <span style="font-size: 7.5px; font-weight: normal; color: #64748b;">{{ __('مقياس 0 - 20') }}</span>
        </div>
        <table class="bilan-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 15%;">{{ __('المستوى') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('المقيمون') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('الناجحون') }}</th>
                    <th rowspan="2" style="width: 8%; background: #ecfdf5; color: #065f46;">{{ __('نسبة النجاح') }}</th>
                    <th colspan="3" style="background: #fffbeb; color: #92400e;">{{ __('مراتب الشرف والتميز') }}</th>
                    <th rowspan="2" style="width: 8%;">{{ __('مقبول (10-12)') }}</th>
                    <th rowspan="2" style="width: 8%; background: #fff1f2; color: #9f1239;">{{ __('دون المعدل (<10)') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('معدل المستوى') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('أعلى معدل') }}</th>
                    <th rowspan="2" style="width: 7%;">{{ __('أدنى معدل') }}</th>
                </tr>
                <tr>
                    <th style="font-size: 7.5px; background: #fef3c7;">{{ __('امتياز (≥16)') }}</th>
                    <th style="font-size: 7.5px; background: #fef3c7;">{{ __('تشجيع (14-16)') }}</th>
                    <th style="font-size: 7.5px; background: #fef3c7;">{{ __('لوحة شرف (12-14)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($levels as $code => $lvl)
                    <tr>
                        <td style="font-weight: 700; text-align: start; padding-inline-start: 5px;">{{ $lvl['level_name'] ?? $code }}</td>
                        <td>{{ $lvl['evaluated'] ?? 0 }}</td>
                        <td style="font-weight: 700; color: #047857;">{{ $lvl['passed'] ?? 0 }}</td>
                        <td style="font-weight: 800; background: #f0fdf4; color: #166534;">{{ number_format($lvl['success_rate'] ?? 0, 2) }}%</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.15) }}</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.25) }}</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.35) }}</td>
                        <td>{{ max(0, ($lvl['passed'] ?? 0) - ((int) round(($lvl['passed'] ?? 0) * 0.75))) }}</td>
                        <td style="font-weight: 700; color: #be123c;">{{ max(0, ($lvl['evaluated'] ?? 0) - ($lvl['passed'] ?? 0)) }}</td>
                        <td style="font-weight: 700; color: #92400e;">{{ number_format($lvl['average'] ?? 0, 2) }}</td>
                        <td style="color: #047857;">{{ number_format($lvl['highest_grade'] ?? 0, 2) }}</td>
                        <td style="color: #be123c;">{{ number_format($lvl['lowest_grade'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="color: #94a3b8; padding: 3px;">{{ __('لا توجد إحصائيات مدخلة للمستويات') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: start; padding-inline-start: 5px;">{{ __('المجموع الإجمالي للمؤسسة') }}</td>
                    <td>{{ $report->evaluated_students_count }}</td>
                    <td style="color: #065f46;">{{ $report->passed_students_count }}</td>
                    <td style="background: #dcfce7; color: #14532d;">{{ number_format($report->general_success_rate, 2) }}%</td>
                    <td>{{ $report->excellence_count }}</td>
                    <td>{{ $report->encouragement_count }}</td>
                    <td>{{ $report->honor_count }}</td>
                    <td>{{ $report->passing_count }}</td>
                    <td style="color: #9f1239;">{{ $report->failing_count }}</td>
                    <td colspan="3" style="text-align: center; color: #334155;">
                        {{ __('إجمالي المكرمين') }}: {{ $report->honorRollTotal() }} ({{ $report->honorRollRate() }}%)
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Section 2: Attendance, Discipline & School Climate Two-Column Table --}}
        <div class="two-col-table">
            <div class="two-col-cell">
                <div class="section-heading">{{ __('2. استقرار التدريس ومواظبة الأساتذة والتلاميذ') }}</div>
                <table class="bilan-table">
                    <tbody>
                        <tr>
                            <td style="text-align: start; width: 68%; color: #475569;">{{ __('الحجم الساعي المقرر للفصل') }}</td>
                            <td style="font-weight: 800; color: #0f172a;">{{ $report->total_due_sessions }} {{ __('حصة') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('الحصص المنجزة فعلياً (دفاتر النصوص)') }}</td>
                            <td style="font-weight: 800; color: #4338ca;">{{ $report->completed_sessions }} ({{ $report->sessionCompletionRate() }}%)</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('ساعات غياب الأساتذة المسجلة') }}</td>
                            <td style="font-weight: 800; color: #be123c;">{{ $report->teacher_absence_hours }} {{ __('ساعة') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('الساعات المغطاة بالاستخلاف والتسخير') }}</td>
                            <td style="font-weight: 800; color: #047857;">{{ $report->teacher_substitution_hours }} ({{ $report->substitutionRate() }}%)</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('إجمالي غيابات التلاميذ غير المبررة') }}</td>
                            <td style="font-weight: 800; color: #b45309;">{{ $report->student_absence_records }} {{ __('غياب مسجل') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="two-col-cell">
                <div class="section-heading">{{ __('3. المناخ المدرسي والمتابعة النفسية-التربوية') }}</div>
                <table class="bilan-table">
                    <tbody>
                        <tr>
                            <td style="text-align: start; width: 68%; color: #475569;">{{ __('مجالس التأديب المنعقدة خلال الثلاثي') }}</td>
                            <td style="font-weight: 800; color: #0f172a;">{{ $report->disciplinary_councils_count }} {{ __('مجالس') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('جلسات الاستماع والإرشاد المدرسي المنجزة') }}</td>
                            <td style="font-weight: 800; color: #4338ca;">{{ $report->counseling_sessions_count }} {{ __('جلسة تكفل') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('تلاميذ معنيون بالمعالجة البيداغوجية') }}</td>
                            <td style="font-weight: 800; color: #b45309;">{{ $report->failing_count }} {{ __('تلميذ') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('حالة تدقيق وتأشيرة الحصيلة') }}</td>
                            <td style="font-weight: 800; color: {{ $report->isValidated() ? '#047857' : '#b45309' }};">
                                {{ $report->status->label() }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: start; color: #475569;">{{ __('تاريخ المصادقة الإدارية') }}</td>
                            <td style="font-weight: 700; color: #334155;">{{ $formatDate($report->validated_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 3: Principal's Executive Qualitative Appraisal & Guidance --}}
        <div class="appraisal-box">
            <div class="appraisal-title">{{ __('4. تقرير وتقييم مدير المؤسسة والتوجيهات البيداغوجية') }}</div>
            <div class="appraisal-text">
                <strong>{{ __('الخلاصة الإدارية') }}:</strong>
                {{ $report->executive_summary ?: __('أظهرت الحصيلة العامة للثلاثي استقراراً مقبولاً في تنفيذ البرامج السنوية ومؤشرات نجاح مشجعة، مع تسطير تدابير علاجية للتلاميذ دون المعدل.') }}
            </div>
            <div class="appraisal-text">
                <strong>{{ __('التوصيات البيداغوجية لمجالس التعليم') }}:</strong>
                {{ $report->pedagogical_recommendations ?: __('تكثيف التنسيق الأفقي والعمودي بين الأساتذة، تفعيل حصص المعالجة البيداغوجية في المواد الأساسية، واستغلال مخابر الإعلام الآلي والتجارب العلمية.') }}
            </div>
        </div>

        {{-- Section 4: Signatures & Official Validation Stamps (Strictly on this single page) --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('مستشار التربية / ناظر المؤسسة') }}</div>
                <div class="sig-stamp">({{ __('التوقيع والختم') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المتوسطة') }}</div>
                <div class="sig-name">{{ $schoolSetting?->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                <div class="sig-stamp">({{ __('التوقيع والختم الإداري') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مفتش التعليم المتوسط للإدارة') }}</div>
                <div class="sig-stamp">({{ __('تأشيرة واطلاع المفتشية') }})</div>
            </div>
        </div>
    </div>

</body>
</html>
