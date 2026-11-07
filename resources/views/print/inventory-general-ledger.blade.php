<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سجل الجرد الدائم العام والخاص') }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 12mm 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 6px; margin-bottom: 10px; }
  .republic-header .republic { font-size: 10pt; letter-spacing: 1px; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 11pt; }

  .doc-title { text-align: center; margin: 8px 0; }
  .doc-title h1 { font-size: 15pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 4px 20px; background: #f0f2f9; }
  .doc-subtitle { font-size: 9.5pt; color: #444; margin-top: 3px; }

  .stats-strip { display: flex; gap: 10px; margin-bottom: 10px; }
  .stat-card { flex: 1; border: 1px solid #bbb; padding: 6px 8px; text-align: center; background: #fff; border-radius: 4px; border-top: 3px solid #1a237e; }
  .stat-card .num { font-size: 13pt; font-weight: bold; color: #1a237e; }
  .stat-card .lbl { font-size: 8.5pt; color: #666; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 12px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 14px; }
  .sig-block { flex: 1; border: 1px solid #ccc; padding: 8px; text-align: center; font-size: 9pt; min-height: 70px; background: #fafafa; }
  .sig-block .role { font-weight: bold; color: #1a237e; margin-bottom: 4px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1a237e; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية</div>
  <div class="meta-row">
    <div>مديرية التربية لولاية: <strong>{{ $school?->state ?? '........................' }}</strong></div>
    <div class="institution">{{ $school?->name ?? 'المؤسسة التعليمية' }}</div>
    <div>السنة المالية / الدراسية: <strong>{{ now(config('app.timezone', 'Africa/Algiers'))->year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>سجل الجرد الدائم العام والخاص (المنشور الوزاري 143/97)</h1>
  <div class="doc-subtitle">الفئة: <strong>{{ $category ? $category->label() : 'جميع فئات التجهيز والأثاث' }}</strong> — تاريخ الاستخراج: <strong>{{ now(config('app.timezone', 'Africa/Algiers'))->format('Y/m/d') }}</strong></div>
</div>

<div class="stats-strip">
  <div class="stat-card">
    <div class="num">{{ $totalItems }}</div>
    <div class="lbl">مجموع القطع المقيدة</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($totalCost, 2) }} دج</div>
    <div class="lbl">القيمة المالية الإجمالية المقدرة</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $items->where('is_reformed', false)->count() }}</div>
    <div class="lbl">العتاد النشط في العهدة</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $items->where('is_reformed', true)->count() }}</div>
    <div class="lbl">العتاد المسقط من الجرد</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 10%;">رقم الجرد</th>
      <th style="width: 14%;">الفئة والتصنيف</th>
      <th style="width: 22%; text-align: right; padding-right: 6px;">تعيين واسم العتاد / التجهيز</th>
      <th style="width: 8%;">تاريخ الاقتناء</th>
      <th style="width: 10%;">القيمة (دج)</th>
      <th style="width: 5%;">العدد</th>
      <th style="width: 15%;">مكان التخصيص والعهدة</th>
      <th style="width: 9%;">الحالة الراهنة</th>
      <th style="width: 7%;">الملاحظات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($items as $item)
    <tr>
      <td><strong>{{ $item->inventory_number }}</strong></td>
      <td>{{ $item->category->label() }}</td>
      <td style="text-align: right; padding-right: 6px;">
        <strong>{{ $item->designation }}</strong>
        @if($item->specifications)
          <div style="font-size: 8pt; color: #555;">{{ $item->specifications }}</div>
        @endif
      </td>
      <td>{{ $item->acquisition_date?->format('Y/m/d') ?? '-' }}</td>
      <td>{{ $item->acquisition_cost ? number_format($item->acquisition_cost, 2) : '-' }}</td>
      <td><strong>{{ $item->quantity }}</strong></td>
      <td>{{ $item->location_name }}</td>
      <td>
        <strong>{{ $item->condition->label() }}</strong>
      </td>
      <td style="font-size: 8pt;">
        {{ $item->is_reformed ? 'مسقط' : ($item->notes ?? '-') }}
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="9" style="padding: 16px; color: #888;">
        لا توجد تجهيزات مسجلة في هذا السجل بعد.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="signatures">
  <div class="sig-block">
    <div class="role">الموظف القائم بالعهدة / المخبري</div>
    <div>المعاينة ومطابقة العتاد الفعلي</div>
  </div>
  <div class="sig-block">
    <div class="role">المقتصد / المسير المالي</div>
    <div>مسؤول سجلات الجرد والمحاسبة المادية</div>
  </div>
  <div class="sig-block">
    <div class="role">مدير المؤسسة التعليمية</div>
    <div>المصادقة والتأشيرة الرسمية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة السجل (Ctrl+P)</button>

</body>
</html>

