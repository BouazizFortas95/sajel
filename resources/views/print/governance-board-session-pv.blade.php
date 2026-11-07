<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('محضر مداولات مجلس التربية والتسيير') }} - {{ $session->session_code }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 portrait; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #0d47a1; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #0d47a1; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }
  .republic-header .institution { font-weight: bold; color: #0d47a1; font-size: 11pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 13pt; font-weight: bold; color: #0d47a1; border: 2px solid #0d47a1; display: inline-block; padding: 3px 20px; background: #e3f2fd; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #444; margin-top: 3px; }

  .session-banner { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; background: #f5f9ff; border: 1px solid #bbdefb; border-radius: 4px; padding: 6px 8px; margin-bottom: 8px; font-size: 8.5pt; }
  .session-banner div strong { color: #0d47a1; }

  .section-box { border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
  .section-title { background: #e3f2fd; color: #0d47a1; font-weight: bold; font-size: 8.5pt; padding: 3px 8px; border-bottom: 1px solid #bbdefb; }
  .section-body { padding: 6px 8px; font-size: 8.5pt; line-height: 1.5; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 6px; }
  .data-table th, .data-table td { border: 1px solid #aaa; padding: 3px 5px; text-align: center; }
  .data-table th { background: #e3f2fd; color: #0d47a1; font-weight: bold; }
  .data-table tr:nth-child(even) { background: #fafafa; }

  .narrative-box { background: #fafafa; border: 1px dashed #bbb; padding: 6px 8px; border-radius: 3px; min-height: 40px; margin-top: 3px; font-size: 8.5pt; }

  .signatures { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #aaa; padding: 6px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #0d47a1; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #0d47a1; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
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
  <h1>{{ __('محضر مداولات مجلس التربية والتسيير (CEG)') }}</h1>
  <div class="decree">تطبيقاً لأحكام القانون 08-04 المتضمن القانون التوجيهي للتربية الوطنية، والمرسوم التنفيذي رقم 90-12 المحدد لتنظيم وعمل مجلس التربية والتسيير</div>
</div>

<div class="session-banner">
  <div>رقم الدورة: <strong>{{ $session->session_code }}</strong></div>
  <div>طبيعة الدورة: <strong>{{ $session_term_label }}</strong></div>
  <div>تاريخ وتوقيت الانعقاد: <strong>{{ $formatted_session_date }} ({{ $session->session_time }})</strong></div>
  <div>مكان الاجتماع: <strong>{{ $session->meeting_place }}</strong></div>
</div>

<!-- 1. افتتاح الجلسة والتحقق من النصاب القانوني -->
<div class="section-box">
  <div class="section-title">1. افتتاح الجلسة والتحقق من النصاب القانوني (Ouverture et vérification du quorum)</div>
  <div class="section-body">
    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
      <div>رئيس المجلس: <strong>{{ $session->president_name }}</strong></div>
      <div>مقرر الجلسة: <strong>{{ $session->rapporteur_name }}</strong></div>
      <div>النصاب المطلوب: <strong>{{ $session->quorum_required }} أعضاء</strong> | الحاضرون: <strong>{{ $session->members_present_count }}</strong></div>
      <div>حالة النصاب: <strong style="color: #2e7d32;">{{ $session->is_quorum_reached ? 'مكتمل وقانوني' : 'غير مكتمل' }}</strong></div>
    </div>
  </div>
</div>

<!-- 2. قائمة الأعضاء الحاضرين والتوقيعات -->
<div class="section-box">
  <div class="section-title">2. تشكيلة الأعضاء الحاضرين وتوقيعاتهم (Membres présents et émargement)</div>
  <div class="section-body">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 5%;">#</th>
          <th style="width: 25%;">الاسم واللقب الكامل</th>
          <th style="width: 25%;">الصفة في المجلس</th>
          <th style="width: 25%;">الصفة الوظيفية بالمؤسسة</th>
          <th style="width: 20%;">التوقيع والتأشيرة</th>
        </tr>
      </thead>
      <tbody>
        @forelse($members as $idx => $m)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $m->member_name }}</strong></td>
          <td>{{ $m->member_category?->label() ?? $m->member_category }}</td>
          <td>{{ $m->institution_role }}</td>
          <td style="color: #666; font-style: italic;">حاضر ومصادق</td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="padding: 6px; color: #666;">تم تسجيل حضور الأعضاء القانونيين للمجلس.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- 3. جدول الأعمال -->
<div class="section-box">
  <div class="section-title">3. جدول أعمال الدورة (Ordre du jour)</div>
  <div class="section-body">
    <div class="narrative-box" style="min-height: 25px;">{{ $session->agenda_items }}</div>
  </div>
</div>

<!-- 4. وقائع المناقشات والمداولات -->
<div class="section-box">
  <div class="section-title">4. وقائع المداولات والمناقشات التفصيلية (Délibérations)</div>
  <div class="section-body">
    <div class="narrative-box">{{ $session->deliberation_details ?? 'تم استعراض النقاط المدرجة في جدول الأعمال وفتح باب المناقشة المستفيضة لكافة الأعضاء الحاضرين حول تحسين تمدرس التلاميذ وتطبيق النظام الداخلي ومشروع المؤسسة.' }}</div>
  </div>
</div>

<!-- 5. القرارات والتوصيات المصادق عليها -->
<div class="section-box">
  <div class="section-title">5. القرارات والتوصيات المتخذة بإجماع الأعضاء (Décisions et recommandations)</div>
  <div class="section-body">
    <div class="narrative-box">{{ $session->decisions_summary ?? 'صادق المجلس بالإجماع على مشروع المؤسسة والنظام الداخلي، مع التأكيد على تكثيف المتابعة البيداغوجية ودعم التنسيق مع جمعية أولياء التلاميذ.' }}</div>
  </div>
</div>

<!-- التوقيعات الرسمية -->
<div class="signatures">
  <div class="sig-box">
    <div class="title">مقرر الجلسة (الناظر / مستشار التربية)</div>
    <div>الاسم والصفة: .......................................</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">ممثل أولياء التلاميذ / ممثل الأساتذة</div>
    <div>التأشيرة بالنيابة عن الأعضاء المنتخبين</div>
    <div style="margin-top: 25px;">التوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (رئيس المجلس)</div>
    <div>في: ................................... بتاريخ: .........................</div>
    <div style="margin-top: 25px;">الختم الرسمي وتوقيع المدير:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة محضر المداولة الرسمي</button>

</body>
</html>
