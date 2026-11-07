<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('الكشف الإسمي لمنحة التمدرس الخاصة 5000 دج') }} - {{ $campaign['title'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 10mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1b5e20; padding-bottom: 5px; margin-bottom: 8px; }
  .republic-header .republic { font-size: 10.5pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1b5e20; font-size: 10.5pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #1b5e20; border: 2px solid #1b5e20; display: inline-block; padding: 3px 20px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .decree { font-size: 8.5pt; color: #444; margin-top: 3px; }

  .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
  .card { border: 1px solid #c8e6c9; background: #f1f8e9; border-radius: 4px; padding: 5px 8px; text-align: center; }
  .card .label { font-size: 7.5pt; color: #555; margin-bottom: 2px; }
  .card .val { font-size: 10.5pt; font-weight: bold; color: #1b5e20; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 10px; }
  .data-table th, .data-table td { border: 1px solid #999; padding: 4px 5px; text-align: center; }
  .data-table th { background: #e8f5e9; color: #1b5e20; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }
  .data-table tfoot td { font-weight: bold; background: #f1f8e9; color: #1b5e20; }

  .signatures { display: flex; justify-content: space-between; gap: 15px; margin-top: 14px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 6px; text-align: center; min-height: 65px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 8.5pt; margin-bottom: 3px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1b5e20; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $institution['wilaya'] }}</div>
  <div class="meta-row">
    <div>الدائرة: <strong>{{ $institution['daira'] }}</strong> | البلدية: <strong>{{ $institution['commune'] }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution['name'] }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $campaign['academic_year'] }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>الكشف الإسمي للمستفيدين من منحة التمدرس الخاصة (5000 دج)</h1>
  <div class="decree">تطبيقاً لأحكام المرسوم التنفيذي رقم 21-61 المؤرخ في 26 جمادى الثانية 1442 الموافق 08 فبراير 2021</div>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">إجمالي المستفيدين المقبولين</div>
    <div class="val">{{ $total_count }} تلميذ</div>
  </div>
  <div class="card">
    <div class="label">قيمة المنحة الفردية</div>
    <div class="val">{{ number_format($campaign['grant_amount'], 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">المبلغ المالي الإجمالي الإجمالي</div>
    <div class="val">{{ number_format($total_amount, 2) }} دج</div>
  </div>
  <div class="card">
    <div class="label">تاريخ استخراج الكشف</div>
    <div class="val">{{ $generated_at }}</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 3%;">رقم</th>
      <th style="width: 8%;">رقم الملف</th>
      <th style="width: 14%;">لقب واسم التلميذ</th>
      <th style="width: 8%;">رقم التعريف المدرسي</th>
      <th style="width: 8%;">تاريخ الميلاد</th>
      <th style="width: 7%;">القسم</th>
      <th style="width: 13%;">فئة الاستحقاق الاجتماعي</th>
      <th style="width: 13%;">اسم ولقب الولي / الكفيل</th>
      <th style="width: 8%;">رقم ب.ت.و للولي</th>
      <th style="width: 10%;">الحساب البريدي (CCP/Clé)</th>
      <th style="width: 8%;">المبلغ (دج)</th>
    </tr>
  </thead>
  <tbody>
    @forelse($beneficiaries as $ben)
      <tr>
        <td>{{ $ben['index'] }}</td>
        <td style="font-weight: bold;">{{ $ben['file_number'] }}</td>
        <td style="text-align: right; padding-right: 6px; font-weight: bold;">{{ $ben['student_name'] }}</td>
        <td>{{ $ben['matricule'] }}</td>
        <td>{{ $ben['date_of_birth'] }}</td>
        <td>{{ $ben['classroom'] }}</td>
        <td>{{ $ben['eligibility_category'] }}</td>
        <td style="text-align: right; padding-right: 6px;">{{ $ben['guardian_name'] }}</td>
        <td>{{ $ben['guardian_id'] }}</td>
        <td style="direction: ltr; font-family: monospace;">{{ $ben['ccp_account'] }}</td>
        <td style="font-weight: bold;">{{ number_format($ben['grant_amount'], 2) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="11" style="padding: 12px; color: #777;">لا توجد ملفات مقبولة مسجلة في هذه الحملة حالياً.</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td colspan="10" style="text-align: left; padding-left: 12px;">المجموع العام للكشف الإسمي (خمسة آلاف دينار جزائري لكل تلميذ مستفيد):</td>
      <td>{{ number_format($total_amount, 2) }} دج</td>
    </tr>
  </tfoot>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي (المقرر)</div>
    <div style="font-size: 8pt; color: #555;">التأشيرة والختم والتاريخ</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة (رئيس اللجنة)</div>
    <div style="font-size: 8pt; color: #555;">المصادقة والختم الرسمي</div>
  </div>
  <div class="sig-box">
    <div class="title">تأشيرة ومصادقة الدائرة / مديرية التربية</div>
    <div style="font-size: 8pt; color: #555;">رئيس اللجنة الدائرية المشتركة</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة الكشف الرسمي') }}</button>

</body>
</html>
