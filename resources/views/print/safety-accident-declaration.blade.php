<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('استمارة التصريح الرسمي بحادث مدرسي') }} - {{ $incident->incident_number }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; padding: 10mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #b71c1c; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 11pt; color: #b71c1c; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #b71c1c; font-size: 11pt; }

  .doc-title { text-align: center; margin: 8px 0 12px; }
  .doc-title h1 { font-size: 13.5pt; font-weight: bold; color: #b71c1c; border: 2px solid #b71c1c; display: inline-block; padding: 4px 22px; background: #ffebee; border-radius: 4px; }
  .doc-title .decree { font-size: 8.5pt; color: #555; margin-top: 3px; }

  .ref-banner { display: flex; justify-content: space-between; background: #fdf2f2; border: 1px solid #ef9a9a; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px; font-size: 9pt; }
  .ref-banner strong { color: #b71c1c; }

  .section-box { border: 1px solid #c5cae9; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e8eaf6; color: #1a237e; font-weight: bold; font-size: 9.5pt; padding: 3px 10px; border-bottom: 1px solid #c5cae9; display: flex; justify-content: space-between; }
  .section-body { padding: 6px 10px; font-size: 9pt; line-height: 1.5; }

  .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .info-item { display: flex; }
  .info-label { font-weight: bold; color: #444; width: 140px; flex-shrink: 0; }
  .info-val { color: #111; flex-grow: 1; border-bottom: 1px dotted #ccc; }

  .narrative-box { background: #fafafa; border: 1px dashed #bbb; padding: 6px 10px; border-radius: 3px; min-height: 48px; margin-top: 4px; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 14px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; font-size: 8.5pt; }
  .sig-box .title { font-weight: bold; color: #b71c1c; font-size: 9pt; margin-bottom: 4px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #b71c1c; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
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
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('استمارة التصريح الرسمي بحادث مدرسي / حادث عمل') }}</h1>
  <div class="decree">تطبيقاً لأحكام القانون 88-07 المتعلق بالوقاية الصحية والأمن، والتعليمات الوزارية الخاصة بالتأمين المدرسي وحوادث العمل</div>
</div>

<div class="ref-banner">
  <div>رقم ملف الحادث: <strong>{{ $incident->incident_number }}</strong></div>
  <div>طبيعة الحادث: <strong>{{ $victim_type_label }}</strong></div>
  <div>درجة الخطورة: <strong>{{ $severity_label }}</strong></div>
  <div>تاريخ التصريح: <strong>{{ $formatted_declaration_date }}</strong></div>
</div>

<!-- 1. معلومات الضحية -->
<div class="section-box">
  <div class="section-title">
    <span>1. هوية ومعلومات الضحية (Identité de la victime)</span>
    <span>صفة المصاب: {{ $incident->victim_type === 'student' ? 'تلميذ متمدرس' : 'موظف / أستاذ' }}</span>
  </div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">الاسم واللقب:</span>
        <span class="info-val"><strong>{{ $incident->victim_name }}</strong></span>
      </div>
      @if($student)
      <div class="info-item">
        <span class="info-label">رقم التسجيل المدرسي:</span>
        <span class="info-val">{{ $student->registration_number ?? $student->matricule_number ?? '—' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">القسم والفرع:</span>
        <span class="info-val">{{ $student->group?->classroom?->name ?? '—' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">تاريخ ومكان الميلاد:</span>
        <span class="info-val">{{ $student->date_of_birth?->format('Y-m-d') ?? '—' }} {{ $student->place_of_birth ? 'بـ '.$student->place_of_birth : '' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">ولي الأمر / الهاتف:</span>
        <span class="info-val">{{ $student->guardian_name ?? '—' }} ({{ $student->guardian_phone ?? '—' }})</span>
      </div>
      <div class="info-item">
        <span class="info-label">العنوان الشخصي:</span>
        <span class="info-val">{{ $student->address ?? '—' }}</span>
      </div>
      @endif
    </div>
  </div>
</div>

<!-- 2. ظروف ومكان وتاريخ الحادث -->
<div class="section-box">
  <div class="section-title">
    <span>2. ظروف وتاريخ ومكان وقوع الحادث (Date, lieu et circonstances)</span>
  </div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">تاريخ وتوقيت الحادث:</span>
        <span class="info-val">{{ $formatted_incident_date }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">المكان الدقيق بالهيكل:</span>
        <span class="info-val"><strong>{{ $incident->location }}</strong></span>
      </div>
      <div class="info-item">
        <span class="info-label">المسؤول/المشرف التربوي:</span>
        <span class="info-val">{{ $incident->supervising_personnel_at_time ?? 'تحت الحراسة العامة' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">المحقق الإداري:</span>
        <span class="info-val">{{ $incident->investigating_officer_name ?? 'مستشار التربية' }}</span>
      </div>
    </div>
    <div style="margin-top: 6px;">
      <strong style="color: #444; font-size: 8.5pt;">الرواية المفصلة للوقائع والأسباب (Récit des faits):</strong>
      <div class="narrative-box">{{ $incident->circumstances_description }}</div>
    </div>
    @if($incident->witnesses_statements)
    <div style="margin-top: 4px;">
      <strong style="color: #444; font-size: 8.5pt;">شهادة الشهود الحاضرين (Témoins):</strong>
      <div class="narrative-box" style="min-height: 28px;">{{ $incident->witnesses_statements }}</div>
    </div>
    @endif
  </div>
</div>

<!-- 3. الإصابات والإسعافات الأولية والإخلاء الطبي -->
<div class="section-box">
  <div class="section-title">
    <span>3. المعاينة الطبية والإسعافات والإخلاء الصحي (Constat médical et évacuation)</span>
  </div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">طبيعة الإصابات الجسدية:</span>
        <span class="info-val" style="color: #b71c1c;"><strong>{{ $incident->bodily_injuries_description ?? '—' }}</strong></span>
      </div>
      <div class="info-item">
        <span class="info-label">الإسعافات المقدمة بالعيادة:</span>
        <span class="info-val">{{ $incident->immediate_care_administered ?? 'إسعافات أولية أولية بقاعة التمريض' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">الإخلاء للمستشفى:</span>
        <span class="info-val">{{ $incident->hospital_evacuation ? 'نعم — تم الإخلاء الاستعجالي' : 'لا — عولج بالمؤسسة' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">المؤسسة الاستشفائية / العيادة:</span>
        <span class="info-val">{{ $incident->hospital_name ?? '—' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">مرجع الشهادة الطبية الأولية:</span>
        <span class="info-val">{{ $incident->medical_certificate_ref ?? 'شهادة طبية رقم ... بتاريخ ...' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">مدة العجز المؤقت (ITT):</span>
        <span class="info-val"><strong>{{ $incident->temporary_incapacity_days }} يوماً</strong></span>
      </div>
    </div>
  </div>
</div>

<!-- 4. المتابعة الإدارية والتأمينية -->
<div class="section-box">
  <div class="section-title">
    <span>4. المتابعة القانونية والتأمين المدرسي (Démarches administratives et assurances)</span>
  </div>
  <div class="section-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">التصريح لدى التأمين (MAATEC):</span>
        <span class="info-val">{{ $incident->insurance_declared ? 'تم التصريح في الآجال القانونية (48 ساعة)' : 'قيد الإجراء' }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">إخطار مديرية التربية:</span>
        <span class="info-val">{{ $incident->directorate_notified ? 'تم إرسال نسخة لمصلحة التمدرس والتأمينات' : 'قيد الإرسال' }}</span>
      </div>
    </div>
  </div>
</div>

<!-- 5. التأشيرات الرسمية -->
<div class="signatures">
  <div class="sig-box">
    <div class="title">الأستاذ / المشرف المسؤول أثناء الحصة</div>
    <div>الاسم واللقب: .......................................</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي (مسؤول التأمين)</div>
    <div>الاسم: .......................................</div>
    <div style="margin-top: 25px;">التوقيع والتأشيرة:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (رئيس المؤسسة)</div>
    <div>في: ............................... بتاريخ: .....................</div>
    <div style="margin-top: 25px;">الختم الرسمي والتوقيع:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة الاستمارة الرسمية</button>

</body>
</html>

