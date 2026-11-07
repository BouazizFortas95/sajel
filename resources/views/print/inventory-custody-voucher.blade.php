<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('سند تسليم واستلام عهدة التجهيزات المدرسية') }} - {{ $allocation->room_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Amiri', 'DejaVu Sans', 'Arial', sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .republic {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .ministry {
            font-size: 10pt;
            margin-bottom: 4px;
        }
        .school-meta {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-top: 4px;
        }
        .doc-title-box {
            background-color: #f3f4f6;
            border: 1.5px solid #111827;
            padding: 8px 12px;
            text-align: center;
            margin: 10px 0;
            border-radius: 4px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 9.5pt;
            color: #4b5563;
            margin-top: 3px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10pt;
        }
        .meta-grid td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f9fafb;
            width: 20%;
        }
        .meta-val {
            width: 30%;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #374151;
            padding: 5px 6px;
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
        .pledge-box {
            border: 1.5px dashed #4b5563;
            background-color: #fdfdfd;
            padding: 8px 12px;
            margin: 10px 0;
            font-size: 9.5pt;
            line-height: 1.5;
            text-align: justify;
            border-radius: 4px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .sig-block {
            width: 30%;
            text-align: center;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 6px;
            min-height: 85px;
            background-color: #fafafa;
        }
        .sig-title {
            font-weight: bold;
            font-size: 9.5pt;
            border-bottom: 1px dashed #9ca3af;
            padding-bottom: 3px;
            margin-bottom: 45px;
        }
        .footer-note {
            font-size: 8pt;
            color: #6b7280;
            text-align: center;
            margin-top: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
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
            <div><strong>{{ __('السنة الدراسية') }}:</strong> {{ $allocation->academic_year ?? '2026/2027' }}</div>
        </div>
    </div>

    <div class="doc-title-box">
        <h1 class="doc-title">{{ __('سند ومحضر تسليم واستلام عهدة التجهيزات المدرسية') }}</h1>
        <div class="doc-subtitle">{{ __('(المكتبية، العلمية، المخبرية، البيداغوجية، والإعلام الآلي - المنشور الوزاري 143/97)') }}</div>
    </div>

    <table class="meta-grid">
        <tr>
            <td class="meta-label">{{ __('الموقع / الجناح') }}:</td>
            <td class="meta-val"><strong>{{ $allocation->room_name }}</strong></td>
            <td class="meta-label">{{ __('طبيعة الفضاء والعهدة') }}:</td>
            <td class="meta-val">
                @if($allocation->custody_nature === 'scientific_lab' || $allocation->room_type === 'lab')
                    {{ __('عهدة علمية ومخبرية (مخبر/ورشة)') }}
                @elseif($allocation->custody_nature === 'office' || $allocation->room_type === 'office')
                    {{ __('عهدة مكتبية وإدارية') }}
                @else
                    {{ __('عهدة بيداغوجية عامة (قاعة تدريس/فضاء)') }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('المسؤول المستلم للعهدة') }}:</td>
            <td class="meta-val"><strong>{{ $allocation->responsibleUser?->name ?? 'غير محدد' }}</strong></td>
            <td class="meta-label">{{ __('الوظيفة / الصفة') }}:</td>
            <td class="meta-val">{{ $allocation->responsibleUser?->role?->label() ?? __('أستاذ / موظف مسؤول') }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('مرجع بطاقة العهدة') }}:</td>
            <td class="meta-val">{{ $allocation->allocation_ref }}</td>
            <td class="meta-label">{{ __('تاريخ التوثيق') }}:</td>
            <td class="meta-val">{{ $allocation->signed_at?->format('Y-m-d') ?? $generatedAt->format('Y-m-d') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 17%;">{{ __('رقم الجرد النظامي') }}</th>
                <th style="width: 30%;">{{ __('تعيين العتاد والتجهيز') }}</th>
                <th style="width: 24%;">{{ __('المواصفات التقنية') }}</th>
                <th style="width: 8%;">{{ __('الكمية') }}</th>
                <th style="width: 16%;">{{ __('الحالة عند الاستلام') }}</th>
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
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding: 15px; color: #6b7280;">{{ __('لا توجد تجهيزات مسجلة في هذا الموقع حالياً') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f3f4f6; font-weight: bold;">
                <td colspan="4" class="text-right">{{ __('المجموع الإجمالي للتجهيزات المسلمة على سبيل العهدة:') }}</td>
                <td>{{ $totalQuantity }}</td>
                <td>{{ number_format($totalValue, 2) }} دج</td>
            </tr>
        </tfoot>
    </table>

    <div class="pledge-box">
        <strong>{{ __('تعهد والتزام صاحب العهدة:') }}</strong>
        {{ __('أنا الموقع أدناه، أصرح أنني تسلمت التجهيزات والأثاث المبينة أعلاه بحالة مطابقة للجدول، وأتعهد بالمحافظة عليها وحسن استعمالها وصيانتها ضمن المهام الموكلة إليّ، كما أتعهد بعدم نقل أي قطعة منها خارج هذا الفضاء إلا بإذن كتابي ومسبق من المصلحة المسيرة (المقتصدية) وتحت إشراف مدير المؤسسة.') }}
    </div>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">{{ __('المستلم (صاحب العهدة)') }}</div>
            <div>{{ $allocation->responsibleUser?->name ?? '.......................' }}</div>
        </div>

        <div class="sig-block">
            <div class="sig-title">{{ __('المسلِّم (المسير المالي / المقتصد)') }}</div>
            <div>{{ __('المصالح الاقتصادية والمالية') }}</div>
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
