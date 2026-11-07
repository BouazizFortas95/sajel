<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سند تسليم واستلام مجانية المستلزمات') }} - {{ $distribution['voucher_number'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1b5e20; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 11pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 6px; }
  .republic-header .institution { font-weight: bold; color: #1b5e20; font-size: 11.5pt; }

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 14pt; font-weight: bold; color: #1b5e20; border: 2px solid #1b5e20; display: inline-block; padding: 4px 24px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .num { font-size: 10pt; font-weight: bold; color: #333; margin-top: 4px; }

  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
  .info-box { border: 1px solid #c8e6c9; background: #f9fbf9; padding: 8px 12px; border-radius: 4px; font-size: 9.5pt; }
  .info-box .title { font-weight: bold; color: #1b5e20; margin-bottom: 4px; border-bottom: 1px dashed #c8e6c9; padding-bottom: 2px; }

  .aid-details-box { border: 1.5px solid #1b5e20; background: #fff; padding: 12px; border-radius: 4px; margin-bottom: 16px; }
  .aid-details-box .heading { font-weight: bold; color: #1b5e20; font-size: 10.5pt; margin-bottom: 8px; }
  .aid-description { background: #f1f8e9; padding: 10px; border-radius: 4px; font-size: 10pt; line-height: 1.6; border: 1px solid #c8e6c9; margin-bottom: 8px; }

  .undertaking { border: 1px solid #bbb; background: #fdfdfd; padding: 10px 14px; border-radius: 4px; font-size: 9.5pt; line-height: 1.7; margin-bottom: 20px; }
  .undertaking strong { color: #1b5e20; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 24px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 10px; text-align: center; min-height: 90px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 9.5pt; margin-bottom: 6px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1b5e20; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مصلحة التضامن المدرسي</div>
  <div class="meta-row">
    <div>مديرية التربية لولاية: <strong>{{ $institution['wilaya'] }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution['name'] }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $campaign['academic_year'] }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>سند ومحضر تسليم واستلام مجانية المستلزمات المدرسية</h1>
  <div class="num">وصل رقم: <strong>{{ $distribution['voucher_number'] }}</strong> | التاريخ: <strong>{{ $distribution['distribution_date'] }}</strong></div>
</div>

<div class="info-grid">
  <div class="info-box">
    <div class="title">بيانات التلميذ المستفيد</div>
    <div>لقب واسم التلميذ: <strong>{{ $student['full_name'] }}</strong></div>
    <div>رقم التعريف المدرسي: <strong>{{ $student['matricule'] }}</strong></div>
    <div>القسم / الفوج التربوي: <strong>{{ $student['classroom'] }}</strong></div>
  </div>
  <div class="info-box">
    <div class="title">بيانات الولي / الكفيل المستلم</div>
    <div>اسم ولقب الولي: <strong>{{ $student['guardian_name'] }}</strong></div>
    <div>رقم الهاتف: <strong>{{ $student['guardian_phone'] }}</strong></div>
    <div>الجهة المانحة: <strong>{{ $distribution['donor_entity'] }}</strong></div>
  </div>
</div>

<div class="aid-details-box">
  <div class="heading">نوع وطبيعة الإعانة المدرسية المسلمة ({{ $distribution['aid_type'] }}):</div>
  <div class="aid-description">
    {{ $distribution['items_description'] }}
  </div>
  <div style="font-size: 9pt; color: #555;">
    القيمة التقديرية للإعانة: <strong>{{ number_format($distribution['estimated_value'], 2) }} دج</strong>
    @if($distribution['notes'])
      | ملاحظات: {{ $distribution['notes'] }}
    @endif
  </div>
</div>

<div class="undertaking">
  <strong>إشعار وتعهد بالاستلام:</strong><br>
  أنا الموقع أسفله، السيد(ة): <strong>{{ $student['guardian_name'] }}</strong>، ولي أمر التلميذ(ة): <strong>{{ $student['full_name'] }}</strong>، أصرح بأنني استلمت فعلياً المستلزمات والكتب المذكورة أعلاه في إطار برنامج التضامن المدرسي ومجانية الكتاب والحقيبة المدرسية للسنة الدراسية <strong>{{ $campaign['academic_year'] }}</strong>، وأتعهد بالمحافظة عليها طيلة الموسم الدراسي واستعمالها للأغراض البيداغوجية والتربوية المخصصة لها.
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">توقيع وبصمة الولي / الكفيل المستلم</div>
    <div style="font-size: 8.5pt; color: #555;">قرئ وقبل، مع البصمة والتاريخ</div>
  </div>
  <div class="sig-box">
    <div class="title">المسير المالي والمقتصد (المسلّم)</div>
    <div style="font-size: 8.5pt; color: #555;">{{ $issuer['name'] }} — التأشيرة والختم</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة (المصادق)</div>
    <div style="font-size: 8.5pt; color: #555;">الختم الرسمي للمؤسسة</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة سند التسليم') }}</button>

</body>
</html>

