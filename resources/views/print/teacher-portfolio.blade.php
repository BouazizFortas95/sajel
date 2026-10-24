<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('ملف إنجاز الأستاذ') }} — {{ $teacher->name }}</title>
<style>
@page { size: A4 portrait; margin: 12mm; @top-left { content: none; } }
body { font-family: Arial, sans-serif; color: #0f172a; background: #fff; font-size: 11pt; }
.header { border-bottom: 3px solid #1a3a5c; padding-bottom: 8px; margin-bottom: 12px; }
.header h1 { font-size: 16pt; margin: 0; color: #1a3a5c; }
.header .sub { font-size: 9pt; color: #64748b; }
.card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
.card h2 { font-size: 13pt; color: #1a3a5c; margin: 0 0 6px 0; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 9pt; }
th { background: #1a3a5c; color: #fff; padding: 4px 6px; text-align: start; }
td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; }
.signature-box { border-top: 1px solid #334155; padding-top: 4px; min-width: 160px; font-size: 9pt; color: #475569; }
.footer { margin-top: 10px; font-size: 8pt; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<div class="header">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <div>
      <h1>{{ __('ملف إنجاز الأستاذ') }}</h1>
      <div class="sub">{{ __('وزارة التربية الوطنية') }} — {{ __('التقرير المهني') }}</div>
    </div>
    <div style="text-align:right; font-size:9pt; color:#475569;">
      <strong>{{ $teacher->name }}</strong><br>
      {{ $teacher->primarySubject?->name ?? '' }} / {{ $teacher->secondarySubject?->name ?? '' }}<br>
      {{ now(config('app.timezone'))->translatedFormat('l, d M Y') }}
    </div>
  </div>
</div>

<!-- Teacher Identity -->
<div class="card">
  <h2>{{ __('بطاقة الهوية المهنية') }}</h2>
  <table>
    <tr><th style="width:30%">{{ __('الاسم الكامل') }}</th><td>{{ $teacher->name }}</td></tr>
    <tr><th>{{ __('المادة الأساسية') }}</th><td>{{ $teacher->primarySubject?->name ?? '—' }}</td></tr>
    <tr><th>{{ __('المادة الثانوية') }}</th><td>{{ $teacher->secondarySubject?->name ?? '—' }}</td></tr>
    <tr><th>{{ __('عدد الساعات الأسبوعية') }}</th><td>{{ count($schedules ?? collect()) }} {{ __('حصة') }}</td></tr>
    <tr><th>{{ __('الأقسام الموكلة') }}</th><td>{{ collect($schedules ?? collect())->map(fn($s) => $s->classroom?->name)->filter()->unique()->join(', ') ?: '—' }}</td></tr>
  </table>
</div>

<!-- Teaching & Register Compliance -->
<div class="card">
  <h2>{{ __('التدريس والامتثال لسجل النصوص') }}</h2>
  <table>
    <thead><tr><th>{{ __('المؤشر') }}</th><th>{{ __('القيمة') }}</th></tr></thead>
    <tbody>
      <tr><td>{{ __('الدروس المنجزة المؤكدة') }}</td><td>{{ $completedLogs }}</td></tr>
      <tr><td>{{ __('السجلات المؤشرة إدارياً') }}</td><td>{{ $reviewedLogs }}</td></tr>
      <tr><td>{{ __('المذكرة التربوية المعتمدة') }}</td><td>{{ $memoCount }}</td></tr>
    </tbody>
  </table>
</div>

<!-- Meetings -->
<div class="card">
  <h2>{{ __('الندوات التربوية والاجتماعات التنسيقية') }}</h2>
  <table>
    <thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('النوع') }}</th><th>{{ __('العنوان') }}</th><th>{{ __('المؤطر') }}</th><th>{{ __('التوصيات') }}</th></tr></thead>
    <tbody>
      @forelse ($meetings as $m)
      <tr>
        <td>{{ $m->date?->format('Y-m-d') }}</td>
        <td>{{ match($m->meeting_type ?? '') { 'inspector_seminar' => __('ندوة تكوينية'), 'coordination_meeting' => __('اجتماع تنسيقي'), 'class_council' => __('مجلس القسم'), default => $m->meeting_type } }}</td>
        <td>{{ $m->title }}</td>
        <td>{{ $m->supervisor_name ?: '—' }}</td>
        <td>{{ $m->recommendations ? \Illuminate\Support\Str::limit($m->recommendations, 60) : '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center; padding:8px;">{{ __('لا توجد ندوات مسجلة') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="signature-box" style="margin-top:14px; display:flex; gap:40px; flex-wrap:wrap;">
  <div><strong>{{ __('إمضاء الأستاذ(ة)') }}</strong><br>_________________________</div>
  <div><strong>{{ __('إمضاء المفتش / المشرف') }}</strong><br>_________________________</div>
</div>

<div class="footer">{{ __('ملف إنجاز الأستاذ — الوثيقة الرسمية') }} | {{ now(config('app.timezone'))->translatedFormat('Y') }}</div>
</body>
</html>