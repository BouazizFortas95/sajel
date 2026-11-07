<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('ورقة المقابلة ومحضر المشاركة الرياضية') }} - {{ $event->event_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 10mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1565c0; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 2px; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #0d47a1; border: 2px solid #1565c0; display: inline-block; padding: 3px 18px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .ref-banner { display: flex; justify-content: space-between; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 4px 10px; margin-bottom: 8px; font-size: 8.5pt; }

  .match-score-box { text-align: center; border: 2px solid #1565c0; background: #f0f7ff; border-radius: 6px; padding: 8px; margin-bottom: 8px; }
  .score-title { font-size: 11pt; font-weight: bold; color: #0d47a1; margin-bottom: 4px; }
  .score-numbers { font-size: 18pt; font-weight: bold; color: #1565c0; font-family: monospace; letter-spacing: 2px; }

  .section-box { border: 1px solid #c5cae9; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e8eaf6; color: #1a237e; font-weight: bold; font-size: 9pt; padding: 3px 8px; border-bottom: 1px solid #c5cae9; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; line-height: 1.5; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8.5pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 3px 6px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 14px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 75px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1565c0; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — الرابطة الولائية للرياضة المدرسية (LWSS)</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>الجمعية الثقافية والرياضية المدرسية (ASCS)</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('ورقة المقابلة ومحضر المشاركة في المنافسة المدرسية') }}</h1>
  <div class="decree">Feuille de match officielle et rapport d'engagement sportif scolaire</div>
</div>

<div class="ref-banner">
  <div>رمز التظاهرة: <strong>{{ $event->event_code }}</strong></div>
  <div>تاريخ المقابلة: <strong>{{ $event->event_date?->format('Y-m-d') }} ({{ $event->event_time }})</strong></div>
  <div>المستوى التنافسي: <strong>{{ $event->competition_level?->label() }}</strong></div>
  <div>الميدان / القاعة: <strong>{{ $event->location_venue }}</strong></div>
</div>

<div class="match-score-box">
  <div class="score-title">
    {{ $school_name }} ({{ $discipline?->name_ar }}) ضد {{ $event->opponent_team_name ?: 'المؤسسة المنافسة' }}
  </div>
  @if($event->our_score !== null && $event->opponent_score !== null)
    <div class="score-numbers">
      {{ $event->our_score }} — {{ $event->opponent_score }}
    </div>
    <div style="font-size: 9pt; font-weight: bold; color: {{ $event->match_result === \App\Enums\SportsMatchResult::Win ? '#2e7d32' : '#c62828' }}; margin-top: 2px;">
      النتيجة النهائية: {{ $event->match_result?->label() }} {{ $event->ranking_achieved ? '— ' . $event->ranking_achieved : '' }}
    </div>
  @else
    <div style="font-size: 10pt; color: #666; padding: 4px;">{{ __('المباراة مبرمجة قيد التنظيم والتأطير') }}</div>
  @endif
</div>

<div class="section-box">
  <div class="section-title">1. قائمة التلاميذ الرياضيين المؤهلين والمشاركين (Feuille d'engagement)</div>
  <div class="section-body">
    <table class="table-clean">
      <thead>
        <tr>
          <th>الرقم</th>
          <th>رقم الإجازة (Licence)</th>
          <th>اللقب والاسم</th>
          <th>القسم</th>
          <th>الفئة العمرية</th>
          <th>الفحص الطبي</th>
          <th>توقيع التلميذ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($licensed_students as $idx => $lic)
          <tr>
            <td>{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: bold;">{{ $lic->license_number }}</td>
            <td style="font-weight: bold; text-align: right; padding-right: 8px;">{{ $lic->student?->full_name }}</td>
            <td>{{ $lic->student?->group?->classroom?->name }}</td>
            <td>{{ $discipline?->age_category?->label() }}</td>
            <td style="color: #2e7d32; font-weight: bold;">مؤهل (UDS)</td>
            <td style="width: 80px;"></td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="padding: 10px; color: #777;">{{ __('لا يوجد رياضيون مقيدون في هذا الاختصاص بعد.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="section-box">
  <div class="section-title">2. تقرير التأطير والروح الرياضية (Fair-Play) والملاحظات الفنية</div>
  <div class="section-body">
    <div>الأساتذة والمؤطرون المرافقون: <strong>{{ $event->chaperones_list ?: $discipline?->supervising_teacher_name }}</strong></div>
    <div style="margin-top: 4px;">علامة الروح الرياضية والانضباط: <strong>{{ $event->fair_play_score }} / 10</strong></div>
    <div style="margin-top: 4px; min-height: 35px; background: #fafafa; border: 1px dashed #ccc; padding: 4px 8px; border-radius: 3px;">
      {{ $event->participation_notes ?: __('جرت المقابلة في ظروف تربوية ورياضية ممتازة سادهـا التنافس الشريف والروح الرياضية العالية.') }}
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">حكم المقابلة / المندوب الفني</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">مصادقة طاقم التحكيم</div>
  </div>
  <div class="sig-box">
    <div class="title">الأستاذ المؤطر (الكاتب العام للجمعية)</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">{{ $discipline?->supervising_teacher_name }}</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس الجمعية (مدير المؤسسة)</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">الختم والتأشيرة الرسمية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة ورقة المقابلة (Imprimer)') }}</button>

</body>
</html>
