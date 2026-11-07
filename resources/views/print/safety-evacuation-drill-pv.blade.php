<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر تمرين الإخلاء الافتراضي للوقاية من الكوارث') }} - {{ $drill->drill_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #c62828; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #c62828; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }
  .republic-header .institution { font-weight: bold; color: #c62828; font-size: 11pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #c62828; border: 2px solid #c62828; display: inline-block; padding: 3px 25px; background: #ffebee; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .summary-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; margin-bottom: 8px; }
  .card { border: 1px solid #ffcdd2; background: #fff8f8; border-radius: 4px; padding: 4px 6px; text-align: center; }
  .card .label { font-size: 7.5pt; color: #666; margin-bottom: 2px; }
  .card .val { font-size: 11pt; font-weight: bold; color: #c62828; }

  .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

  .section-box { border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
  .section-title { background: #f5f5f5; color: #c62828; font-weight: bold; font-size: 8.5pt; padding: 3px 8px; border-bottom: 1px solid #ddd; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; line-height: 1.5; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 8px; }
  .data-table th, .data-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .data-table th { background: #ffebee; color: #c62828; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .narrative-box { background: #fafafa; border: 1px dashed #ccc; padding: 5px 8px; border-radius: 3px; min-height: 42px; font-size: 8.5pt; }

  .signatures { display: flex; justify-content: space-between; gap: 15px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #c62828; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #c62828; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $wilaya }} | المديرية العامة للحماية المدنية</div>
  <div class="meta-row">
    <div>الدائرة: <strong>{{ $daira }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('محضر تمرين الإخلاء الافتراضي المشترك للوقاية من الكوارث والأخطار الكبرى') }}</h1>
  <div class="decree">تنفيذاً لمخطط الأمن الداخلي (PSI) وطبقاً للقانون 04-20 المتعلق بالوقاية من الأخطار الكبرى والتعليمات المشتركة بين وزارة التربية والحماية المدنية</div>
</div>

<div class="summary-cards">
  <div class="card">
    <div class="label">رقم المحضر</div>
    <div class="val">{{ $drill->drill_code }}</div>
  </div>
  <div class="card">
    <div class="label">سيناريو المحاكاة</div>
    <div class="val" style="font-size: 9.5pt;">{{ $scenario_label }}</div>
  </div>
  <div class="card">
    <div class="label">تاريخ وتوقيت التمرين</div>
    <div class="val" style="font-size: 9pt;">{{ $formatted_date }} ({{ $drill->drill_time }})</div>
  </div>
  <div class="card">
    <div class="label">مدة الإخلاء الكامل بالكرونومتر</div>
    <div class="val" style="color: #2e7d32;">{{ $formatted_duration }}</div>
  </div>
  <div class="card">
    <div class="label">إجمالي الأشخاص الذين تم إخلاؤهم</div>
    <div class="val">{{ $drill->total_persons_evacuated }} فرد</div>
  </div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th>التلاميذ الذين تم إخلاؤهم</th>
      <th>المؤطرون والأساتذة والعمال</th>
      <th>منطقة ونقطة التجمع المعتمدة</th>
      <th>الشريك التقني والميداني</th>
      <th>التقييم العام للتمرين</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong style="color: #c62828; font-size: 11pt;">{{ $drill->students_evacuated_count }}</strong> تلميذ</td>
      <td><strong>{{ $drill->staff_evacuated_count }}</strong> مؤطر وعامل</td>
      <td><strong>{{ $drill->assembly_zone_used }}</strong></td>
      <td>{{ $drill->civil_protection_partner ?? 'الوحدة الرئيسية للحماية المدنية' }}</td>
      <td>
        <span style="font-weight: bold; color: #2e7d32;">{{ match($drill->evaluation_rating) { 'excellent' => 'ممتاز جداً (Très satisfaisant)', 'good' => 'ناجح وجيد (Satisfaisant)', 'average' => 'متوسط (Moyen)', default => 'بحاجة لتحسين وتكرار' } }}</span>
      </td>
    </tr>
  </tbody>
</table>

<div class="two-col-grid">
  <!-- الوفد والملاحظات -->
  <div class="section-box">
    <div class="section-title">وفد الحماية المدنية والملاحظات الميدانية المسجلة (Points faibles et goulots d'étranglement)</div>
    <div class="section-body">
      <div><strong>ممثلو وضباط الحماية المدنية الحاضرون:</strong> {{ $drill->civil_protection_representatives ?? 'ملازم أول ومفتش الوقاية بوحدة الحماية المدنية' }}</div>
      <div style="margin-top: 4px;"><strong>النقائص وصعوبات السيولة المسجلة أثناء التدخل:</strong></div>
      <div class="narrative-box">{{ $drill->bottlenecks_and_remarks ?? 'تمت العملية بانتظام مع تسجيل بعض الازدحام عند مخرج الدرج الأيمن للجناح (ب).' }}</div>
    </div>
  </div>

  <!-- التدابير التصحيحية -->
  <div class="section-box">
    <div class="section-title">التدابير التصحيحية وتوصيات اللجنة (Mesures correctives et recommandations)</div>
    <div class="section-body">
      <div><strong>التدابير المقررة في إطار مراجعة مخطط الأمن الداخلي:</strong></div>
      <div class="narrative-box">{{ $drill->corrective_measures ?? 'إعادة توجيه حركة المرور للأفواج، صيانة مكبر الصوت الخارجي للإنذار، وبرمجة تمرين إخلاء ثانٍ خلال الفصل الثاني.' }}</div>
    </div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">منسق الإخلاء / مستشار التربية</div>
    <div>الاسم والصفة: ...........................................</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">ممثل مصالح الحماية المدنية (ضابط الوقاية)</div>
    <div>الرتبة والاسم: ...........................................</div>
    <div style="margin-top: 25px;">التأشيرة والختم:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (رئيس خلية الأزمة)</div>
    <div>في: ................................... بتاريخ: .........................</div>
    <div style="margin-top: 25px;">الختم الرسمي والتوقيع:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة محضر التمرين الرسمي</button>

</body>
</html>

