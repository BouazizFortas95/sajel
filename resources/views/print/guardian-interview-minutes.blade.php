@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $teacherName = $teacher?->name ?? $observation->observed_by ?? __('الأستاذ(ة)');
    $studentName = $student?->full_name ?? __('تلميذ غير محدد');
    $classroomName = $classroom?->name ?? $student?->group?->classroom?->name ?? '—';
    $levelName = $classroom?->level?->name ?? '—';
    $regNumber = $student?->registration_number ?: ($student?->id ? sprintf('%04d', $student->id) : '—');
    $guardianTitle = $guardianName ?? $student?->guardian_name ?? __('ولي أمر التلميذ(ة)');
    $guardianPhone = $student?->guardian_phone ?? '—';
    $appointment = $observation->guardian_summons_appointment_at ?? now();
    $interviewDate = $interviewDate ?? $appointment;
    $location = $teacher?->parent_reception_location ?: __('مكتب الاستشارة التربوية');
    $motiveText = $formattedMotive ?? app(\App\Services\StudentIncidentReportService::class)->formatSummonsMotive($observation);
    $notes = $observation->guardian_attendance_notes ?: __('تم الاستماع إلى ولي الأمر وإطلاعه على الوقائع المسجلة، وتعهد بالحرص على انضباط ابنه(ها) ومتابعة نتائجه وسلوكه بصفة يومية مستمرة.');
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('محضر مقابلة وتعهد ولي أمر التلميذ(ة)'),
    'printSubtitle' => __('مصلحة الاستشارة التربوية — متابعة الانضباط المدرسي والمرافقة التربوية المشتركة'),
    'printOrientation' => 'portrait',
    'printAcademicYear' => $academicYear ?: $schoolSetting->academic_year,
    'printReference' => __('محضر مقابلة رقم: :id', ['id' => sprintf('%04d', $observation->id)]),
    'printMeta' => $formatDate($interviewDate),
    'printAutoPrint' => $autoPrint ?? true,
])

