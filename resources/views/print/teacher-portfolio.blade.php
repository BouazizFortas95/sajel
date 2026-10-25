@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('ملف إنجاز الأستاذ'),
    'printSubtitle' => $teacher->name.' — '.($teacher->primarySubject?->name ?? __('الأستاذ(ة)')),
    'printAcademicYear' => $schoolSetting->academic_year,
    'printReference' => __('التقرير المهني'),
    'printMeta' => $generatedAt->format('Y-m-d'),
    'printAutoPrint' => $autoPrint ?? false,
])

    <section class="identity-card bg-amber-50/70 border border-amber-200 rounded-2xl p-4">
        <div class="section-heading">
            <h2 class="section-title">{{ __('بطاقة الهوية المهنية') }}</h2>
            <span class="badge badge-primary">{{ __('السنة الدراسية') }}: {{ $schoolSetting->academic_year }}</span>
        </div>

        <div class="identity-grid">
            <div class="identity-item">
                <p class="identity-label">{{ __('الاسم الكامل') }}</p>
                <p class="identity-value">{{ $teacher->name }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المادة الأساسية') }}</p>
                <p class="identity-value">{{ $teacher->primarySubject?->name ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المادة الثانوية') }}</p>
                <p class="identity-value">{{ $teacher->secondarySubject?->name ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('عدد الساعات الأسبوعية') }}</p>
                <p class="identity-value">{{ count($schedules ?? collect()) }} {{ __('حصة') }}</p>
            </div>
            <div class="identity-item" style="grid-column: span 2;">
                <p class="identity-label">{{ __('الأقسام الموكلة') }}</p>
                <p class="identity-value">{{ collect($schedules ?? collect())->map(fn ($schedule) => $schedule->sectionName())->filter()->unique()->join('، ') ?: '—' }}</p>
            </div>
            <div class="identity-item" style="grid-column: span 2;">
                <p class="identity-label">{{ __('تاريخ الإصدار') }}</p>
                <p class="identity-value" dir="ltr">{{ $generatedAt->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('التدريس والامتثال لسجل النصوص') }}</h2>
        </div>
        <div class="kpi-strip" style="grid-template-columns: repeat(3, minmax(0, 1fr)) !important;">
            <div class="kpi-card kpi--emerald">
                <p class="kpi-card__label">{{ __('الدروس المنجزة المؤكدة') }}</p>
                <p class="kpi-card__value">{{ $completedLogs }}</p>
                <p class="kpi-card__hint">{{ __('سجل منجز') }}</p>
            </div>
            <div class="kpi-card kpi--amber">
                <p class="kpi-card__label">{{ __('السجلات المؤشرة إدارياً') }}</p>
                <p class="kpi-card__value">{{ $reviewedLogs }}</p>
                <p class="kpi-card__hint">{{ __('تأشيرة إدارية') }}</p>
            </div>
            <div class="kpi-card kpi--sky">
                <p class="kpi-card__label">{{ __('المذكرة التربوية المعتمدة') }}</p>
                <p class="kpi-card__value">{{ $memoCount }}</p>
                <p class="kpi-card__hint">{{ __('مذكرة بيداغوجية') }}</p>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="section-heading">
            <h2 class="section-title">{{ __('الندوات التربوية والاجتماعات التنسيقية') }}</h2>
            <span class="badge badge-gray">{{ $meetings->count() }} {{ __('ندوة / اجتماع') }}</span>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>{{ __('التاريخ') }}</th>
                        <th>{{ __('النوع') }}</th>
                        <th>{{ __('العنوان') }}</th>
                        <th>{{ __('المؤطر') }}</th>
                        <th>{{ __('التوصيات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($meetings as $meeting)
                        <tr>
                            <td dir="ltr">{{ $meeting->date?->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ __(match ($meeting->meeting_type ?? '') {
                                        'inspector_seminar' => 'ندوة تكوينية',
                                        'coordination_meeting' => 'اجتماع تنسيقي',
                                        'class_council' => 'مجلس القسم',
                                        default => 'اجتماع تربوي',
                                    }) }}
                                </span>
                            </td>
                            <td><strong>{{ $meeting->title }}</strong></td>
                            <td>{{ $meeting->supervisor_name ?: '—' }}</td>
                            <td>{{ $meeting->recommendations ? \Illuminate\Support\Str::limit($meeting->recommendations, 80) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 12px;">{{ __('لا توجد ندوات مسجلة') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <footer class="signature-grid">
        <div class="signature-box">
            <strong>{{ __('إمضاء الأستاذ(ة)') }}</strong>
            <span>{{ $teacher->name }}</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('إمضاء المفتش / المشرف') }}</strong>
            <span>{{ __('التاريخ') }}: ____ / ____ / ________</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>{{ __('مدير المتوسطة') }}</strong>
            <span>{{ $schoolSetting->principal_name ?: '_________________________' }}</span>
            <div class="signature-line"></div>
        </div>
    </footer>

    <p class="doc-footer">{{ __('ملف إنجاز الأستاذ — الوثيقة الرسمية') }} | {{ $schoolSetting->school_name }} | {{ $schoolSetting->academic_year }} | {{ $generatedAt->format('Y') }}</p>
</main>
</body>
</html>
