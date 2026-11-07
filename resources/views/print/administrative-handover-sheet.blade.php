<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>محضر تسليم المهام</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 12.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 20mm 18mm 18mm 18mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 3px double #880e4f; padding-bottom: 10px; margin-bottom: 14px; }
  .republic-header .republic { font-size: 11pt; letter-spacing: 1px; color: #880e4f; }
  .republic-header .ministry { font-size: 10.5pt; color: #333; margin-top: 2px; }
  .republic-header .institution { font-size: 13pt; font-weight: bold; margin-top: 6px; color: #880e4f; }

  .doc-title { text-align: center; margin: 18px 0 6px; }
  .doc-title h1 { font-size: 19pt; font-weight: bold; color: #880e4f; border: 2px solid #880e4f; display: inline-block; padding: 6px 24px; }
  .doc-subtitle { text-align: center; font-size: 10.5pt; color: #555; margin-bottom: 18px; font-style: italic; }

  .role-badge { text-align: center; margin-bottom: 14px; }
  .role-badge span { background: #fce4ec; color: #880e4f; border: 1.5px solid #880e4f; border-radius: 4px; padding: 4px 20px; font-size: 13pt; font-weight: bold; }

  .section-title { font-size: 13pt; font-weight: bold; color: #880e4f; margin: 16px 0 8px; border-right: 5px solid #880e4f; padding-right: 10px; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 12pt; }
  .info-table td { padding: 7px 10px; border: 1px solid #bbb; }
  .info-table .label { background: #fce4ec; font-weight: bold; width: 30%; color: #880e4f; white-space: nowrap; }

  .checklist { margin-bottom: 16px; }
  .checklist li { font-size: 12pt; line-height: 2; list-style: none; padding-right: 10px; }
  .checklist li::before { content: "☐ "; color: #880e4f; font-size: 14pt; }

  .body-text { font-size: 12pt; line-height: 2.1; margin-bottom: 14px; text-align: justify; border: 1px solid #ddd; padding: 10px 14px; background: #fff8f8; }

  .signatures { margin-top: 36px; display: flex; justify-content: space-between; gap: 14px; }
  .sig-block { flex: 1; border: 1px solid #bbb; padding: 12px 10px 50px; text-align: center; }
  .sig-block .sig-title { font-weight: bold; font-size: 11pt; color: #880e4f; margin-bottom: 4px; }
  .sig-block .sig-name { font-size: 11pt; border-top: 1px dashed #bbb; padding-top: 6px; margin-top: 4px; color: #555; }
  .sig-block.signed { background: #f1f8e9; }

  .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center; font-size: 9pt; color: #888; }
  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #880e4f; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 11pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — {{ $school->directorate_name ?? '' }}</div>
  <div class="institution">{{ $school->school_name ?? 'المؤسسة التربوية' }}</div>
</div>

<div class="doc-title">
  <h1>محضر تسليم واستلام المهام</h1>
</div>
<div class="doc-subtitle">Procès-Verbal de Passation de Consignes</div>

<div class="role-badge">
  <span>{{ $handover->role instanceof \App\Enums\HandoverRole ? $handover->role->label() : $handover->role }}</span>
</div>

<div class="section-title">أولاً: أطراف العملية</div>
<table class="info-table">
  <tr>
    <td class="label">الموظف المُسلِّم للمهام</td>
    <td>{{ $handover->outgoing_label }}</td>
  </tr>
  <tr>
    <td class="label">الموظف المُتسلِّم للمهام</td>
    <td>{{ $handover->incoming_label }}</td>
  </tr>
  <tr>
    <td class="label">تاريخ التسليم والاستلام</td>
    <td>{{ $handover->handover_date->locale('ar')->isoFormat('LL') }}</td>
  </tr>
</table>

<div class="section-title">ثانياً: محتوى الجرد والوضعية الإدارية</div>
<table class="info-table">
  @if($handover->inventory_ref)
  <tr>
    <td class="label">مرجع محضر الجرد</td>
    <td>{{ $handover->inventory_ref }}</td>
  </tr>
  @endif
  @if($handover->inventory_summary)
  <tr>
    <td class="label">ملخص الجرد</td>
    <td>{{ $handover->inventory_summary }}</td>
  </tr>
  @endif
  @if($handover->pending_dossiers)
  <tr>
    <td class="label">الملفات المعلقة والقضايا الجارية</td>
    <td>{{ $handover->pending_dossiers }}</td>
  </tr>
  @endif
  @if($handover->remarks)
  <tr>
    <td class="label">ملاحظات ومستجدات</td>
    <td>{{ $handover->remarks }}</td>
  </tr>
  @endif
</table>

<div class="section-title">ثالثاً: قائمة البنود المُسلَّمة (نموذج)</div>
<ul class="checklist">
  <li>سجلات المؤسسة الرسمية (سجل اليومية، سجل البريد الصادر والوارد، سجل الغيابات...)</li>
  <li>مفاتيح المكتب والمجلس والقاعات الإدارية</li>
  <li>الختم الرسمي للمؤسسة</li>
  <li>الملفات الإدارية للموظفين والتلاميذ</li>
  <li>الوثائق المالية والمحاسبية (عند الاقتضاء)</li>
  <li>المعدات الإعلامية والتقنية المسجلة في الجرد</li>
</ul>

<div class="body-text">
  تم تحرير هذا المحضر في <strong>{{ $school->school_name ?? '' }}</strong> بتاريخ <strong>{{ $handover->handover_date->locale('ar')->isoFormat('LL') }}</strong>، ويُثبَت تسليم المهام الرسمية من السيد/السيدة <strong>{{ $handover->outgoing_label }}</strong> إلى السيد/السيدة <strong>{{ $handover->incoming_label }}</strong> بصفة <strong>{{ $handover->role instanceof \App\Enums\HandoverRole ? $handover->role->label() : $handover->role }}</strong>، وفق ما هو مدوَّن أعلاه وما تضمنه الجرد المرافق.
</div>

<div class="signatures">
  <div class="sig-block {{ $handover->outgoing_signed ? 'signed' : '' }}">
    <div class="sig-title">الموظف المُسلِّم</div>
    <div class="sig-name">{{ $handover->outgoing_label }}</div>
    @if($handover->outgoing_signed)<div style="color:#388e3c;font-size:10pt;margin-top:6px;">✔ وقَّع</div>@endif
  </div>
  <div class="sig-block {{ $handover->incoming_signed ? 'signed' : '' }}">
    <div class="sig-title">الموظف المُتسلِّم</div>
    <div class="sig-name">{{ $handover->incoming_label }}</div>
    @if($handover->incoming_signed)<div style="color:#388e3c;font-size:10pt;margin-top:6px;">✔ وقَّع</div>@endif
  </div>
  <div class="sig-block {{ $handover->director_signed ? 'signed' : '' }}">
    <div class="sig-title">المدير المصادِق</div>
    <div class="sig-name">{{ $school->school_name ?? '' }}</div>
    @if($handover->director_signed)<div style="color:#388e3c;font-size:10pt;margin-top:6px;">✔ صادق</div>@endif
  </div>
</div>

<div class="footer">
  طُبع بتاريخ {{ $printed_at }} | أرشيف المؤسسة | سري للاستعمال الرسمي فقط
</div>

<button class="print-btn" onclick="window.print()">🖨 طباعة</button>
</body>
</html>