<style>
    @page {
        size: A4 portrait;
        margin: 5mm 8mm 4mm 8mm !important;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Cairo', sans-serif;
        color: #0f172a;
        background: #ffffff;
    }

    .document-sheet {
        padding: 2mm 6mm !important;
        max-width: 100% !important;
        margin-bottom: 0 !important;
    }

    .screen-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 12px;
        margin-bottom: 6px;
        font-family: 'Cairo', sans-serif;
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
        padding: 3px 6px;
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

    .section-box {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        border-radius: 4px;
        padding: 4px 8px;
        margin-bottom: 4px;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .section-title {
        font-size: 8pt;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 2px;
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .section-content {
        font-size: 7.5pt;
        line-height: 1.4;
        color: #0f172a;
        white-space: pre-line;
    }

    .commitments-list {
        margin: 2px 0 0 0;
        padding-right: 18px;
        font-size: 7.5pt;
        line-height: 1.4;
        color: #0f172a;
    }

    .commitments-list li {
        margin-bottom: 2px;
    }

    /* Signatures Section: 4 roles, clean open empty area */
    .signatures-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 6px;
        margin-bottom: 4px;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .signature-card {
        border: none;
        background: transparent;
        padding: 0;
        text-align: center;
        font-family: 'Cairo', sans-serif;
    }

    .signature-role {
        font-size: 7.5pt;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0;
    }

    .signature-sub {
        font-size: 7pt;
        color: #0f172a;
        font-weight: 600;
        margin: 1px 0 0 0;
    }

    .signature-space {
        height: 48px;
    }

    .doc-footer {
        margin-top: 3px !important;
        padding-top: 2px !important;
        border-top: 1px solid #cbd5e1 !important;
        font-size: 6.5pt !important;
        color: #64748b !important;
        display: flex !important;
        justify-content: space-between !important;
    }

    @media print {
        .screen-actions,
        .no-print {
            display: none !important;
        }

        .document-sheet {
            border: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
        }

        .signatures-row {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

{{-- Screen Action Bar --}}
<div class="screen-actions no-print">
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: 700; font-size: 9pt; color: #1e3a8a;">📋 {{ __('محضر رسمي لمقابلة ولي الأمر') }}</span>
        <span style="font-size: 7.5pt; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 3px; font-weight: 600;">
            {{ $studentName }} — {{ $classroomName }}
        </span>
    </div>
    <div style="display: flex; gap: 6px;">
        <button onclick="window.print()" style="cursor: pointer; background: #1e3a8a; color: #ffffff; border: none; padding: 3px 10px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            🖨️ {{ __('طباعة المحضر (A4)') }}
        </button>
        <a href="{{ route('print.student-behavior-report.interview-minutes.pdf', ['locale' => app()->getLocale(), 'behaviorObservation' => $observation]) }}" style="text-decoration: none; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 3px 10px; border-radius: 4px; font-size: 8pt; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            📥 {{ __('تحميل PDF') }}
        </a>
    </div>
</div>

{{-- Student & Guardian Identity Table --}}
<table class="identity-table">
    <tr>
        <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
        <td style="width: 32%; font-weight: 800; font-size: 8pt; color: #1e3a8a;">{{ $studentName }}</td>
        <th>{{ __('ولي الأمر الحاضر') }}</th>
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
        <th>{{ __('تاريخ ومكان المقابلة') }}</th>
        <td>{{ $formatDateTime($interviewDate) }} ({{ $location }})</td>
    </tr>
    <tr>
        <th>{{ __('الأستاذ(ة) المعني(ة)') }}</th>
        <td>{{ $teacherName }}</td>
        <th>{{ __('المادة التعليمية') }}</th>
        <td><strong style="color: #1e3a8a;">{{ $subject }}</strong></td>
    </tr>
</table>

{{-- Section 1: Summons Motive & Facts --}}
<div class="section-box">
    <div class="section-title">
        <span>📌</span> {{ __('أولاً: الوقائع البيداغوجية والسلوكية المرصودة موضوع المقابلة:') }}
    </div>
    <div class="section-content">{{ $motiveText }}</div>
</div>

{{-- Section 2: Meeting Outcomes & Guardian Commitments --}}
<div class="section-box" style="background: #fdfefe; border-color: #93c5fd;">
    <div class="section-title" style="color: #0369a1; border-color: #bfdbfe;">
        <span>📝</span> {{ __('ثانياً: مخرجات جلسة المقابلة وتعهدات ولي أمر التلميذ(ة):') }}
    </div>
    <div class="section-content">
        <p style="margin: 0 0 4px 0; font-weight: 700;">
            {{ __('بناءً على جلسة الاستماع المنعقدة بمكتب الاستشارة التربوية بحضور الإدارة والأستاذ(ة) المعني(ة)، تم إحاطة ولي الأمر علماً بجميع الملاحظات، وعليه يُقر ويلتزم بما يلي:') }}
        </p>
        <ul class="commitments-list">
            <li>{{ __('السهر على المتابعة اليومية لكراس الدروس وكراس المراسلة، وتوقيع الملاحظات المدونة بصفة منتظمة.') }}</li>
            <li>{{ __('إلزام التلميذ(ة) بإحضار كافة الأدوات والكتب المدرسية المقررة، وإنجاز الأنشطة والواجبات التعليمية المكلف بها.') }}</li>
            <li>{{ __('حث التلميذ(ة) على الانضباط التام داخل القسم، واحترام الأساتذة والزملاء وقواعد النظام الداخلي للمؤسسة.') }}</li>
            <li>{{ __('التواصل المستمر والمباشر مع مصلحة الاستشارة التربوية لمتابعة التطور البيداغوجي والسلوكي.') }}</li>
        </ul>
        @if (filled($notes))
            <div style="margin-top: 4px; padding: 4px 6px; background: #eff6ff; border-radius: 3px; border-inline-start: 3px solid #2563eb;">
                <strong style="color: #1e3a8a;">{{ __('ملاحظات وتعهدات خاصة مدونة أثناء الجلسة:') }}</strong>
                <span>{{ $notes }}</span>
            </div>
        @endif
    </div>
</div>

{{-- Section 3: Agreed Educational Measures --}}
<div class="section-box" style="background: #f0fdf4; border-color: #86efac;">
    <div class="section-title" style="color: #166534; border-color: #bbf7d0;">
        <span>⚖️</span> {{ __('ثالثاً: التدابير التربوية المعتمدة من طرف إدارة المؤسسة:') }}
    </div>
    <div class="section-content" style="color: #166534;">
        {{ __('المرافقة البيداغوجية المستمرة للتلميذ(ة)، إدراجه(ها) ضمن آلية المتابعة الفردية بالقسم، مع إمكانية اتخاذ الإجراءات التأديبية المنصوص عليها في النظام الداخلي في حال تكرار المخالفة أو العود.') }}
    </div>
</div>

{{-- Official Institutional Signatures Area (4 Roles without borders/explanatory notes) --}}
<div class="signatures-row">
    <div class="signature-card">
        <p class="signature-role">{{ __('توقيع وبصمة ولي الأمر') }}</p>
        <p class="signature-sub">{{ $guardianTitle }}</p>
        <div class="signature-space"></div>
    </div>

    <div class="signature-card">
        <p class="signature-role">{{ __('إمضاء وتأشيرة الأستاذ(ة)') }}</p>
        <p class="signature-sub">{{ $teacherName }}</p>
        <div class="signature-space"></div>
    </div>

    <div class="signature-card">
        <p class="signature-role">{{ __('تأشيرة مستشار(ة) التربية') }}</p>
        <p class="signature-sub">{{ $schoolSetting->counselor_name ?: __('مستشار التربية') }}</p>
        <div class="signature-space"></div>
    </div>

    <div class="signature-card">
        <p class="signature-role">{{ __('تأشيرة وختم مدير(ة) المؤسسة') }}</p>
        <p class="signature-sub">{{ $schoolSetting?->principal_name ?? __('مدير المؤسسة') }}</p>
        <div class="signature-space"></div>
    </div>
</div>

{{-- Document Footer --}}
<p class="doc-footer">
    <span>{{ __('محضر مقابلة وتعهد ولي أمر التلميذ(ة)') }} | {{ $schoolSetting->school_name }} | {{ $academicYear ?: $schoolSetting->academic_year }}</span>
    <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
</p>

</main>
</body>
</html>
