<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة اشتراك النقل المدرسي') }} - {{ $subscription['card_number'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .card-container { width: 110mm; margin: 20px auto; border: 2.5px solid #0d47a1; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
  .card-header { background: #0d47a1; color: #fff; text-align: center; padding: 8px 10px; }
  .card-header .republic { font-size: 9pt; font-weight: bold; }
  .card-header .title { font-size: 12pt; font-weight: bold; margin-top: 2px; }
  .card-header .sub { font-size: 8pt; opacity: 0.9; }

  .card-body { padding: 12px; display: flex; gap: 12px; }
  .photo-box { width: 30mm; height: 38mm; border: 1.5px dashed #aaa; border-radius: 4px; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 8pt; color: #777; background: #fbfbfb; }

  .card-info { flex: 1; font-size: 9pt; line-height: 1.6; }
  .card-info .label { font-weight: bold; color: #0d47a1; display: inline-block; width: 65px; }

  .card-footer { background: #f0f4f8; border-top: 1px solid #bbdefb; padding: 6px 12px; display: flex; justify-content: space-between; align-items: center; font-size: 8pt; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #0d47a1; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="card-container">
  <div class="card-header">
    <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
    <div class="title">بطاقة ركوب واشتراك النقل المدرسي</div>
    <div class="sub">{{ $institution['name'] }} — بلدية {{ $institution['commune'] }}</div>
  </div>

  <div class="card-body">
    <div class="photo-box">
      <div>صورة<br>شمسية<br>للتلميذ</div>
    </div>

    <div class="card-info">
      <div><span class="label">رقم البطاقة:</span> <strong>{{ $subscription['card_number'] }}</strong></div>
      <div><span class="label">اسم التلميذ:</span> <strong>{{ $student['full_name'] }}</strong></div>
      <div><span class="label">القسم:</span> <strong>{{ $student['classroom'] }}</strong></div>
      <div><span class="label">تاريخ الميلاد:</span> {{ $student['date_of_birth'] }}</div>
      <div><span class="label">الخط:</span> <strong>{{ $route['name'] }} ({{ $route['code'] }})</strong></div>
      <div><span class="label">محطة الركوب:</span> <strong>{{ $subscription['pickup_stop'] }}</strong></div>
      <div><span class="label">توقيت الانطلاق:</span> {{ $route['morning_time'] }} صباحاً</div>
    </div>
  </div>

  <div class="card-footer">
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
    <div>هاتف السائق: {{ $route['driver_phone'] }}</div>
    <div>الختم الرسمي للمؤسسة</div>
  </div>
</div>

<div style="text-align: center; margin-top: 20px; font-size: 8.5pt; color: #666;">
  ملاحظة: هذه البطاقة شخصية وصالحة فقط للتنقل المدرسي على متن حافلات النقل المعتمدة للمؤسسة طيلة الموسم الدراسي الحالي.
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة بطاقة النقل المدرسي') }}</button>

</body>
</html>
