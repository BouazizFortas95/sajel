<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('كشف متابعة استهلاك الطاقة والمرافق ومطابقة الفواتير') }} - {{ $academic_year }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 6mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #e65100; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #e65100; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 2px; }

  .doc-title { text-align: center; margin: 4px 0 8px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #e65100; border: 2px solid #e65100; display: inline-block; padding: 3px 20px; background: #fff3e0; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .ref-banner { display: flex; justify-content: space-between; background: #fff8e1; border: 1px solid #ffe082; border-radius: 4px; padding: 4px 10px; margin-bottom: 8px; font-size: 8.5pt; }

  .kpi-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 8px; }
  .kpi-card { border: 1px solid #ddd; background: #fafafa; border-radius: 4px; padding: 4px 8px; text-align: center; }
  .kpi-card .val { font-size: 11pt; font-weight: bold; color: #e65100; }
  .kpi-card .lbl { font-size: 7.5pt; color: #666; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 3px 5px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .total-row { background: #fff3e0; font-weight: bold; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 65px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #e65100; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 15px; left: 15px; background: #e65100; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 9pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية الوطنية</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>المصالح المالية والاقتصادية — سجل استهلاك الطاقة والمرافق</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('كشف الحصيلة السنوية لاستهلاك الطاقة والمرافق ومطابقة الفواتير') }}</h1>
  <div class="decree">Bilan annuel de consommation des fluides énergétiques (Électricité, Gaz naturel, Eau) et audit des factures</div>
</div>

<div class="kpi-cards">
  <div class="kpi-card">
    <div class="val">{{ number_format($total_electricity_dzd, 2) }} دج</div>
    <div class="lbl">مجموع فاتورة الكهرباء (سونلغاز)</div>
  </div>
  <div class="kpi-card">
    <div class="val">{{ number_format($total_gas_dzd, 2) }} دج</div>
    <div class="lbl">مجموع فاتورة الغاز الطبيعي (سونلغاز)</div>
  </div>
  <div class="kpi-card">
    <div class="val">{{ number_format($total_water_dzd, 2) }} دج</div>
    <div class="lbl">مجموع فاتورة الماء الشروب (ADE)</div>
  </div>
  <div class="kpi-card" style="border-color: #ffb74d; background: #fffde7;">
    <div class="val">{{ number_format($grand_total_dzd, 2) }} دج</div>
    <div class="lbl">المجموع العام لنفقات الطاقة والمرافق</div>
  </div>
</div>

<table class="table-clean">
  <thead>
    <tr>
      <th>رقم الكشف</th>
      <th>العداد المعني</th>
      <th>نوع المرفق</th>
      <th>المورد</th>
      <th>الفترة</th>
      <th>تاريخ القراءة</th>
      <th>الرمز السابق</th>
      <th>الرمز الحالي</th>
      <th>الاستهلاك</th>
      <th>مبلغ الفاتورة</th>
      <th>رقم الفاتورة</th>
      <th>حالة المطابقة والتسديد</th>
      <th>المدقق</th>
    </tr>
  </thead>
  <tbody>
    @forelse($records as $rec)
      <tr>
        <td>{{ $rec->bill_reference }}</td>
        <td>{{ $rec->meter?->meter_code }} ({{ $rec->meter?->meter_serial_number }})</td>
        <td>{{ $rec->meter?->meter_type?->label() }}</td>
        <td>{{ $rec->meter?->utility_provider }}</td>
        <td>{{ $rec->billing_period_quarter }}</td>
        <td>{{ $rec->reading_date_start?->format('Y-m-d') }} إلى {{ $rec->reading_date_end?->format('Y-m-d') }}</td>
        <td>{{ number_format($rec->previous_index) }}</td>
        <td>{{ number_format($rec->current_index) }}</td>
        <td><strong>{{ number_format($rec->consumption_units) }}</strong> {{ $rec->meter?->unit_of_measure }}</td>
        <td><strong>{{ number_format((float) $rec->invoiced_amount_dzd, 2) }} دج</strong></td>
        <td>{{ $rec->invoice_number ?: '—' }}</td>
        <td>{{ $rec->payment_status?->label() }}</td>
        <td>{{ $rec->meter_auditor_name }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="13" style="padding: 12px; color: #777;">{{ __('لا توجد سجلات فواتير مقيدة لهذه السنة الدراسية.') }}</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="9" style="text-align: right; padding-right: 12px;">المجموع الإجمالي لنفقات استهلاك الطاقة والمرافق:</td>
      <td><strong>{{ number_format($grand_total_dzd, 2) }} دج</strong></td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">{{ __('المكلف بمتابعة وقراءة العدادات') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">مراقبة ومطابقة الأرقام المقروءة</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('المقتصد / رئيس المصالح الاقتصادية') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">المطابقة المالية والأمر بالصرف</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('مدير المؤسسة') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">الختم والتأشيرة الرسمية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة الحصيلة (Imprimer)') }}</button>

</body>
</html>

