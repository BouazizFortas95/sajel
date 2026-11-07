<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة الحوض البيداغوجي والحديقة النباتية المدرسية') }} - {{ $plot->plot_code }}</title>
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

  table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 8.5pt; }
  th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: center; }
  th { background: #f1f8e9; color: #1b5e20; font-weight: bold; }

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
  <h1>{{ __('بطاقة الحوض البيداغوجي والحديقة النباتية المدرسية') }}</h1>
  <div class="decree">Fiche Technique de la Parcelle et Jardin Botanique Pédagogique (Éco-École)</div>
</div>

<div class="ref-banner">
  <div>رمز الحوض: <strong>{{ $plot->plot_code }}</strong></div>
  <div>المساحة: <strong>{{ number_format($plot->surface_area_sqm, 2) }} م²</strong></div>
  <div>نظام الري: <strong>{{ $plot->irrigation_type?->label() ?? $plot->irrigation_type }}</strong></div>
  <div>الحالة: <strong>{{ $plot->status?->label() ?? $plot->status }}</strong></div>
</div>

<div class="section-box">
  <div class="section-title">1. البيانات الفنية للحوض والتأطير التربوي</div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">اسم الحوض:</span>
        <span class="info-val">{{ $plot->plot_name }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">موقع الحوض بالمؤسسة:</span>
        <span class="info-val">{{ $plot->location_in_school ?: 'فناء المؤسسة التعليمية' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">طبيعة ونوع التربة:</span>
        <span class="info-val">{{ $plot->soil_type ?: 'تربة زراعية مخصبة' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الأستاذ المؤطر المشرف:</span>
        <span class="info-val">{{ $supervising_teacher }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الفوج التربوي المكلف:</span>
        <span class="info-val">{{ $assigned_classroom }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">عدد الأنواع المزروعة:</span>
        <span class="info-val">{{ $plants->count() }} أصناف نباتية</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">2. سجل وجرد النباتات والمزروعات بالحوض البيداغوجي</div>
  <div class="section-body">
    <table>
      <thead>
        <tr>
          <th>الرمز</th>
          <th>الاسم الشائع (عربي)</th>
          <th>الاسم العلمي / اللاتيني</th>
          <th>التصنيف النباتي</th>
          <th>العدد المغروس</th>
          <th>تاريخ الغرس</th>
          <th>الحالة الصحية</th>
        </tr>
      </thead>
      <tbody>
        @forelse($plants as $plant)
          <tr>
            <td><strong>{{ $plant->plant_code }}</strong></td>
            <td>{{ $plant->common_name_ar }}</td>
            <td><em>{{ $plant->botanical_name ?: '—' }}</em></td>
            <td>{{ $plant->plant_category?->label() ?? $plant->plant_category }}</td>
            <td>{{ $plant->quantity_planted }}</td>
            <td>{{ $plant->planting_date ? $plant->planting_date->format('Y-m-d') : '—' }}</td>
            <td>{{ $plant->health_status?->label() ?? $plant->health_status }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="color: #777;">{{ __('لا توجد نباتات مسجلة في هذا الحوض بعد.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">ممثل الفوج التربوي المكلف</div>
    <p>مندوب الفوج التلميذي</p>
  </div>
  <div class="sig-box">
    <div class="title">أستاذ علوم الطبيعة والحياة المشرف</div>
    <p>{{ $supervising_teacher }}</p>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية</div>
    <p>التوقيع وختم المؤسسة</p>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة بطاقة الحوض (Imprimer)') }}</button>

</body>
</html>

