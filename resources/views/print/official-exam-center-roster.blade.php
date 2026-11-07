<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('مخطط توزيع القاعات والحراسة') }} - {{ $center->center_name }}</title>
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
  .doc-title h1 { font-size: 14pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 4px 22px; background: #f0f2f9; }

  .summary-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 12px; }
  .card { border: 1px solid #c5cae9; background: #f8f9ff; border-radius: 4px; padding: 6px 10px; text-align: center; }
  .card .label { font-size: 8pt; color: #555; margin-bottom: 2px; }
  .card .val { font-size: 11pt; font-weight: bold; color: #1a237e; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 12px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .data-table th { background: #e8eaf6; color: #1a237e; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 16px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 9pt; margin-bottom: 4px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1a237e; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — الديوان الوطني للامتحانات والمسابقات (ONEC)</div>
  <div class="meta-row">
    <div>مديرية التربية لولاية: <strong>{{ $school?->state ?? '........................' }}</strong></div>
    <div class="institution">مركز الإجراء: <strong>{{ $center->center_name }}</strong> (كود: {{ $center->center_code }})</div>
    <div>دورة: <strong>{{ $center->session_year }}</strong> | الامتحان: <strong>{{ $center->exam_type->label() }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>مخطط توزيع القاعات وجدول تأطير الحراسة والأمانة</h1>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">إجمالي قاعات الامتحان</div>
    <div class="val">{{ $telemetry['totalRooms'] }} قاعة</div>
  </div>
  <div class="card">
    <div class="label">إجمالي المترشحين المسجلين</div>
    <div class="val">{{ $telemetry['totalCandidates'] }} مترشح</div>
  </div>
  <div class="card">
    <div class="label">رؤساء القاعات المكلفين</div>
    <div class="val" style="color: #1e40af;">{{ $telemetry['roomChiefsCount'] }} رئيس</div>
  </div>
  <div class="card">
    <div class="label">الأساتذة الحراس بالقاعات</div>
    <div class="val" style="color: #15803d;">{{ $telemetry['roomSupervisorsCount'] }} حارس</div>
  </div>
  <div class="card">
    <div class="label">نسبة تغطية التأطير</div>
    <div class="val" style="color: #b91c1c;">{{ $telemetry['coveragePercentage'] }}%</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 7%;">القاعة</th>
      <th style="width: 15%; text-align: right; padding-right: 6px;">موقع الحجرة</th>
      <th style="width: 18%;">مجال أرقام التسجيل</th>
      <th style="width: 7%;">المترشحين</th>
      <th style="width: 23%; text-align: right; padding-right: 6px;">رئيس القاعة</th>
      <th style="width: 30%; text-align: right; padding-right: 6px;">الأساتذة الحراس المساعدون</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rooms as $room)
    @php
      $chief = $room->supervisors->firstWhere('duty_role', \App\Enums\OfficialExamDutyRole::RoomChief);
      $assistants = $room->supervisors->where('duty_role', \App\Enums\OfficialExamDutyRole::RoomSupervisor);
    @endphp
    <tr>
      <td style="font-weight: bold; color: #1a237e;">{{ $room->room_label }}</td>
      <td style="text-align: right; padding-right: 6px;">{{ $room->physical_location ?? 'الجناح الرئيسي' }}</td>
      <td style="font-family: monospace; font-size: 8pt;">
        @if($room->candidate_start_number)
          {{ $room->candidate_start_number }} ⟵ {{ $room->candidate_end_number }}
        @else
          ---
        @endif
      </td>
      <td style="font-weight: bold;">{{ $room->candidate_count }}</td>
      <td style="text-align: right; padding-right: 6px;">
        @if($chief)
          <strong>{{ $chief->supervisor_name }}</strong>
          @if($chief->origin_institution)
            <div style="font-size: 7.5pt; color: #555;">({{ $chief->origin_institution }})</div>
          @endif
        @else
          <span style="color: #999;">لم يُعيّن بعد</span>
        @endif
      </td>
      <td style="text-align: right; padding-right: 6px;">
        @forelse($assistants as $ast)
          <div style="margin-bottom: 2px;">
            • <strong>{{ $ast->supervisor_name }}</strong>
            @if($ast->origin_institution)
              <span style="font-size: 7.5pt; color: #555;">({{ $ast->origin_institution }})</span>
            @endif
          </div>
        @empty
          <span style="color: #999;">لم يُعيّن بعد</span>
        @endforelse
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" style="padding: 14px; color: #666;">{{ __('لم يتم توليد قاعات الامتحان لهذا المركز حتى الآن.') }}</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">رئيس أمانة مركز الإجراء</div>
    <div style="font-size: 8pt; color: #555;">(ضبط القوائم وتوزيع الوثائق)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">الملاحظ الوزاري المعتمد</div>
    <div style="font-size: 8pt; color: #555;">(المعاينة ومطابقة الشروط النظامية)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس مركز الإجراء</div>
    <div style="font-size: 8pt; color: #555;">(المصادقة الرسمية على المخطط)</div>
    <div style="margin-top: 24px; font-weight: bold;">التوقيع والختم: ........................</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة المخطط') }}</button>

</body>
</html>
