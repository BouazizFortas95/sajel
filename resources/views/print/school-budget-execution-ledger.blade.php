<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('كشف تنفيذ الميزانية المدرسية وحساب التسيير') }} - {{ $fiscalYear }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 12mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 6px; margin-bottom: 10px; }
  .republic-header .republic { font-size: 10.5pt; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 11pt; }

  .doc-title { text-align: center; margin: 8px 0 12px; }
  .doc-title h1 { font-size: 14pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 4px 20px; background: #f0f2f9; }

  .summary-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 12px; }
  .card { border: 1px solid #c5cae9; background: #f8f9ff; border-radius: 4px; padding: 6px 10px; text-align: center; }
  .card .label { font-size: 8.5pt; color: #555; margin-bottom: 2px; }
  .card .val { font-size: 11pt; font-weight: bold; color: #1a237e; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 14px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 5px 6px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }
  .data-table tfoot td { font-weight: bold; background: #e8eaf6; color: #1a237e; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 18px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 75px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 9.5pt; margin-bottom: 4px; }

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
    <div>السنة المالية: <strong>{{ $fiscalYear }}</strong> | تاريخ الإصدار: <strong>{{ $generatedAt->format('Y-m-d') }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>كشف تنفيذ ميزانية المؤسسة وسجل تسيير النفقات (المرسوم 91-311)</h1>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">إجمالي الاعتمادات المخصصة</div>
    <div class="val">{{ number_format($totalAllocated, 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">مجموع المبالغ الملتزم بها</div>
    <div class="val" style="color: #c2410c;">{{ number_format($totalCommitted, 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">المبالغ المصفاة والمدفوعة</div>
    <div class="val" style="color: #15803d;">{{ number_format($totalLiquidated, 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">الرصيد المالي المتبقي المتاح</div>
    <div class="val" style="color: #1e40af;">{{ number_format($availableBalance, 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">نسبة استهلاك الميزانية</div>
    <div class="val">
      {{ $totalAllocated > 0 ? round(($totalCommitted / $totalAllocated) * 100, 1) : 0 }}%
    </div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 5%;">#</th>
      <th style="width: 9%;">النوع</th>
      <th style="width: 24%; text-align: right; padding-right: 6px;">الباب المالي</th>
      <th style="width: 24%; text-align: right; padding-right: 6px;">المادة وتعيين النفقة / الإيراد</th>
      <th style="width: 10%;">الاعتماد المخصص (دج)</th>
      <th style="width: 10%;">الملتزم به (دج)</th>
      <th style="width: 10%;">المصفى والمدفوع (دج)</th>
      <th style="width: 10%;">الرصيد المتبقي (دج)</th>
      <th style="width: 8%;">نسبة الاستهلاك</th>
    </tr>
  </thead>
  <tbody>
    @forelse($lines as $index => $line)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td>
        <span style="font-weight: bold; color: {{ $line->budget_type === \App\Enums\BudgetType::Expenditure ? '#b91c1c' : '#15803d' }};">
          {{ $line->budget_type->label() }}
        </span>
      </td>
      <td style="text-align: right; padding-right: 6px;">{{ $line->chapter }}</td>
      <td style="text-align: right; padding-right: 6px;"><strong>{{ $line->article }}</strong></td>
      <td style="font-weight: bold;">{{ number_format($line->allocated_amount, 2) }}</td>
      <td style="color: #c2410c;">{{ number_format($line->committed_amount, 2) }}</td>
      <td style="color: #15803d;">{{ number_format($line->liquidated_amount, 2) }}</td>
      <td style="font-weight: bold; color: #1e40af;">{{ number_format($line->availableBalance(), 2) }}</td>
      <td>{{ $line->utilizationRate() }}%</td>
    </tr>
    @empty
    <tr>
      <td colspan="9" style="padding: 16px; color: #777;">{{ __('لا توجد بنود ميزانية مسجلة لهذه السنة المالية') }}</td>
    </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" style="text-align: right; padding-right: 12px;">المجاميع العامة للميزانية:</td>
      <td>{{ number_format($totalAllocated, 2) }} دج</td>
      <td>{{ number_format($totalCommitted, 2) }} دج</td>
      <td>{{ number_format($totalLiquidated, 2) }} دج</td>
      <td>{{ number_format($availableBalance, 2) }} دج</td>
      <td>{{ $totalAllocated > 0 ? round(($totalCommitted / $totalAllocated) * 100, 1) : 0 }}%</td>
    </tr>
  </tfoot>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المسير المالي والمحاسب العمومي</div>
    <div style="font-size: 8pt; color: #555;">(ضبط حسابات التسيير والتنفيذ)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (الآمر بالصرف)</div>
    <div style="font-size: 8pt; color: #555;">(المصادقة على تنفيذ الميزانية)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">مجلس التربية والتسيير / التوجيه والتسيير</div>
    <div style="font-size: 8pt; color: #555;">(المداولة والمصادقة الدورية)</div>
    <div style="margin-top: 24px; font-weight: bold;">بتاريخ: ........................</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة الكشف') }}</button>

</body>
</html>
