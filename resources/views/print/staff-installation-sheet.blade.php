<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>محضر التنصيب</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 13pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 20mm 18mm 18mm 18mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 3px double #1a237e; padding-bottom: 10px; margin-bottom: 14px; }
  .republic-header .republic { font-size: 11pt; letter-spacing: 1px; color: #1a237e; }
  .republic-header .ministry { font-size: 10.5pt; color: #333; margin-top: 2px; }
  .republic-header .directorate { font-size: 10.5pt; color: #333; }
  .republic-header .institution { font-size: 13pt; font-weight: bold; margin-top: 6px; color: #1a237e; }

  .doc-title { text-align: center; margin: 18px 0 10px; }
  .doc-title h1 { font-size: 20pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 6px 30px; }
  .doc-subtitle { text-align: center; font-size: 10.5pt; color: #555; margin-bottom: 18px; font-style: italic; }

  .preamble { margin-bottom: 16px; font-size: 12.5pt; line-height: 2; border: 1px solid #bbb; padding: 10px 14px; background: #f9f9fb; border-radius: 4px; }
  .preamble strong { color: #1a237e; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12.5pt; }
  .info-table td { padding: 7px 10px; border: 1px solid #bbb; vertical-align: top; }
  .info-table .label { background: #e8eaf6; font-weight: bold; width: 32%; color: #1a237e; white-space: nowrap; }
  .info-table .value { background: #fff; }

  .section-title { font-size: 14pt; font-weight: bold; color: #1a237e; margin: 20px 0 8px; border-right: 5px solid #1a237e; padding-right: 10px; }

  .body-text { font-size: 12.5pt; line-height: 2.1; margin-bottom: 14px; text-align: justify; }

  .signatures { margin-top: 40px; display: flex; justify-content: space-between; gap: 20px; }
  .sig-block { flex: 1; border: 1px solid #bbb; padding: 12px 10px 50px; text-align: center; background: #fafafa; }
  .sig-block .sig-title { font-weight: bold; font-size: 11.5pt; margin-bottom: 6px; color: #1a237e; }
  .sig-block .sig-name { font-size: 11pt; color: #555; margin-top: 4px; border-top: 1px dashed #bbb; padding-top: 6px; }

  .footer { margin-top: 30px; border-top: 1px solid #bbb; padding-top: 10px; text-align: center; font-size: 9pt; color: #888; }
  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1a237e; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 11pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية</div>
  <div class="directorate">{{ $school->directorate_name ?? 'مديرية التربية' }}</div>
  <div class="institution">{{ $school->school_name ?? 'المؤسسة التربوية' }}</div>
</div>

<div class="doc-title">
  <h1>محـضـر تـنـصـيـب</h1>
</div>
<div class="doc-subtitle">Procès-Verbal d'Installation</div>

<div class="preamble">
  بناءً على المرسوم التنفيذي رقم <strong>08-315 المؤرخ في 11 أكتوبر 2008</strong> المتضمن القانون الأساسي الخاص بموظفي التربية الوطنية، وعلى الأمر رقم <strong>06-03 المؤرخ في 15 يوليو 2006</strong> المتضمن القانون الأساسي للوظيفة العمومية،<br>
  وبناءً على <strong>قرار التعيين رقم {{ $installation->recruitment_order_ref ?? '...' }}</strong>،
  @if($installation->ministry_decision_ref)
    والمقرر الوزاري رقم <strong>{{ $installation->ministry_decision_ref }}</strong>،
  @endif
  <br>يُنصَّب السيد/السيدة المبيَّن هويته أدناه في الوظيفة المحددة بمؤسستنا.
</div>

<div class="section-title">أولاً: الهوية المدنية والمهنية</div>
<table class="info-table">
  <tr>
    <td class="label">الاسم واللقب</td>
    <td class="value">{{ $user->name ?? '—' }}</td>
    <td class="label">رقم التسجيل (الماتريكول)</td>
    <td class="value">{{ $user->registration_number ?? '—' }}</td>
  </tr>
  <tr>
    <td class="label">الوظيفة / الصفة</td>
    <td class="value">{{ $installation->role_label ?? '—' }}</td>
    <td class="label">الرتبة القانونية</td>
    <td class="value">{{ $installation->statutory_grade ?? $user->statutory_grade ?? '—' }}</td>
  </tr>
  <tr>
    <td class="label">تاريخ الميلاد</td>
    <td class="value">{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->locale('ar')->isoFormat('LL') : '—' }}</td>
    <td class="label">مكان الميلاد</td>
    <td class="value">{{ $user->birth_place ?? '—' }}</td>
  </tr>
  <tr>
    <td class="label">المؤسسة السابقة</td>
    <td class="value" colspan="3">{{ $installation->previous_institution ?? 'أول تعيين' }}</td>
  </tr>
</table>

<div class="section-title">ثانياً: بيانات التنصيب</div>
<table class="info-table">
  <tr>
    <td class="label">تاريخ التنصيب</td>
    <td class="value">{{ $installation->installation_date->locale('ar')->isoFormat('LL') }}</td>
    <td class="label">مرجع قرار التعيين</td>
    <td class="value">{{ $installation->recruitment_order_ref ?? '—' }}</td>
  </tr>
  <tr>
    <td class="label">المؤسسة المعيَّن بها</td>
    <td class="value" colspan="3">{{ $school->school_name ?? '—' }}</td>
  </tr>
  @if($installation->notes)
  <tr>
    <td class="label">ملاحظات</td>
    <td class="value" colspan="3">{{ $installation->notes }}</td>
  </tr>
  @endif
</table>

<div class="body-text">
  تفيد الإدارة بأنه قد جرى تنصيب السيد/السيدة <strong>{{ $user->name ?? '—' }}</strong> بصفة <strong>{{ $installation->role_label ?? '—' }}</strong> بمؤسستنا ابتداءً من تاريخ <strong>{{ $installation->installation_date->locale('ar')->isoFormat('LL') }}</strong>، وذلك وفق الأحكام التشريعية والتنظيمية المعمول بها.
</div>

<div class="signatures">
  <div class="sig-block">
    <div class="sig-title">توقيع الموظف المنصَّب</div>
    <div class="sig-name">{{ $user->name ?? '—' }}</div>
  </div>
  <div class="sig-block">
    <div class="sig-title">مدير المؤسسة</div>
    <div class="sig-name">{{ $school->school_name ?? '' }}</div>
  </div>
</div>

<div class="footer">
  حُرِّر بـ {{ $school->municipality_name ?? '' }} بتاريخ {{ $printed_at }} | سجل أرشيف المؤسسة
</div>

<button class="print-btn" onclick="window.print()">🖨 طباعة</button>
</body>
</html>

