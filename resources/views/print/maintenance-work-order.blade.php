<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سند وأمر أشغال وتدخل تقني') }} - {{ $order_number }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; padding: 10mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 11pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1b5e20; font-size: 11pt; }

  .doc-title { text-align: center; margin: 8px 0 12px; }
  .doc-title h1 { font-size: 13.5pt; font-weight: bold; color: #1b5e20; border: 2px solid #2e7d32; display: inline-block; padding: 4px 22px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .decree { font-size: 8.5pt; color: #555; margin-top: 3px; }

  .ref-banner { display: flex; justify-content: space-between; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px; font-size: 9pt; }
  .ref-banner strong { color: #1b5e20; }

  .section-box { border: 1px solid #c5cae9; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e8eaf6; color: #1a237e; font-weight: bold; font-size: 9.5pt; padding: 3px 10px; border-bottom: 1px solid #c5cae9; display: flex; justify-content: space-between; }
  .section-body { padding: 6px 10px; font-size: 9pt; line-height: 1.5; }

  .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .info-item { display: flex; }
  .info-label { font-weight: bold; color: #444; width: 140px; flex-shrink: 0; }
  .info-val { color: #111; flex-grow: 1; border-bottom: 1px dotted #ccc; }

  .narrative-box { background: #fafafa; border: 1px dashed #bbb; padding: 6px 10px; border-radius: 3px; min-height: 52px; margin-top: 4px; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 9pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 4px 8px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 14px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 85px; background: #fafafa; border-radius: 4px; font-size: 8.5pt; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 9pt; margin-bottom: 4px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #2e7d32; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية الوطنية</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div class="institution">مصلحة الصيانة والوسائل العامة</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('سند وأمر أشغال وتدخل تقني لصيانة المنشآت') }}</h1>
  <div class="decree">Bon de travail et ordre d'intervention technique — الصيانة الوقائية والعلاجية للهياكل المدرسية</div>
</div>

<div class="ref-banner">
  <div>رقم أمر الأشغال: <strong>{{ $order_number }}</strong></div>
  <div>تاريخ التبليغ: <strong>{{ $work_order->report_date?->format('Y-m-d') }}</strong></div>
  <div>مجال التدخل: <strong>{{ $work_order->category?->label() }}</strong></div>
  <div>درجة الاستعجال: <strong>{{ $work_order->priority_level?->label() }}</strong></div>
</div>

<div class="section-box">
  <div class="section-title">
    <span>1. بيانات الهيكل والموقع المعني بالعطب</span>
  </div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">اسم الهيكل / الجناح:</span>
        <span class="info-val">{{ $facility?->facility_name ?? __('غير محدد') }} ({{ $facility?->facility_code }})</span>
      </div>
      <div class="info-item">
        <span class="info-label">المبنى / الجناح:</span>
        <span class="info-val">{{ $facility?->building_block ?? '—' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الموقع الدقيق:</span>
        <span class="info-val">{{ $work_order->exact_location }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">حالة الهيكل العامة:</span>
        <span class="info-val">{{ $facility?->condition_rating?->label() ?? '—' }}</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">
    <span>2. وصف العطب والأشغال المطلوبة (Constat de la panne / Travaux demandés)</span>
  </div>
  <div class="section-body">
    <div class="narrative-box">
      {{ $work_order->issue_description }}
    </div>
    <div style="margin-top: 6px; font-size: 8.5pt; color: #555;">
      <span>المبلغ عن العطب: <strong>{{ $reported_by }}</strong></span>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">
    <span>3. تفاصيل الإنجاز والتدخل التقني (Exécution et rapport d'intervention)</span>
  </div>
  <div class="section-body">
    <table class="table-clean">
      <thead>
        <tr>
          <th>جهة التنفيذ</th>
          <th>اسم التقني / المقاولة</th>
          <th>تاريخ البرمجة</th>
          <th>تاريخ الانتهاء</th>
          <th>التكلفة التقديرية</th>
          <th>التكلفة الفعلية المنجزة</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ $work_order->intervention_type === 'InternalStaff' ? __('أعوان الصيانة بالمؤسسة (OP)') : __('مقاولة / مؤسسة خاصة معتمدة') }}</td>
          <td>{{ $work_order->contractor_name ?: __('الفريق التقني للمؤسسة') }}</td>
          <td>{{ $work_order->scheduled_date?->format('Y-m-d') ?: '—' }}</td>
          <td>{{ $work_order->completion_date?->format('Y-m-d') ?: __('قيد الإنجاز') }}</td>
          <td>{{ number_format((float) $work_order->estimated_cost, 2) }} دج</td>
          <td>{{ number_format((float) $work_order->actual_cost, 2) }} دج</td>
        </tr>
      </tbody>
    </table>

    <div style="margin-top: 6px;">
      <span style="font-weight: bold; font-size: 9pt;">تقرير التقني وملاحظات الإنجاز:</span>
      <div class="narrative-box">
        {{ $work_order->technician_notes ?: __('تمت المعاينة والتدخل وفق المعايير التقنية المعمول بها.') }}
      </div>
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">{{ __('العون التقني / الطالب') }}</div>
    <div style="font-size: 8pt; margin-top: 30px;">{{ $reported_by }}</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('المصالح الاقتصادية والمالية (المقتصد)') }}</div>
    <div style="font-size: 8pt; margin-top: 30px;">تأشيرة ومصادقة المقتصد</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('مدير المؤسسة') }}</div>
    <div style="font-size: 8pt; margin-top: 30px;">الختم والتوقيع الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة السند (Imprimer)') }}</button>

</body>
</html>
