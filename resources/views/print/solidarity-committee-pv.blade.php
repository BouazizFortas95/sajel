<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر مداولات لجنة التضامن المدرسي') }} - {{ $campaign['title'] }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1b5e20; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 11pt; color: #1b5e20; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9pt; margin-top: 6px; }
  .republic-header .institution { font-weight: bold; color: #1b5e20; font-size: 11.5pt; }

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 14pt; font-weight: bold; color: #1b5e20; border: 2px solid #1b5e20; display: inline-block; padding: 4px 24px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .sub { font-size: 9.5pt; color: #444; margin-top: 4px; }

  .legal-preamble { background: #fafafa; border: 1px solid #ddd; border-right: 3px solid #1b5e20; padding: 8px 12px; margin-bottom: 14px; font-size: 9pt; line-height: 1.6; }
  .narrative { text-align: justify; line-height: 1.7; font-size: 9.5pt; margin-bottom: 14px; }

  .stats-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 16px; }
  .stats-table th, .stats-table td { border: 1px solid #999; padding: 5px 8px; text-align: center; }
  .stats-table th { background: #e8f5e9; color: #1b5e20; font-weight: bold; }

  .members-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 20px; }
  .members-table th, .members-table td { border: 1px solid #999; padding: 6px 8px; text-align: center; }
  .members-table th { background: #e8f5e9; color: #1b5e20; font-weight: bold; }

  .deliberation-clauses { margin-bottom: 16px; line-height: 1.7; font-size: 9.5pt; }
  .deliberation-clauses ol { padding-right: 20px; }

  .signatures { display: flex; justify-content: space-between; margin-top: 20px; }
  .sig-box { width: 45%; border: 1px solid #aaa; padding: 10px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1b5e20; font-size: 9.5pt; margin-bottom: 6px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1b5e20; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $institution['wilaya'] }}</div>
  <div class="meta-row">
    <div>الدائرة: <strong>{{ $institution['daira'] }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution['name'] }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $campaign['academic_year'] }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>محضر مداولات لجنة التضامن المدرسي بالمؤسسة</h1>
  <div class="sub">لدراسة والبت في ملفات الاستفادة من منحة التمدرس الخاصة (5000 دج) والمساعدات العينية</div>
</div>

<div class="legal-preamble">
  <strong>المراجع القانونية والتنظيمية:</strong><br>
  — القانون رقم 08-04 المؤرخ في 23 جانفي 2008 المتضمن القانون التوجيهي للتربية الوطنية.<br>
  — المرسوم التنفيذي رقم 21-61 المؤرخ في 26 جمادى الثانية 1442 الموافق 08 فبراير 2021 المحدد لشروط وكيفيات منح منحة التمدرس الخاصة.<br>
  — المنشور الوزاري المشترك المنظم لعمليات التضامن المدرسي ومنحة التمدرس الخاصة لفائدة التلاميذ المعوزين.
</div>

<div class="narrative">
  في يوم <strong>{{ $deliberation_date }}</strong>، وعلى الساعة العاشرة صباحاً، اجتمعت بمقر المؤسسة لجنة التضامن المدرسي تحت رئاسة السيد مدير المؤسسة وبحضور السادة الأعضاء المبينة أسماؤهم وصفاتهم في الجدول أدناه، وذلك بعد اكتمال النصاب القانوني؛ بهدف دراسة وتدقيق كافة الملفات والوثائق الثبوتية الاجتماعية المودعة من قبل أولياء التلاميذ، للبت في قوائم المستفيدين من منحة التمدرس الخاصة (5000 دج) للسنة الدراسية <strong>{{ $campaign['academic_year'] }}</strong>.
</div>

<table class="stats-table">
  <thead>
    <tr>
      <th>إجمالي الملفات المودعة</th>
      <th>الملفات المقبولة والمستوفية</th>
      <th>الملفات المرفوضة</th>
      <th>الملفات قيد التدقيق</th>
      <th>المبلغ الإجمالي المالي المترتب</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="font-weight: bold; font-size: 11pt;">{{ $statistics['total_applicants'] }}</td>
      <td style="font-weight: bold; font-size: 11pt; color: #1b5e20;">{{ $statistics['approved_count'] }}</td>
      <td style="font-weight: bold; font-size: 11pt; color: #c62828;">{{ $statistics['rejected_count'] }}</td>
      <td style="font-weight: bold; font-size: 11pt; color: #f57f17;">{{ $statistics['pending_count'] }}</td>
      <td style="font-weight: bold; font-size: 11pt; color: #1b5e20;">{{ number_format($statistics['total_financial_volume'], 2) }} دج</td>
    </tr>
  </tbody>
</table>

<div style="font-weight: bold; color: #1b5e20; margin-bottom: 6px;">توزيع المستفيدين المقبولين حسب فئات الاستحقاق الاجتماعي (المرسوم التنفيذي 21-61):</div>
<table class="stats-table" style="margin-bottom: 14px;">
  <thead>
    <tr>
      <th>اليتامى (فاقدو الأب أو الوالدين)</th>
      <th>أرباب الأسر المعوزة (بدون أي دخل)</th>
      <th>أرباب الأسر ذات الدخل الضعيف (≤ SNMG)</th>
      <th>ذوو الاحتياجات الخاصة والإعاقة</th>
      <th>ضحايا المأساة الوطنية وحالات خاصة</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>{{ $statistics['categories']['orphans'] }}</strong></td>
      <td><strong>{{ $statistics['categories']['needy'] }}</strong></td>
      <td><strong>{{ $statistics['categories']['low_income'] }}</strong></td>
      <td><strong>{{ $statistics['categories']['special_needs'] }}</strong></td>
      <td><strong>{{ $statistics['categories']['national_tragedy'] }}</strong></td>
    </tr>
  </tbody>
</table>

<div class="deliberation-clauses">
  <strong>قرارات وتوصيات اللجنة:</strong>
  <ol>
    <li>المصادقة بالإجماع على القائمة الإسمية المرفقة المتضمنة (<strong>{{ $statistics['approved_count'] }}</strong>) تلميذاً مستفيداً بعد التحقق التام من وثائق الإثبات (شهادات عدم الخضوع للضريبة، كشوف الراتب، شهادات الكفالة والإعاقة).</li>
    <li>إرسال الكشف الإسمي المعتمد مرفقاً بقرص مضغوط والملفات الورقية إلى اللجنة الدائرية المشتركة ومديرية التربية للتأشير النهائي والمصادقة على صرف المنحة عبر البريد (CCP).</li>
    <li>أوصت اللجنة بتخصيص مجانية طقم الكتب المدرسية والحقيبة المدرسية ذات الأولوية لجميع التلاميذ المقبولين في هذه القائمة.</li>
  </ol>
</div>

<div style="font-weight: bold; color: #1b5e20; margin-bottom: 6px;">أعضاء اللجنة الحاضرون والمصادقون على المداولة:</div>
<table class="members-table">
  <thead>
    <tr>
      <th style="width: 30%;">الاسم واللقب</th>
      <th style="width: 45%;">الصفة في اللجنة والمؤسسة</th>
      <th style="width: 25%;">التوقيع والملاحظات</th>
    </tr>
  </thead>
  <tbody>
    @foreach($members as $m)
      <tr>
        <td style="font-weight: bold;">{{ $m['name'] }}</td>
        <td>{{ $m['role_label'] }}</td>
        <td>{{ $m['attended'] ? 'حاضر ومصادق ✓' : 'غائب بعذر' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المقتصد / المسير المالي (مقرر اللجنة)</div>
    <div style="font-size: 8.5pt; color: #555;">الاسم، التوقيع والختم الرسمي</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة (رئيس اللجنة)</div>
    <div style="font-size: 8.5pt; color: #555;">المصادقة، التوقيع والختم الرسمي</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ {{ __('طباعة محضر المداولات') }}</button>

</body>
</html>

