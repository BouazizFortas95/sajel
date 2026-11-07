<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة الإجازة الرياضية المدرسية') }} - {{ $license->license_number }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; padding: 12mm; }
  @page { size: A4 portrait; margin: 10mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1565c0; padding-bottom: 5px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 4px; }

  .card-container { border: 2px solid #1565c0; border-radius: 8px; padding: 16px; background: #fafafa; margin: 0 auto; max-width: 650px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

  .card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #90caf9; padding-bottom: 10px; margin-bottom: 12px; }
  .ascs-title { font-size: 13pt; font-weight: bold; color: #0d47a1; }
  .license-badge { background: #1565c0; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 11pt; }

  .card-body { display: flex; gap: 16px; }
  .photo-box { width: 110px; height: 130px; border: 2px dashed #999; border-radius: 6px; display: flex; align-items: center; justify-content: center; background: #fff; color: #888; font-size: 8.5pt; text-align: center; flex-shrink: 0; }

  .info-table { flex: 1; border-collapse: collapse; width: 100%; font-size: 9.5pt; }
  .info-table td { padding: 4px 6px; border-bottom: 1px dotted #ccc; }
  .info-table .lbl { font-weight: bold; color: #333; width: 140px; }
  .info-table .val { color: #111; font-weight: bold; }

  .visas-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 16px; text-align: center; font-size: 8pt; }
  .visa-box { border: 1px solid #bbb; border-radius: 4px; padding: 6px; background: #fff; min-height: 75px; }
  .visa-title { font-weight: bold; color: #0d47a1; margin-bottom: 4px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

  .watermark { text-align: center; font-size: 7.5pt; color: #888; margin-top: 8px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1565c0; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 10pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — الرابطة الولائية للرياضة المدرسية (LWSS / FASS)</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="card-container">
  <div class="card-header">
    <div>
      <div class="ascs-title">الجمعية الثقافية والرياضية المدرسية (ASCS)</div>
      <div style="font-size: 8.5pt; color: #555;">{{ $association->association_name }} — رقم الانخراط: {{ $association->affiliation_number }}</div>
    </div>
    <div class="license-badge">
      {{ $license->license_type?->label() }}
    </div>
  </div>

  <div class="card-body">
    <div class="photo-box">
      @if($license->photo_path)
        <img src="{{ asset($license->photo_path) }}" alt="Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
      @else
        <span>صورة التلميذ(ة)<br>3 × 4 سم</span>
      @endif
    </div>

    <table class="info-table">
      <tr>
        <td class="lbl">رقم الإجازة الرياضية:</td>
        <td class="val" style="color: #0d47a1; font-family: monospace;">{{ $license->license_number }}</td>
      </tr>
      <tr>
        <td class="lbl">اللقب والاسم:</td>
        <td class="val">{{ $student?->full_name }}</td>
      </tr>
      <tr>
        <td class="lbl">رقم التعريف المدرسي:</td>
        <td class="val" style="font-family: monospace;">{{ $student?->registration_number ?: '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">تاريخ ومكان الميلاد:</td>
        <td class="val">{{ $student?->date_of_birth?->format('Y-m-d') }}</td>
      </tr>
      <tr>
        <td class="lbl">القسم والفوج التربوي:</td>
        <td class="val">{{ $student?->group?->classroom?->name }} ({{ $student?->group?->name }})</td>
      </tr>
      <tr>
        <td class="lbl">النشاط / الاختصاص الرياضي:</td>
        <td class="val" style="color: #2e7d32;">{{ $discipline?->name_ar }} ({{ $discipline?->discipline_code }})</td>
      </tr>
      <tr>
        <td class="lbl">الفئة العمرية:</td>
        <td class="val">{{ $discipline?->age_category?->label() }}</td>
      </tr>
      <tr>
        <td class="lbl">صلاحية الإجازة:</td>
        <td class="val">من {{ $license->issue_date?->format('Y-m-d') }} إلى {{ $license->expiry_date?->format('Y-m-d') }}</td>
      </tr>
    </table>
  </div>

  <div class="visas-grid">
    <div class="visa-box">
      <div class="visa-title">الفحص الطبي للقدرة البدنية (UDS)</div>
      <div style="font-size: 7.5pt; color: #2e7d32; font-weight: bold; margin-top: 4px;">
        {{ $license->medical_aptitude_verdict?->label() }}
      </div>
      <div style="font-size: 7pt; color: #666; margin-top: 15px;">ختم وتأشيرة الطبيب الفاحص</div>
    </div>
    <div class="visa-box">
      <div class="visa-title">الترخيص والتأمين الأبوي</div>
      <div style="font-size: 7.5pt; color: #1565c0; font-weight: bold; margin-top: 4px;">
        {{ $license->parental_consent_signed ? 'موافقة وتعهد الولي مؤكد' : 'قيد الانتظار' }}
      </div>
      <div style="font-size: 7pt; color: #666; margin-top: 15px;">توقيع ولي أمر التلميذ</div>
    </div>
    <div class="visa-box">
      <div class="visa-title">رئيس الجمعية (مدير المؤسسة)</div>
      <div style="font-size: 7.5pt; color: #0d47a1; font-weight: bold; margin-top: 4px;">
        اعتماد ومصادقة الجمعية
      </div>
      <div style="font-size: 7pt; color: #666; margin-top: 15px;">الختم والتوقيع الرسمي</div>
    </div>
  </div>

  <div class="watermark">
    بطاقة رسمية غير قابلة للتحويل، تلزم صاحبها بالانضباط والروح الرياضية وتمثيل المؤسسة بامتياز في المنافسات الرسمية والودية.
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة بطاقة الإجازة (Imprimer)') }}</button>

</body>
</html>

