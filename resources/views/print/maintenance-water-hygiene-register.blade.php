<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سجل النظافة والتطهير الدوري لخزانات وباشات الماء') }} - {{ $reference }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 6mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #0288d1; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #01579b; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 2px; }

  .doc-title { text-align: center; margin: 4px 0 8px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #01579b; border: 2px solid #0288d1; display: inline-block; padding: 3px 20px; background: #e1f5fe; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .ref-banner { display: flex; justify-content: space-between; background: #fafafa; border: 1px solid #ddd; border-radius: 4px; padding: 4px 10px; margin-bottom: 8px; font-size: 8.5pt; }

  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

  .section-box { border: 1px solid #b3e5fc; border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
  .section-title { background: #e1f5fe; color: #01579b; font-weight: bold; font-size: 9pt; padding: 3px 8px; border-bottom: 1px solid #b3e5fc; }
  .section-body { padding: 5px 8px; font-size: 8.5pt; line-height: 1.4; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8.5pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .badge-pass { background: #e8f5e9; color: #2e7d32; font-weight: bold; padding: 2px 6px; border-radius: 3px; border: 1px solid #a5d6a7; }
  .badge-danger { background: #ffebee; color: #c62828; font-weight: bold; padding: 2px 6px; border-radius: 3px; border: 1px solid #ef9a9a; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #01579b; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 15px; left: 15px; background: #0288d1; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 9pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية الوطنية</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>سجل النظافة الصحية وحماية الماء الشروب (Hygiène de l'eau potable)</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('سجل وشهادة النظافة والتطهير الدوري لخزانات وباشات الماء') }}</h1>
  <div class="decree">Registre sanitaire de nettoyage, désinfection et analyse microbiologique des bâches à eau et citernes</div>
</div>

<div class="ref-banner">
  <div>رقم العملية: <strong>{{ $reference }}</strong></div>
  <div>تاريخ التدخل: <strong>{{ $inspection->inspection_date?->format('Y-m-d') }}</strong></div>
  <div>تاريخ التدخل القادم: <strong>{{ $inspection->next_disinfection_due_date?->format('Y-m-d') }}</strong></div>
  <div>المكتب البلدي للنظافة / المنفذ: <strong>{{ $inspection->executed_by }}</strong></div>
  <div>شهادة الصلاحية: <strong class="{{ $inspection->water_potable_clearance === \App\Enums\WaterPotabilityStatus::CompliantPotable ? 'badge-pass' : 'badge-danger' }}">{{ $inspection->water_potable_clearance?->label() }}</strong></div>
</div>

<div class="grid-2">
  <div class="section-box">
    <div class="section-title">1. المواصفات الهندسية للخزان أو الباشة المائية</div>
    <div class="section-body">
      <table class="table-clean">
        <tr>
          <th style="width: 35%;">تعريف الخزان / الباشة:</th>
          <td><strong>{{ $inspection->tank_name_code }}</strong></td>
          <th style="width: 30%;">النوع الهندسي:</th>
          <td>{{ $inspection->tank_type?->label() }}</td>
        </tr>
        <tr>
          <th>السعة التخزينية الإجمالية:</th>
          <td><strong>{{ $inspection->capacity_cubic_meters }} م³</strong></td>
          <th>طبيعة العملية:</th>
          <td>{{ $inspection->operation_type }}</td>
        </tr>
        <tr>
          <th>المادة الكيميائية المطهرة:</th>
          <td colspan="3">{{ $inspection->chemical_agent_used ?: 'ماء جافيل / ماء الأكسجين / هيبوكلوريت' }}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="section-box">
    <div class="section-title">2. نتائج الفحص المخبري ومعايير جودة المياه</div>
    <div class="section-body">
      <table class="table-clean">
        <tr>
          <th style="width: 40%;">نسبة الكلور المتبقي (Chlore résiduel):</th>
          <td>
            <strong>{{ $inspection->residual_chlorine_ppm }} PPM</strong>
            <span style="font-size: 7.5pt; color: #555;">(المعدل القانوني: 0.2 - 0.5 PPM)</span>
          </td>
        </tr>
        <tr>
          <th>مرجع التحليل البكتيريولوجي:</th>
          <td>{{ $inspection->certification_ref ?: __('تحليل مخبري مطابق لمعايير المياه الصالحة للشرب') }}</td>
        </tr>
        <tr>
          <th>المطابقة للشرب (Potabilité):</th>
          <td>
            <span class="{{ $inspection->water_potable_clearance === \App\Enums\WaterPotabilityStatus::CompliantPotable ? 'badge-pass' : 'badge-danger' }}">
              {{ $inspection->water_potable_clearance?->label() }}
            </span>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">3. ملاحظات النظافة والتدابير الوقائية المتخذة</div>
  <div class="section-body" style="min-height: 40px;">
    {{ $inspection->notes ?: __('تم إفراغ الخزان، غسله وتطهيره بالمواد الكيميائية المعتمدة وإعادة ملئه، مع قياس نسبة الكلور الحر المتبقي، والماء صالح للاستهلاك والشرب بالمطعم والنظام الداخلي والحنفيات.') }}
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">{{ __('طبيب الصحة المدرسية / ممثل مكتب النظافة BHM') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">معاينة وتأشيرة السلامة الصحية</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('المقتصد / المصالح الاقتصادية') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">متابعة عمليات النظافة والتطهير</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('مدير المؤسسة') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">الختم والتأشيرة الرسمية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة السجل (Imprimer)') }}</button>

</body>
</html>

