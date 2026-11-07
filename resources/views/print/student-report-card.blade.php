@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $generatedAt = $generatedAt ?? now(config('app.timezone', 'Africa/Algiers'));
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ'),
    'printSubtitle' => $studentIdentity['full_name'].' — '.($studentIdentity['classroom'] ?? ''),
    'printAcademicYear' => $studentIdentity['academic_year'] ?? $schoolSetting->academic_year,
    'printReference' => __('بطاقة المتابعة البيداغوجية والسلوكية'),
    'printMeta' => $studentIdentity['registration_number'] ?: null,
    'printAutoPrint' => $autoPrint ?? false,
])

    <!-- ── أولاً: بطاقة هوية ومعلومات التلميذ ── -->
    <section class="identity-card" style="margin-top: 8px;">
        <div class="section-heading" style="margin-bottom: 6px;">
            <h2 class="section-title" style="font-size: 11pt;">{{ __('بطاقة هوية التلميذ') }}</h2>
            <span class="badge badge-primary" style="background: #1a237e; color: #fff; font-size: 8pt; padding: 2px 8px; border-radius: 2px;">
                {{ __('السنة الدراسية') }}: {{ $studentIdentity['academic_year'] }}
            </span>
        </div>

        <div class="identity-grid grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="identity-item">
                <p class="identity-label">{{ __('الاسم واللقب') }}</p>
                <p class="identity-value">{{ $studentIdentity['full_name'] }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('رقم التسجيل') }}</p>
                <p class="identity-value" dir="ltr" style="text-align: right;">{{ $studentIdentity['registration_number'] ?: '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('تاريخ الميلاد') }}</p>
                <p class="identity-value" dir="ltr" style="text-align: right;">{{ $studentIdentity['date_of_birth']?->format('Y-m-d') ?? '—' }}</p>
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

    <!-- ── ثانياً: وضعية المواظبة والانضباط ── -->
    <section class="report-section" style="margin-top: 8px;">
        <div class="section-heading" style="margin-bottom: 6px;">
            <h2 class="section-title" style="font-size: 11pt;">{{ __('ملخص الحضور والغياب') }}</h2>
            <span class="badge badge-success" style="background: #065f46; color: #fff; font-size: 7.5pt; padding: 2px 6px; border-radius: 2px;">
                {{ __('آخر تحديث') }}: {{ $generatedAt->format('Y-m-d H:i') }}
            </span>
        </div>

        <div class="kpi-strip grid grid-cols-5 gap-2 text-center">
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

        @if ($recentAttendanceAlerts->isNotEmpty())
            <div class="table-wrap" style="margin-top: 6px;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">{{ __('التاريخ') }}</th>
                            <th style="width: 16%;">{{ __('الحالة') }}</th>
                            <th style="width: 25%;">{{ __('المادة') }}</th>
                            <th>{{ __('ملاحظات المعلم') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentAttendanceAlerts as $attendance)
                            <tr>
                                <td dir="ltr" style="text-align: center;">{{ $attendance->dailyLog?->date?->format('Y-m-d') ?? '—' }}</td>
                                <td style="text-align: center;">
                                    <span class="badge {{ $attendance->status?->value === 'absent' ? 'badge-danger' : 'badge-warning' }}" style="font-weight: 700;">
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
            <div style="font-size: 8.5pt; color: #065f46; background: #f0fdf4; border: 1px dashed #86efac; padding: 3px 8px; margin-top: 4px; text-align: center; border-radius: 2px;">
                {{ __('لا توجد غيابات أو تأخرات مسجلة') }}
            </div>
        @endif
    </section>

    <!-- ── ثالثاً: النتائج والتقييمات حسب المادة ── -->
    <section class="report-section" style="margin-top: 8px;">
        <div class="section-heading" style="margin-bottom: 6px;">
            <h2 class="section-title" style="font-size: 11pt;">{{ __('النتائج والتقييمات حسب المادة') }}</h2>
            <span class="badge badge-gray" style="background: #1a237e; color: #fff; font-size: 7.5pt; padding: 2px 6px; border-radius: 2px;">
                {{ __('العلامات على 20') }}
            </span>
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
                                <th style="width: 32%;">{{ __('التقييم') }}</th>
                                <th style="width: 18%; text-align: center;">{{ __('النوع') }}</th>
                                <th style="width: 16%; text-align: center;">{{ __('التاريخ') }}</th>
                                <th style="width: 14%; text-align: center;">{{ __('العلامة') }}</th>
                                <th>{{ __('ملاحظات المعلم') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subjectGroup['assessments'] as $assessmentRow)
                                <tr>
                                    <td><strong>{{ $assessmentRow['assessment']->title }}</strong></td>
                                    <td style="text-align: center;">{{ $assessmentRow['assessment']->type?->label() ?? '—' }}</td>
                                    <td dir="ltr" style="text-align: center;">{{ $assessmentRow['assessment']->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td style="text-align: center;">
                                        <strong>
                                            {{ $assessmentRow['grade']?->score ?? '—' }} / {{ $assessmentRow['assessment']->max_score }}
                                        </strong>
                                    </td>
                                    <td>{{ $assessmentRow['grade']?->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted" style="font-size: 8.5pt; color: #64748b; padding: 4px;">{{ __('لا توجد تقييمات مسجلة') }}</p>
        @endforelse
    </section>

    <!-- ── رابعاً: السجل السلوكي والانضباطي ── -->
    <section class="report-section" style="margin-top: 8px;">
        <div class="section-heading" style="margin-bottom: 6px;">
            <h2 class="section-title" style="font-size: 11pt;">{{ __('السجل السلوكي والانضباطي') }}</h2>
            <span class="badge badge-info" style="background: #1a237e; color: #fff; font-size: 7.5pt; padding: 2px 6px; border-radius: 2px;">
                {{ $observations->count() }} {{ __('ملاحظة') }}
            </span>
        </div>

        @if ($observations->isNotEmpty())
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: center;">{{ __('التاريخ') }}</th>
                            <th style="width: 15%; text-align: center;">{{ __('النوع') }}</th>
                            <th style="width: 25%;">{{ __('الملاحظة') }}</th>
                            <th style="width: 18%;">{{ __('المادة') }}</th>
                            <th style="width: 12%; text-align: center;">{{ __('المتابعة') }}</th>
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
                                <td dir="ltr" style="text-align: center;">{{ $observation->date?->format('Y-m-d') }}</td>
                                <td style="text-align: center;">
                                    <span class="badge {{ $observation->category === 'positive' ? 'badge-good' : 'badge-warning' }}" style="font-weight: 700;">
                                        {{ __($behaviorLabels[$observation->category] ?? 'ملاحظة') }}
                                    </span>
                                </td>
                                <td>{{ $observation->notes }}</td>
                                <td>{{ $observation->schedule?->subject?->name ?? '—' }}</td>
                                <td style="text-align: center;">{{ $observation->is_followed_up ? __('تمت المتابعة') : __('قيد المتابعة') }}</td>
                                <td>{{ $observation->follow_up_notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="font-size: 8.5pt; color: #065f46; background: #f0fdf4; border: 1px dashed #86efac; padding: 3px 8px; text-align: center; border-radius: 2px;">
                {{ __('لا توجد ملاحظات سلوكية مسجلة') }}
            </div>
        @endif
    </section>

    <!-- ── خامساً: حصص المعالجة البيداغوجية ── -->
    <section class="report-section" style="margin-top: 8px;">
        <div class="section-heading" style="margin-bottom: 6px;">
            <h2 class="section-title" style="font-size: 11pt;">{{ __('حصص المعالجة البيداغوجية') }}</h2>
            <span class="badge badge-sky" style="background: #0369a1; color: #fff; font-size: 7.5pt; padding: 2px 6px; border-radius: 2px;">
                {{ $remediations->count() }} {{ __('حصة') }}
            </span>
        </div>

        @if ($remediations->isNotEmpty())
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 14%; text-align: center;">{{ __('التاريخ') }}</th>
                            <th style="width: 16%;">{{ __('المادة') }}</th>
                            <th style="width: 22%;">{{ __('سبب المعالجة') }}</th>
                            <th style="width: 20%;">{{ __('الأنشطة') }}</th>
                            <th style="width: 10%; text-align: center;">{{ __('الحضور') }}</th>
                            <th style="width: 10%; text-align: center;">{{ __('الحالة') }}</th>
                            <th>{{ __('نتيجة المتابعة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($remediations as $remediation)
                            <tr>
                                <td dir="ltr" style="text-align: center;">{{ $remediation->session_date?->format('Y-m-d') }}</td>
                                <td>{{ $remediation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $remediation->reason }}</td>
                                <td>{{ $remediation->activities }}</td>
                                <td style="text-align: center;">
                                    <span class="badge {{ $remediation->attended ? 'badge-good' : 'badge-danger' }}" style="font-weight: 700;">
                                        {{ $remediation->attended ? __('حضر') : __('لم يحضر') }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge" style="font-size: 7.5pt;">
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
            <div style="font-size: 8.5pt; color: #065f46; background: #f0fdf4; border: 1px dashed #86efac; padding: 3px 8px; text-align: center; border-radius: 2px;">
                {{ __('لا توجد حصص معالجة مسجلة') }}
            </div>
        @endif
    </section>

    <!-- ── سادساً: التأشيرات والمصادقات الرسمية ── -->
    <footer class="signature-grid">
        <div class="signature-box">
            <strong>{{ __('إمضاء الأستاذ(ة)') }}</strong>
            <span>{{ __('الأستاذ المسؤول عن المتابعة البيداغوجية') }}</span>
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

    <div style="margin-top: 8px; padding-top: 4px; border-top: 1px solid #cbd5e1; display: flex; justify-content: space-between; font-size: 7.5pt; color: #64748b;">
        <div>حرر بـ: <strong>{{ $schoolSetting->municipality ?: 'أم الطيور' }}</strong> في: <strong>{{ $generatedAt->translatedFormat('d F Y') }}</strong></div>
        <div>{{ __('وثيقة رسمية صادرة عن منظومة سَجِّل الرقمية') }} · {{ $schoolSetting->school_name }} · {{ $schoolSetting->academic_year }}</div>
        <div dir="ltr">REF: SAJEL-{{ $studentIdentity['registration_number'] ?: 'STD' }}</div>
    </div>
</main>
</body>
</html>
