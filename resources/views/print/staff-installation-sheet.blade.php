@php
    $roleMap = [
        'developer' => 'مطور النظام',
        'principal' => 'مدير المؤسسة',
        'censor' => 'ناظر الدروس',
        'general_supervisor' => 'مستشار التربية',
        'education_supervisor' => 'مشرف التربية',
        'counselor' => 'مستشار التوجيه والإرشاد',
        'finance_officer' => 'المقتصد / مسير مالي',
        'head_teacher' => 'أستاذ رئيسي / منسق مادة',
        'teacher' => 'أستاذ',
        'admin' => 'المكلف بالرقمنة وأمانة النظام',
    ];

    $rawRole = trim($installation->role_label ?? '');
    $lowerRole = strtolower($rawRole);

    if (isset($roleMap[$lowerRole])) {
        $roleLabel = $roleMap[$lowerRole];
    } elseif ($rawRole !== '') {
        $roleLabel = $rawRole;
    } elseif ($user?->role instanceof \App\Enums\Role && isset($roleMap[$user->role->value])) {
        $roleLabel = $roleMap[$user->role->value];
    } else {
        $roleLabel = 'أستاذ';
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>محضر تنصيب — {{ $user->name ?? '' }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  @page {
    size: A4 portrait;
    margin: 8mm 12mm 6mm 12mm;
  }

  html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #111;
    direction: rtl;
    font-family: 'Amiri', serif;
    font-size: 10.5pt;
    line-height: 1.45;
  }

  @media print {
    body {
      print-color-adjust: exact;
      -webkit-print-color-adjust: exact;
      overflow: hidden;
    }
    .print-btn {
      display: none !important;
    }
  }

  .sheet-container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    page-break-inside: avoid;
    break-inside: avoid;
  }

  /* ── Header ── */
  .republic-header {
    text-align: center;
    border-bottom: 2px double #1a237e;
    padding-bottom: 4px;
    margin-bottom: 6px;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .republic-header .republic {
    font-size: 10.5pt;
    letter-spacing: 0.5px;
    color: #1a237e;
    font-weight: bold;
  }
  .republic-header .ministry {
    font-size: 9.5pt;
    color: #333;
    margin-top: 1px;
  }
  .republic-header .directorate {
    font-size: 9.5pt;
    color: #333;
  }
  .republic-header .institution {
    font-size: 11.5pt;
    font-weight: bold;
    margin-top: 2px;
    color: #1a237e;
  }

  /* ── Title ── */
  .doc-title-wrapper {
    text-align: center;
    margin: 4px 0 2px;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .doc-title {
    display: inline-block;
    border: 2px solid #1a237e;
    padding: 3px 26px;
    font-size: 15pt;
    font-weight: bold;
    color: #1a237e;
    letter-spacing: 1px;
    background: #f8faff;
  }
  .doc-subtitle {
    font-size: 9pt;
    color: #555;
    margin-top: 2px;
    margin-bottom: 6px;
    font-style: italic;
  }

  /* ── Preamble ── */
  .preamble {
    margin-bottom: 6px;
    font-size: 9.5pt;
    line-height: 1.5;
    border: 1px solid #cbd5e1;
    padding: 5px 10px;
    background: #f8fafc;
    border-radius: 4px;
    text-align: justify;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .preamble strong {
    color: #1a237e;
  }

  /* ── Sections ── */
  .section-title {
    font-size: 10.5pt;
    font-weight: bold;
    color: #1a237e;
    margin: 6px 0 3px;
    border-right: 4px solid #1a237e;
    padding-right: 8px;
    page-break-inside: avoid;
    break-inside: avoid;
    page-break-after: avoid;
    break-after: avoid;
  }

  /* ── Table ── */
  .info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 6px;
    font-size: 9.5pt;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .info-table td {
    padding: 4px 8px;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
  }
  .info-table .label {
    background: #e8eaf6;
    font-weight: bold;
    width: 25%;
    color: #1a237e;
    white-space: nowrap;
  }
  .info-table .value {
    background: #fff;
    color: #1e293b;
  }

  /* ── Body declaration ── */
  .body-text {
    font-size: 10pt;
    line-height: 1.55;
    margin: 6px 0;
    text-align: justify;
    background: #fdfdfe;
    border: 1px solid #e2e8f0;
    border-right: 3px solid #1a237e;
    padding: 5px 10px;
    border-radius: 2px;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .body-text strong {
    color: #1a237e;
  }

  /* ── Signatures ── */
  .signatures {
    margin-top: 8px;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .sig-block {
    flex: 1;
    border: 1px solid #94a3b8;
    padding: 6px 8px 30px;
    text-align: center;
    background: #fafafa;
    border-radius: 4px;
    position: relative;
  }
  .sig-block .sig-title {
    font-weight: bold;
    font-size: 10pt;
    margin-bottom: 2px;
    color: #1a237e;
  }
  .sig-block .sig-name {
    font-size: 9.5pt;
    color: #475569;
    margin-top: 4px;
    border-top: 1px dashed #cbd5e1;
    padding-top: 3px;
  }

  /* ── Footer ── */
  .footer {
    margin-top: 6px;
    border-top: 1px solid #cbd5e1;
    padding-top: 3px;
    text-align: center;
    font-size: 8pt;
    color: #64748b;
    page-break-inside: avoid;
    break-inside: avoid;
  }

  .print-btn {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: #1a237e;
    color: #fff;
    border: none;
    padding: 8px 18px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 10pt;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    z-index: 9999;
  }
</style>
</head>
<body>

<div class="sheet-container">
  <div class="republic-header">
    <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
    <div class="ministry">وزارة التربية الوطنية</div>
    <div class="directorate">{{ $school->directorate_name ?? 'مديرية التربية' }}</div>
    <div class="institution">{{ $school->school_name ?? 'المؤسسة التربوية' }}</div>
  </div>

  <div class="doc-title-wrapper">
    <div class="doc-title">محـضـر تـنـصـيـب</div>
    <div class="doc-subtitle">Procès-Verbal d'Installation</div>
  </div>

  <div class="preamble">
    بناءً على المرسوم التنفيذي رقم <strong>08-315 المؤرخ في 11 أكتوبر 2008</strong> المتضمن القانون الأساسي الخاص بموظفي التربية الوطنية، وعلى الأمر رقم <strong>06-03 المؤرخ في 15 يوليو 2006</strong> المتضمن القانون الأساسي للوظيفة العمومية،<br>
    وبناءً على <strong>قرار التعيين رقم {{ $installation->recruitment_order_ref ?? '...' }}</strong>،
    @if($installation->ministry_decision_ref)
      والمقرر الوزاري رقم <strong>{{ $installation->ministry_decision_ref }}</strong>،
    @endif
    <br>يُنصَّب السيد/السيدة المبيَّن هويته أدناه في الوظيفة المحددة بمؤسستنا.
  </div>

  <div class="section-title">أولاً: الهوية المدنية والمهنية</div>
  <table class="info-table">
    <tr>
      <td class="label">الاسم واللقب</td>
      <td class="value">{{ $user->name ?? '—' }}</td>
      <td class="label">رقم التسجيل (الماتريكول)</td>
      <td class="value">{{ $user->registration_number ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">الوظيفة / الصفة</td>
      <td class="value"><strong>{{ $roleLabel }}</strong></td>
      <td class="label">الرتبة القانونية</td>
      <td class="value">{{ $installation->statutory_grade ?? $user->statutory_grade ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">تاريخ الميلاد</td>
      <td class="value">{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->locale('ar')->isoFormat('LL') : '—' }}</td>
      <td class="label">مكان الميلاد</td>
      <td class="value">{{ $user->birth_place ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">المؤسسة السابقة</td>
      <td class="value" colspan="3">{{ $installation->previous_institution ?? 'أول تعيين' }}</td>
    </tr>
  </table>

  <div class="section-title">ثانياً: بيانات التنصيب</div>
  <table class="info-table">
    <tr>
      <td class="label">تاريخ التنصيب</td>
      <td class="value">{{ $installation->installation_date ? \Carbon\Carbon::parse($installation->installation_date)->locale('ar')->isoFormat('LL') : '—' }}</td>
      <td class="label">مرجع قرار التعيين</td>
      <td class="value">{{ $installation->recruitment_order_ref ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">المؤسسة المعيَّن بها</td>
      <td class="value" colspan="3">{{ $school->school_name ?? '—' }}</td>
    </tr>
    @if($installation->notes)
    <tr>
      <td class="label">ملاحظات</td>
      <td class="value" colspan="3">{{ $installation->notes }}</td>
    </tr>
    @endif
  </table>

  <div class="body-text">
    تفيد الإدارة بأنه قد جرى تنصيب السيد/السيدة <strong>{{ $user->name ?? '—' }}</strong> بصفة <strong>{{ $roleLabel }}</strong> بمؤسستنا ابتداءً من تاريخ <strong>{{ $installation->installation_date ? \Carbon\Carbon::parse($installation->installation_date)->locale('ar')->isoFormat('LL') : '—' }}</strong>، وذلك وفق الأحكام التشريعية والتنظيمية المعمول بها.
  </div>

  <div class="signatures">
    <div class="sig-block">
      <div class="sig-title">توقيع الموظف المنصَّب</div>
      <div class="sig-name">{{ $user->name ?? '—' }}</div>
    </div>
    <div class="sig-block">
      <div class="sig-title">مدير المؤسسة</div>
      <div class="sig-name">{{ $school->principal_name ?: ($school->school_name ?? '') }}</div>
    </div>
  </div>

  <div class="footer">
    حُرِّر بـ {{ $school->municipality_name ?? 'المؤسسة' }} بتاريخ {{ $printed_at ?? now()->locale('ar')->isoFormat('LL') }} | سجل أرشيف المؤسسة
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨 طباعة المحضر</button>
</body>
</html>
