<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر فتح أظرفة المواضيع') }} - {{ $incident->incident_number }}</title>
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

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 15pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 6px 26px; background: #f0f2f9; }
  .doc-subtitle { font-size: 9pt; color: #555; margin-top: 4px; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt; }
  .info-table td { padding: 6px 10px; border: 1px solid #bbb; }
  .info-table .lbl { background: #e8eaf6; font-weight: bold; width: 30%; color: #1a237e; }

  .declaration-box { border: 1.5px solid #1a237e; background: #f8f9ff; border-radius: 4px; padding: 12px; margin-bottom: 16px; font-size: 10pt; line-height: 1.6; text-align: justify; }

  .witness-section { margin-bottom: 16px; }
  .section-title { font-weight: bold; color: #1a237e; font-size: 10.5pt; border-bottom: 1px solid #c5cae9; padding-bottom: 4px; margin-bottom: 8px; }
  .witness-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .witness-card { border: 1px solid #bbb; border-radius: 4px; padding: 8px; background: #fafafa; font-size: 9.5pt; }

  .signatures { display: flex; justify-content: space-between; gap: 14px; margin-top: 20px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 85px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 9.5pt; margin-bottom: 4px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1a237e; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — الديوان الوطني للامتحانات والمسابقات (ONEC)</div>
  <div class="meta-row">
    <div>مديرية التربية لولاية: <strong>{{ $school?->state ?? '........................' }}</strong></div>
    <div class="institution">مركز الإجراء: <strong>{{ $center?->center_name }}</strong></div>
    <div>دورة: <strong>{{ $center?->session_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>محضر فتح أظرفة مواضيع الامتحان وتأكيد سلامتها</h1>
  <div class="doc-subtitle">Procès-Verbal d'Ouverture des Plis de Sujets (ONEC)</div>
</div>

<table class="info-table">
  <tr>
    <td class="lbl">رقم ومرجع المحضر:</td>
    <td><strong>{{ $incident->incident_number }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">امتحان الشهادة:</td>
    <td><strong>{{ $center?->exam_type?->label() }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">مادة الامتحان:</td>
    <td><strong style="font-size: 11pt; color: #1a237e;">{{ $incident->subject_name }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">تاريخ ويوم الجلسة:</td>
    <td>{{ $incident->incident_date?->format('Y-m-d') }} (الفترة: {{ $incident->session_time === 'morning' ? 'الصباحية' : 'المسائية' }})</td>
  </tr>
  <tr>
    <td class="lbl">رقم القاعة وموقعها:</td>
    <td><strong>{{ $room?->room_label ?? 'القاعة المركزية' }}</strong> - {{ $room?->physical_location ?? 'الجناح الرئيسي' }}</td>
  </tr>
</table>

<div class="declaration-box">
  <strong>إشهاد رسمي:</strong> نُشهد نحن الموقعين أسفله، رئيس القاعة والأساتذة الحراس، بحضور تلميذين شاهدين من المترشحين، أننا عاينّا أظرفة مواضيع مادة <strong>({{ $incident->subject_name }})</strong> المخصصة لهذه القاعة، وتأكدنا جميعا من سلامة ختمها وشمعها الأمني وعدم تعرضها لأي فتح أو تمزيق سابق، وتم فتح الظرف أمام الجميع في التوقيت النظامي المحدد.
</div>

<div class="witness-section">
  <div class="section-title">معاينة وإشهاد المترشحَين الشاهدَين من القاعة:</div>
  <div class="witness-grid">
    <div class="witness-card">
      <div>المترشح الشاهد (01): ............................................</div>
      <div style="margin-top: 4px;">رقم التسجيل: ....................................................</div>
      <div style="margin-top: 18px; text-align: center; font-weight: bold;">توقيع المترشح (01)</div>
    </div>
    <div class="witness-card">
      <div>المترشح الشاهد (02): ............................................</div>
      <div style="margin-top: 4px;">رقم التسجيل: ....................................................</div>
      <div style="margin-top: 18px; text-align: center; font-weight: bold;">توقيع المترشح (02)</div>
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">الأساتذة الحراس بالقاعة</div>
    <div style="font-size: 8pt; color: #555;">(معاينة الفتح وتوزيع المواضيع)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس القاعة المسؤول</div>
    <div style="font-size: 8pt; color: #555;">(المسؤولية القانونية عن القاعة)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس مركز الإجراء</div>
    <div style="font-size: 8pt; color: #555;">(تأشيرة وختم المركز)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة المحضر') }}</button>

</body>
</html>
