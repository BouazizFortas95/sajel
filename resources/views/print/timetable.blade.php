@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $weekStart = $weekDates->first();
    $weekEnd = $weekDates->last();
    $teacherName = $teacher?->name ?? __('جميع الأساتذة');
    $subjectName = $teacher?->primarySubject?->name
        ?? $subjects->pluck('name')->implode(' / ')
        ?: __('التعليم المتوسط');
    $assignedClassrooms = $schedules
        ->map(fn ($s) => $s->classroom?->name ?? $s->group?->classroom?->name)
        ->filter()
        ->unique()
        ->values()
        ->sort(SORT_NATURAL)
        ->values();
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('جدول التوقيت الأسبوعي الرسمي'),
    'printSubtitle' => $teacher
        ? __('الأستاذ(ة): :name — مادة :subject', ['name' => $teacherName, 'subject' => $subjectName])
        : ($classroom ? __('القسم: :classroom (:level)', ['classroom' => $classroom->name, 'level' => $classroom->level?->name ?? '—']) : __('المخطط الأسبوعي العام للدروس')),
    'printOrientation' => 'landscape',
    'printAcademicYear' => $academicYear ?: $schoolSetting->academic_year,
    'printReference' => __('جدول التوزيع الزمني'),
    'printMeta' => now()->format('Y-m-d'),
    'printAutoPrint' => $autoPrint ?? true,
])

