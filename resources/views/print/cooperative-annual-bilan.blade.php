<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('الحصيلة الأدبية والمالية للتعاونية المدرسية والنادي الأخضر') }} - {{ $academic_year }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 6mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 4px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 11pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 9pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #1b5e20; border: 2px solid #2e7d32; display: inline-block; padding: 3px 25px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .kpi-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin-bottom: 8px; }
  .kpi-card { background: #f1f8e9; border: 1px solid #c5e1a5; border-radius: 4px; padding: 6px; text-align: center; }
  .kpi-value { font-size: 11pt; font-weight: bold; color: #1b5e20; }
  .kpi-label { font-size: 7.5pt; color: #555; margin-top: 2px; }

  .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

  .section-box { border: 1px solid #c8e6c9; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e8f5e9; color: #1b5e20; font-weight: bold; font-size: 9pt; padding: 3px 8px; border-bottom: 1px solid #c8e6c9; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; }

  table { width: 100%; border-collapse: collapse; font-size: 8pt; }
  th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }
  th { background: #f1f8e9; color: #1b5e20; font-weight: bold; }

  .badge-income { background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
  .badge-expense { background: #ffebee; color: #c62828; padding: 2px 6px; border-radius: 3px; font-weight: bold; }

  .totals-bar { display: flex; justify-content: space-between; background: #f9fbe7; border: 1px solid #dce775; padding: 6px 12px; border-radius: 4px; font-size: 8.5pt; font-weight: bold; margin-bottom: 8px; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 8.5pt; margin-bottom: 2px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #2e7d32; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $wilaya_name }}</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $institution_name }}</strong></div>
    <div>الجمعية والتعاونية: <strong>{{ $cooperative->cooperative_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('الحصيلة الأدبية والمالية للتعاونية المدرسية والنادي البيئي') }}</h1>
  <div class="decree">Bilan Moral et Financier Annuel de la Coopérative Scolaire & Éco-École (Circulaire ministérielle)</div>
</div>

<div class="kpi-row">
  <div class="kpi-card">
    <div class="kpi-value">{{ $telemetry['totalMembersCount'] }}</div>
    <div class="kpi-label">التلاميذ المنخرطون</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">{{ $telemetry['totalProjectsCount'] }}</div>
    <div class="kpi-label">المشاريع المنجزة</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">{{ $telemetry['gardenPlotsCount'] }} أحواض ({{ number_format($telemetry['totalGardenAreaSqm'], 0) }} م²)</div>
    <div class="kpi-label">الحديقة البيداغوجية</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">{{ $telemetry['treesPlanted'] }} شجرة</div>
    <div class="kpi-label">أشجار تم غرسها</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">{{ number_format($telemetry['wasteRecycledKg'], 1) }} كغ</div>
    <div class="kpi-label">نفايات تم تدويرها</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">{{ number_format($telemetry['treasuryBalanceDzd'], 2) }} دج</div>
    <div class="kpi-label">رصيد الصندوق الصافي</div>
  </div>
</div>

<div class="two-cols">
  <!-- Moral Report: Projects & Initiatives -->
  <div class="section-box">
    <div class="section-title">أولاً: التقرير الأدبي — سجل المبادرات والمشاريع التضامنية والبيئية</div>
    <div class="section-body">
      <table>
        <thead>
          <tr>
            <th>الرمز</th>
            <th>عنوان المشروع</th>
            <th>المجال</th>
            <th>الميزانية</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          @forelse($projects as $p)
            <tr>
              <td><strong>{{ $p->project_code }}</strong></td>
              <td style="text-align: right;">{{ $p->title }}</td>
              <td>{{ $p->project_type?->label() ?? $p->project_type }}</td>
              <td>{{ number_format($p->budget_spent, 2) }} دج</td>
              <td>{{ $p->status?->label() ?? $p->status }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="color: #777;">{{ __('لا توجد مشاريع مسجلة لهذه السنة الدراسية.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Eco Actions & Green Club -->
  <div class="section-box">
    <div class="section-title">ثانياً: حصيلة النادي الأخضر والتشجير والتوعية البيئية</div>
    <div class="section-body">
      <table>
        <thead>
          <tr>
            <th>رمز النشاط</th>
            <th>العنوان والمجال</th>
            <th>المشاركون</th>
            <th>أشجار مغروسة</th>
            <th>رسكلة (كغ)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($eco_actions as $act)
            <tr>
              <td><strong>{{ $act->action_code }}</strong></td>
              <td style="text-align: right;">{{ $act->action_title }}</td>
              <td>{{ $act->participants_count }}</td>
              <td>{{ $act->trees_planted_count }}</td>
              <td>{{ number_format($act->waste_collected_kg, 1) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="color: #777;">{{ __('لا توجد أنشطة بيئية مسجلة لهذه السنة.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Financial Ledger Statement -->
<div class="section-box">
  <div class="section-title">ثالثاً: التقرير المالي — دفتر حركة صندوق التعاونية المدرسية (مداخيل ومصاريف)</div>
  <div class="section-body">
    <table>
      <thead>
        <tr>
          <th>المرجع</th>
          <th>التاريخ</th>
          <th>طبيعة العملية</th>
          <th>البيان والشرح</th>
          <th>الجهة المستفيدة / المصدر</th>
          <th>المبلغ (دج)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $t)
          <tr>
            <td><strong>{{ $t->transaction_reference }}</strong></td>
            <td>{{ $t->transaction_date?->format('Y-m-d') }}</td>
            <td>
              <span class="{{ $t->transaction_type?->isIncome() ? 'badge-income' : 'badge-expense' }}">
                {{ $t->transaction_type?->label() ?? $t->transaction_type }}
              </span>
            </td>
            <td style="text-align: right;">{{ $t->description ?: '—' }}</td>
            <td>{{ $t->beneficiary_or_source }}</td>
            <td><strong>{{ number_format($t->amount_dzd, 2) }}</strong></td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="color: #777;">{{ __('لا توجد حركات مالية مسجلة لهذا الموسم.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="totals-bar">
  <div>إجمالي المداخيل المحصلة: <span style="color: #2e7d32;">{{ number_format($telemetry['incomeTotalDzd'], 2) }} دج</span></div>
  <div>إجمالي المصاريف المنجزة: <span style="color: #c62828;">{{ number_format($telemetry['expenseTotalDzd'], 2) }} دج</span></div>
  <div>الرصيد الصافي المتاح بالصندوق: <span style="color: #1b5e20;">{{ number_format($telemetry['treasuryBalanceDzd'], 2) }} دج</span></div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">أمين مال التعاونية المدرسية</div>
    <p>{{ $cooperative->student_treasurer_name ?: 'التلميذ(ة) أمين المال' }}</p>
  </div>
  <div class="sig-box">
    <div class="title">الأستاذ المشرف المنسق</div>
    <p>التوقيع والتحفظ</p>
  </div>
  <div class="sig-box">
    <div class="title">رئيس مكتب التعاونية التلميذي</div>
    <p>{{ $cooperative->student_president_name ?: 'التلميذ(ة) رئيس المكتب' }}</p>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (رئيس الشرف)</div>
    <p>المصادقة والتأشيرة الرسمية</p>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة الحصيلة السنوية (Imprimer)') }}</button>

</body>
</html>
