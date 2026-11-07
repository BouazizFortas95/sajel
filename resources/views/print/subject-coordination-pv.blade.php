<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر جلسة التنسيق البيداغوجي') }} - {{ $subject->name }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 12pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 18mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 11pt; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 10pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 12pt; }

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 17pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 6px 24px; background: #f0f2f9; }
  .doc-subtitle { font-size: 10.5pt; color: #444; margin-top: 4px; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11pt; }
  .info-table td { padding: 6px 10px; border: 1px solid #bbb; }
  .info-table .lbl { background: #e8eaf6; font-weight: bold; width: 28%; color: #1a237e; }

  .section-block { margin-bottom: 12px; border: 1px solid #c5cae9; border-radius: 4px; overflow: hidden; }
  .section-header { background: #e8eaf6; color: #1a237e; font-weight: bold; font-size: 11.5pt; padding: 6px 12px; border-bottom: 1px solid #c5cae9; }
  .section-body { padding: 10px 12px; font-size: 11pt; line-height: 1.8; text-align: justify; background: #fff; }

  .attendees-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
  .attendee-tag { background: #f0f2f9; border: 1px solid #c5cae9; padding: 3px 10px; border-radius: 12px; font-size: 10pt; }

  .signatures { display: flex; justify-content: space-between; gap: 16px; margin-top: 24px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 8px; text-align: center; min-height: 80px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 10.5pt; margin-bottom: 4px; }
  .sig-box .sub { font-size: 9.5pt; color: #666; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #1a237e; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 10pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية</div>
  <div class="meta-row">
    <div>مديرية التربية لولاية: <strong>{{ $school?->state ?? '........................' }}</strong></div>
    <div class="institution">{{ $school?->name ?? 'المؤسسة التعليمية' }}</div>
    <div>السنة الدراسية: <strong>{{ $protocol->academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>محضر جلسة التنسيق البيداغوجي للمادة (القرار الوزاري 157)</h1>
  <div class="doc-subtitle">مادة: <strong>{{ $subject->name }}</strong> — الفصل: <strong>الفصل {{ $protocol->trimester }}</strong> — تاريخ الانعقاد: <strong>{{ $protocol->meeting_date->format('Y/m/d') }}</strong></div>
</div>

<table class="info-table">
  <tr>
    <td class="lbl">الأستاذ منسق المادة (المسؤول):</td>
    <td><strong>{{ $coordinator?->name ?? '................................................' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">موضوع جلسة التنسيق:</td>
    <td><strong>{{ $protocol->topic }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">الأساتذة الحاضرون بالجلسة:</td>
    <td>
      <div class="attendees-list">
        @forelse($attendeeUsers as $user)
          <span class="attendee-tag">{{ $user->name }}</span>
        @empty
          <span>جميع أساتذة المادة بالمؤسسة</span>
        @endforelse
      </div>
    </td>
  </tr>
</table>

<div class="section-block">
  <div class="section-header">1. تقييم تدرج البرامج والمنهاج الدراسي</div>
  <div class="section-body">
    {{ $protocol->syllabus_pacing_notes ?: 'تمت دراسة مدى تقدم كل أستاذ في إنجاز التدرج السنوي المعتمد، والتأكد من مطابقة الحصص المنجزة للمخطط الوزاري وتحقيق الكفاءات المستهدفة.' }}
  </div>
</div>

<div class="section-block">
  <div class="section-header">2. توحيد الفروض والتقويمات البيداغوجية</div>
  <div class="section-body">
    {{ $protocol->unified_exams_notes ?: 'تم الاتفاق على معايير وبناء الفروض المحروسة الموحدة وفق سلم تنقيط دقيق يراعي المستويات المعرفية والوضعيات الإدماجية المقررة.' }}
  </div>
</div>

<div class="section-block">
  <div class="section-header">3. تشخيص الصعوبات التعلمية وخطة المعالجة</div>
  <div class="section-body">
    {{ $protocol->difficulties_and_remediations ?: 'تشخيص النقائص الملاحظة لدى المتعلمين في بعض المفاهيم الأساسية، وضبط رزنامة حصص الدعم والمعالجة البيداغوجية الموجهة للفئات المتعثرة.' }}
  </div>
</div>

<div class="section-block">
  <div class="section-header">4. التوجيهات والتوصيات المرفوعة لإدارة المؤسسة</div>
  <div class="section-body">
    {{ $protocol->recommendations ?: 'الرفع من وتيرة استغلال الوسائل التعليمية والمخابر، والمطالبة بتوفير بعض المراجع والوسائل المساعدة لتعزيز التحصيل الدراسي.' }}
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">الأساتذة الحاضرون</div>
    <div class="sub">التأشيرة والتوقيعات</div>
  </div>
  <div class="sig-box">
    <div class="title">الأستاذ(ة) منسق(ة) المادة</div>
    <div class="sub">{{ $coordinator?->name ?? 'التوقيع' }}</div>
  </div>
  <div class="sig-box">
    <div class="title">ناظر(ة) الدروس / مدير(ة) المؤسسة</div>
    <div class="sub">تأشيرة واطلاع الإدارة</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة المحضر (Ctrl+P)</button>

</body>
</html>
