<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سجل المراقبة الدورية لأجهزة الإطفاء ومعدات السلامة والغاز') }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 8mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #b71c1c; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #b71c1c; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 3px; }
  .republic-header .institution { font-weight: bold; color: #b71c1c; font-size: 11pt; }

  .doc-title { text-align: center; margin: 6px 0 10px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #b71c1c; border: 2px solid #b71c1c; display: inline-block; padding: 3px 25px; background: #ffebee; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 10px; }
  .data-table th, .data-table td { border: 1px solid #aaa; padding: 4px 6px; text-align: center; }
  .data-table th { background: #ffebee; color: #b71c1c; font-weight: bold; font-size: 8.5pt; }
  .data-table tr:nth-child(even) { background: #fafafa; }
  .data-table tfoot td { font-weight: bold; background: #fff3e0; color: #e65100; }

  .status-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 7.5pt; }
  .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
  .status-due { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }
  .status-bad { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

  .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 15px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 6px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8.5pt; }
  .sig-box .title { font-weight: bold; color: #b71c1c; font-size: 9pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #b71c1c; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 9.5pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية لولاية {{ $wilaya }}</div>
  <div class="meta-row">
    <div>تاريخ وميقات التحيين: <strong>{{ $generated_at }}</strong></div>
    <div class="institution">المؤسسة: <strong>{{ $institution_name }}</strong></div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('سجل المراقبة الدورية لأجهزة الإطفاء ومعدات السلامة وشبكة الغاز') }}</h1>
  <div class="decree">تنفيذاً للقانون 88-07 والمخطط الداخلي للأمن للوقاية من الحرائق وحوادث الاختناق بالغاز</div>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 4%;">#</th>
      <th style="width: 12%;">رمز وتعيين الجهاز</th>
      <th style="width: 18%;">طبيعة ومواصفات العتاد</th>
      <th style="width: 22%;">الموقع الدقيق بالهيكل والمبنى</th>
      <th style="width: 11%;">تاريخ آخر فحص معتمد</th>
      <th style="width: 11%;">تاريخ الفحص القادم الملزم</th>
      <th style="width: 12%;">الحالة التقنية</th>
      <th style="width: 10%;">الهيئة الفاحصة</th>
    </tr>
  </thead>
  <tbody>
    @forelse($equipments as $idx => $item)
    <tr>
      <td>{{ $idx + 1 }}</td>
      <td><strong>{{ $item->equipment_code }}</strong></td>
      <td style="text-align: right;">{{ $item->equipment_type?->label() ?? $item->equipment_type }}</td>
      <td style="text-align: right;">{{ $item->exact_location }}</td>
      <td>{{ $item->last_inspection_date?->format('Y-m-d') ?? '—' }}</td>
      <td>
        <strong>{{ $item->next_inspection_due_date?->format('Y-m-d') ?? '—' }}</strong>
      </td>
      <td>
        @if($item->isExpiredOrDue())
          <span class="status-badge status-bad">منتهي / يستوجب الفحص</span>
        @elseif($item->isOperational())
          <span class="status-badge status-ok">صالح ومطابق</span>
        @else
          <span class="status-badge status-due">{{ $item->inspection_status?->label() ?? 'بحاجة لصيانة' }}</span>
        @endif
      </td>
      <td>{{ $item->inspector_entity ?? 'المؤسسة المعتمدة' }}</td>
    </tr>
    @empty
    <tr>
      <td colspan="8" style="padding: 15px; color: #666;">لا توجد أجهزة مسجلة حالياً في السجل.</td>
    </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" style="text-align: right;">إجمالي أجهزة ومعدات السلامة والإطفاء المقيدة بالسجل:</td>
      <td colspan="4">{{ $total_count }} جهاز سلامة وإطفاء</td>
    </tr>
  </tfoot>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المسؤول التقني / المقتصد (المسير المالي)</div>
    <div>المشرف على متابعة شبكات السلامة والغاز والتدفئة</div>
    <div style="margin-top: 25px;">التأشيرة والتوقيع:</div>
  </div>
  <div class="sig-box">
    <div class="title">الهيئة المعتمدة لصيانة وتعبئة أجهزة الإطفاء</div>
    <div>تأشيرة المؤسسة أو الخبير المعتمد</div>
    <div style="margin-top: 25px;">الختم والتاريخ:</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (رئيس لجنة الوقاية والأمن)</div>
    <div>في: ....................................... بتاريخ: ..............................</div>
    <div style="margin-top: 25px;">الختم الرسمي وتوقيع المدير:</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">طباعة السجل الرسمي</button>

</body>
</html>
