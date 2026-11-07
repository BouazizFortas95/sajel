<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('بطاقة ومحضر المطابقة والمعاينة السنوية') }} - {{ $recolement->recolement_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'Amiri', 'DejaVu Sans', 'Arial', sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .republic {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .ministry {
            font-size: 9.5pt;
            margin-bottom: 3px;
        }
        .school-meta {
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            margin-top: 3px;
        }
        .doc-title-box {
            background-color: #f3f4f6;
            border: 1.5px solid #111827;
            padding: 6px 10px;
            text-align: center;
            margin: 8px 0;
            border-radius: 4px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 9pt;
            color: #4b5563;
            margin-top: 2px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9.5pt;
        }
        .meta-grid td {
            padding: 3px 6px;
            border: 1px solid #d1d5db;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f9fafb;
            width: 16%;
        }
        .meta-val {
            width: 34%;
        }
        .kpi-row {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .kpi-box {
            flex: 1;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            border-radius: 4px;
            padding: 4px 6px;
            text-align: center;
        }
        .kpi-val {
            font-size: 12pt;
            font-weight: bold;
            color: #111827;
        }
        .kpi-label {
            font-size: 8pt;
            color: #4b5563;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8.5pt;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #374151;
            padding: 4px 5px;
            text-align: center;
        }
        table.items-table th {
            background-color: #e5e7eb;
            font-weight: bold;
            color: #111827;
        }
        table.items-table td.text-right {
            text-align: right;
        }
        .findings-box {
            border: 1px solid #9ca3af;
            background-color: #fafafa;
            padding: 6px 10px;
            margin: 8px 0;
            font-size: 9pt;
            border-radius: 4px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .sig-block {
            width: 30%;
            text-align: center;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 4px;
            min-height: 70px;
            background-color: #fafafa;
        }
        .sig-title {
            font-weight: bold;
            font-size: 9pt;
            border-bottom: 1px dashed #9ca3af;
            padding-bottom: 2px;
            margin-bottom: 35px;
        }
        .footer-note {
            font-size: 7.5pt;
            color: #6b7280;
            text-align: center;
            margin-top: 8px;
            border-top: 1px solid #e5e7eb;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="republic">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
        <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
        <div class="school-meta">
            <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $school->wilaya ?? '....................' }}</div>
            <div><strong>{{ __('المؤسسة') }}:</strong> {{ $school->school_name ?? config('app.name', 'سجِّل') }}</div>
            <div><strong>{{ __('السنة الدراسية') }}:</strong> {{ $recolement->academic_year ?? '2026/2027' }}</div>
        </div>
    </div>

    <div class="doc-title-box">
        <h1 class="doc-title">{{ __('بطاقة ومحضر المطابقة والمعاينة السنوية للتجهيزات المدرسية') }}</h1>
        <div class="doc-subtitle">{{ __('(Fiche de Récolement Périodique et de Conformité d\'Inventaire - المنشور الوزاري 143/97)') }}</div>
    </div>

    <table class="meta-grid">
        <tr>
            <td class="meta-label">{{ __('رقم محضر المطابقة') }}:</td>
            <td class="meta-val"><strong style="font-family: monospace;">{{ $recolement->recolement_number }}</strong></td>
            <td class="meta-label">{{ __('تاريخ المعاينة الميدانية') }}:</td>
            <td class="meta-val">{{ $recolement->recolement_date?->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('الفضاء / القاعة / المخبر') }}:</td>
            <td class="meta-val"><strong>{{ $recolement->room_name }}</strong> ({{ $recolement->room_type }})</td>
            <td class="meta-label">{{ __('رئيس لجنة المطابقة') }}:</td>
            <td class="meta-val">{{ $recolement->auditor?->name ?? __('المسير المالي / المقتصد') }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('المسؤول الشاهد / العهدة') }}:</td>
            <td class="meta-val">{{ $recolement->witness?->name ?? 'غير محدد' }}</td>
            <td class="meta-label">{{ __('النتيجة والوضعية القانونية') }}:</td>
            <td class="meta-val">
                <strong>{{ $recolement->status->label() }}</strong>
            </td>
        </tr>
    </table>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-val">{{ $recolement->theoretical_count }}</div>
            <div class="kpi-label">{{ __('العتاد النظري المقيد') }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-val">{{ $recolement->verified_count }}</div>
            <div class="kpi-label">{{ __('العتاد الفعلي المعاين') }}</div>
        </div>
        <div class="kpi-box" style="border-top: 3px solid #10b981;">
            <div class="kpi-val" style="color: #059669;">{{ $recolement->conform_count }}</div>
            <div class="kpi-label">{{ __('سليم ومطابق') }}</div>
        </div>
        <div class="kpi-box" style="border-top: 3px solid #ef4444;">
            <div class="kpi-val" style="color: #dc2626;">{{ $recolement->missing_count }}</div>
            <div class="kpi-label">{{ __('مفقود / ضائع') }}</div>
        </div>
        <div class="kpi-box" style="border-top: 3px solid #3b82f6;">
            <div class="kpi-val" style="color: #2563eb;">{{ $recolement->surplus_count }}</div>
            <div class="kpi-label">{{ __('فائض غير مقيد') }}</div>
        </div>
        <div class="kpi-box" style="border-top: 3px solid #f59e0b;">
            <div class="kpi-val" style="color: #d97706;">{{ $recolement->damaged_count }}</div>
            <div class="kpi-label">{{ __('تالف / للإسقاط') }}</div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 14%;">{{ __('رقم الجرد') }}</th>
                <th style="width: 25%;">{{ __('تعيين العتاد') }}</th>
                <th style="width: 18%;">{{ __('المواصفات التقنية') }}</th>
                <th style="width: 8%;">{{ __('الكمية المسجلة') }}</th>
                <th style="width: 15%;">{{ __('الحالة الفنية المعاينة') }}</th>
                <th style="width: 16%;">{{ __('ملاحظات لجنة المطابقة') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $item->inventory_number }}</td>
                    <td class="text-right"><strong>{{ $item->designation }}</strong></td>
                    <td class="text-right">{{ $item->specifications ?: '-' }}</td>
                    <td><strong>{{ $item->quantity }}</strong></td>
                    <td>{{ $item->condition->label() }}</td>
                    <td class="text-right" style="font-size: 8pt;">
                        @if($item->is_reformed)
                            <span style="color: #dc2626;">{{ __('مسقط من الجرد') }}</span>
                        @elseif($item->condition->value === 'damaged')
                            <span style="color: #d97706;">{{ __('مقترح للإصلاح / الإسقاط') }}</span>
                        @else
                            <span style="color: #059669;">{{ __('مطابق ومستغل') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 10px; color: #6b7280;">{{ __('لم يتم العثور على أصول مقيدة مرتبطة بهذا الموقع أثناء المعاينة') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="findings-box">
        <strong>{{ __('خلاصة تقرير وتوصيات لجنة المعاينة والمطابقة:') }}</strong>
        <p style="margin: 2px 0 0 0;">{{ $recolement->findings ?: __('تمت المعاينة الميدانية ومطابقة محتويات الفضاء المذكور مع سجلات الجرد الدائم وبطاقة التخصيص، وتأكدت اللجنة من مطابقة التجهيزات وسلامتها القانونية.') }}</p>
    </div>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">{{ __('مسؤول الفضاء / صاحب العهدة') }}</div>
            <div>{{ $recolement->witness?->name ?? '.......................' }}</div>
        </div>

        <div class="sig-block">
            <div class="sig-title">{{ __('رئيس لجنة المطابقة (المسير المالي)') }}</div>
            <div>{{ $recolement->auditor?->name ?? __('المقتصد المسؤول') }}</div>
        </div>

        <div class="sig-block">
            <div class="sig-title">{{ __('اعتماد مدير المؤسسة') }}</div>
            <div>{{ __('الآمر بالصرف (الختم والتوقيع)') }}</div>
        </div>
    </div>

    <div class="footer-note">
        {{ __('وثيقة رسمية صادرة آلياً عبر نظام سجِّل — طبقا لأحكام المنشور الوزاري رقم 143/97 والمرسوم التنفيذي رقم 91-311 المتعلق بالمحاسبة العمومية') }}
    </div>

</body>
</html>

