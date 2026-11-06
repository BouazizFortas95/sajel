@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y - H:i') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $termStr = $council->session_term ? __('الفصل ').$council->session_term : __('الجلسة الافتتاحية السنوية');
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('محضر اجتماع مجلس التعليم') }} — {{ $council->title }}</title>

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
            margin: 6mm 8mm 6mm 8mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 200mm;
            margin: 6px auto;
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
            max-width: 200mm;
            margin: 0 auto;
            padding: 2mm 3mm;
            background: #ffffff;
        }

        /* Traditional Header */
        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 3px;
            line-height: 1.2;
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
            padding-bottom: 3px;
            margin-bottom: 4px;
            font-size: 8.5px;
        }
        .header-meta-col-school {
            display: table-cell !important;
            width: 33% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.3;
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
            padding: 3px 6px;
        }
        .header-meta-col-title .main-title {
            font-size: 11.5px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
        }
        .header-meta-col-title .sub-title {
            font-size: 9px;
            font-weight: 700;
            color: #92400e;
            margin-top: 1px;
        }
        .header-meta-col-date {
            display: table-cell !important;
            width: 23% !important;
            text-align: end;
            vertical-align: middle;
            font-size: 8px;
            line-height: 1.3;
        }

        /* Section Boxes */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 4px;
            overflow: hidden;
        }
        .section-header {
            background: #f1f5f9;
            padding: 2.5px 6px;
            font-weight: 800;
            font-size: 8.5px;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .section-content {
            padding: 4px 6px;
            font-size: 8.5px;
            line-height: 1.35;
        }

        /* Att Table */
        .att-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .att-table th, .att-table td {
            border: 1px solid #cbd5e1;
            padding: 2px 4px;
        }
        .att-table thead th {
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
            text-align: center;
        }

        /* Signatures */
        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.2px solid #0f172a !important;
            padding-top: 3px !important;
            margin-top: 4px !important;
            text-align: center !important;
            page-break-inside: avoid !important;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top !important;
            padding: 1px 4px !important;
        }
        .sig-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-box {
            height: 35px;
            border-bottom: 1px dashed #94a3b8;
            margin-top: 3px;
        }

        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 3px;
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
                <strong>{{ __('محضر اجتماع مجلس التعليم والتنسيق البيداغوجي للمادة') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8; margin-inline-start: 8px;">{{ $council->pv_reference }}</span>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-amber">{{ __('طباعة المحضر') }}</button>
                <button onclick="window.history.back()" class="btn btn-slate">{{ __('رجوع') }}</button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }} — {{ __('مديرية التعليم المتوسط') }}</div>
        </div>

        {{-- Meta Table --}}
        <div class="header-meta-table">
            <div class="header-meta-col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                <div>{{ __('مادة') }}: <strong>{{ $council->subject?->name ?? __('تنسيق عام') }}</strong></div>
            </div>
            <div class="header-meta-col-title">
                <div class="main-title">{{ $council->council_type->label() }}</div>
                <div class="sub-title">{{ $council->title }} · {{ $termStr }} ({{ $yearStr }})</div>
            </div>
            <div class="header-meta-col-date">
                <div>{{ __('تاريخ الانعقاد') }}: <strong>{{ $formatDateTime($council->held_at) }}</strong></div>
                <div>{{ __('المكان') }}: {{ $council->location ?? __('قاعة الأساتذة') }}</div>
                <div style="font-family: monospace; color: #64748b;">{{ $council->pv_reference }}</div>
            </div>
        </div>

        {{-- 1. Agenda --}}
        <div class="section-box">
            <div class="section-header">📋 {{ __('جدول الأعمال المسطر للجلسة:') }}</div>
            <div class="section-content">
                {{ $council->agenda ?? __('دراسة التوجيهات الرسمية، ضبط التدرج البيداغوجي، وتوحيد وتيرة الفروض والاختبارات الفصلية.') }}
            </div>
        </div>

        {{-- 2. Teacher Attendances --}}
        <div class="section-box">
            <div class="section-header">
                👥 {{ __('سجل حضور أساتذة المادة:') }}
                <span style="font-weight: normal; margin-inline-start: auto; color: #64748b;">
                    {{ __('الحاضرون') }}: <strong>{{ $presentTeachers->count() }}</strong> / {{ $totalTeachers }} ({{ $attendanceRate }}%)
                </span>
            </div>
            <div class="section-content" style="padding: 0;">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;">#</th>
                            <th style="text-align: right;">{{ __('اسم ولقب الأستاذ') }}</th>
                            <th style="width: 100px;">{{ __('الصفة في المجلس') }}</th>
                            <th style="width: 70px;">{{ __('الحالة') }}</th>
                            <th style="width: 100px;">{{ __('التوقيع / التأشيرة') }}</th>
                            <th>{{ __('ملاحظات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($council->attendances as $idx => $att)
                            <tr>
                                <td style="text-align: center;">{{ $idx + 1 }}</td>
                                <td style="font-weight: 700;">{{ $att->user?->name }}</td>
                                <td style="text-align: center; color: #475569;">{{ $att->role_in_council }}</td>
                                <td style="text-align: center;">
                                    @if ($att->is_present)
                                        <span style="color: #047857; font-weight: 700;">✓ {{ __('حاضر') }}</span>
                                    @else
                                        <span style="color: #b91c1c; font-weight: 700;">✗ {{ __('غائب') }}</span>
                                    @endif
                                </td>
                                <td style="text-align: center; height: 18px;">
                                    @if ($att->is_present)
                                        <span style="font-size: 7px; color: #94a3b8;">[{{ __('موقع') }}]</span>
                                    @else
                                        <span style="font-size: 7px; color: #b91c1c;">{{ $att->absence_justification ?? __('غير مبرر') }}</span>
                                    @endif
                                </td>
                                <td style="color: #64748b;">{{ $att->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b;">{{ __('لم يتم تسجيل حضور أساتذة بعد.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 3. Curriculum Pacing & Progress Notes --}}
        <div class="section-box">
            <div class="section-header">📈 {{ __('حصيلة تنفيذ المنهاج وتدرج التعلمات:') }}</div>
            <div class="section-content">
                {{ $council->curriculum_pacing_notes ?? __('تمت معاينة ومطابقة دفاتر النصوص ووتيرة تنفيذ المقاطع التعليمية، ووجد السير البيداغوجي متوافقاً مع المنهاج الرسمي.') }}
            </div>
        </div>

        {{-- 4. Unified Assessments Agreement --}}
        <div class="section-box">
            <div class="section-header">📝 {{ __('رزنامة التقويمات والفروض الموحدة والمشاريع البيداغوجية:') }}</div>
            <div class="section-content">
                {{ $council->unified_assessments_agreement ?? __('الاتفاق على بناء فروض موحدة للفصل، وتحديد سلم التنقيط المشترك، وإسناد مشاريع المادة وفق الدليل البيداغوجي.') }}
            </div>
        </div>

        {{-- 5. Recommendations & Decisions --}}
        <div class="section-box">
            <div class="section-header">⚖️ {{ __('التوصيات والقرارات البيداغوجية المتفق عليها:') }}</div>
            <div class="section-content">
                {{ $council->recommendations ?? __('الالتزام بالمعالجة البيداغوجية للنقائص المرصودة، وتفعيل حصص الدعم واستغلال المخابر والوسائل التعليمية المتاحة.') }}
            </div>
        </div>

        {{-- Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('أستاذ منسق المادة (مقرر الجلسة)') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ $council->coordinator?->name ?? __('الأستاذ المنسق') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مفتش التعليم المتوسط للمادة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('للاطلاع والتأشيرة البيداغوجية') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('المصادقة والتنفيذ') }}</div>
                <div class="sig-box"></div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — محضر مجلس التعليم والتنسيق البيداغوجي') }} · {{ $council->pv_reference }}
        </div>
    </div>
</body>
</html>

