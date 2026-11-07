<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('رخصة خروج تلميذ داخلي') }} - {{ $pass['number'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #0d47a1; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 6px; }
  .republic-header .institution { font-weight: bold; color: #0d47a1; font-size: 11.5pt; }

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 14pt; font-weight: bold; color: #0d47a1; border: 2px solid #0d47a1; display: inline-block; padding: 4px 24px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .num { font-size: 10pt; font-weight: bold; color: #333; margin-top: 4px; }

  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
  .info-box { border: 1px solid #bbdefb; background: #fbfdff; padding: 8px 12px; border-radius: 4px; font-size: 9.5pt; }
  .info-box .title { font-weight: bold; color: #0d47a1; margin-bottom: 4px; border-bottom: 1px dashed #bbdefb; padding-bottom: 2px; }

  .details-box { border: 1.5px solid #0d47a1; background: #fff; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 9.5pt; line-height: 1.8; }
  .details-box strong { color: #0d47a1; }

  .undertaking { border: 1px solid #bbb; background: #fdfdfd; padding: 10px 14px; border-radius: 4px; font-size: 9pt; line-height: 1.7; margin-bottom: 20px; }

  .signatures { display: flex; justify-content: space-between; gap: 15px; margin-top: 20px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 8px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 9pt; margin-bottom: 4px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #0d47a1; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $institution['wilaya'] }}</div>
  <div class="meta-row">
    <div class="institution">المؤسسة: <strong>{{ $institution['name'] }}</strong> (مصلحة النظام الداخلي)</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>بطاقة ورخصة خروج تلميذ(ة) داخلي (Billet de Sortie)</h1>
  <div class="num">رقم الرخصة: <strong>{{ $pass['number'] }}</strong> | طبيعة الخروج: <strong>{{ $pass['type'] }}</strong></div>
</div>

<div class="info-grid">
  <div class="info-box">
    <div class="title">بيانات التلميذ الداخلي</div>
    <div>لقب واسم التلميذ: <strong>{{ $student['full_name'] }}</strong></div>
    <div>رقم التعريف المدرسي: <strong>{{ $student['matricule'] }}</strong></div>
    <div>القسم / الفوج التربوي: <strong>{{ $student['classroom'] }}</strong></div>
  </div>
  <div class="info-box">
    <div class="title">بيانات الولي / المرافق المفوّض</div>
    <div>اسم ولقب الولي أو المرافق: <strong>{{ $pass['escort'] }}</strong></div>
    <div>رقم هاتف الولي: <strong>{{ $student['guardian_phone'] }}</strong></div>
    <div>الوجهة / مقر الإقامة: <strong>{{ $pass['destination'] }}</strong></div>
  </div>
</div>

<div class="details-box">
  <div>تاريخ وساعة مغادرة المؤسسة: <strong>{{ $pass['departure'] }}</strong></div>
  <div>تاريخ وساعة العودة الإلزامية للمرقد: <strong>{{ $pass['expected_return'] }}</strong></div>
  <div>حالة الرخصة: <strong>{{ $pass['status'] }}</strong></div>
  @if($pass['notes'])
    <div>ملاحظات وتوجيهات خاصة: {{ $pass['notes'] }}</div>
  @endif
</div>

<div class="undertaking">
  <strong>تعهد والتزام الولي أو المرافق:</strong><br>
  أنا الموقع أسفله، السيد(ة): <strong>{{ $pass['escort'] }}</strong>، بصفتي ولي أمر / المرافق المفوّض للتلميذ(ة) المذكور أعلاه، أتعهد باستلامه ومرافقته طيلة فترة الخروج المرخص بها، وتحمل كامل المسؤولية المدنية عنه، وضمان عودته والتحاقه بالمرقد الداخلي للمؤسسة قبل انقضاء الأجل المحدد أعلاه.
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">توقيع الولي أو المرافق المستلم</div>
    <div style="font-size: 8pt; color: #555;">قرئ وقبل، مع كتابة الاسم والتاريخ</div>
  </div>
  <div class="sig-box">
    <div class="title">عون الحراسة ومراقبة الدخول والخروج</div>
    <div style="font-size: 8pt; color: #555;">ساعة الخروج الفعلية والتأشيرة</div>
  </div>
  <div class="sig-box">
    <div class="title">مستشار التربية المسؤول عن النظام الداخلي</div>
    <div style="font-size: 8pt; color: #555;">{{ $authorizer['name'] }} — الختم والتأشيرة</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة رخصة الخروج') }}</button>

</body>
</html>
