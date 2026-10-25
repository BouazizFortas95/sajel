@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ'),
    'printSubtitle' => $studentIdentity['full_name'].' — '.($studentIdentity['classroom'] ?? ''),
    'printAcademicYear' => $studentIdentity['academic_year'] ?? $schoolSetting->academic_year,
    'printReference' => __('بطاقة المتابعة البيداغوجية والسلوكية'),
    'printMeta' => $studentIdentity['registration_number'] ?: null,
    'printAutoPrint' => $autoPrint ?? false,
])

    <section class="identity-card bg-amber-50/70 border border-amber-200 rounded-2xl p-4">
        <div class="section-heading">
            <h2 class="section-title">{{ __('بطاقة هوية التلميذ') }}</h2>
            <span class="badge badge-primary">{{ __('السنة الدراسية') }}: {{ $studentIdentity['academic_year'] }}</span>
        </div>

        <div class="identity-grid grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="identity-item">
                <p class="identity-label">{{ __('الاسم واللقب') }}</p>
                <p class="identity-value">{{ $studentIdentity['full_name'] }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('رقم التسجيل') }}</p>
                <p class="identity-value" dir="ltr">{{ $studentIdentity['registration_number'] ?: '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('تاريخ الميلاد') }}</p>
                <p class="identity-value" dir="ltr">{{ $studentIdentity['date_of_birth']?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المستوى والقسم والفوج') }}</p>
                <p class="identity-value">
                    {{ $studentIdentity['level'] ?? '—' }}
                    @if ($studentIdentity['classroom'])
                        — {{ $studentIdentity['classroom'] }}
                    @endif
                    @if ($studentIdentity['group'])
                        — {{ $studentIdentity['group'] }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('ملخص الحضور والغياب') }}</h2>
            <span class="badge badge-success">{{ __('آخر تحديث') }}: {{ $generatedAt->format('Y-m-d H:i') }}</span>
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

        <div class="section-heading" style="margin-top: 12px; margin-bottom: 6px;">
            <h3 class="section-title" style="font-size: 11px;">{{ __('آخر الغيابات والتأخرات') }}</h3>
            <span class="muted" style="font-size: 9px;">{{ __('عدد حصص الحضور المسجلة') }}: {{ $attendanceTotal }}</span>
        </div>

        @if ($recentAttendanceAlerts->isNotEmpty())
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
                        @foreach ($recentAttendanceAlerts as $attendance)
                            <tr>
                                <td dir="ltr">{{ $attendance->dailyLog?->date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $attendance->status?->value === 'absent' ? 'badge-danger' : 'badge-warning' }}">
                                        {{ $attendance->status?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $attendance->dailyLog?->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $attendance->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد غيابات أو تأخرات مسجلة') }}</p>
        @endif
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('النتائج والتقييمات حسب المادة') }}</h2>
            <span class="badge badge-gray">{{ __('العلامات على 20') }}</span>
        </div>

        @forelse ($gradesBySubject as $subjectGroup)
            <div class="subject-card">
                <div class="subject-card__head">
                    <h3 class="subject-card__title">{{ $subjectGroup['subject']?->name ?? '—' }}</h3>
                    <span class="subject-card__average">
                        {{ __('معدل المادة على 20') }}:
                        {{ $subjectGroup['average'] === null ? '—' : number_format($subjectGroup['average'], 2) }}
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
                            @foreach ($subjectGroup['assessments'] as $assessmentRow)
                                <tr>
                                    <td><strong>{{ $assessmentRow['assessment']->title }}</strong></td>
                                    <td>{{ $assessmentRow['assessment']->type?->label() ?? '—' }}</td>
                                    <td dir="ltr">{{ $assessmentRow['assessment']->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $assessmentRow['grade']?->score ?? '—' }} / {{ $assessmentRow['assessment']->max_score }}
                                        </span>
                                    </td>
                                    <td>{{ $assessmentRow['grade']?->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد تقييمات مسجلة') }}</p>
        @endforelse
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('السجل السلوكي والانضباطي') }}</h2>
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
                            <th>{{ __('ملاحظات المتابعة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($observations as $observation)
                            @php
                                $behaviorLabels = [
                                    'positive' => 'إيجابي',
                                    'negative' => 'سلبي',
                                    'disciplinary' => 'انضباطي',
                                    'participation' => 'مشاركة',
                                    'helpfulness' => 'تعاون',
                                    'leadership' => 'قيادة',
                                    'improvement' => 'تحسن',
                                    'other_positive' => 'إيجابي',
                                    'disruption' => 'إخلال بالنظام',
                                    'disrespect' => 'عدم احترام',
                                    'violence' => 'عنف',
                                    'cheating' => 'غش',
                                    'absence_without_leave' => 'غياب بدون إذن',
                                    'other_negative' => 'سلبي',
                                ];
                            @endphp
                            <tr>
                                <td dir="ltr">{{ $observation->date?->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge {{ $observation->category === 'positive' ? 'badge-success' : 'badge-warning' }}">
                                        {{ __($behaviorLabels[$observation->category] ?? 'ملاحظة') }}
                                    </span>
                                </td>
                                <td>{{ $observation->notes }}</td>
                                <td>{{ $observation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $observation->is_followed_up ? __('تمت المتابعة') : __('قيد المتابعة') }}</td>
                                <td>{{ $observation->follow_up_notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="font-size: 10px;">{{ __('لا توجد ملاحظات سلوكية مسجلة') }}</p>
        @endif
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('حصص المعالجة البيداغوجية') }}</h2>
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
                            <th>{{ __('الحالة') }}</th>
                            <th>{{ __('نتيجة المتابعة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($remediations as $remediation)
                            <tr>
                                <td dir="ltr">{{ $remediation->session_date?->format('Y-m-d') }}</td>
                                <td>{{ $remediation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $remediation->reason }}</td>
                                <td>{{ $remediation->activities }}</td>
                                <td>
                                    <span class="badge {{ $remediation->attended ? 'badge-success' : 'badge-danger' }}">
                                        {{ $remediation->attended ? __('حضر') : __('لم يحضر') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-gray">
                                        {{ __(match ($remediation->status) {
                                            'completed' => 'منجز',
                                            'ongoing' => 'مستمر',
                                            default => 'مخطط',
                                        }) }}
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

    @if (isset($portalUrl))
        <section class="report-section">
            <div class="section-heading">
                <h2 class="section-title">{{ __('بوابة متابعة ولي الأمر') }}</h2>
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
                    <p style="color: #92400e; font-size: 11px; font-weight: 900;">{{ __('رابط المتابعة الرقمية لولي الأمر') }}</p>
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
            <strong>{{ __('تأشيرة مستشار التربية / المدير') }}</strong>
            <span>{{ __('تأشيرة الإدارة') }} — {{ $schoolSetting->principal_name ?: __('مستشار التربية / المدير') }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('إمضاء ولي الأمر') }}</strong>
            <span>{{ __('إطلاع وتوقيع ولي الأمر') }} — {{ __('التاريخ') }}: ____ / ____ / ________</span>
            <div class="signature-line"></div>
        </div>
    </footer>

    <p class="doc-footer">{{ __('وثيقة سرية للاستخدام في المتابعة التربوية') }} · {{ $schoolSetting->school_name }} · {{ $schoolSetting->academic_year }}</p>
</main>
</body>
</html>
