<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('سجل المتابعات النفسية والتربوية السنوي - :year', ['year' => $academic_year]) }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 11pt;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0 0 2px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
            font-weight: bold;
        }
        .title-box {
            text-align: center;
            border: 2px solid #0f172a;
            background: #f1f5f9;
            padding: 5px 8px;
            margin: 6px 0 8px 0;
            border-radius: 4px;
        }
        .title-box h3 {
            font-size: 11.5pt;
            font-weight: bold;
            margin: 0 0 2px 0;
            color: #0f172a;
        }
        .kpi-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .kpi-card {
            flex: 1;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 4px;
            padding: 4px 6px;
            text-align: center;
        }
        .kpi-card .val {
            font-size: 11pt;
            font-weight: bold;
            color: #0284c7;
        }
        .kpi-card .lbl {
            font-size: 7.5pt;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: center;
        }
        th {
            background: #e2e8f0;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-open { background: #fee2e2; color: #991b1b; }
        .badge-progress { background: #fef3c7; color: #92400e; }
        .badge-resolved { background: #dcfce7; color: #166534; }
        .signatures {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 40%;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
            min-height: 45px;
        }
        .sign-box .role {
            font-weight: bold;
            margin-bottom: 25px;
        }
        .footer-note {
            text-align: center;
            font-size: 7pt;
            color: #64748b;
            margin-top: 8px;
            border-top: 1px dotted #cbd5e1;
            padding-top: 3px;
        }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</h1>
        <h2>{{ __('وزارة التربية الوطنية') }}</h2>
        <div class="meta">
            <span>{{ __('مديرية التربية لولاية:') }} {{ $school->directorate_name ?? '..................' }}</span>
            <span>{{ __('المؤسسة:') }} {{ $school->school_name ?? '..................' }}</span>
            <span>{{ __('السنة الدراسية:') }} {{ $academic_year }}</span>
        </div>
    </div>

    <div class="title-box">
        <h3>{{ __('سجل المتابعات النفسية والتربوية والمقابلات الإرشادية السنوي') }}</h3>
    </div>

    {{-- KPI row --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="val">{{ $kpis['total_sessions'] }}</div>
            <div class="lbl">{{ __('إجمالي المقابلات المنفذة') }}</div>
        </div>
        <div class="kpi-card">
            <div class="val" style="color: #166534;">{{ $kpis['resolved_sessions'] }} ({{ $kpis['resolution_rate'] }}%)</div>
            <div class="lbl">{{ __('المقابلات المعالجة والمغلقة') }}</div>
        </div>
        <div class="kpi-card">
            <div class="val" style="color: #92400e;">{{ $kpis['in_progress_sessions'] }}</div>
            <div class="lbl">{{ __('قيد المتابعة الدورية') }}</div>
        </div>
        <div class="kpi-card">
            <div class="val" style="color: #b91c1c;">{{ $kpis['chronic_count'] }}</div>
            <div class="lbl">{{ __('حالات الأمراض المزمنة') }}</div>
        </div>
        <div class="kpi-card">
            <div class="val" style="color: #6b21a8;">{{ $kpis['difficulties_count'] }}</div>
            <div class="lbl">{{ __('صعوبات التعلم المشخصة') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 10%;">{{ __('تاريخ المقابلة') }}</th>
                <th style="width: 18%;">{{ __('اسم ولقب التلميذ') }}</th>
                <th style="width: 10%;">{{ __('القسم') }}</th>
                <th style="width: 18%;">{{ __('داعي / سبب المقابلة') }}</th>
                <th style="width: 12%;">{{ __('المستهدف') }}</th>
                <th style="width: 18%;">{{ __('الخطة والتوصيات') }}</th>
                <th style="width: 10%;">{{ __('الحالة') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $idx => $s)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $s->session_date ? $s->session_date->format('Y/m/d') : '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ $s->student->full_name ?? '-' }}</td>
                    <td>{{ $s->student?->group?->classroom?->name ?? '-' }}</td>
                    <td style="text-align: right;">{{ $s->reason_category->label() }}</td>
                    <td>{{ $s->intervieweeLabel() }}</td>
                    <td style="text-align: right;">{{ $s->action_plan ? \Illuminate\Support\Str::limit($s->action_plan, 50) : '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $s->status === \App\Enums\CounselingSessionStatus::Resolved ? 'resolved' : ($s->status === \App\Enums\CounselingSessionStatus::InProgress ? 'progress' : 'open') }}">
                            {{ $s->status->label() }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 12px; color: #64748b;">
                        {{ __('لا توجد جلسات أو مقابلات إرشادية مسجلة لهذه السنة الدراسية.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div class="sign-box">
            <div class="role">{{ __('مستشار(ة) التوجيه والإرشاد المدرسي') }}</div>
            <div style="font-size: 7.5pt; color: #64748b;">{{ __('التاريخ، الخاتم والتوقيع:') }}</div>
        </div>
        <div class="sign-box">
            <div class="role">{{ __('تأشيرة وخاتم مدير(ة) المؤسسة') }}</div>
            <div style="font-size: 7.5pt; color: #64748b;">{{ __('التاريخ، الخاتم والتوقيع:') }}</div>
        </div>
    </div>

    <div class="footer-note">
        {{ __('سجل رسمي لمصلحة الإرشاد والتوجيه المدرسي — منصة سجِّل للرقمنة المدرسية') }}
    </div>

</body>
</html>
