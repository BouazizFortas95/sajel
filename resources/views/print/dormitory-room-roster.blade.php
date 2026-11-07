<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('مخطط توزيع المرقد') }} - {{ $room['name'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 10mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #0d47a1; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 10.5pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #0d47a1; font-size: 10.5pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #0d47a1; border: 2px solid #0d47a1; display: inline-block; padding: 3px 20px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .sub { font-size: 8.5pt; color: #444; margin-top: 3px; }

  .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
  .card { border: 1px solid #bbdefb; background: #f5f9ff; border-radius: 4px; padding: 5px 8px; text-align: center; }
  .card .label { font-size: 7.5pt; color: #555; margin-bottom: 2px; }
  .card .val { font-size: 10.5pt; font-weight: bold; color: #0d47a1; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 10px; }
  .data-table th, .data-table td { border: 1px solid #999; padding: 4px 5px; text-align: center; }
  .data-table th { background: #e3f2fd; color: #0d47a1; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .signatures { display: flex; justify-content: space-between; gap: 15px; margin-top: 14px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 6px; text-align: center; min-height: 65px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 8.5pt; margin-bottom: 3px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #0d47a1; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $institution['wilaya'] }}</div>
  <div class="meta-row">
    <div>الدائرة: <strong>{{ $institution['daira'] }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution['name'] }}</strong> (مصلحة النظام الداخلي)</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>مخطط توزيع نزلاء المرقد وجرد الأسرّة والخزانات الفردية</h1>
  <div class="sub">{{ $room['name'] }} (رمز المرقد: {{ $room['code'] }} — {{ $room['wing'] }} — الطابق {{ $room['floor'] }})</div>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">طاقة الاستيعاب الإجمالية</div>
    <div class="val">{{ $room['capacity'] }} سرير</div>
  </div>
  <div class="card">
    <div class="label">الأسرّة المشغولة فعلياً</div>
    <div class="val">{{ $room['occupied'] }} نزيل</div>
  </div>
  <div class="card">
    <div class="label">الأسرّة الشاغرة المتاحة</div>
    <div class="val">{{ $room['available'] }} سرير شاغر</div>
  </div>
  <div class="card">
    <div class="label">المشرف المسؤول عن الجناح</div>
    <div class="val" style="font-size: 9.5pt;">{{ $room['supervisor'] }}</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 4%;">رقم</th>
      <th style="width: 10%;">رقم السرير</th>
      <th style="width: 10%;">رقم الخزانة</th>
      <th style="width: 22%;">لقب واسم التلميذ النزيل</th>
      <th style="width: 12%;">رقم التعريف المدرسي</th>
      <th style="width: 10%;">القسم</th>
      <th style="width: 10%;">تاريخ الدخول</th>
      <th style="width: 10%;">استلام الفراش</th>
      <th style="width: 12%;">ملاحظات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($allocations as $alloc)
      <tr>
        <td>{{ $alloc['index'] }}</td>
        <td style="font-weight: bold;">{{ $alloc['bed_number'] }}</td>
        <td style="font-weight: bold;">{{ $alloc['locker_number'] }}</td>
        <td style="text-align: right; padding-right: 6px; font-weight: bold;">{{ $alloc['student_name'] }}</td>
        <td style="font-family: monospace;">{{ $alloc['matricule'] }}</td>
        <td>{{ $alloc['classroom'] }}</td>
        <td>{{ $alloc['check_in_date'] }}</td>
        <td>{{ $alloc['linen_received'] }}</td>
        <td style="font-size: 8pt; color: #555;">{{ $alloc['notes'] }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="9" style="padding: 12px; color: #777;">المرقد شاغر حالياً ولا توجد أسرّة موزعة.</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">مساعد التربية / المشرف المسؤول عن الجناح</div>
    <div style="font-size: 8pt; color: #555;">التأشيرة، التاريخ والملاحظات</div>
  </div>
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي (مسؤول العهدة والمفروشات)</div>
    <div style="font-size: 8pt; color: #555;">مراقبة ومطابقة عهدة الأفرشة والأسرّة</div>
  </div>
  <div class="sig-box">
    <div class="title">مستشار التربية الرئيسي / رئيس المصلحة</div>
    <div style="font-size: 8pt; color: #555;">المصادقة والتأشيرة اليومية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة مخطط المرقد') }}</button>

</body>
</html>