<style>
    @page {
        size: A4 landscape;
        margin: 5mm 8mm 4mm 8mm !important;
    }

    .document-sheet {
        padding: 4mm 6mm !important;
        margin-bottom: 0 !important;
    }

    .doc-header {
        margin-bottom: 3px !important;
    }

    .doc-header-col {
        padding: 3px 8px !important;
    }

    .doc-school-name {
        font-size: 11pt !important;
        margin-bottom: 1px !important;
    }

    .doc-header-republic .republic-name {
        font-size: 10.5pt !important;
    }

    .doc-header-republic .ministry {
        font-size: 9pt !important;
        margin-top: 1px !important;
    }

    .doc-header-republic .directorate {
        font-size: 8pt !important;
        margin-top: 1px !important;
    }

    .doc-header-meta {
        font-size: 7.5pt !important;
        gap: 2px !important;
    }

    .doc-header-school .meta-label,
    .doc-header-meta .meta-label {
        font-size: 7.5pt !important;
    }

    .doc-header-school .meta-value,
    .doc-header-meta .meta-value {
        font-size: 8pt !important;
    }

    .doc-title-wrapper {
        margin-top: 3px !important;
        margin-bottom: 3px !important;
    }

    .doc-title-banner {
        padding: 2px 18px !important;
        font-size: 11pt !important;
    }

    .doc-subtitle {
        margin-top: 2px !important;
        font-size: 8.5pt !important;
    }

    .identity-strip {
        margin-top: 2px !important;
        margin-bottom: 4px !important;
        padding: 3px 8px !important;
        border: 1.5px solid #1a237e !important;
        background: #f8fafc !important;
        border-radius: 4px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .identity-strip-main {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
        flex: 1 !important;
        min-width: 0 !important;
    }

    .identity-strip-label {
        font-size: 8pt !important;
        color: #1a237e !important;
        font-family: 'Cairo', sans-serif !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
    }

    .identity-pills-list {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 4px !important;
        align-items: center !important;
    }

    .classroom-pill {
        background: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
        font-weight: 700 !important;
        font-size: 7.5pt !important;
        padding: 1px 6px !important;
        border-radius: 3px !important;
        font-family: 'Cairo', sans-serif !important;
        white-space: nowrap !important;
    }

    .identity-strip-load {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        border-inline-start: 1.5px solid #cbd5e1 !important;
        padding-inline-start: 10px !important;
    }

    .load-badge {
        background: #1a237e !important;
        color: #ffffff !important;
        padding: 1px 7px !important;
        border-radius: 3px !important;
        font-size: 7.5pt !important;
        font-weight: 700 !important;
        font-family: 'Cairo', sans-serif !important;
    }

    .load-period {
        font-size: 7pt !important;
        color: #64748b !important;
        font-family: 'Cairo', sans-serif !important;
    }

    /* Timetable Table */
    .timetable-grid-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: 'Cairo', sans-serif;
        border: 1.5px solid #1a237e;
    }

    .timetable-grid-table th,
    .timetable-grid-table td {
        border: 1px solid #cbd5e1;
        padding: 3px 4px;
        vertical-align: top;
    }

    .timetable-grid-table thead th {
        background-color: #1a237e;
        color: #ffffff;
        font-size: 8pt;
        font-weight: 700;
        text-align: center;
        padding: 3px 2px;
        border: 1px solid #3b82f6;
    }

    .slot-cell {
        background-color: #f8fafc;
        text-align: center;
        vertical-align: middle !important;
        width: 9%;
    }

    .schedule-item {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-inline-start: 3px solid #1a237e;
        border-radius: 2px;
        padding: 2px 4px;
        margin-bottom: 2px;
        line-height: 1.25;
    }

    .schedule-item:last-child {
        margin-bottom: 0;
    }

    .schedule-subject {
        font-weight: 700;
        color: #0f172a;
        font-size: 8pt;
        display: block;
    }

    .schedule-meta {
        font-size: 7pt;
        color: #475569;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1px;
    }

    .schedule-badge {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        padding: 0 4px;
        border-radius: 2px;
        font-weight: 700;
        font-size: 7pt;
    }

    /* Signatures */
    .signature-grid {
        margin-top: 6px !important;
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 16px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .signature-box {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        text-align: center !important;
        font-family: 'Cairo', sans-serif !important;
    }

    .signature-box strong {
        font-size: 8pt !important;
        color: #1a237e !important;
        display: block !important;
    }

    .signature-box span {
        font-size: 7.5pt !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        margin-top: 1px !important;
        display: block !important;
    }

    .signature-box .signature-space {
        height: 45px !important;
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

        .doc-header,
        .doc-title-wrapper,
        .identity-strip,
        .timetable-section,
        .signature-grid,
        .doc-footer {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

    {{-- Classrooms & Workload Strip --}}
    <div class="identity-strip">
        <div class="identity-strip-main">
            <span class="identity-strip-label">{{ __('الأقسام التربوية المسندة') }}@if($assignedClassrooms->isNotEmpty()) ({{ $assignedClassrooms->count() }})@endif:</span>
            <div class="identity-pills-list">
                @forelse ($assignedClassrooms as $clsName)
                    <span class="classroom-pill">{{ $clsName }}</span>
                @empty
                    <span class="classroom-pill">{{ $classroom?->name ?? __('جميع الأقسام') }}</span>
                @endforelse
            </div>
        </div>
        <div class="identity-strip-load">
            <span class="identity-strip-label">{{ __('الحجم الساعي الأسبوعي') }}:</span>
            <strong class="load-badge">{{ $scheduleCount }} {{ __('حصة أسبوعياً') }}</strong>
            <span class="load-period" dir="ltr">({{ $formatDate($weekStart) }} — {{ $formatDate($weekEnd) }})</span>
        </div>
    </div>

    {{-- Timetable Table --}}
    <div class="timetable-section">
        <table class="timetable-grid-table">
            <thead>
                <tr>
                    <th style="width: 10%;">{{ __('التوقيت') }}</th>
                    @foreach ($days as $day)
                        <th style="width: 18%;">
                            <div>{{ $day->label() }}</div>
                            @if (isset($weekDates[$day->value]))
                                <span style="font-size: 7pt; font-weight: normal; opacity: 0.85;" dir="ltr">
                                    {{ $weekDates[$day->value]->format('m/d') }}
                                </span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($timeSlots as $slot)
                    <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                        <td class="slot-cell">
                            <div style="font-weight: 700; color: #1a237e; font-size: 8pt;" dir="ltr">{{ $slot['start'] }}</div>
                            <div style="font-size: 6.5pt; color: #64748b;" dir="ltr">{{ $slot['end'] }}</div>
                        </td>

                        @foreach ($days as $day)
                            @php
                                $cellSchedules = $grid[$day->value][$slot['key']] ?? [];
                            @endphp
                            <td>
                                @forelse ($cellSchedules as $schedule)
                                    <div
                                        class="schedule-item"
                                        style="border-inline-start-color: {{ $schedule->subject?->color ?? '#1a237e' }};"
                                    >
                                        <span class="schedule-subject">{{ $schedule->subject?->name ?? '—' }}</span>
                                        <div class="schedule-meta">
                                            <span class="schedule-badge">
                                                {{ $schedule->classroom?->name ?? $schedule->group?->classroom?->name ?? '—' }}
                                                @if ($schedule->group)
                                                    <small>({{ $schedule->group->name }})</small>
                                                @endif
                                            </span>
                                            @if (! $teacher && $schedule->user)
                                                <span style="font-size: 6.5pt; color: #475569;">{{ $schedule->user->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <span style="display: block; text-align: center; color: #cbd5e1; font-size: 8pt;">—</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $days->count() + 1 }}" style="text-align: center; padding: 15px; color: #64748b;">
                            {{ __('لا توجد حصص مسجلة في جدول التوقيت.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Signatures Area (Teacher, Director of Studies / Counselor, Principal) --}}
    <div class="signature-grid">
        <div class="signature-box">
            <strong>{{ __('إمضاء وتأشيرة الأستاذ(ة)') }}</strong>
            <span>{{ $teacherName }}</span>
            <div class="signature-space"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة السيد(ة) ناظر(ة) الدروس') }}</strong>
            <span>{{ $schoolSetting->counselor_name ?: __('ناظر(ة) الدروس / مستشار(ة) التربية') }}</span>
            <div class="signature-space"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة وختم السيد(ة) مدير(ة) المؤسسة') }}</strong>
            <span>{{ $schoolSetting->principal_name ?: __('مدير المؤسسة') }}</span>
            <div class="signature-space"></div>
        </div>
    </div>

    {{-- Footer --}}
    <p class="doc-footer">
        <span>{{ __('جدول التوقيت الأسبوعي الرسمي') }} | {{ $schoolSetting->school_name }} | {{ $academicYear ?: $schoolSetting->academic_year }}</span>
        <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
    </p>

</main>
</body>
</html>
