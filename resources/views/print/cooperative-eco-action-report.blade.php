<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر إنجاز نشاط بيئي ومبادرة النادي الأخضر') }} - {{ $action->action_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; padding: 10mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 11pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }

  .doc-title { text-align: center; margin: 8px 0 12px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #1b5e20; border: 2px solid #2e7d32; display: inline-block; padding: 4px 20px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .decree { font-size: 8.5pt; color: #555; margin-top: 3px; }

  .ref-banner { display: flex; justify-content: space-between; background: #f1f8e9; border: 1px solid #c5e1a5; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px; font-size: 9pt; }

  .section-box { border: 1px solid #c8e6c9; border-radius: 4px; margin-bottom: 10px; overflow: hidden; }
  .section-title { background: #e8f5e9; color: #1b5e20; font-weight: bold; font-size: 9.5pt; padding: 4px 10px; border-bottom: 1px solid #c8e6c9; }
  .section-body { padding: 8px 12px; font-size: 9pt; line-height: 1.6; }

  .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .info-item { display: flex; }
  .info-label { font-weight: bold; color: #444; width: 140px; flex-shrink: 0; }
  .info-val { color: #111; flex-grow: 1; border-bottom: 1px dotted #ccc; font-weight: bold; }

  .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 6px; text-align: center; }
  .stat-card { background: #f9fbe7; border: 1px solid #dce775; border-radius: 4px; padding: 8px; }
  .stat-number { font-size: 14pt; font-weight: bold; color: #33691e; }
  .stat-label { font-size: 8.5pt; color: #555; }

  .details-box { background: #fafafa; border: 1px dashed #aaa; padding: 8px 12px; border-radius: 4px; font-size: 8.5pt; line-height: 1.6; margin-top: 6px; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 18px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 85px; background: #fafafa; border-radius: 4px; font-size: 8.5pt; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 9pt; margin-bottom: 4px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #2e7d32; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $wilaya_name }}</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $institution_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('محضر إنجاز نشاط بيئي ومبادرة النادي الأخضر') }}</h1>
  <div class="decree">Procès-Verbal d'Action Environnementale & Initiative Club Vert Éco-École</div>
</div>

<div class="ref-banner">
  <div>رمز النشاط: <strong>{{ $action->action_code }}</strong></div>
  <div>تاريخ التنفيذ: <strong>{{ $action->execution_date?->format('Y-m-d') }}</strong></div>
  <div>مجال النشاط: <strong>{{ $action->action_category?->label() ?? $action->action_category }}</strong></div>
</div>

<div class="section-box">
  <div class="section-title">1. بطاقة النشاط البيئي والهيئات المشاركة</div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">عنوان المبادرة:</span>
        <span class="info-val">{{ $action->action_title }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الأستاذ منسق النادي:</span>
        <span class="info-val">{{ $supervising_teacher }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">مكان التنفيذ:</span>
        <span class="info-val">{{ $action->location_details ?: 'محيط وساحات المؤسسة التعليمية' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الشريك / الهيئة المدعمة:</span>
        <span class="info-val">{{ $action->partner_organization ?: 'النوادي المدرسية والجمعيات البيئية المحلية' }}</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">2. الحصيلة الميدانية والأثر البيئي الملموس</div>
  <div class="section-body">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number">{{ $action->participants_count }}</div>
        <div class="stat-label">تلميذاً ومؤطراً مشاركاً</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">{{ $action->trees_planted_count }}</div>
        <div class="stat-label">شجرة وشتلة تم غرسها</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">{{ number_format($action->waste_collected_kg, 1) }} كغ</div>
        <div class="stat-label">نفايات تم فرزها وتجميعها للرسكلة</div>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">3. التقرير الأدبي والظروف الميدانية للنشاط</div>
  <div class="section-body">
    <div class="details-box">
      {{ $action->impact_summary ?: 'تم إنجاز النشاط البيئي في ظروف تربوية ممتازة وسط تفاعل وتنافس إيجابي لترسيخ الوعي الإيكولوجي والمحافظة على النظافة والغطاء النباتي المدرسي.' }}
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">منسق النادي الأخضر التلميذي</div>
    <p>ممثل تلاميذ النادي</p>
  </div>
  <div class="sig-box">
    <div class="title">الأستاذ المشرف المؤطر</div>
    <p>{{ $supervising_teacher }}</p>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية</div>
    <p>التوقيع وختم المؤسسة</p>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة محضر النشاط البيئي (Imprimer)') }}</button>

</body>
</html>

