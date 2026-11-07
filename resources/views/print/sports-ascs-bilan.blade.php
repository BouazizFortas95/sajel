<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('الحصيلة الأدبية والمالية للجمعية الثقافية والرياضية المدرسية') }} - {{ $academic_year }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 6mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1565c0; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 2px; }

  .doc-title { text-align: center; margin: 4px 0 8px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #0d47a1; border: 2px solid #1565c0; display: inline-block; padding: 3px 20px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .kpi-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 8px; }
  .kpi-card { border: 1px solid #ddd; background: #fafafa; border-radius: 4px; padding: 4px 8px; text-align: center; }
  .kpi-card .val { font-size: 12pt; font-weight: bold; color: #0d47a1; }
  .kpi-card .lbl { font-size: 7.5pt; color: #666; }

  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px; }

  .section-box { border: 1px solid #bbdefb; border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
  .section-title { background: #e3f2fd; color: #0d47a1; font-weight: bold; font-size: 9pt; padding: 3px 8px; border-bottom: 1px solid #bbdefb; }
  .section-body { padding: 5px 8px; font-size: 8pt; line-height: 1.4; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 3px 5px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 65px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 15px; left: 15px; background: #1565c0; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 9pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية الوطنية — الرابطة الولائية للرياضة المدرسية (LWSS)</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>الجمعية الثقافية والرياضية المدرسية (ASCS) — وصل الاعتماد: {{ $association->approval_receipt_number }}</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('الحصيلة الأدبية والمالية للجمعية الثقافية والرياضية المدرسية (ASCS)') }}</h1>
  <div class="decree">Bilan moral et financier annuel — الأنشطة الرياضية والمنافسات وحسابات الخزينة والعتاد الرياضي</div>
</div>

<div class="kpi-cards">
  <div class="kpi-card">
    <div class="val">{{ $telemetry['total_licenses'] }}</div>
    <div class="lbl">إجمالي التلاميذ المنخرطين (ذكور: {{ $telemetry['boys_licensed'] }} | إناث: {{ $telemetry['girls_licensed'] }})</div>
  </div>
  <div class="kpi-card">
    <div class="val">{{ $telemetry['disciplines_count'] }}</div>
    <div class="lbl">الأنشطة والاختصاصات المعتمدة بالمؤسسة</div>
  </div>
  <div class="kpi-card">
    <div class="val">{{ $telemetry['total_competitions'] }} (فوز: {{ $telemetry['matches_won'] }})</div>
    <div class="lbl">المباريات والبطولات المنجزة</div>
  </div>
  <div class="kpi-card" style="border-color: #81c784; background: #f1f8e9;">
    <div class="val" style="color: #2e7d32;">{{ number_format($telemetry['treasury_balance_dzd'], 2) }} دج</div>
    <div class="lbl">رصيد خزينة الجمعية المتبقي</div>
  </div>
</div>

<div class="grid-2">
  <!-- Moral Bilan -->
  <div class="section-box">
    <div class="section-title">1. الحصيلة الأدبية والأنشطة المنجزة (Bilan moral)</div>
    <div class="section-body">
      <table class="table-clean">
        <thead>
          <tr>
            <th>النشاط / الاختصاص</th>
            <th>الصنف</th>
            <th>المؤطر</th>
            <th>عدد المنخرطين</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          @foreach($disciplines as $disc)
            <tr>
              <td style="font-weight: bold; text-align: right;">{{ $disc->name_ar }}</td>
              <td>{{ $disc->category?->label() }}</td>
              <td>{{ $disc->supervising_teacher_name }}</td>
              <td><strong>{{ $disc->licenses_count }}</strong></td>
              <td style="color: #2e7d32; font-weight: bold;">نشط</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Financial Bilan -->
  <div class="section-box">
    <div class="section-title">2. الحصيلة المالية وحسابات الخزينة (Bilan financier)</div>
    <div class="section-body">
      <table class="table-clean">
        <thead>
          <tr>
            <th>بيان الإيرادات والنفقات</th>
            <th>المبلغ المنجز (دج)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="text-align: right;">إجمالي اشتراكات التلاميذ السنوية والمداخيل:</td>
            <td style="font-weight: bold; color: #2e7d32;">+ {{ number_format($telemetry['treasury_income_dzd'], 2) }} دج</td>
          </tr>
          <tr>
            <td style="text-align: right;">إجمالي مصاريف العتاد الرياضي والنقل والجوائز:</td>
            <td style="font-weight: bold; color: #c62828;">- {{ number_format($telemetry['treasury_expense_dzd'], 2) }} دج</td>
          </tr>
          <tr style="background: #e8f5e9; font-weight: bold;">
            <td style="text-align: right;">الرصيد الصافي المتاح بخزينة الجمعية:</td>
            <td style="color: #1b5e20;">{{ number_format($telemetry['treasury_balance_dzd'], 2) }} دج</td>
          </tr>
        </tbody>
      </table>
      <div style="margin-top: 4px; font-size: 7.5pt; color: #555;">
        المصادقة على الحساب المالي من طرف الجمعية العامة للجمعية الثقافية والرياضية المدرسية.
      </div>
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">الكاتب العام (أستاذ التربية البدنية)</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">{{ $association->secretary_general_name }}</div>
  </div>
  <div class="sig-box">
    <div class="title">أمين المال (المقتصد)</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">{{ $association->treasurer_name }}</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس الجمعية (مدير المؤسسة)</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">{{ $association->principal_president_name }}</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة الحصيلة (Imprimer)') }}</button>

</body>
</html>

