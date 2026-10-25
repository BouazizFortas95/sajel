@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('مجلس القسم — محضر المداولات'),
    'printSubtitle' => trim(($classroom->level?->name ?? '').' — '.$classroom->name.' — '.($group?->name ?? __('القسم كاملاً'))),
    'printOrientation' => 'landscape',
    'printAcademicYear' => $schoolSetting->academic_year ?: $classroom->academic_year,
    'printReference' => __('التقرير البيداغوجي'),
    'printMeta' => $generatedAt->format('Y-m-d H:i'),
    'printAutoPrint' => $autoPrint ?? true,
])

    <section class="report-section" style="margin-top: 10px;">
        <div class="kpi-strip council-kpis" style="grid-template-columns: repeat(7, minmax(0, 1fr)) !important;">
            <div class="kpi-card kpi--sky">
                <p class="kpi-card__label">{{ __('إجمالي التلاميذ') }}</p>
                <p class="kpi-card__value">{{ $kpis['total_students'] }}</p>
                <p class="kpi-card__hint">{{ __('تلميذ في القسم') }}</p>
            </div>
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('المعدل العام للقسم') }}/20</p>
                <p class="kpi-card__value">{{ $kpis['class_general_average'] !== null ? number_format($kpis['class_general_average'], 2) : '—' }}</p>
                <p class="kpi-card__hint">{{ $kpis['graded_students'] }} {{ __('مرصود') }}</p>
            </div>
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('نسبة النجاح') }}</p>
                <p class="kpi-card__value">{{ number_format($kpis['pass_rate'], 1) }}%</p>
                <p class="kpi-card__hint">{{ __('من المرصودين') }}</p>
            </div>
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('أعلى معدل') }}</p>
                <p class="kpi-card__value">{{ $kpis['highest_average'] !== null ? number_format($kpis['highest_average'], 2) : '—' }}</p>
                <p class="kpi-card__hint">/20</p>
            </div>
            <div class="kpi-card kpi--rose">
                <p class="kpi-card__label">{{ __('أدنى معدل') }}</p>
                <p class="kpi-card__value">{{ $kpis['lowest_average'] !== null ? number_format($kpis['lowest_average'], 2) : '—' }}</p>
                <p class="kpi-card__hint">/20</p>
            </div>
            <div class="kpi-card kpi--amber">
                <p class="kpi-card__label">{{ __('لوحة الشرف') }}</p>
                <p class="kpi-card__value">{{ $kpis['honor_roll_count'] }}</p>
                <p class="kpi-card__hint">{{ __('معدل 14 أو أكثر') }}</p>
            </div>
            <div class="kpi-card kpi--sky">
                <p class="kpi-card__label">{{ __('إجمالي الغيابات') }}</p>
                <p class="kpi-card__value">{{ $kpis['total_absences'] }}</p>
                <p class="kpi-card__hint">{{ $kpis['total_lates'] }} {{ __('تأخر') }}</p>
            </div>
        </div>

        <div class="honor-strip">
            <span class="badge badge-success">{{ __('class_council.recommendation.excellence') }}: {{ $honorDistribution['excellence'] }}</span>
            <span class="badge badge-info">{{ __('class_council.recommendation.encouragement') }}: {{ $honorDistribution['encouragement'] }}</span>
            <span class="badge badge-primary">{{ __('class_council.recommendation.honor_roll') }}: {{ $honorDistribution['honor_roll'] }}</span>
            <span class="badge badge-gray">{{ __('class_council.recommendation.acceptable') }}: {{ $honorDistribution['acceptable'] }}</span>
            <span class="badge badge-danger">{{ __('class_council.recommendation.warning') }}: {{ $honorDistribution['warning'] }}</span>
            <span class="badge badge-gray">{{ __('class_council.recommendation.pending') }}: {{ $honorDistribution['pending'] }}</span>
        </div>
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('مداولات القسم') }}</h2>
            <span class="badge badge-gray">{{ __('آخر تحديث') }}: {{ $generatedAt->format('Y-m-d H:i') }}</span>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 2%;">#</th>
                        <th>{{ __('التلميذ(ة)') }}</th>
                        <th>{{ __('الفوج') }}</th>
                        <th>{{ __('المعدل العام') }}</th>
                        @foreach ($subjects as $subject)
                            <th>{{ $subject->name }}</th>
                        @endforeach
                        <th>{{ __('الحضور') }}</th>
                        <th>{{ __('السلوك') }}</th>
                        <th>{{ __('التوصية') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($studentDeliberations as $del)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $del['full_name'] }}</strong>
                                <div class="muted" style="font-size: 8px;" dir="ltr">{{ $del['registration_number'] }}</div>
                            </td>
                            <td>{{ $del['group'] ?? '—' }}</td>
                            <td>
                                @if ($del['generalAverage'] === null)
                                    <span class="badge badge-gray">{{ __('لم تُرصد بعد') }}</span>
                                @else
                                    <span class="badge {{ $del['generalAverage'] >= 10 ? 'badge-success' : 'badge-danger' }}">
                                        {{ number_format($del['generalAverage'], 2) }} / 20
                                    </span>
                                @endif
                            </td>
                            @foreach ($subjects as $subject)
                                @php
                                    $subjectAverage = collect($del['perSubjectAverages'])->firstWhere('subject_id', $subject->id);
                                @endphp
                                <td>
                                    @if ($subjectAverage)
                                        <span class="badge {{ $subjectAverage['average'] >= 10 ? 'badge-success' : 'badge-danger' }}">
                                            {{ number_format($subjectAverage['average'], 2) }}
                                        </span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="font-size: 8px;">
                                <span class="badge badge-success">{{ __('حاضر') }}: {{ $del['attendanceCounts']['present'] }}</span>
                                <span class="badge badge-danger">{{ __('غائب') }}: {{ $del['attendanceCounts']['absent'] }}</span>
                                <span class="badge badge-warning">{{ __('متأخر') }}: {{ $del['attendanceCounts']['late'] }}</span>
                            </td>
                            <td style="font-size: 8px;">
                                <span class="badge badge-success">+{{ $del['behaviorCounts']['positive'] }}</span>
                                <span class="badge badge-danger">−{{ $del['behaviorCounts']['negative'] }}</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $del['recommendation_color'] ?? 'gray' }}">
                                    {{ $del['recommendation'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 8 + $subjects->count() }}" class="text-center" style="padding: 12px;">
                                {{ __('لا توجد بيانات للتلاميذ في هذا القسم') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <footer class="signature-grid">
        <div class="signature-box">
            <strong>{{ __('إمضاء أساتذة القسم') }}</strong>
            <span>{{ now()->translatedFormat('d M Y') }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة مستشار التربية') }}</strong>
            <span>{{ $schoolSetting->counselor_name ?: '_________________________' }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('ختم وإمضاء مدير المتوسطة') }}</strong>
            <span>{{ $schoolSetting->principal_name ?: '_________________________' }}</span>
            <div class="signature-line"></div>
        </div>
    </footer>

    <p class="doc-footer">{{ __('مجلس القسم — الوثيقة الرسمية') }} | {{ $schoolSetting->school_name }} | {{ $schoolSetting->academic_year ?: $classroom->academic_year }} | {{ __('صادرة عن') }} Sajel</p>
</main>
</body>
</html>
