@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $teacherName = $teacher?->name ?? $observation->observed_by ?? __('الأستاذ(ة)');
    $studentName = $student?->full_name ?? __('تلميذ غير محدد');
    $classroomName = $classroom?->name ?? $student?->group?->classroom?->name ?? '—';
    $levelName = $classroom?->level?->name ?? '—';
    $regNumber = $student?->registration_number ?: ($student?->id ? sprintf('%04d', $student->id) : '—');
    $categoryLabel = match($observation->category) {
        'positive' => __('تقرير تميز وسلوك إيجابي'),
        'negative' => __('تقرير إهمال بيداغوجي وسلوكي'),
        'disciplinary' => __('تقرير إحالة ومخالفة انضباطية'),
        default => __('تقرير متابعة تربوية وسلوكية'),
    };
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('تقرير أستاذ(ة) حول تلميذ(ة)'),
    'printSubtitle' => __('المتابعة البيداغوجية والانضباط المدرسي — تسيير القسم'),
    'printOrientation' => 'portrait',
    'printAcademicYear' => $academicYear ?: $schoolSetting->academic_year,
    'printReference' => __('سجل المتابعة السلوكية'),
    'printMeta' => $formatDate($observation->date),
    'printAutoPrint' => $autoPrint ?? true,
])

<style>
    @page {
        size: A4 portrait;
        margin: 5mm 10mm 4mm 10mm !important;
    }

    .document-sheet {
        padding: 4mm 8mm !important;
    }

    .report-badge-container {
        text-align: center;
        margin: 4px 0 6px 0;
    }

    .report-category-badge {
        display: inline-block;
        padding: 2px 14px;
        background: #1a237e;
        color: #ffffff;
        border-radius: 4px;
        font-weight: 700;
        font-size: 9.5pt;
        letter-spacing: 0.5px;
    }

    .report-category-badge.positive {
        background: #166534 !important;
    }

    .report-category-badge.disciplinary {
        background: #991b1b !important;
    }

    .identity-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        font-family: 'Cairo', sans-serif;
        border: 1.5px solid #1a237e;
        background: #ffffff;
    }

    .identity-table th,
    .identity-table td {
        border: 1px solid #cbd5e1;
        padding: 3px 6px;
        font-size: 8pt;
    }

    .identity-table th {
        background: #f8fafc;
        color: #1a237e;
        font-weight: 700;
        text-align: right;
        width: 18%;
    }

    .identity-table td {
        color: #0f172a;
        font-weight: 600;
    }

    .section-header {
        background: #f1f5f9;
        border-inline-start: 4px solid #1a237e;
        padding: 3px 6px;
        font-size: 8.5pt;
        font-weight: 700;
        color: #1a237e;
        margin: 6px 0 3px 0;
    }

    .report-narrative-box {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        border-radius: 4px;
        padding: 8px 10px;
        font-size: 8.5pt;
        line-height: 1.4;
        color: #0f172a;
        white-space: pre-wrap;
        min-height: 70px;
        font-family: 'Cairo', sans-serif;
    }

    .signatures-section {
        margin-top: 8px !important;
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 16px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .sign-box {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        text-align: center !important;
        font-family: 'Cairo', sans-serif !important;
    }

    .sign-box strong {
        font-size: 8pt !important;
        color: #1a237e !important;
        display: block !important;
    }

    .sign-box span {
        font-size: 7.5pt !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        display: block !important;
        margin-top: 1px !important;
    }

    .sign-box .sign-space {
        height: 48px !important;
    }

    .doc-footer {
        margin-top: 8px !important;
        padding-top: 3px !important;
        border-top: 1px solid #cbd5e1 !important;
        font-size: 6.5pt !important;
        color: #64748b !important;
        display: flex !important;
        justify-content: space-between !important;
    }

    @media print {
        html, body {
            background: #ffffff !important;
        }

        .document-sheet {
            border: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signatures-section,
        .identity-table,
        .report-narrative-box {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

    {{-- Category Badge --}}
    <div class="report-badge-container">
        <span class="report-category-badge {{ $observation->category }}">
            {{ $categoryLabel }}
        </span>
    </div>

    {{-- Identity Table --}}
    <table class="identity-table">
        <tbody>
            <tr>
                <th>{{ __('التلميذ(ة) المعني(ة)') }}</th>
                <td style="font-size: 9.5pt; font-weight: 700; color: #1a237e;">{{ $studentName }}</td>
                <th>{{ __('رقم التسجيل') }}</th>
                <td dir="ltr" style="font-family: monospace; font-weight: 700;">{{ $regNumber }}</td>
            </tr>
            <tr>
                <th>{{ __('القسم التربوي') }}</th>
                <td><strong>{{ $classroomName }}</strong> @if($levelName !== '—') <span style="color: #64748b;">({{ $levelName }})</span> @endif</td>
                <th>{{ __('تاريخ الواقعة / الحصة') }}</th>
                <td dir="ltr">{{ $formatDate($observation->date) }}</td>
            </tr>
            <tr>
                <th>{{ __('الأستاذ(ة) المحيل(ة)') }}</th>
                <td><strong>{{ $teacherName }}</strong></td>
                <th>{{ __('المادة التعليمية') }}</th>
                <td>{{ $subject }}</td>
            </tr>
            @if ($schedule)
                <tr>
                    <th>{{ __('توقيت الحصة') }}</th>
                    <td colspan="3" dir="ltr" style="text-align: right;">
                        {{ $schedule->day_of_week?->label() }} — {{ $schedule->start_time?->format('H:i') }} إلى {{ $schedule->end_time?->format('H:i') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Report Content --}}
    <div class="section-header">
        {{ __('نص التقرير والوقائع المرصودة') }}
    </div>
    <div class="report-narrative-box">{{ $observation->notes }}</div>

    @if (filled($observation->follow_up_notes))
        <div class="section-header" style="margin-top: 10px; border-inline-start-color: #0284c7;">
            {{ __('ملاحظات وقرار المتابعة الإدارية / البيداغوجية') }}
        </div>
        <div class="report-narrative-box" style="min-height: 45px; background: #f8fafc;">{{ $observation->follow_up_notes }}</div>
    @endif

    {{-- Signatures Area --}}
    <div class="signatures-section">
        <div class="sign-box">
            <strong>{{ __('إمضاء وتأشيرة الأستاذ(ة)') }}</strong>
            <span>{{ $teacherName }}</span>
            <div class="sign-space"></div>
        </div>
        <div class="sign-box">
            <strong>{{ __('تأشيرة السيد(ة) مستشار(ة) التربية') }}</strong>
            <span>{{ $schoolSetting->counselor_name ?: __('مستشار(ة) التربية / ناظر(ة) الدروس') }}</span>
            <div class="sign-space"></div>
        </div>
        <div class="sign-box">
            <strong>{{ __('قرار وتأشيرة السيد(ة) مدير(ة) المؤسسة') }}</strong>
            <span>{{ $schoolSetting->principal_name ?: __('مدير المؤسسة') }}</span>
            <div class="sign-space"></div>
        </div>
    </div>

    {{-- Footer --}}
    <p class="doc-footer">
        <span>{{ __('تقرير أستاذ(ة) حول تلميذ(ة)') }} | {{ $schoolSetting->school_name }} | {{ $academicYear }}</span>
        <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
    </p>

</main>
</body>
</html>

