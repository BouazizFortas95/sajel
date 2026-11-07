<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('البطاقة التركيبية وعقد أهداف مشروع المؤسسة') }} - {{ $project->project_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #00695c; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #00695c; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }
  .republic-header .institution { font-weight: bold; color: #00695c; font-size: 11pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #00695c; border: 2px solid #00695c; display: inline-block; padding: 3px 25px; background: #e0f2f1; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 8px; }
  .card { border: 1px solid #b2dfdb; background: #f0fdf4; border-radius: 4px; padding: 4px 6px; text-align: center; }
  .card .label { font-size: 7.5pt; color: #555; margin-bottom: 2px; }
  .card .val { font-size: 11pt; font-weight: bold; color: #00695c; }

  .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

  .section-box { border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
  .section-title { background: #e0f2f1; color: #00695c; font-weight: bold; font-size: 8.5pt; padding: 3px 8px; border-bottom: 1px solid #b2dfdb; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; line-height: 1.5; }

  .axes-list { list-style: none; space-y: 4px; }
  .axes-list li { padding: 3px 6px; background: #fafafa; border: 1px solid #eee; border-radius: 3px; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }

  .targets-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
  .targets-table th, .targets-table td { border: 1px solid #b2dfdb; padding: 4px 6px; text-align: center; }
  .targets-table th { background: #e0f2f1; color: #00695c; font-weight: bold; }

  .narrative-box { background: #fafafa; border: 1px dashed #ccc; padding: 5px 8px; border-radius: 3px; min-height: 35px; font-size: 8.5pt; }

  .signatures { display: flex; justify-content: space-between; gap: 15px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 65px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #00695c; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #00695c; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $wilaya }}</div>
  <div class="meta-row">
    <div>الدائرة: <strong>{{ $daira }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution_name }}</strong></div>
    <div>الفترة الزمنية للتعاقد: <strong>{{ $project->start_year }} — {{ $project->end_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('البطاقة التركيبية وعقد أهداف مشروع المؤسسة (Projet d\'Établissement)') }}</h1>
  <div class="decree">تنفيذاً للمنشور الوزاري المنظم لمشاريع المؤسسات التعليمية وعقود النجاعة البيداغوجية</div>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">رمز المشروع</div>
    <div class="val">{{ $project->project_code }}</div>
  </div>
  <div class="card">
    <div class="label">المدى الزمني</div>
    <div class="val">{{ $project->start_year }} / {{ $project->end_year }}</div>
  </div>
  <div class="card">
    <div class="label">حالة المشروع والاعتماد</div>
    <div class="val" style="font-size: 9.5pt;">{{ $status_label }}</div>
  </div>
  <div class="card">
    <div class="label">نسبة الإنجاز المحققة</div>
    <div class="val" style="color: #2e7d32;">{{ $completion_rate }}%</div>
  </div>
</div>

<div class="two-col-grid">
  <!-- المحاور الاستراتيجية -->
  <div class="section-box">
    <div class="section-title">المحاور الاستراتيجية الكبرى للمشروع (Axes stratégiques prioritaires)</div>
    <div class="section-body">
      <ul class="axes-list">
        @if(is_array($strategic_axes))
          @foreach($strategic_axes as $idx => $axis)
            <li>
              <strong style="color: #00695c;">{{ $idx + 1 }}.</strong>
              <span>{{ $axis }}</span>
            </li>
          @endforeach
        @endif
      </ul>
    </div>
  </div>

  <!-- الأهداف الكمية المستهدفة -->
  <div class="section-box">
    <div class="section-title">الأهداف والمؤشرات الكمية للنجاعة (Objectifs quantitatifs & Cibles)</div>
    <div class="section-body">
      <table class="targets-table">
        <thead>
          <tr>
            <th style="width: 70%; text-align: right;">المؤشر الكمي المستهدف</th>
            <th style="width: 30%;">النسبة / القيمة المستهدفة</th>
          </tr>
        </thead>
        <tbody>
          @if(is_array($quantitative_targets))
            @foreach($quantitative_targets as $target => $val)
            <tr>
              <td style="text-align: right;"><strong>{{ $target }}</strong></td>
              <td style="color: #00695c; font-weight: bold;">{{ $val }}</td>
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- خطة العمل والأنشطة الإجرائية -->
<div class="section-box" style="margin-bottom: 8px;">
  <div class="section-title">ملخص خطة العمل والأنشطة الإجرائية (Plan d'action et démarches opérationnelles)</div>
  <div class="section-body">
    <div class="narrative-box">{{ $project->action_plan ?? 'برمجة حصص الدعم والاستدراك البيداغوجي، تفعيل نوادي الروبوتيك والقراءة، تنظيم قوافل تحسيسية للوقاية، وتفعيل دور مجالس التعليم والأقسام في المتابعة الفردية للتلاميذ.' }}</div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">منسق لجنة قيادة المشروع</div>
    <div>الاسم والصفة: .......................................</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة (رئيس مجلس التربية والتسيير)</div>
    <div>في: ............................... بتاريخ: .....................</div>
    <div style="margin-top: 25px;">الختم والتوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">مفتش المقاطعة التربوية / الإدارية</div>
    <div>تأشيرة مصلحة التمدرس والتفتيش بالمديرية</div>
    <div style="margin-top: 25px;">الختم والتأشيرة الرسمية:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة بطاقة المشروع الرسمية</button>

</body>
</html>
