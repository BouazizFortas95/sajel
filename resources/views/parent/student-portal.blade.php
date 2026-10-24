<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $student->full_name }} — {{ __('بوابة ولي الأمر') }}</title>
<style>
@media screen { body { background: #f8fafc; font-family: system-ui, Arial, sans-serif; } .card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); } }
.header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#fff; padding: 24px; border-radius: 16px; text-align:center; }
.header h1 { font-size: 22pt; margin: 0; }
.badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11pt; font-weight: bold; }
.grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap:16px; }
.section-title { font-size: 14pt; color: #1a3a5c; border-right: 4px solid #f59e0b; padding-right: 10px; margin: 0 0 12px 0; }
.table { width:100%; border-collapse:collapse; font-size: 11pt; }
.table th { background:#1a3a5c; color:#fff; padding:6px 8px; text-align:start; }
.table td { border-bottom:1px solid #e2e8f0; padding:6px 8px; }
.qr { width: 120px; height: 120px; background: #fff; padding: 8px; border-radius: 12px; border: 2px solid #f59e0b; }
</style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-3xl mx-auto px-4 py-6">

  <div class="header">
    <h1>🛡️ {{ __('بوابة متابعة ولي الأمر') }}</h1>
    <div style="opacity:.95; font-size:12pt;">{{ $student->full_name }} — {{ $student->registration_number }}</div>
    <div style="font-size:10pt; opacity:.8; margin-top:6px;">{{ $classroom?->name ?? '—' }} / {{ $group?->name ?? '—' }} — {{ $student->date_of_birth?->format('Y-m-d') ?? '—' }}</div>
  </div>

  <div class="grid" style="margin-top:16px;">
    <div class="card">
      <h2 class="section-title">📋 {{ __('الواجبات المنزلية والدروس الأخيرة') }}</h2>
      <div class="table-wrapper">
        <table class="table">
          <thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('المادة') }}</th><th>{{ __('عنوان الدرس') }}</th><th>{{ __('الواجب') }}</th></tr></thead>
          <tbody>
            @forelse ($recentLogs as $log)
            <tr>
              <td>{{ $log->date?->format('Y-m-d') }}</td>
              <td>{{ $log->schedule?->subject?->name ?? '—' }}</td>
              <td>{{ $log->title }}</td>
              <td>{{ $log->homework ? \Illuminate\Support\Str::limit($log->homework, 60) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; color:#64748b;">{{ __('لا يوجد واجبات مسجلة بعد') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2 class="section-title">📊 {{ __('ملخص الحضور') }}</h2>
      <div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 8px;">
        <div style="background:#dcfce7; padding:10px; border-radius:8px;"><strong style="color:#166534">{{ $attendanceCounts['present'] ?? 0 }}</strong> {{ __('حاضر') }}</div>
        <div style="background:#ffe4e6; padding:10px; border-radius:8px;"><strong style="color:#9f1239">{{ $attendanceCounts['absent'] ?? 0 }}</strong> {{ __('غائب') }}</div>
        <div style="background:#fef3c7; padding:10px; border-radius:8px;"><strong style="color:#92400e">{{ $attendanceCounts['late'] ?? 0 }}</strong> {{ __('متأخر') }}</div>
        <div style="background:#dbeafe; padding:10px; border-radius:8px;"><strong style="color:#1e40af">{{ $attendanceCounts['excused'] ?? 0 }}</strong> {{ __('معفى') }}</div>
      </div>
      <div style="margin-top:8px; font-size:11pt; color:#475569;">{{ __('نسبة الحضور') }}: <strong>{{ $attendanceRate }}%</strong></div>
      @if($recentAbsences->isNotEmpty())
      <div style="margin-top:6px; font-size:10pt; color:#9f1239;">{{ __('أحدث الغيابات / التأخر') }}: {{ $recentAbsences->pluck('date')->map(fn($d)=>$d?->format('Y-m-d'))->join('، ') }}</div>
      @endif
    </div>

    <div class="card">
      <h2 class="section-title">🎓 {{ __('التقييمات الدراسية') }}</h2>
      @forelse ($gradesBySubject as $subject => $grades)
      <div style="margin-bottom:8px; padding:8px; background:#f1f5f9; border-radius:8px;">
        <strong>{{ $subject }}</strong>
        <div style="font-size:10pt; color:#334155;">{{ __('متوسط') }}: <strong style="color:#1a3a5c">{{ round($grades->avg('score') ?? 0, 2) }}/20</strong></div>
      </div>
      @empty
      <div style="color:#64748b;">{{ __('لا توجد تقييمات مسجلة بعد') }}</div>
      @endforelse
    </div>

    <div class="card">
      <h2 class="section-title">📝 {{ __('الملاحظات والسلوكية') }}</h2>
      @forelse ($observations as $obs)
      <div style="font-size:10pt; padding:4px 0; border-bottom:1px dashed #e2e8f0;">{{ $obs->date?->format('Y-m-d') }} — {{ $obs->category }} — {{ $obs->notes }}</div>
      @empty
      <div style="color:#64748b;">{{ __('لا توجد ملاحظات') }}</div>
      @endforelse
    </div>

    <div class="card">
      <h2 class="section-title">📌 {{ __('التوصيات وال remediation') }}</h2>
      @forelse ($remediations as $rem)
      <div style="font-size:10pt; padding:4px 0; border-bottom:1px dashed #e2e8f0;">{{ $rem->session_date?->format('Y-m-d') }} — {{ $rem->reason }}</div>
      @empty
      <div style="color:#64748b;">{{ __('لا يوجد') }}</div>
      @endforelse
    </div>
  </div>

  <div style="text-align:center; margin-top:24px; padding:12px; background:#fff; border-radius:12px; border:2px dashed #f59e0b;">
    <div style="font-size:10pt; color:#475569;">{{ __('رابط المتابعة الرقمية لولي الأمر') }}</div>
    <div style="font-family: monospace; word-break:break-all; background:#1a3a5c; color:#fff; padding:8px 12px; border-radius:8px; margin-top:6px; font-size:10pt;">{{ $portalUrl }}</div>
    <svg class="qr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" style="margin:10px auto 0; display:block;">
      <rect width="100" height="100" fill="#fff"/>
      <g fill="#1a3a5c">
        <rect x="10" y="10" width="30" height="30"/><rect x="15" y="15" width="20" height="20" fill="#fff"/><rect x="20" y="20" width="10" height="10" fill="#1a3a5c"/>
        <rect x="60" y="10" width="30" height="30"/><rect x="65" y="15" width="20" height="20" fill="#fff"/><rect x="70" y="20" width="10" height="10" fill="#1a3a5c"/>
        <rect x="10" y="60" width="30" height="30"/><rect x="15" y="65" width="20" height="20" fill="#fff"/><rect x="20" y="70" width="10" height="10" fill="#1a3a5c"/>
        <rect x="45" y="45" width="10" height="10"/><rect x="55" y="45" width="10" height="10"/><rect x="45" y="55" width="10" height="10"/><rect x="55" y="55" width="10" height="10"/>
        <rect x="45" y="10" width="10" height="10"/><rect x="65" y="45" width="10" height="10"/><rect x="80" y="80" width="10" height="10"/>
      </g>
    </svg>
  </div>
</div>
</body>
</html>