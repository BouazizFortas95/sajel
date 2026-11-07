<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر الجمعية العامة لأولياء التلاميذ') }} - {{ $meeting->meeting_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #2e7d32; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }
  .republic-header .institution { font-weight: bold; color: #2e7d32; font-size: 11pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #2e7d32; border: 2px solid #2e7d32; display: inline-block; padding: 3px 20px; background: #e8f5e9; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #444; margin-top: 3px; }

  .banner { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 4px; padding: 6px 8px; margin-bottom: 8px; font-size: 8.5pt; }
  .banner div strong { color: #2e7d32; }

  .section-box { border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e8f5e9; color: #2e7d32; font-weight: bold; font-size: 8.5pt; padding: 3px 8px; border-bottom: 1px solid #c8e6c9; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; line-height: 1.5; }

  .bureau-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 4px; }
  .bureau-table th, .bureau-table td { border: 1px solid #bbb; padding: 4px 6px; text-align: center; }
  .bureau-table th { background: #e8f5e9; color: #2e7d32; font-weight: bold; }

  .narrative-box { background: #fafafa; border: 1px dashed #bbb; padding: 6px 8px; border-radius: 3px; min-height: 40px; margin-top: 3px; font-size: 8.5pt; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 6px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #2e7d32; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #2e7d32; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
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
  <h1>{{ __('محضر الجمعية العامة لجمعية أولياء التلاميذ (APE)') }}</h1>
  <div class="decree">تطبيقاً للقانون رقم 12-06 المتعلق بالجمعيات، والمنشور الوزاري المحدد لكيفيات التنسيق والشراكة بين إدارة المؤسسة وجمعية الأولياء</div>
</div>

<div class="banner">
  <div>رقم المحضر: <strong>{{ $meeting->meeting_code }}</strong></div>
  <div>نوع الجمعية: <strong>{{ $meeting_type_label }}</strong></div>
  <div>تاريخ الانعقاد: <strong>{{ $formatted_meeting_date }}</strong></div>
  <div>عدد الأولياء الحاضرين: <strong>{{ $meeting->attendees_count }} ولياً</strong></div>
</div>

<!-- 1. افتتاح الجلسة والاعتماد -->
<div class="section-box">
  <div class="section-title">1. افتتاح الجلسة وبيانات الاعتماد (Ouverture et agrément)</div>
  <div class="section-body">
    <div style="display: flex; justify-content: space-between;">
      <div>اسم الجمعية: <strong>{{ $association?->association_name ?? 'جمعية أولياء تلاميذ المؤسسة' }}</strong></div>
      <div>رقم وصل الإيداع / الاعتماد: <strong>{{ $association?->registration_number ?? 'وصــل رقم ...' }}</strong></div>
      <div>تاريخ الاعتماد الولائي: <strong>{{ $association?->accreditation_date?->format('Y-m-d') ?? '—' }}</strong></div>
    </div>
  </div>
</div>

<!-- 2. تشكيلة المكتب التنفيذي للجمعية -->
<div class="section-box">
  <div class="section-title">2. تشكيلة المكتب التنفيذي للجمعية (Bureau exécutif élu)</div>
  <div class="section-body">
    <table class="bureau-table">
      <thead>
        <tr>
          <th>المهمة في المكتب</th>
          <th>الاسم واللقب الكامل</th>
          <th>رقم هاتف الاتصال</th>
          <th>الصفة المهنية</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong style="color: #2e7d32;">رئيس الجمعية</strong></td>
          <td><strong>{{ $association?->president_name ?? '—' }}</strong></td>
          <td>{{ $association?->president_phone ?? '—' }}</td>
          <td>ولي تلميذ متمدرس</td>
        </tr>
        <tr>
          <td>نائب الرئيس</td>
          <td>{{ $association?->vice_president_name ?? '—' }}</td>
          <td>—</td>
          <td>ولي تلميذ متمدرس</td>
        </tr>
        <tr>
          <td>الكاتب العام</td>
          <td>{{ $association?->secretary_general_name ?? '—' }}</td>
          <td>—</td>
          <td>ولي تلميذ متمدرس</td>
        </tr>
        <tr>
          <td>أمين المال</td>
          <td>{{ $association?->treasurer_name ?? '—' }}</td>
          <td>—</td>
          <td>ولي تلميذ متمدرس</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- 3. جدول أعمال الجمعية العامة -->
<div class="section-box">
  <div class="section-title">3. جدول أعمال الجمعية العامة (Ordre du jour)</div>
  <div class="section-body">
    <div class="narrative-box" style="min-height: 25px;">{{ $meeting->agenda_topics }}</div>
  </div>
</div>

<!-- 4. المداولات والتقريرين الأدبي والمالي -->
<div class="section-box">
  <div class="section-title">4. وقائع المناقشة وعرض التقريرين الأدبي والمالي (Débats et bilans)</div>
  <div class="section-body">
    <div class="narrative-box">{{ $meeting->deliberation_narrative ?? 'تم عرض التقرير الأدبي لأنشطة الجمعية والتقرير المالي للمداخيل والمصاريف والمصادقة عليهما بالأغلبية المطلقة للأولياء الحاضرين.' }}</div>
  </div>
</div>

<!-- 5. التوصيات وبرنامج التنسيق مع إدارة المؤسسة -->
<div class="section-box">
  <div class="section-title">5. التوصيات وقرارات الشراكة والتعاون (Recommandations et partenariat)</div>
  <div class="section-body">
    <div class="narrative-box">{{ $meeting->recommendations_and_actions ?? 'المساهمة في حملات التضامن المدرسي، مرافقة التلاميذ في الامتحانات الرسمية، التحسيس بمخاطر العنف والتسرب، والمشاركة الفعالة في مجلس التربية والتسيير.' }}</div>
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">كاتب الجمعية العامة</div>
    <div>الاسم والتوقيع: .......................................</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">رئيس جمعية أولياء التلاميذ</div>
    <div>الاسم والختم: .......................................</div>
    <div style="margin-top: 25px;">الختم والتوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (تأشيرة الإحاطة)</div>
    <div>في: ............................... بتاريخ: .....................</div>
    <div style="margin-top: 25px;">الختم الرسمي وتوقيع المدير:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة محضر الجمعية الرسمية</button>

</body>
</html>
