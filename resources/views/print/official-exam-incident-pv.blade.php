<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر إثبات واقعة غش / إخلال بالامتحان') }} - {{ $incident->incident_number }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 11pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #b91c1c; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 10.5pt; color: #b91c1c; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #b91c1c; font-size: 11pt; }

  .doc-title { text-align: center; margin: 10px 0 14px; }
  .doc-title h1 { font-size: 15pt; font-weight: bold; color: #b91c1c; border: 2px solid #b91c1c; display: inline-block; padding: 5px 22px; background: #fef2f2; }
  .doc-subtitle { font-size: 8.5pt; color: #7f1d1d; margin-top: 4px; font-weight: bold; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
  .info-table td { padding: 5px 8px; border: 1px solid #bbb; }
  .info-table .lbl { background: #fee2e2; font-weight: bold; width: 30%; color: #991b1b; }

  .box { border: 1px solid #bbb; border-radius: 4px; padding: 10px; margin-bottom: 12px; background: #fafafa; }
  .box-title { font-weight: bold; color: #991b1b; font-size: 10pt; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 6px; }
  .box-content { font-size: 9.5pt; line-height: 1.5; text-align: justify; }

  .seized-box { border: 1.5px dashed #b91c1c; background: #fff5f5; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
  .seized-box .title { font-weight: bold; color: #b91c1c; font-size: 9.5pt; margin-bottom: 4px; }

  .legal-warning { font-size: 8pt; color: #555; text-align: justify; line-height: 1.4; margin-bottom: 14px; padding: 5px 8px; background: #f9f9f9; border-right: 3px solid #b91c1c; }

  .signatures { display: flex; justify-content: space-between; gap: 12px; margin-top: 18px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #991b1b; font-size: 9pt; margin-bottom: 4px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #b91c1c; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
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
  <h1>محضر إثبات محاولة غش / إخلال بسير الامتحان الرسمي</h1>
  <div class="doc-subtitle">طبقا لأحكام المرسوم التنفيذي 20-241 وقانون العقوبات لحماية نزاهة الامتحانات</div>
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
    <td class="lbl">مادة الامتحان وتوقيت الجلسة:</td>
    <td><strong>{{ $incident->subject_name }}</strong> ({{ $incident->session_time === 'morning' ? 'الفترة الصباحية' : 'الفترة المسائية' }}) - {{ $incident->incident_date?->format('Y-m-d') }}</td>
  </tr>
  <tr>
    <td class="lbl">اسم ولقب المترشح:</td>
    <td><strong style="font-size: 11pt; color: #b91c1c;">{{ $incident->candidate_name ?? 'مترشح بالقاعة' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">رقم التسجيل الرسمي:</td>
    <td><strong style="font-family: monospace; font-size: 11pt;">{{ $incident->candidate_registration_number ?? '---' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">القاعة وموقعها:</td>
    <td>{{ $room?->room_label ?? 'القاعة' }} ({{ $room?->physical_location ?? 'الجناح الرئيسي' }})</td>
  </tr>
</table>

<div class="box">
  <div class="box-title">حيثيات ووقائع المخالفة المضبوطة:</div>
  <div class="box-content">{{ $incident->description }}</div>
</div>

@if($incident->seized_evidence)
<div class="seized-box">
  <div class="title">المحجوزات ووسائل الغش المضبوطة في حوزة المترشح:</div>
  <div style="font-size: 9pt; color: #111;">{{ $incident->seized_evidence }}</div>
</div>
@endif

@if($incident->measures_taken)
<div class="box">
  <div class="box-title">الإجراءات والتدابير المتخذة فوراً:</div>
  <div class="box-content">{{ $incident->measures_taken }}</div>
</div>
@endif

<div class="legal-warning">
  <strong>تنبيه قانوني:</strong> يُحال هذا المحضر فورا إلى رئيس مركز الإجراء ولجنة الانضباط بالديوان الوطني للامتحانات والمسابقات لاتخاذ العقوبات الإدارية والتربوية (الإقصاء لعدة دورات) والإحالة على الجهات القضائية المختصة طبقا للتشريع الساري المفعول.
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">الأساتذة الحراس الشاهدون</div>
    <div style="font-size: 7.5pt; color: #555;">(معاينة واقعة الضبط)</div>
    <div style="margin-top: 20px; font-weight: bold;">التوقيع: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس القاعة المسؤول</div>
    <div style="font-size: 7.5pt; color: #555;">(تحرير المحضر وحجز الوسائل)</div>
    <div style="margin-top: 20px; font-weight: bold;">التوقيع: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس مركز الإجراء</div>
    <div style="font-size: 7.5pt; color: #555;">(الإخطار والمصادقة)</div>
    <div style="margin-top: 20px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">الملاحظ الوزاري المعتمد</div>
    <div style="font-size: 7.5pt; color: #555;">(تأشيرة المتابعة)</div>
    <div style="margin-top: 20px; font-weight: bold;">التوقيع: ........................</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة المحضر') }}</button>

</body>
</html>
