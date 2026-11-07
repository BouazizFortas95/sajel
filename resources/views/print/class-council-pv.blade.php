<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر مداولات مجلس القسم') }} - {{ $classroom->name }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 11pt; color: #111; background: #fff; direction: rtl; }
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

  .council-info-grid { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; font-size: 9.5pt; }
  .info-box { flex: 1; border: 1px solid #c5cae9; padding: 6px 10px; background: #fafbff; border-radius: 4px; }
  .info-box strong { color: #1a237e; }

  .stats-strip { display: flex; gap: 10px; margin-bottom: 10px; }
  .stat-card { flex: 1; border: 1px solid #bbb; padding: 6px 8px; text-align: center; background: #fff; border-radius: 4px; border-top: 3px solid #1a237e; }
  .stat-card .num { font-size: 13pt; font-weight: bold; color: #1a237e; }
  .stat-card .lbl { font-size: 8.5pt; color: #666; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 10px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }
  .badge-honor { font-weight: bold; color: #2e7d32; }
  .badge-sanction { font-weight: bold; color: #c62828; }

  .appreciation-box { border: 1px solid #c5cae9; padding: 6px 10px; background: #fff; margin-bottom: 10px; font-size: 9.5pt; }
  .appreciation-box strong { color: #1a237e; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
  .sig-block { flex: 1; border: 1px solid #ccc; padding: 6px; text-align: center; font-size: 8.5pt; min-height: 65px; background: #fafafa; }
  .sig-block .role { font-weight: bold; color: #1a237e; margin-bottom: 2px; }
  .sig-block .name { color: #555; }

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
    <div>السنة الدراسية: <strong>{{ $session->academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>محضر مداولات مجلس القسم (القرار الوزاري 158)</h1>
  <div class="doc-subtitle">الفصل: <strong>الفصل {{ $session->trimester }}</strong> — القسم: <strong>{{ $classroom->name }}</strong> — تاريخ الانعقاد: <strong>{{ $session->session_date->format('Y/m/d') }}</strong></div>
</div>

<div class="council-info-grid">
  <div class="info-box">
    <div><strong>رئيس المجلس:</strong> {{ $session->president?->name ?? 'مدير المؤسسة / الناظر' }}</div>
    <div><strong>الأستاذ الرئيسي:</strong> {{ $session->mainTeacher?->name ?? '........................' }}</div>
  </div>
  <div class="info-box">
    <div><strong>مستشار التوجيه:</strong> {{ $session->counselor?->name ?? '........................' }}</div>
    <div><strong>مستشار التربية:</strong> {{ $session->supervisor?->name ?? '........................' }}</div>
  </div>
  <div class="info-box">
    <div><strong>مندوبو القسم:</strong> {{ $session->student_delegates ?: 'حضر ممثلو التلاميذ بالجلسة التمهيدية' }}</div>
    <div><strong>حالة المحضر:</strong> <strong>{{ $session->status->label() }}</strong></div>
  </div>
</div>

<div class="stats-strip">
  <div class="stat-card">
    <div class="num">{{ $session->total_students }}</div>
    <div class="lbl">تعداد القسم</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($session->class_average ?? 0, 2) }} / 20</div>
    <div class="lbl">معدل القسم العام</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($session->highest_average ?? 0, 2) }}</div>
    <div class="lbl">أعلى معدل</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($session->lowest_average ?? 0, 2) }}</div>
    <div class="lbl">أدنى معدل</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($session->success_rate ?? 0, 1) }} %</div>
    <div class="lbl">نسبة الحاصلين على المعدل (>= 10)</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $session->honorsCount() }}</div>
    <div class="lbl">التشريفات الشرفية</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $session->sanctionsCount() }}</div>
    <div class="lbl">العقوبات التربوية</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 4%;">الرتبة</th>
      <th style="width: 24%; text-align: right; padding-right: 8px;">اسم ولقب التلميذ(ة)</th>
      <th style="width: 12%;">تاريخ الميلاد</th>
      <th style="width: 9%;">المعدل الفصلي</th>
      @if($session->trimester === 3)
      <th style="width: 9%;">المعدل السنوي</th>
      @endif
      <th style="width: 16%;">التشريفات واللوحة الشرفية</th>
      <th style="width: 13%;">العقوبات والتنبيهات</th>
      @if($session->trimester === 3)
      <th style="width: 13%;">قرار نهاية السنة</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @forelse($decisions as $decision)
    <tr>
      <td><strong>{{ $decision->rank ?? '-' }}</strong></td>
      <td style="text-align: right; padding-right: 8px;"><strong>{{ $decision->student?->last_name }} {{ $decision->student?->first_name }}</strong></td>
      <td>{{ $decision->student?->birth_date?->format('Y/m/d') ?? '-' }}</td>
      <td><strong>{{ $decision->trimester_average !== null ? number_format($decision->trimester_average, 2) : '-' }}</strong></td>
      @if($session->trimester === 3)
      <td><strong>{{ $decision->annual_average !== null ? number_format($decision->annual_average, 2) : '-' }}</strong></td>
      @endif
      <td class="{{ $decision->honors->value !== 'none' ? 'badge-honor' : '' }}">
        {{ $decision->honors->label() }}
      </td>
      <td class="{{ $decision->sanctions->value !== 'none' ? 'badge-sanction' : '' }}">
        {{ $decision->sanctions->label() }}
      </td>
      @if($session->trimester === 3)
      <td>
        <strong>{{ $decision->end_of_year_decision->label() }}</strong>
      </td>
      @endif
    </tr>
    @empty
    <tr>
      <td colspan="{{ $session->trimester === 3 ? 8 : 6 }}" style="padding: 16px; color: #888;">
        لم تُسجل قرارات بعد، يُرجى الضغط على زر استيراد العلامات وحساب المداولات.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="appreciation-box">
  <strong>التقدير العام للقسم وملاحظات المجلس:</strong>
  <div>{{ $session->general_appreciation ?: 'سجل المجلس استقراراً تربوياً عاماً مع ضرورة تكثيف حصص الدعم والمعالجة البيداغوجية للمواد الأساسية ومتابعة التلاميذ ذوي النتائج المتعثرة.' }}</div>
  @if($session->council_decisions_summary)
  <div style="margin-top: 4px;"><strong>ملخص القرارات الإضافية:</strong> {{ $session->council_decisions_summary }}</div>
  @endif
</div>

<div class="signatures">
  <div class="sig-block">
    <div class="role">الأساتذة أعضاء المجلس</div>
    <div class="name">تأشيرة وتوقيعات أساتذة القسم</div>
  </div>
  <div class="sig-block">
    <div class="role">مستشار(ة) التوجيه المدرسي</div>
    <div class="name">{{ $session->counselor?->name ?? 'التوقيع والختم' }}</div>
  </div>
  <div class="sig-block">
    <div class="role">مستشار(ة) التربية</div>
    <div class="name">{{ $session->supervisor?->name ?? 'التوقيع والختم' }}</div>
  </div>
  <div class="sig-block">
    <div class="role">الأستاذ(ة) الرئيسي(ة)</div>
    <div class="name">{{ $session->mainTeacher?->name ?? 'التوقيع' }}</div>
  </div>
  <div class="sig-block">
    <div class="role">رئيس المجلس (المدير / الناظر)</div>
    <div class="name">{{ $session->president?->name ?? 'التوقيع وختم المؤسسة' }}</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة المحضر (Ctrl+P)</button>

</body>
</html>

