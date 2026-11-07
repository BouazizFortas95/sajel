<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة تخصيص العتاد') }} - {{ $allocation->room_name }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 11pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 10.5pt; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 11pt; }

  .doc-title { text-align: center; margin: 10px 0 14px; }
  .doc-title h1 { font-size: 16pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 5px 24px; background: #f0f2f9; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10.5pt; }
  .info-table td { padding: 6px 10px; border: 1px solid #bbb; }
  .info-table .lbl { background: #e8eaf6; font-weight: bold; width: 28%; color: #1a237e; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 14px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 5px 8px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .signatures { display: flex; justify-content: space-between; gap: 16px; margin-top: 24px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 10pt; margin-bottom: 4px; }

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
    <div>السنة الدراسية: <strong>{{ $allocation->academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>بطاقة تخصيص العتاد والتجهيز (المنشور 143/97)</h1>
</div>

<table class="info-table">
  <tr>
    <td class="lbl">رقم ومرجع بطاقة التخصيص:</td>
    <td><strong>{{ $allocation->allocation_ref }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">اسم وموقع الحجرة / المخبر:</td>
    <td><strong>{{ $allocation->room_name }}</strong> ({{ $allocation->room_type }})</td>
  </tr>
  <tr>
    <td class="lbl">المسؤول عن العهدة / الأستاذ(ة):</td>
    <td><strong>{{ $allocation->responsibleUser?->name ?? '....................................' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">مجموع القطع المخصصة:</td>
    <td><strong>{{ $items->count() }} قطعة وتجهيز</strong></td>
  </tr>
</table>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 6%;">#</th>
      <th style="width: 18%;">رقم الجرد</th>
      <th style="width: 44%; text-align: right; padding-right: 8px;">تعيين واسم التجهيز / الأثاث</th>
      <th style="width: 12%;">العدد</th>
      <th style="width: 20%;">الحالة المعاينة</th>
    </tr>
  </thead>
  <tbody>
    @forelse($items as $index => $item)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td><strong>{{ $item->inventory_number }}</strong></td>
      <td style="text-align: right; padding-right: 8px;">
        <strong>{{ $item->designation }}</strong>
        @if($item->specifications)
          <div style="font-size: 8pt; color: #555;">{{ $item->specifications }}</div>
        @endif
      </td>
      <td><strong>{{ $item->quantity }}</strong></td>
      <td>{{ $item->condition->label() }}</td>
    </tr>
    @empty
    <tr>
      <td colspan="5" style="padding: 16px; color: #888;">
        لم تُسجل تجهيزات مخصصة لهذه القاعة حتى الآن.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المسؤول عن العهدة (الأستاذ / الموظف)</div>
    <div style="font-size: 9pt; color: #555;">استلمت التجهيزات المذكورة أعلاه في حالة صالحة للاستعمال وألتزم بالحفاظ عليها.</div>
  </div>
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي</div>
    <div style="font-size: 9pt; color: #555;">التأشيرة ومطابقة سجل الجرد العام</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية</div>
    <div style="font-size: 9pt; color: #555;">المصادقة والختم الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة البطاقة (Ctrl+P)</button>

</body>
</html>

