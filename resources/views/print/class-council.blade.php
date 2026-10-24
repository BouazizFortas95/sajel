<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('مجلس القسم') }} — {{ $classroom->name }}</title>
<style>
@page { size: A4 landscape; margin: 10mm; @top-left { content: none; } }
body { font-family: Arial, sans-serif; color: #0f172a; background: #fff; font-size: 11pt; }
.header { border-bottom: 3px solid #1a3a5c; padding-bottom: 8px; margin-bottom: 12px; }
.header h1 { font-size: 16pt; margin: 0; color: #1a3a5c; }
.header .sub { font-size: 9pt; color: #64748b; }
.kpi-strip { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.kpi-card { flex: 1; min-width: 110px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 8px; }
.kpi-card .label { font-size: 8pt; color: #64748b; }
.kpi-card .value { font-size: 12pt; font-weight: bold; color: #1a3a5c; }
.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-amber { background: #fef3c7; color: #92400e; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-rose { background: #ffe4e6; color: #9f1239; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 9pt; }
th { background: #1a3a5c; color: #fff; padding: 4px 6px; text-align: start; }
td { border-bottom: 1px solid #e2e8f0; padding: 3px 6px; }
.section { margin-top: 14px; padding-top: 6px; border-top: 1px solid #cbd5e1; }
.section h2 { font-size: 12pt; color: #1a3a5c; margin: 0 0 6px 0; }
.signature-grid { display: flex; gap: 40px; margin-top: 20px; }
.signature-box { border-top: 1px solid #334155; padding-top: 4px; min-width: 160px; font-size: 9pt; color: #475569; }
.footer { margin-top: 10px; font-size: 8pt; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<div class="header">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <div>
      <h1>{{ __('مجلس القسم') }}</h1>
      <div class="sub">{{ __('وزارة التربية الوطنية') }} — {{ __('التقرير البيداغوجي') }}</div>
    </div>
    <div style="text-align:right; font-size:9pt; color:#475569;">
      <strong>{{ $classroom->level?->name ?? '' }}</strong> {{ $classroom->name }}<br>
      {{ $group ? $group->name : __('القسم كاملاً') }}<br>
      {{ now(config('app.timezone'))->translatedFormat('l, d M Y') }}
    </div>
  </div>
</div>

<!-- KPI Strip -->
<div class="kpi-strip">
  <div class="kpi-card"><div class="label">{{ __('إجمالي التلاميذ') }}</div><div class="value">{{ $kpis['total_students'] }}</div></div>
  <div class="kpi-card"><div class="label">{{ __('المعدل العام للقسم') }}/20</div><div class="value">{{ $kpis['class_general_average'] ?? '—' }}</div></div>
  <div class="kpi-card"><div class="label">{{ __('نسبة النجاح') }}</div><div class="value">{{ $kpis['pass_rate'] }}%</div></div>
  <div class="kpi-card"><div class="label">{{ __('أعلى معدل') }}</div><div class="value">{{ $kpis['highest_average'] ?? '—' }}</div></div>
  <div class="kpi-card"><div class="label">{{ __('أدنى معدل') }}</div><div class="value">{{ $kpis['lowest_average'] ?? '—' }}</div></div>
  <div class="kpi-card"><div class="label">{{ __('لوحة الشرف') }}</div><div class="value">{{ $kpis['honor_roll_count'] }}</div></div>
  <div class="kpi-card"><div class="label">{{ __('إجمالي الغيابات') }}</div><div class="value">{{ $kpis['total_absences'] }}</div></div>
</div>

<!-- Honor Distribution -->
<div style="margin-bottom:10px; font-size:9pt;">
  @foreach ($honorDistribution as $key => $count)
    <span class="badge badge-{{ $key === 'excellence' ? 'green' : ($key === 'encouragement' ? 'amber' : ($key === 'honor_roll' ? 'blue' : ($key === 'warning' ? 'rose' : 'amber'))) }}" style="margin-left:4px;">{{ __("class_council.{$key}") }}: {{ $count }}</span>
  @endforeach
</div>

<!-- Student Deliberation Table -->
<table>
<thead>
<tr>
  <th>{{ __('التلميذ(ة)') }}</th>
  <th>{{ __('القسم') }}</th>
  <th>{{ __('المعدل العام') }}</th>
  @foreach ($subjects as $subject)
    <th>{{ $subject->name }}</th>
  @endforeach
  <th>{{ __('الحضور') }}</th>
  <th>{{ __('الملاحظات السلوكية') }}</th>
  <th>{{ __('التوصية') }}</th>
</tr>
</thead>
<tbody>
@forelse ($studentDeliberations as $del)
<tr>
  <td><strong>{{ $del['full_name'] }}</strong><br>{{ $del['registration_number'] }}</td>
  <td>{{ $del['group'] ?? __('—') }}</td>
  <td>{{ $del['generalAverage'] !== null ? number_format($del['generalAverage'], 2) : '—' }}/20</td>
  @foreach ($subjects as $subject)
    @php
      $subAvg = collect($del['perSubjectAverages'])->firstWhere('subject_id', $subject->id);
    @endphp
    <td>{{ $subAvg ? number_format($subAvg['average'], 2) : '—' }}</td>
  @endforeach
  <td style="font-size:8pt;">
    <span class="badge badge-green">{{ __('حاضر') }}: {{ $del['attendanceCounts']['present'] }}</span>
    <span class="badge badge-rose">{{ __('غائب') }}: {{ $del['attendanceCounts']['absent'] }}</span>
    <span class="badge badge-amber">{{ __('متأخر') }}: {{ $del['attendanceCounts']['late'] }}</span>
  </td>
  <td>
    @if ($del['behaviorCounts']['negative'] > 0)
      <span style="color:#9f1239; font-size:8pt;">⚠ {{ $del['behaviorCounts']['negative'] }} {{ __('تنبيه') }}</span>
    @else
      —
    @endif
  </td>
  <td style="font-size:8pt;">{{ $del['recommendation'] }}</td>
</tr>
@empty
<tr><td colspan="20" style="text-align:center; padding:12px;">{{ __('لا توجد بيانات للتلاميذ في هذا القسم') }}</td></tr>
@endforelse
</tbody>
</table>

<!-- Signature Boxes -->
<div style="margin-top:14px; padding-top:6px; border-top:2px solid #1a3a5c;">
  <div style="display:flex; gap:30px; flex-wrap:wrap;">
    <div class="signature-box"><strong>{{ __('إمضاء أساتذة القسم') }}</strong><br>{{ now()->translatedFormat('d M Y') }}</div>
    <div class="signature-box"><strong>{{ __('تأشيرة مستشار التربية') }}</strong><br>_________________________</div>
    <div class="signature-box"><strong>{{ __('ختم وإمضاء مدير المتوسطة') }}</strong><br>_________________________</div>
  </div>
</div>

<div class="footer">
{{ __('مجلس القسم — الوثيقة الرسمية') }} | {{ $classroom->academic_year }} | {{ __('صادرة عن') }} Sajel
</div>
</body>
</html>