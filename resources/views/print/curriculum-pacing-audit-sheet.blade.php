<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('كشف تدقيق ومتابعة نسب إنجاز المناهج الدراسية') }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 12mm 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 6px; margin-bottom: 10px; }
  .republic-header .republic { font-size: 10pt; letter-spacing: 1px; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 11pt; }

  .doc-title { text-align: center; margin: 8px 0; }
  .doc-title h1 { font-size: 15pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 4px 20px; background: #f0f2f9; }
  .doc-subtitle { font-size: 9.5pt; color: #444; margin-top: 3px; }

  .stats-strip { display: flex; gap: 10px; margin-bottom: 10px; }
  .stat-card { flex: 1; border: 1px solid #bbb; padding: 6px 8px; text-align: center; background: #fff; border-radius: 4px; }
  .stat-card.track { border-top: 3px solid #2e7d32; }
  .stat-card.minor { border-top: 3px solid #f57c00; }
  .stat-card.critical { border-top: 3px solid #d32f2f; }
  .stat-card.ahead { border-top: 3px solid #0288d1; }
  .stat-card .num { font-size: 13pt; font-weight: bold; }
  .stat-card .lbl { font-size: 8.5pt; color: #666; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 12px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .badge-ahead { background: #e1f5fe; color: #0288d1; font-weight: bold; padding: 2px 6px; border-radius: 4px; }
  .badge-track { background: #e8f5e9; color: #2e7d32; font-weight: bold; padding: 2px 6px; border-radius: 4px; }
  .badge-minor { background: #fff3e0; color: #ef6c00; font-weight: bold; padding: 2px 6px; border-radius: 4px; }
  .badge-critical { background: #ffebee; color: #c62828; font-weight: bold; padding: 2px 6px; border-radius: 4px; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 14px; }
  .sig-block { flex: 1; border: 1px solid #ccc; padding: 8px; text-align: center; font-size: 9pt; min-height: 70px; background: #fafafa; }
  .sig-block .role { font-weight: bold; color: #1a237e; margin-bottom: 4px; }

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
    <div>السنة الدراسية: <strong>{{ $academicYear }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>كشف تدقيق ومتابعة نسب إنجاز المناهج والتدرج السنوي</h1>
  <div class="doc-subtitle">الفصل: <strong>الفصل {{ $trimester }}</strong> {{ $level ? '— المستوى: ' . $level->name : '— لجميع المستويات التعليمية' }} — تاريخ المعاينة: <strong>{{ now(config('app.timezone', 'Africa/Algiers'))->format('Y/m/d') }}</strong></div>
</div>

<div class="stats-strip">
  <div class="stat-card track">
    <div class="num" style="color: #2e7d32;">{{ $onTrackCount }}</div>
    <div class="lbl">أفواج مطابقة للتدرج</div>
  </div>
  <div class="stat-card ahead">
    <div class="num" style="color: #0288d1;">{{ $aheadCount }}</div>
    <div class="lbl">أفواج متقدمة في المنهاج</div>
  </div>
  <div class="stat-card minor">
    <div class="num" style="color: #ef6c00;">{{ $minorDelaysCount }}</div>
    <div class="lbl">تأخر طفيف (أقل من حصتين)</div>
  </div>
  <div class="stat-card critical">
    <div class="num" style="color: #c62828;">{{ $criticalDelaysCount }}</div>
    <div class="lbl">تأخر حرج (يتطلب استدراكاً)</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 4%;">#</th>
      <th style="width: 14%;">القسم / الفوج</th>
      <th style="width: 14%;">المادة التعليمية</th>
      <th style="width: 16%;">الأستاذ(ة) المشرف(ة)</th>
      <th style="width: 8%;">المقرر وزارياً</th>
      <th style="width: 8%;">المنجز فعلياً</th>
      <th style="width: 8%;">نسبة الإنجاز</th>
      <th style="width: 14%;">وضعية التدرج</th>
      <th style="width: 14%;">إجراءات الاستدراك المبرمجة</th>
    </tr>
  </thead>
  <tbody>
    @forelse($audits as $index => $audit)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td><strong>{{ $audit->classroom?->name }}</strong></td>
      <td>{{ $audit->subject?->name }}</td>
      <td>{{ $audit->teacher?->name ?? 'موزع حسب الجداول' }}</td>
      <td>{{ $audit->expected_resources_count }}</td>
      <td><strong>{{ $audit->completed_resources_count }}</strong></td>
      <td><strong>{{ number_format($audit->execution_percentage, 1) }} %</strong></td>
      <td>
        @php
          $cls = match($audit->pacing_status->value) {
            'ahead' => 'badge-ahead',
            'on_track' => 'badge-track',
            'minor_delay' => 'badge-minor',
            'critical_delay' => 'badge-critical',
            default => 'badge-track',
          };
        @endphp
        <span class="{{ $cls }}">{{ $audit->pacing_status->label() }}</span>
      </td>
      <td style="font-size: 8.5pt;">
        {{ $audit->compensatory_plan ?: ($audit->pacing_status->value === 'critical_delay' ? 'تخصيص حصص استدراك مسائية' : 'لا يتطلب استدراكاً') }}
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="9" style="padding: 16px; color: #888;">
        لا توجد معاينات مسجلة لهذا الفصل، يُرجى تشغيل التدقيق الآلي من لوحة التحكم.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="signatures">
  <div class="sig-block">
    <div class="role">الأساتذة مسؤولو المواد والتنسيق</div>
    <div>المعاينة والمصادقة على وضعية التدرج</div>
  </div>
  <div class="sig-block">
    <div class="role">ناظر(ة) الدروس</div>
    <div>التأشيرة ومتابعة حصص الاستدراك</div>
  </div>
  <div class="sig-block">
    <div class="role">مدير(ة) المؤسسة التعليمية</div>
    <div>المصادقة والختم الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة الكشف (Ctrl+P)</button>

</body>
</html>

