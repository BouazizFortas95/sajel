@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $teacherName = $teacher?->name ?? $observation->observed_by ?? __('الأستاذ(ة)');
    $receptionLocation = $teacher?->parent_reception_location ?: __('مكتب الاستشارة التربوية');
    $studentName = $student?->full_name ?? __('تلميذ غير محدد');
    $classroomName = $classroom?->name ?? $student?->group?->classroom?->name ?? '—';
    $levelName = $classroom?->level?->name ?? '—';
    $regNumber = $student?->registration_number ?: ($student?->id ? sprintf('%04d', $student->id) : '—');
    $guardianTitle = $guardianName ?? $student?->guardian_name ?? __('ولي أمر التلميذ(ة)');
    $guardianPhone = $student?->guardian_phone ?? '—';
    $appointment = $observation->guardian_summons_appointment_at ?? now()->addDay()->setTime(10, 0);
    $sentDate = $observation->guardian_summons_sent_at ?? $observation->date ?? now();
    $motiveText = $formattedMotive ?? app(\App\Services\StudentIncidentReportService::class)->formatSummonsMotive($observation);
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('استدعاء ولي أمر التلميذ(ة)'),
    'printSubtitle' => __('مصلحة الاستشارة التربوية — متابعة التمدرس والانضباط المدرسي'),
    'printOrientation' => 'portrait',
    'printAcademicYear' => $academicYear ?: $schoolSetting->academic_year,
    'printReference' => __('استدعاء رقم: :id', ['id' => sprintf('%04d', $observation->id)]),
    'printMeta' => $formatDate($sentDate),
    'printAutoPrint' => $autoPrint ?? true,
])

