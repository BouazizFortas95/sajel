<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة مشروع ومبادرة التعاونية المدرسية') }} - {{ $project->project_code }}</title>
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
  <h1>{{ __('بطاقة مشروع ومبادرة التعاونية المدرسية') }}</h1>
  <div class="decree">Fiche de Projet Coopératif Scolaire & Action Éducative / Solidaire</div>
</div>

<div class="ref-banner">
  <div>رمز المشروع: <strong>{{ $project->project_code }}</strong></div>
  <div>تاريخ الإنشاء: <strong>{{ $generated_at }}</strong></div>
  <div>الحالة الحالية: <strong>{{ $project->status?->label() ?? $project->status }}</strong></div>
</div>

<div class="section-box">
  <div class="section-title">1. بطاقة تعريف المشروع والمبادرة التضامنية / الإبداعية</div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">عنوان المشروع:</span>
        <span class="info-val">{{ $project->title }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">طبيعة وتصنيف المشروع:</span>
        <span class="info-val">{{ $project->project_type?->label() ?? $project->project_type }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الجمهور والتلاميذ المستهدفون:</span>
        <span class="info-val">{{ $project->target_audience ?: 'تلاميذ المؤسسة والنوادي المدرسية' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">تاريخ الانطلاق:</span>
        <span class="info-val">{{ $project->start_date?->format('Y-m-d') }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">تاريخ الاختتام / الإنجاز:</span>
        <span class="info-val">{{ $project->end_date ? $project->end_date->format('Y-m-d') : 'مفتوح ومستمر' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الأستاذ المؤطر المنسق:</span>
        <span class="info-val">{{ $coordinator_name }}</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">2. الميزانية التقديرية والموارد المالية للتعاونية</div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">الميزانية المخصصة:</span>
        <span class="info-val">{{ number_format($project->budget_allocated, 2) }} دج</span>
      </div>
      <div class="info-item">
        <span class="info-label">المبلغ المصروف فعلياً:</span>
        <span class="info-val">{{ number_format($project->budget_spent, 2) }} دج</span>
      </div>
      <div class="info-item">
        <span class="info-label">رصيد صندوق التعاونية:</span>
        <span class="info-val">{{ number_format($cooperative->treasury_balance, 2) }} دج</span>
      </div>
      <div class="info-item">
        <span class="info-label">مصدر التمويل:</span>
        <span class="info-val">صندوق التعاونية المدرسية واشتراكات الأعضاء</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">3. الفريق التلميذي القيادي والمهام الموزعة</div>
  <div class="section-body">
    <div class="details-box">
      {{ $project->student_leads_summary ?: 'تم توزيع المهام بين أفواج التلاميذ المنخرطين تحت إشراف مكتب التعاونية المدرسية.' }}
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">4. المخرجات والنتائج التربوية والمجتمعية المحققة</div>
  <div class="section-body">
    <div class="details-box">
      {{ $project->achievements_notes ?: 'ساهم المشروع في تعزيز روح العمل الجماعي والمسؤولية والمواطنة والتضامن بين صفوف المتمدرسين.' }}
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">رئيس مكتب التعاونية التلميذي</div>
    <p>{{ $cooperative->student_president_name ?: 'التلميذ(ة) رئيس المكتب' }}</p>
  </div>
  <div class="sig-box">
    <div class="title">الأستاذ المؤطر المشرف</div>
    <p>{{ $coordinator_name }}</p>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التربوية</div>
    <p>التوقيع والختم الرسمي</p>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة بطاقة المشروع (Imprimer)') }}</button>

</body>
</html>
