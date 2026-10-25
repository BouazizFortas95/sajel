@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $behaviorLabels = [
        'positive' => __('إيجابي'),
        'negative' => __('سلبي'),
        'disciplinary' => __('انضباطي'),
        'participation' => __('مشاركة'),
        'helpfulness' => __('تعاون'),
        'leadership' => __('قيادة'),
        'improvement' => __('تحسن'),
        'other_positive' => __('إيجابي'),
        'disruption' => __('إخلال بالنظام'),
        'disrespect' => __('عدم احترام'),
        'violence' => __('عنف'),
        'cheating' => __('غش'),
        'absence_without_leave' => __('غياب بدون إذن'),
        'other_negative' => __('سلبي'),
    ];
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('بوابة متابعة ولي الأمر'),
    'printSubtitle' => $student->full_name.' — '.($classroom?->name ?? __('بدون قسم')),
    'printAcademicYear' => $classroom?->academic_year ?: $schoolSetting->academic_year,
    'printReference' => __('بوابة المتابعة الرقمية'),
    'printMeta' => $student->registration_number ?: null,
    'printAutoPrint' => false,
])

    <section class="identity-card bg-amber-50/70 border border-amber-200 rounded-2xl p-4">
        <div class="section-heading">
            <h2 class="section-title">{{ __('بطاقة هوية التلميذ') }}</h2>
            <span class="badge badge-primary">{{ __('السنة الدراسية') }}: {{ $classroom?->academic_year ?: $schoolSetting->academic_year }}</span>
        </div>

        <div class="identity-grid">
            <div class="identity-item">
                <p class="identity-label">{{ __('الاسم واللقب') }}</p>
                <p class="identity-value">{{ $student->full_name }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('رقم التسجيل') }}</p>
                <p class="identity-value" dir="ltr">{{ $student->registration_number ?: '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('تاريخ الميلاد') }}</p>
                <p class="identity-value" dir="ltr">{{ $student->date_of_birth?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المستوى والقسم والفوج') }}</p>
                <p class="identity-value">
                    {{ $classroom?->level?->name ?? '—' }}
                    @if ($classroom)
                        — {{ $classroom->name }}
                    @endif
                    @if ($group)
                        — {{ $group->name }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('ملخص الحضور') }}</h2>
            <span class="badge {{ $generalAverage !== null && $generalAverage >= 10 ? 'badge-success' : 'badge-warning' }}">
                {{ __('المعدل العام') }}:
                {{ $generalAverage !== null ? number_format($generalAverage, 2).'/20' : __('لم تُرصد بعد') }}
            </span>
        </div>

        <div class="kpi-strip grid grid-cols-5 gap-3 text-center">
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('نسبة الحضور') }}</p>
                <p class="kpi-card__value">{{ number_format($attendanceRate, 1) }}%</p>
                <p class="kpi-card__hint">{{ __('من :count حصة', ['count' => $attendanceTotal]) }}</p>
            </div>
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('حاضر') }}</p>
                <p class="kpi-card__value">{{ $attendanceCounts['present'] }}</p>
                <p class="kpi-card__hint">{{ __('حضور مؤكد') }}</p>
            </div>
            <div class="kpi-card kpi--rose">
                <p class="kpi-card__label">{{ __('غائب') }}</p>
                <p class="kpi-card__value">{{ $attendanceCounts['absent'] }}</p>
                <p class="kpi-card__hint">{{ __('غياب مسجل') }}</p>
            </div>
            <div class="kpi-card kpi--amber">
                <p class="kpi-card__label">{{ __('متأخر') }}</p>
                <p class="kpi-card__value">{{ $attendanceCounts['late'] }}</p>
                <p class="kpi-card__hint">{{ __('تأخر مسجل') }}</p>
            </div>
            <div class="kpi-card kpi--sky">
                <p class="kpi-card__label">{{ __('معفى') }}</p>
                <p class="kpi-card__value">{{ $attendanceCounts['excused'] }}</p>
                <p class="kpi-card__hint">{{ __('غياب بعذر') }}</p>
            </div>
        </div>

        @if ($recentAbsences->isNotEmpty())
            <div class="section-heading" style="margin-top: 12px; margin-bottom: 6px;">
                <h3 class="section-title" style="font-size: 11px;">{{ __('آخر الغيابات والتأخرات') }}</h3>
            </div>
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('الحالة') }}</th>
                            <th>{{ __('المادة') }}</th>
                            <th>{{ __('ملاحظات المعلم') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentAbsences as $absence)
                            <tr>
                                <td dir="ltr">{{ $absence->dailyLog?->date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $absence->status?->value === 'absent' ? 'badge-danger' : 'badge-warning' }}">
                                        {{ $absence->status?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $absence->dailyLog?->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $absence->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('الواجبات المنزلية والدروس الأخيرة') }}</h2>
            <span class="badge badge-info">{{ $recentLogs->count() }} {{ __('سجل') }}</span>
        </div>

        @if ($recentLogs->isNotEmpty())
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('المادة') }}</th>
                            <th>{{ __('عنوان الدرس') }}</th>
                            <th>{{ __('الواجب') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentLogs as $log)
                            <tr>
                                <td dir="ltr">{{ $log->date?->format('Y-m-d') }}</td>
                                <td><span class="badge badge-primary">{{ $log->schedule?->subject?->name ?? '—' }}</span></td>
                                <td><strong>{{ $log->title }}</strong></td>
                                <td>{{ $log->homework ? \Illuminate\Support\Str::limit($log->homework, 80) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد واجبات مسجلة بعد') }}</p>
        @endif
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('التقييمات الدراسية') }}</h2>
            <span class="badge badge-gray">{{ __('العلامات على 20') }}</span>
        </div>

        @forelse ($subjectSummaries as $summary)
            <div class="subject-card">
                <div class="subject-card__head">
                    <h3 class="subject-card__title">{{ $summary['subject'] }}</h3>
                    <span class="subject-card__average">
                        {{ __('معدل المادة على 20') }}:
                        {{ $summary['average'] !== null ? number_format($summary['average'], 2) : __('لم تُرصد بعد') }}
                    </span>
                </div>
                <div class="table-wrap" style="border: 0; border-radius: 0;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>{{ __('التقييم') }}</th>
                                <th>{{ __('النوع') }}</th>
                                <th>{{ __('التاريخ') }}</th>
                                <th>{{ __('العلامة') }}</th>
                                <th>{{ __('ملاحظات المعلم') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['grades'] as $grade)
                                <tr>
                                    <td><strong>{{ $grade->assessment?->title ?? '—' }}</strong></td>
                                    <td>{{ $grade->assessment?->type?->label() ?? '—' }}</td>
                                    <td dir="ltr">{{ $grade->assessment?->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $grade->score ?? '—' }} / {{ $grade->assessment?->max_score ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $grade->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد تقييمات مسجلة بعد') }}</p>
        @endforelse
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('الملاحظات والسلوكية') }}</h2>
            <span class="badge badge-info">{{ $observations->count() }} {{ __('ملاحظة') }}</span>
        </div>

        @if ($observations->isNotEmpty())
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('النوع') }}</th>
                            <th>{{ __('الملاحظة') }}</th>
                            <th>{{ __('المادة') }}</th>
                            <th>{{ __('المتابعة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($observations as $observation)
                            @php
                                $negative = in_array($observation->category, ['negative', 'disciplinary', 'disruption', 'disrespect', 'violence', 'cheating', 'absence_without_leave', 'other_negative'], true);
                            @endphp
                            <tr>
                                <td dir="ltr">{{ $observation->date?->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge {{ $negative ? 'badge-danger' : 'badge-success' }}">
                                        {{ $behaviorLabels[$observation->category] ?? __('ملاحظة') }}
                                    </span>
                                </td>
                                <td>{{ $observation->notes }}</td>
                                <td>{{ $observation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $observation->is_followed_up ? __('تمت المتابعة') : __('قيد المتابعة') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد ملاحظات') }}</p>
        @endif
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('التوصيات والمعالجة البيداغوجية') }}</h2>
            <span class="badge badge-sky">{{ $remediations->count() }} {{ __('حصة') }}</span>
        </div>

        @if ($remediations->isNotEmpty())
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('المادة') }}</th>
                            <th>{{ __('سبب المعالجة') }}</th>
                            <th>{{ __('الأنشطة') }}</th>
                            <th>{{ __('الحضور') }}</th>
                            <th>{{ __('نتيجة المتابعة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($remediations as $remediation)
                            <tr>
                                <td dir="ltr">{{ $remediation->session_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $remediation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $remediation->reason }}</td>
                                <td>{{ $remediation->activities }}</td>
                                <td>
                                    <span class="badge {{ $remediation->attended ? 'badge-success' : 'badge-danger' }}">
                                        {{ $remediation->attended ? __('حضر') : __('لم يحضر') }}
                                    </span>
                                </td>
                                <td>{{ $remediation->follow_up ?: __('لا توجد متابعة مسجلة') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد حصص معالجة مسجلة') }}</p>
        @endif
    </section>

    @if (filled($portalUrl ?? null))
        <section class="report-section">
            <div class="section-heading">
                <h2 class="section-title">{{ __('رابط المتابعة الرقمية لولي الأمر') }}</h2>
                <span class="badge badge-success">{{ __('صالح لمدة 90 يوماً') }}</span>
            </div>
            <div class="qr-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" stroke="#b45309" stroke-width="2" aria-hidden="true">
                    <rect x="10" y="10" width="30" height="30" stroke-width="4"/><rect x="15" y="15" width="20" height="20" fill="#fff" stroke-width="2"/><rect x="20" y="20" width="10" height="10" fill="#b45309"/>
                    <rect x="60" y="10" width="30" height="30" stroke-width="4"/><rect x="65" y="15" width="20" height="20" fill="#fff" stroke-width="2"/><rect x="70" y="20" width="10" height="10" fill="#b45309"/>
                    <rect x="10" y="60" width="30" height="30" stroke-width="4"/><rect x="15" y="65" width="20" height="20" fill="#fff" stroke-width="2"/><rect x="20" y="70" width="10" height="10" fill="#b45309"/>
                    <rect x="45" y="45" width="10" height="10" fill="#b45309"/>
                </svg>
                <div>
                    <p style="color: #92400e; font-size: 11px; font-weight: 900;">{{ __('بوابة متابعة ولي الأمر') }}</p>
                    <p class="qr-card__url">{{ $portalUrl }}</p>
                    <p class="muted" style="margin-top: 2px; font-size: 8px;">{{ __('صالح لمدة 90 يوماً من تاريخ الطباعة') }}</p>
                </div>
            </div>
        </section>
    @endif

    <footer class="signature-grid">
        <div class="signature-box">
            <strong>{{ __('إمضاء الأستاذ(ة)') }}</strong>
            <span>{{ __('الأستاذ المسؤول عن المتابعة') }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة الإدارة') }}</strong>
            <span>{{ $schoolSetting->principal_name ?: __('مستشار التربية / المدير') }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('إطلاع وتوقيع ولي الأمر') }}</strong>
            <span>{{ __('التاريخ') }}: ____ / ____ / ________</span>
            <div class="signature-line"></div>
        </div>
    </footer>

    <p class="doc-footer">{{ __('بوابة متابعة ولي الأمر') }} · {{ $schoolSetting->school_name }} · {{ $classroom?->academic_year ?: $schoolSetting->academic_year }}</p>
</main>
</body>
</html>