<style>
    @page {
        size: A4 portrait;
        margin: 4mm 8mm 4mm 8mm !important;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Cairo', sans-serif;
    }

    .document-sheet {
        padding: 2mm 6mm !important;
        max-width: 100% !important;
    }

    .identity-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        margin-bottom: 5px;
        font-family: 'Cairo', sans-serif;
        border: 1.5px solid #1e3a8a;
        background: #ffffff;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .identity-table th,
    .identity-table td {
        border: 1px solid #cbd5e1;
        padding: 2px 6px;
        font-size: 7.5pt;
    }

    .identity-table th {
        background: #f8fafc;
        color: #1e3a8a;
        font-weight: 700;
        text-align: right;
        width: 18%;
    }

    .identity-table td {
        color: #0f172a;
        font-weight: 600;
    }

    .appointment-box {
        border: 1.5px solid #2563eb;
        background: #eff6ff;
        border-radius: 4px;
        padding: 5px 8px;
        margin-bottom: 5px;
        font-family: 'Cairo', sans-serif;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .appointment-title {
        font-size: 8pt;
        font-weight: 800;
        color: #1e3a8a;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .appointment-details {
        font-size: 7.5pt;
        line-height: 1.4;
        color: #1e293b;
    }

    .motive-box {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        border-radius: 4px;
        padding: 5px 8px;
        margin-bottom: 5px;
        font-family: 'Amiri', serif;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .motive-title {
        font-size: 8pt;
        font-weight: 700;
        color: #1e3a8a;
        font-family: 'Cairo', sans-serif;
        margin-bottom: 2px;
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 2px;
    }

    .motive-content {
        font-size: 8pt;
        line-height: 1.35;
        color: #0f172a;
        white-space: pre-line;
    }

    .instructions-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 4px;
        padding: 4px 8px;
        margin-bottom: 5px;
        font-size: 7pt;
        line-height: 1.35;
        color: #92400e;
        font-family: 'Cairo', sans-serif;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .signatures-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-top: 4px;
        margin-bottom: 5px;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .signature-card {
        border: 1px dashed #94a3b8;
        border-radius: 4px;
        padding: 3px 5px;
        text-align: center;
        background: #f8fafc;
        min-height: 38px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .signature-role {
        font-size: 7.5pt;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0;
    }

    .signature-sub {
        font-size: 6.5pt;
        color: #64748b;
        margin: 1px 0 0 0;
    }

    .tear-off-section {
        border-top: 1.5px dashed #94a3b8;
        padding-top: 4px;
        margin-top: 4px;
        font-family: 'Cairo', sans-serif;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .tear-off-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 7pt;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
    }

    .tear-off-card {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 7pt;
        line-height: 1.35;
        color: #1e293b;
    }
</style>

{{-- Screen Action Bar --}}
<div class="no-print" style="max-width: 1000px; margin: 0 auto 8px auto; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-family: 'Cairo', sans-serif;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: 700; font-size: 9pt; color: #1e3a8a;">✉️ {{ __('استدعاء رسمي لولي الأمر') }}</span>
        <span style="font-size: 7.5pt; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 3px; font-weight: 600;">
            {{ $studentName }} — {{ $classroomName }}
        </span>
    </div>
    <div style="display: flex; gap: 6px;">
        <button onclick="window.print()" style="cursor: pointer; background: #1e3a8a; color: #ffffff; border: none; padding: 3px 10px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            🖨️ {{ __('طباعة الاستدعاء (A4)') }}
        </button>
        <a href="{{ route('print.student-behavior-report.guardian-summons.pdf', ['locale' => app()->getLocale(), 'behaviorObservation' => $observation]) }}" style="text-decoration: none; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 3px 10px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            📥 {{ __('تحميل PDF') }}
        </a>
    </div>
</div>

{{-- Student & Guardian Identity Table --}}
<table class="identity-table">
    <tr>
        <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
        <td style="width: 32%; font-weight: 800; font-size: 8pt; color: #1e3a8a;">{{ $studentName }}</td>
        <th>{{ __('ولي الأمر المحترم') }}</th>
        <td style="font-weight: 700;">{{ $guardianTitle }}</td>
    </tr>
    <tr>
        <th>{{ __('القسم التربوي') }}</th>
        <td>{{ $classroomName }} ({{ $levelName }})</td>
        <th>{{ __('هاتف ولي الأمر') }}</th>
        <td style="direction: ltr; text-align: right; font-family: monospace;">{{ $guardianPhone }}</td>
    </tr>
    <tr>
        <th>{{ __('رقم التسجيل') }}</th>
        <td style="font-family: monospace;">{{ $regNumber }}</td>
        <th>{{ __('الأستاذ(ة) المعني(ة)') }}</th>
        <td>{{ $teacherName }} — <span style="color: #1e3a8a; font-weight: 700;">{{ $subject }}</span></td>
    </tr>
</table>

{{-- Appointment Slot --}}
<div class="appointment-box">
    <div class="appointment-title">
        <span>📅</span> {{ __('الموعد المحدد للمقابلة ومكان الاستقبال:') }}
    </div>
    <div class="appointment-details">
        {{ __('يرجى من السيد(ة) ولي أمر التلميذ(ة) الحضور إلى المؤسسة التعليمية (') . ($schoolSetting->school_name ?? __('المتوسطة')) . (') لمقابلة الإدارة والأستاذ(ة) المعني(ة) بـ: ') }}
        <strong>{{ $receptionLocation }}</strong>{{ __(', وذلك يوم:') }}
        <strong style="color: #1e3a8a; font-size: 8.5pt;">{{ $formatDateTime($appointment) }}</strong>
        <span style="color: #475569; font-size: 7pt;">({{ __('فترة الاستقبال الأسبوعية الرسمية للأستاذ(ة)') }})</span>.
    </div>
</div>

{{-- Motive & Observations --}}
<div class="motive-box">
    <div class="motive-title">
        {{ __('موضوع الاستدعاء والوقائع المرصودة أثناء الحصة:') }}
    </div>
    <div class="motive-content">{{ $motiveText }}</div>
</div>

{{-- Administrative Instructions --}}
<div class="instructions-box">
    <strong>⚠️ {{ __('ملاحظة هامة لولي الأمر:') }}</strong>
    {{ __('إن المتابعة الدورية والمشتركة بين الأسرة والمؤسسة التربوية هي الركيزة الأساسية لضمان النجاح البيداغوجي والانضباط السلوكي للتلميذ. حضوركم في الموعد المحدد إلزامي لتفادي اتخاذ الإجراءات التأديبية المنصوص عليها في النظام الداخلي.') }}
</div>

{{-- Official Institutional Signatures (3 Boxes on same page) --}}
<div class="signatures-row">
    <div class="signature-card">
        <div>
            <p class="signature-role">{{ __('إمضاء وتأشيرة الأستاذ(ة)') }}</p>
            <p class="signature-sub">{{ $teacherName }}</p>
        </div>
        <p class="signature-sub" style="font-size: 6pt; color: #94a3b8;">{{ __('التاريخ والإمضاء') }}</p>
    </div>

    <div class="signature-card">
        <div>
            <p class="signature-role">{{ __('تأشيرة مستشار(ة) التربية / ناظر(ة) الدروس') }}</p>
            <p class="signature-sub">{{ __('المعاينة والمتابعة') }}</p>
        </div>
        <p class="signature-sub" style="font-size: 6pt; color: #94a3b8;">{{ __('التأشيرة والختم') }}</p>
    </div>

    <div class="signature-card">
        <div>
            <p class="signature-role">{{ __('تأشيرة وختم السيد(ة) مدير(ة) المؤسسة') }}</p>
            <p class="signature-sub">{{ $schoolSetting?->principal_name ?? __('إدارة المؤسسة') }}</p>
        </div>
        <p class="signature-sub" style="font-size: 6pt; color: #94a3b8;">{{ __('المصادقة والاعتماد') }}</p>
    </div>
</div>

{{-- Tear-off Acknowledgment Slip (وصل استلام استدعاء ولي الأمر) --}}
<div class="tear-off-section">
    <div class="tear-off-header">
        <span>✂️ {{ __('وصل استلام استدعاء ولي الأمر (يُقتطع ويُعاد فوراً إلى مصلحة الاستشارة التربوية موقعاً)') }}</span>
        <span style="font-family: monospace;">Ref: SUM-{{ sprintf('%04d', $observation->id) }}</span>
    </div>

    <div class="tear-off-card">
        <p style="margin: 0 0 3px 0; font-weight: 700;">
            {{ __('أنا الموقع أسفله، السيد(ة): ............................................................. ولي أمر التلميذ(ة): :name (قسم: :classroom)، أشهد بأنني تسلمت الاستدعاء الرسمي الموجه لي، وألتزم بالحضور إلى المؤسسة لمقابلة الإدارة والأستاذ(ة) في الموعد المحدد (:date).', [
                'name' => $studentName,
                'classroom' => $classroomName,
                'date' => $formatDateTime($appointment),
            ]) }}
        </p>
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dotted #cbd5e1; padding-top: 2px; font-size: 6.5pt; color: #475569;">
            <span>{{ __('تاريخ الاستلام:') }} ...... / ...... / 2026</span>
            <span style="font-weight: 700; color: #0f172a;">{{ __('توقيع ولي الأمر:') }} ..................................................</span>
        </div>
    </div>
</div>
