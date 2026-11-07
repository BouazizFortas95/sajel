<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('سند الطلب') }} - {{ $commitment->order_reference }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 11pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 portrait; margin: 15mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 6px; margin-bottom: 12px; }
  .republic-header .republic { font-size: 10.5pt; color: #1a237e; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-top: 4px; }
  .republic-header .institution { font-weight: bold; color: #1a237e; font-size: 11pt; }

  .doc-title { text-align: center; margin: 12px 0 16px; }
  .doc-title h1 { font-size: 16pt; font-weight: bold; color: #1a237e; border: 2px solid #1a237e; display: inline-block; padding: 6px 28px; background: #f0f2f9; }
  .doc-subtitle { font-size: 9pt; color: #555; margin-top: 4px; }

  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
  .card-box { border: 1px solid #bbb; border-radius: 4px; padding: 10px; background: #fafafa; }
  .card-title { font-weight: bold; color: #1a237e; font-size: 10.5pt; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 6px; }
  .card-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-bottom: 4px; }
  .card-row .lbl { color: #555; font-weight: bold; }

  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt; }
  .info-table td { padding: 6px 10px; border: 1px solid #bbb; }
  .info-table .lbl { background: #e8eaf6; font-weight: bold; width: 28%; color: #1a237e; }

  .amount-highlight { margin: 14px 0; padding: 10px 14px; background: #eef2ff; border: 1.5px solid #1a237e; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
  .amount-highlight .amount-val { font-size: 14pt; font-weight: bold; color: #1a237e; }

  .legal-note { font-size: 8.5pt; color: #666; text-align: justify; line-height: 1.5; margin-bottom: 16px; padding: 6px 10px; background: #f9f9f9; border-right: 3px solid #1a237e; }

  .signatures { display: flex; justify-content: space-between; gap: 16px; margin-top: 24px; }
  .sig-box { flex: 1; border: 1px solid #bbb; padding: 10px; text-align: center; min-height: 100px; background: #fafafa; border-radius: 4px; }
  .sig-box .title { font-weight: bold; color: #1a237e; font-size: 10pt; margin-bottom: 6px; }

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
    <div>السنة المالية: <strong>{{ $commitment->budgetLine?->fiscal_year ?? date('Y') }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>سند طلب بضائع وخدمات / BON DE COMMANDE</h1>
  <div class="doc-subtitle">طبقا لأحكام القانون 90-21 المتعلق بالمحاسبة العمومية والمرسوم التنفيذي 91-311</div>
</div>

<div class="info-grid">
  <div class="card-box">
    <div class="card-title">بيانات ومراجع السند والالتزام</div>
    <div class="card-row">
      <span class="lbl">رقم سند الطلب:</span>
      <span><strong>{{ $commitment->order_reference }}</strong></span>
    </div>
    <div class="card-row">
      <span class="lbl">تاريخ الإصدار:</span>
      <span><strong>{{ $commitment->order_date?->format('Y-m-d') }}</strong></span>
    </div>
    <div class="card-row">
      <span class="lbl">رقم الالتزام المحاسبي:</span>
      <span><strong>{{ $commitment->commitment_number }}</strong></span>
    </div>
    <div class="card-row">
      <span class="lbl">حالة الالتزام:</span>
      <span><strong>{{ $commitment->status?->label() }}</strong></span>
    </div>
  </div>

  <div class="card-box">
    <div class="card-title">بيانات المورد / المتعامل الاقتصادي</div>
    <div class="card-row">
      <span class="lbl">اسم المتعامل:</span>
      <span><strong>{{ $commitment->supplier_name }}</strong></span>
    </div>
    <div class="card-row">
      <span class="lbl">رقم التعريف الجبائي (NIF):</span>
      <span><strong>{{ $commitment->supplier_tax_number ?? 'غير مسجل' }}</strong></span>
    </div>
    <div class="card-row">
      <span class="lbl">السنة الدراسية:</span>
      <span><strong>{{ $commitment->budgetLine?->academic_year ?? '2026/2027' }}</strong></span>
    </div>
  </div>
</div>

<table class="info-table">
  <tr>
    <td class="lbl">الباب المحاسبي:</td>
    <td><strong>{{ $commitment->budgetLine?->chapter ?? '---' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">المادة المحاسبية:</td>
    <td><strong>{{ $commitment->budgetLine?->article ?? '---' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">موضوع الطلبية بالتفصيل:</td>
    <td><strong>{{ $commitment->subject }}</strong></td>
  </tr>
  @if($commitment->notes)
  <tr>
    <td class="lbl">شروط وملاحظات التسليم:</td>
    <td>{{ $commitment->notes }}</td>
  </tr>
  @endif
</table>

<div class="amount-highlight">
  <div>
    <div style="font-size: 10pt; color: #444;">المبلغ الإجمالي التقديري للالتزام:</div>
    <div style="font-size: 8.5pt; color: #666;">Montant total estimé de la commande</div>
  </div>
  <div class="amount-val">{{ number_format($commitment->estimated_amount, 2) }} دج</div>
</div>

<div class="legal-note">
  <strong>شروط قانونية:</strong> يخضع هذا السند لأحكام قانون الصفقات العمومية والمحاسبة العمومية. يلتزم المورد بتسليم المواد أو إنجاز الخدمات طبقا للمواصفات المتفق عليها مرفقة بالفاتورة الأصلية ووصل التسليم المؤرخ والممضي في الآجال المحددة نظاميا.
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">المسير المالي / المقتصد</div>
    <div style="font-size: 8.5pt; color: #555;">(تأشيرة توفر الاعتماد المالي والالتزام)</div>
    <div style="margin-top: 30px; font-weight: bold;">بتاريخ: ........................</div>
  </div>
  <div class="sig-box">
    <div class="title">مدير المؤسسة التعليمية (الآمر بالصرف)</div>
    <div style="font-size: 8.5pt; color: #555;">(الأمر بالطلب والارتباط)</div>
    <div style="margin-top: 30px; font-weight: bold;">بتاريخ: ........................</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة السند') }}</button>

</body>
</html>
