@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $receptionService = app(\App\Services\TeacherParentReceptionService::class);
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('جدول الاستقبال الأسبوعي لأولياء التلاميذ'),
    'printSubtitle' => __('مصلحة الاستشارة التربوية — تنظيم المقابلات الدورية والمتابعة بين الأسرة والمؤسسة'),
    'printOrientation' => 'landscape',
    'printAcademicYear' => $academicYear ?: $schoolSetting->academic_year,
    'printReference' => __('جدول استقبال الأولياء'),
    'printMeta' => now()->format('Y-m-d'),
    'printAutoPrint' => $autoPrint ?? true,
])

<style>
    @page {
        size: A4 landscape;
        margin: 5mm 8mm 5mm 8mm !important;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Cairo', sans-serif;
        color: #0f172a;
        background: #ffffff;
    }

    .document-sheet {
        padding: 4mm 6mm !important;
        margin-bottom: 0 !important;
    }

    /* Screen Action Bar */
    .screen-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 14px;
        margin-bottom: 8px;
        font-family: 'Cairo', sans-serif;
    }

    .screen-actions-title {
        font-weight: 700;
        font-size: 9pt;
        color: #1e3a8a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 8pt;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 4px;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        font-family: 'Cairo', sans-serif;
    }

    .btn-primary {
        background: #1e3a8a;
        color: #ffffff;
    }

    .btn-secondary {
        background: #ffffff;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    /* Summary KPI Bar */
    .summary-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f0fdf4;
        border: 1.5px solid #86efac;
        border-radius: 4px;
        padding: 3px 12px;
        margin-top: 3px;
        margin-bottom: 5px;
        font-size: 7.5pt;
        font-family: 'Cairo', sans-serif;
    }

    .summary-badge {
        font-weight: 700;
        color: #166534;
    }

    /* Receptions Table */
    .receptions-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Cairo', sans-serif;
        font-size: 7.5pt;
        border: 1.5px solid #1e3a8a;
        background: #ffffff;
    }

    .receptions-table th,
    .receptions-table td {
        border: 1px solid #cbd5e1;
        padding: 3px 5px;
        vertical-align: middle;
    }

    .receptions-table thead th {
        background-color: #1e3a8a;
        color: #ffffff;
        font-weight: 700;
        text-align: center;
        padding: 4px 5px;
        border: 1px solid #3b82f6;
    }

    .receptions-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }

    .receptions-table tbody tr:hover {
        background-color: #f1f5f9;
    }

    .teacher-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 8pt;
    }

    .subject-pill {
        display: inline-block;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 1px 6px;
        border-radius: 3px;
        font-weight: 700;
        font-size: 7pt;
        border: 1px solid #bfdbfe;
    }

    .classroom-pills-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }

    .class-pill {
        background: #f1f5f9;
        color: #334155;
        padding: 0 4px;
        border-radius: 2px;
        font-size: 6.5pt;
        font-weight: 600;
        border: 1px solid #e2e8f0;
    }

    .day-badge {
        display: inline-block;
        font-weight: 700;
        color: #1e3a8a;
        background: #e0e7ff;
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 7.5pt;
    }

    .time-slot {
        font-weight: 700;
        color: #0f172a;
        direction: ltr;
        display: inline-block;
        font-size: 7.5pt;
    }

    .location-cell {
        color: #047857;
        font-weight: 600;
    }

    /* Signatures Section */
    .signatures-grid {
        margin-top: 8px !important;
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 24px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .signature-card {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        text-align: center !important;
        font-family: 'Cairo', sans-serif !important;
    }

    .signature-card strong {
        font-size: 8pt !important;
        color: #1e3a8a !important;
        display: block !important;
    }

    .signature-card span {
        font-size: 7.5pt !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        display: block !important;
        margin-top: 1px !important;
    }

    .signature-card .signature-space {
        height: 48px !important;
    }

    .doc-footer {
        margin-top: 4px !important;
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

        .receptions-table thead {
            display: table-header-group !important;
        }

        .receptions-table tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signatures-grid {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

{{-- Screen Action Bar --}}
<div class="screen-actions no-print">
    <div class="screen-actions-title">
        <span>👨‍🏫</span>
        <span>{{ __('جدول الاستقبال الأسبوعي لأولياء التلاميذ') }} — {{ $schoolSetting->school_name }}</span>
        <span style="font-size: 7.5pt; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 3px; font-weight: 600;">
            {{ __('السنة الدراسية:') }} {{ $academicYear ?: $schoolSetting->academic_year }}
        </span>
    </div>
    <div style="display: flex; gap: 6px;">
        <button onclick="window.print()" class="btn-action btn-primary">
            🖨️ {{ __('طباعة الجدول الرسمي (A4)') }}
        </button>
        <a href="{{ route('print.teacher-parent-receptions.pdf', ['locale' => app()->getLocale()]) }}" class="btn-action btn-secondary">
            📥 {{ __('تحميل نسخة PDF') }}
        </a>
    </div>
</div>

{{-- Summary KPI Strip --}}
<div class="summary-strip">
    <div>
        <span class="summary-badge">📌 {{ __('الهدف:') }}</span>
        <span>{{ __('تحديد موعد أسبوعي قار لكل أستاذ(ة) لاستقبال الأولياء خلال فترات الفراغ البيداغوجي بالمؤسسة.') }}</span>
    </div>
    <div>
        <span>{{ __('إجمالي الأساتذة:') }} <strong>{{ $totalTeachers }}</strong></span>
        <span style="margin: 0 6px;">|</span>
        <span style="color: #166534;">{{ __('المعتمد:') }} <strong>{{ $assignedCount }}</strong></span>
        @if ($pendingCount > 0)
            <span style="margin: 0 6px;">|</span>
            <span style="color: #dc2626;">{{ __('غير محدد:') }} <strong>{{ $pendingCount }}</strong></span>
        @endif
    </div>
</div>

{{-- Official Institutional Receptions Table --}}
<table class="receptions-table">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 18%;">{{ __('الأستاذ(ة)') }}</th>
            <th style="width: 14%;">{{ __('المادة التعليمية') }}</th>
            <th style="width: 22%;">{{ __('الأقسام المسندة') }}</th>
            <th style="width: 11%;">{{ __('يوم الاستقبال') }}</th>
            <th style="width: 12%;">{{ __('توقيت المقابلة') }}</th>
            <th style="width: 12%;">{{ __('مكان الاستقبال') }}</th>
            <th style="width: 7%;">{{ __('ملاحظات') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($teachers as $index => $t)
            @php
                $classes = $t->schedules
                    ->map(fn ($s) => $s->classroom?->name ?? $s->group?->classroom?->name)
                    ->filter()
                    ->unique()
                    ->sort(SORT_NATURAL)
                    ->values();
                $dayLabel = $t->parentReceptionDayLabel() ?: '—';
                $timeRange = $t->parentReceptionTimeRange() ?: '—';
                $location = $t->parent_reception_location ?: __('مكتب الاستشارة التربوية');
            @endphp
            <tr>
                <td style="text-align: center; font-weight: 700; color: #64748b;">{{ $index + 1 }}</td>
                <td class="teacher-name">{{ $t->name }}</td>
                <td>
                    <span class="subject-pill">{{ $t->primarySubject?->name ?? __('عام') }}</span>
                </td>
                <td>
                    <div class="classroom-pills-wrap">
                        @forelse ($classes as $cName)
                            <span class="class-pill">{{ $cName }}</span>
                        @empty
                            <span style="color: #94a3b8; font-size: 7pt;">—</span>
                        @endforelse
                    </div>
                </td>
                <td style="text-align: center;">
                    @if ($t->hasParentReceptionSlot())
                        <span class="day-badge">{{ $dayLabel }}</span>
                    @else
                        <span style="color: #94a3b8;">—</span>
                    @endif
                </td>
                <td style="text-align: center;">
                    @if ($t->hasParentReceptionSlot())
                        <span class="time-slot">{{ $timeRange }}</span>
                    @else
                        <span style="color: #dc2626; font-size: 7pt;">{{ __('قيد التحديد') }}</span>
                    @endif
                </td>
                <td class="location-cell" style="text-align: center;">
                    {{ $location }}
                </td>
                <td style="text-align: center; font-size: 6.5pt; color: #64748b;">
                    {{ $t->parent_reception_notes ? \Illuminate\Support\Str::limit($t->parent_reception_notes, 20) : __('فراغ معتمد') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 12px; color: #64748b;">
                    {{ __('لا يوجد أساتذة مسجلون في النظام.') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Institutional Signatures Area --}}
<div class="signatures-grid">
    <div class="signature-card">
        <strong>{{ __('تأشيرة السيد(ة) مستشار(ة) التربية / ناظر(ة) الدروس') }}</strong>
        <span>{{ $schoolSetting->counselor_name ?: __('مصلحة الاستشارة التربوية') }}</span>
        <div class="signature-space"></div>
    </div>
    <div class="signature-card">
        <strong>{{ __('المصادقة والاعتماد: السيد(ة) مدير(ة) المؤسسة') }}</strong>
        <span>{{ $schoolSetting->principal_name ?: __('إدارة المؤسسة') }}</span>
        <div class="signature-space"></div>
    </div>
</div>

{{-- Document Footer --}}
<p class="doc-footer">
    <span>{{ __('جدول الاستقبال الأسبوعي لأولياء التلاميذ') }} | {{ $schoolSetting->school_name }} | {{ $academicYear ?: $schoolSetting->academic_year }}</span>
    <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
</p>

</main>
</body>
</html>
