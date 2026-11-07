<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر إسقاط التجهيزات غير الصالحة للاستعمال') }}</title>
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
  .doc-title h1 { font-size: 15pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 5px 24px; background: #f0f2f9; }

  .preamble { margin-bottom: 12px; font-size: 10.5pt; line-height: 1.8; text-align: justify; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10.5pt; }
  .info-table td { padding: 6px 10px; border: 1px solid #bbb; }
  .info-table .lbl { background: #e8eaf6; font-weight: bold; width: 30%; color: #1a237e; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 14px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 5px 8px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .obs-box { border: 1px solid #c5cae9; padding: 8px 12px; margin-bottom: 14px; background: #fafbff; font-size: 10pt; line-height: 1.8; }
  .obs-box strong { color: #1a237e; }

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
    <div>السنة المالية: <strong>{{ now(config('app.timezone', 'Africa/Algiers'))->year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>محضر معاينة وإسقاط التجهيزات غير الصالحة للاستعمال</h1>
</div>

<div class="preamble">
  بناءً على المنشور الوزاري رقم 143/97 المتعلق بجرد الأملاك المنقولة بالمؤسسات التعليمية، اجتمعت لجنة المعاينة والإسقاط برئاسة السيد(ة) مدير المؤسسة وعضوية المقتصد وممثلي الأساتذة لمعاينة العتاد المقترح للإسقاط من سجل الجرد العام.
</div>

<table class="info-table">
  <tr>
    <td class="lbl">رقم المحضر الرسمي:</td>
    <td><strong>{{ $reform->reform_number }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">تاريخ المعاينة والانعقاد:</td>
    <td><strong>{{ $reform->reform_date->format('Y/m/d') }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">مجموع القطع المقترحة للإسقاط:</td>
    <td><strong>{{ $reform->total_items_count }} قطعة</strong></td>
  </tr>
  <tr>
    <td class="lbl">القيمة المالية التقديرية الإجمالية:</td>
    <td><strong>{{ number_format($reform->total_estimated_value, 2) }} دج</strong></td>
  </tr>
</table>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 6%;">#</th>
      <th style="width: 18%;">رقم الجرد</th>
      <th style="width: 40%; text-align: right; padding-right: 8px;">تعيين التجهيز / الأثاث</th>
      <th style="width: 12%;">تاريخ الاقتناء</th>
      <th style="width: 24%;">أسباب الإسقاط والوضعية الفنية</th>
    </tr>
  </thead>
  <tbody>
    @forelse($items as $index => $item)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td><strong>{{ $item->inventory_number }}</strong></td>
      <td style="text-align: right; padding-right: 8px;">
        <strong>{{ $item->designation }}</strong>
      </td>
      <td>{{ $item->acquisition_date?->format('Y/m/d') ?? '-' }}</td>
      <td style="font-size: 8.5pt;">{{ $item->reform_reason ?: 'تقادم وتهالك غير قابل للإصلاح' }}</td>
    </tr>
    @empty
    <tr>
      <td colspan="5" style="padding: 16px; color: #888;">
        لم تُسجل قطع مرفقة بهذا المحضر.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="obs-box">
  <strong>ملاحظات ومداولات لجنة الإسقاط:</strong>
  <div>{{ $reform->commission_observations ?: 'عاينت اللجنة العتاد المذكور وتأكدت من عدم جدوى إصلاحه اقتصادياً وتقترح عرضه على مصالح أملاك الدولة للتصرف فيه وفق التشريع المعمول به.' }}</div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">أعضاء لجنة المعاينة</div>
    <div style="font-size: 8.5pt; color: #555;">الأساتذة وممثلو الموظفين</div>
  </div>
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي</div>
    <div style="font-size: 8.5pt; color: #555;">عضو ومقرر اللجنة</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس اللجنة (مدير المؤسسة)</div>
    <div style="font-size: 8.5pt; color: #555;">المصادقة والختم الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة المحضر (Ctrl+P)</button>

</body>
</html>
