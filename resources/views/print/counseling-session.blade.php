<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('تقرير مقابلة إرشادية فردية - :name', ['name' => $student->full_name]) }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 10.5pt;
            line-height: 1.45;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 12pt;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 11pt;
            margin: 0 0 3px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            margin-top: 4px;
            font-weight: bold;
        }
        .title-box {
            text-align: center;
            border: 2px solid #0f172a;
            background: #f8fafc;
            padding: 8px 12px;
            margin: 12px 0;
            border-radius: 4px;
            position: relative;
        }
        .title-box h3 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            color: #0f172a;
        }
        .title-box p {
            font-size: 9pt;
            margin: 0;
            color: #475569;
        }
        .confidential-stamp {
            position: absolute;
            left: 12px;
            top: 8px;
            border: 1px dashed #dc2626;
            color: #dc2626;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 3px;
        }
        .section-title {
            background: #f1f5f9;
            padding: 4px 8px;
            font-size: 10pt;
            font-weight: bold;
            border-right: 4px solid #0284c7;
            margin: 10px 0 6px 0;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 8px;
        }
        .grid-table td, .grid-table th {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }
        .grid-table th {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: right;
            width: 25%;
        }
        .text-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px;
            min-height: 80px;
            background: #fafafa;
            font-size: 9.5pt;
            line-height: 1.5;
            margin-bottom: 10px;
            white-space: pre-wrap;
        }
        .signatures {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 45%;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            min-height: 80px;
        }
        .sign-box .role {
            font-weight: bold;
            margin-bottom: 45px;
        }
        .footer-note {
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            margin-top: 20px;
            border-top: 1px dotted #cbd5e1;
            padding-top: 4px;
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
        <span class="confidential-stamp">{{ __('سري وخاص') }}</span>
        <h3>{{ __('محضر وتقرير مقابلة إرشادية فردية') }}</h3>
        <p>{{ __('مصلحة التوجيه والإرشاد المدرسي والمهني') }}</p>
    </div>

    {{-- SECTION 1: Student and Session Meta --}}
    <div class="section-title">1. {{ __('بيانات التلميذ والمقابلة') }}</div>
    <table class="grid-table">
        <tr>
            <th>{{ __('اسم ولقب التلميذ:') }}</th>
            <td style="font-weight: bold;">{{ $student->full_name }}</td>
            <th>{{ __('القسم والفوج التربوي:') }}</th>
            <td>{{ $student->group?->classroom?->name ?? '-' }} ({{ $student->group?->name ?? '-' }})</td>
        </tr>
        <tr>
            <th>{{ __('تاريخ إجراء المقابلة:') }}</th>
            <td style="font-weight: bold; color: #0284c7;">{{ $session->session_date ? $session->session_date->format('Y/m/d') : '-' }}</td>
            <th>{{ __('المستهدف بالمقابلة:') }}</th>
            <td>{{ $session->intervieweeLabel() }}</td>
        </tr>
        <tr>
            <th>{{ __('داعي وسبب المقابلة:') }}</th>
            <td style="font-weight: bold; color: #b91c1c;">{{ $session->reason_category->label() }}</td>
            <th>{{ __('مستشار(ة) التوجيه المسؤول:') }}</th>
            <td>{{ $counselor->name ?? ($school->counselor_name ?? '-') }}</td>
        </tr>
        <tr>
            <th>{{ __('حالة المقابلة:') }}</th>
            <td>{{ $session->status->label() }}</td>
            <th>{{ __('تاريخ المتابعة اللاحق:') }}</th>
            <td>{{ $session->follow_up_date ? $session->follow_up_date->format('Y/m/d') : __('غير محدد') }}</td>
        </tr>
    </table>

    {{-- SECTION 2: Observations --}}
    <div class="section-title">2. {{ __('وقائع المقابلة وملاحظات مستشار التوجيه') }}</div>
    <div class="text-box">
        {{ $session->observations ?: __('لم تدون ملاحظات تفصيلية.') }}
    </div>

    {{-- SECTION 3: Action Plan & Recommendations --}}
    <div class="section-title">3. {{ __('الخطة الإرشادية، التوصيات والتدابير المتخذة') }}</div>
    <div class="text-box">
        {{ $session->action_plan ?: __('تم تقديم النصائح والتوجيهات الميدانية للتلميذ مع الاتفاق على الالتزام بالقواعد والتحصيل.') }}
    </div>

    {{-- SECTION 4: Signatures --}}
    <div class="signatures">
        <div class="sign-box">
            <div class="role">{{ __('مستشار(ة) التوجيه والإرشاد المدرسي') }}</div>
            <div style="font-size: 8.5pt; color: #64748b;">{{ __('الخاتم والتوقيع:') }}</div>
        </div>
        <div class="sign-box">
            <div class="role">{{ __('المعني بالمقابلة (التلميذ / الولي)') }}</div>
            <div style="font-size: 8.5pt; color: #64748b;">{{ __('التوقيع:') }}</div>
        </div>
    </div>

    <div class="footer-note">
        {{ __('وثيقة إرشادية سرية محررة وفق الأطر التنظيمية لوزارة التربية الوطنية — منصة سجِّل للرقمنة التربوية') }}
    </div>

</body>
</html>

