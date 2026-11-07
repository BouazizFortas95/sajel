<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('استمارة الترخيص الأبوي والشهادة الطبية للنشاط الرياضي') }} - {{ $license->license_number }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; padding: 10mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1565c0; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }

  .doc-title { text-align: center; margin: 8px 0 12px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #0d47a1; border: 2px solid #1565c0; display: inline-block; padding: 4px 20px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .decree { font-size: 8.5pt; color: #555; margin-top: 3px; }

  .ref-banner { display: flex; justify-content: space-between; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px; font-size: 9pt; }

  .section-box { border: 1px solid #bbdefb; border-radius: 4px; margin-bottom: 10px; overflow: hidden; }
  .section-title { background: #e3f2fd; color: #0d47a1; font-weight: bold; font-size: 9.5pt; padding: 4px 10px; border-bottom: 1px solid #bbdefb; }
  .section-body { padding: 8px 12px; font-size: 9pt; line-height: 1.6; }

  .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .info-item { display: flex; }
  .info-label { font-weight: bold; color: #444; width: 140px; flex-shrink: 0; }
  .info-val { color: #111; flex-grow: 1; border-bottom: 1px dotted #ccc; font-weight: bold; }

  .legal-statement { background: #fafafa; border: 1px dashed #999; padding: 8px 12px; border-radius: 4px; font-size: 8.5pt; line-height: 1.6; margin-top: 6px; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 16px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 85px; background: #fafafa; border-radius: 4px; font-size: 8.5pt; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 9pt; margin-bottom: 4px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1565c0; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — الجمعية الثقافية والرياضية المدرسية (ASCS)</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('استمارة الترخيص الأبوي والشهادة الطبية للممارسة الرياضية المدرسية') }}</h1>
  <div class="decree">Engagement parental de participation & Certificat médical d'aptitude physique (Loi 13-05 relative au sport)</div>
</div>

<div class="ref-banner">
  <div>رقم الإجازة الرياضية: <strong style="font-family: monospace;">{{ $license->license_number }}</strong></div>
  <div>النشاط الرياضي: <strong>{{ $discipline?->name_ar }}</strong></div>
  <div>الفئة العمرية: <strong>{{ $discipline?->age_category?->label() }}</strong></div>
  <div>تاريخ التسجيل: <strong>{{ $license->issue_date?->format('Y-m-d') }}</strong></div>
</div>

<div class="section-box">
  <div class="section-title">1. بيانات التلميذ(ة) المنخرط(ة)</div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">اللقب والاسم:</span>
        <span class="info-val">{{ $student?->full_name }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">رقم التعريف المدرسي:</span>
        <span class="info-val">{{ $student?->registration_number ?: '—' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">تاريخ ومكان الميلاد:</span>
        <span class="info-val">{{ $student?->date_of_birth?->format('Y-m-d') }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">القسم والفوج التربوي:</span>
        <span class="info-val">{{ $student?->group?->classroom?->name }} ({{ $student?->group?->name }})</span>
      </div>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">2. شهادة الفحص الطبي للقدرة البدنية (Certificat médical d'aptitude)</div>
  <div class="section-body">
    <p>أنا الموقع أسفله، الطبيب الفاحص لوحدة الكشف والمتابعة (UDS) / الطبيب المعتمد:</p>
    <div style="margin: 6px 0; font-weight: bold; color: #0d47a1;">
      د/ {{ $license->medical_examiner_name ?: 'طبيب وحدة الكشف والمتابعة المدرسية' }}
    </div>
    <p>أشهد أنني قمت بالفحص الإكلينيكي الدقيق للتلميذ(ة) المذكور(ة) أعلاه، وتبين أنه(ا):</p>
    <div style="margin: 8px 0; text-align: center;">
      <span style="font-size: 11pt; font-weight: bold; padding: 4px 16px; border: 2px solid #2e7d32; background: #e8f5e9; color: #1b5e20; border-radius: 4px;">
        {{ $license->medical_aptitude_verdict?->label() }}
      </span>
    </div>
    <p style="font-size: 8.5pt; color: #555;">
      ولا تظهر عليه(ا) أية موانع صحية أو صدرية أو قلبية للممارسة المنتظمة للتربية البدنية والنشاط الرياضي التنافسي في اختصاص ({{ $discipline?->name_ar }}).
    </p>
  </div>
</div>

<div class="section-box">
  <div class="section-title">3. ترخيص وتعهد الولي الشرعي (Autorisation parentale d'engagement)</div>
  <div class="section-body">
    <div class="legal-statement">
      أنا الموقع أسفله، السيد(ة): ............................................................................، بصفتي ولي أمر التلميذ(ة): <strong>{{ $student?->full_name }}</strong>،
      <br>
      أصرّح بموافقتي الكاملة وترخيصي لابني/ابنتي بالانخراط في الجمعية الثقافية والرياضية المدرسية (ASCS) والمشاركة في الحصص التدريبية والبطولات المدرسية المنظمة من طرف الرابطة الولائية للرياضة المدرسية، مع الالتزام بالنظام الداخلي للمؤسسة، والترخيص لإدارة المؤسسة ومؤطري الجمعية بنقله(ا) للملاعب والقاعات أثناء المنافسات الرياضية الرسمية.
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">طبيب الفحص (UDS)</div>
    <div style="font-size: 7.5pt; margin-top: 30px;">التوقيع والختم الطبي</div>
  </div>
  <div class="sig-box">
    <div class="title">توقيع وموافقة ولي الأمر</div>
    <div style="font-size: 7.5pt; margin-top: 30px;">قرئ وصودق عليه — بتـاريخ: ..............</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس الجمعية (مدير المؤسسة)</div>
    <div style="font-size: 7.5pt; margin-top: 30px;">المصادقة والختم الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة الاستمارة (Imprimer)') }}</button>

</body>
</html>

