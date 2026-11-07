<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>سجل البريد الصادر والوارد</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 10.5pt; color: #111; background: #fff; direction: rtl; }
  @page { size: A4 landscape; margin: 14mm 12mm 12mm 12mm; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px double #004d40; padding-bottom: 8px; margin-bottom: 10px; }
  .republic-header .center { text-align: center; }
  .republic-header .republic { font-size: 11pt; letter-spacing: 1px; color: #004d40; }
  .republic-header .ministry { font-size: 10pt; color: #333; }
  .republic-header .institution { font-size: 12pt; font-weight: bold; color: #004d40; margin-top: 4px; }

  .doc-title { text-align: center; margin: 10px 0; }
  .doc-title h1 { font-size: 17pt; font-weight: bold; color: #004d40; border: 2px solid #004d40; display: inline-block; padding: 5px 20px; }
  .doc-info { text-align: center; font-size: 10pt; color: #555; margin-bottom: 10px; }

  .mail-type-badge { text-align: center; margin-bottom: 10px; }
  .mail-type-badge span { display: inline-block; padding: 4px 24px; border-radius: 4px; font-size: 13pt; font-weight: bold; }
  .badge-incoming { background: #e3f2fd; color: #0d47a1; border: 1.5px solid #0d47a1; }
  .badge-outgoing { background: #fff3e0; color: #e65100; border: 1.5px solid #e65100; }

  .mail-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
  .mail-table thead tr { background: #004d40; color: #fff; }
  .mail-table thead th { padding: 7px 5px; border: 1px solid #004d40; text-align: center; font-weight: bold; font-size: 10pt; }
  .mail-table tbody tr:nth-child(even) { background: #e8f5e9; }
  .mail-table tbody tr:nth-child(odd) { background: #fff; }
  .mail-table tbody td { padding: 6px 5px; border: 1px solid #bbb; text-align: center; vertical-align: middle; }
  .mail-table tbody td.subject-col { text-align: right; padding-right: 8px; }

  .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 8.5pt; font-weight: bold; }
  .badge-pending { background: #fff9c4; color: #f57f17; }
  .badge-processed { background: #e8f5e9; color: #2e7d32; }
  .badge-archived { background: #f5f5f5; color: #616161; }
  .badge-urgent { background: #fce4ec; color: #c62828; }
  .badge-confidential { background: #f3e5f5; color: #6a1b9a; }
  .badge-normal { background: #e3f2fd; color: #1565c0; }
  .badge-overdue { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }

  .total-row { background: #004d40 !important; color: #fff; font-weight: bold; }
  .total-row td { border-color: #004d40 !important; color: #fff; }

  .footer { margin-top: 14px; display: flex; justify-content: space-between; font-size: 9pt; color: #888; border-top: 1px solid #ccc; padding-top: 8px; }
  .print-btn { position: fixed; bottom: 20px; left: 20px; background: #004d40; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 11pt; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div style="text-align:right;">
    <div>الموسم الدراسي: {{ $school->academic_year ?? '' }}</div>
    <div>البلدية: {{ $school->municipality_name ?? '' }}</div>
  </div>
  <div class="center">
    <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
    <div class="ministry">وزارة التربية الوطنية — {{ $school->directorate_name ?? '' }}</div>
    <div class="institution">{{ $school->school_name ?? 'المؤسسة التربوية' }}</div>
  </div>
  <div style="text-align:left;">
    <div>السنة: {{ $year }}</div>
    <div>طُبع: {{ $printed_at }}</div>
  </div>
</div>

<div class="doc-title">
  <h1>سجل البريد الرسمي</h1>
</div>

<div class="mail-type-badge">
  <span class="{{ $mail_type->value === 'incoming' ? 'badge-incoming' : 'badge-outgoing' }}">
    {{ $mail_type->value === 'incoming' ? 'البريد الوارد — Courrier Arrivée' : 'البريد الصادر — Courrier Départ' }}
  </span>
</div>

<div class="doc-info">
  إجمالي السجلات: {{ $mails->count() }} | سنة {{ $year }}
</div>

<table class="mail-table">
  <thead>
    <tr>
      <th style="width:5%">رقم الترتيب</th>
      <th style="width:11%">رقم البريد</th>
      <th style="width:8%">التاريخ</th>
      <th style="width:13%">{{ $mail_type->value === 'incoming' ? 'المُرسِل' : 'المُرسَل إليه' }}</th>
      <th style="width:11%">رقم المرجع</th>
      <th style="width:22%">الموضوع</th>
      <th style="width:8%">السرية</th>
      <th style="width:9%">أجل الرد</th>
      <th style="width:8%">الحالة</th>
      <th style="width:5%">المُعيَّن</th>
    </tr>
  </thead>
  <tbody>
    @forelse($mails as $index => $mail)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td><strong>{{ $mail->mail_number }}</strong></td>
      <td>{{ $mail->mail_date->format('Y/m/d') }}</td>
      <td>{{ $mail_type->value === 'incoming' ? ($mail->sender_name ?? '—') : ($mail->recipient_name ?? '—') }}</td>
      <td>{{ $mail->reference_number ?? '—' }}</td>
      <td class="subject-col">{{ $mail->subject }}</td>
      <td>
        @php $conf = $mail->confidentiality; @endphp
        <span class="badge badge-{{ $conf->value }}">{{ $conf->label() }}</span>
      </td>
      <td>
        @if($mail->response_deadline)
          @if($mail->isOverdue())
            <span class="badge badge-overdue">{{ $mail->response_deadline->format('Y/m/d') }} ⚠</span>
          @else
            {{ $mail->response_deadline->format('Y/m/d') }}
          @endif
        @else
          —
        @endif
      </td>
      <td>
        @php $status = $mail->status; @endphp
        <span class="badge badge-{{ $status->value }}">{{ $status->label() }}</span>
      </td>
      <td>{{ $mail->assignedUser?->name ? mb_substr($mail->assignedUser->name, 0, 10) : '—' }}</td>
    </tr>
    @empty
    <tr>
      <td colspan="10" style="text-align:center; color:#888; padding:16px;">لا توجد سجلات لهذه الفترة</td>
    </tr>
    @endforelse
    @if($mails->count() > 0)
    <tr class="total-row">
      <td colspan="10">المجموع: {{ $mails->count() }} وثيقة |
        معالجة: {{ $mails->where('status', \App\Enums\MailStatus::Processed)->count() }} |
        قيد المعالجة: {{ $mails->where('status', \App\Enums\MailStatus::Pending)->count() }} |
        مؤرشفة: {{ $mails->where('status', \App\Enums\MailStatus::Archived)->count() }}
      </td>
    </tr>
    @endif
  </tbody>
</table>

<div class="footer">
  <div>مدير المؤسسة: ........................................ التوقيع: ....................</div>
  <div>وثيقة رسمية — للاستعمال الإداري فقط | {{ $school->school_name ?? '' }} | {{ $year }}</div>
  <div>الختم الرسمي</div>
</div>

<button class="print-btn" onclick="window.print()">🖨 طباعة</button>
</body>
</html>

