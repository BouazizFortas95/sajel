@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('تقرير زيارة وتفتيش تربوي'),
    'printSubtitle' => $report->teacher->name.' — '.($report->subject?->name ?? ''),
    'printAcademicYear' => $schoolSetting->academic_year,
    'printReference' => __('تفتيش بيداغوجي'),
    'printMeta' => $report->visit_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
    'printAutoPrint' => $autoPrint ?? false,
])

    <section class="identity-card bg-amber-50/70 border border-amber-200 rounded-2xl p-4">
        <div class="section-heading">
            <h2 class="section-title">{{ __('بطاقة الزيارة البيداغوجية') }}</h2>
            <span class="badge badge-primary">{{ __('السنة الدراسية') }}: {{ $schoolSetting->academic_year }}</span>
        </div>

        <div class="identity-grid">
            <div class="identity-item">
                <p class="identity-label">{{ __('الأستاذ(ة) المزار') }}</p>
                <p class="identity-value">{{ $report->teacher->name }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المادة التعليمية') }}</p>
                <p class="identity-value">{{ $report->subject?->name ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('القسم / الفوج التربوي') }}</p>
                <p class="identity-value">{{ $report->classroom?->name ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('المشرف على الزيارة') }}</p>
                <p class="identity-value">{{ $report->inspector_name }} ({{ $report->inspector_role_label }})</p>
            </div>
            <div class="identity-item" style="grid-column: span 2;">
                <p class="identity-label">{{ __('موضوع الحصة التعليمية / المورد المعرفي') }}</p>
                <p class="identity-value">{{ $report->lesson_topic }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('تاريخ الزيارة') }}</p>
                <p class="identity-value" dir="ltr">{{ $report->visit_date?->format('Y-m-d') }}</p>
            </div>
            <div class="identity-item">
                <p class="identity-label">{{ __('التقدير العام') }}</p>
                <p class="identity-value font-bold text-amber-700">{{ $report->appreciation_label }} ({{ $report->pedagogical_score ? number_format($report->pedagogical_score, 2).' / 20' : '—' }})</p>
            </div>
        </div>
    </section>

    @if (! empty($report->evaluation_criteria) && is_array($report->evaluation_criteria))
        <section class="report-section mt-4">
            <div class="section-heading">
                <h2 class="section-title">{{ __('شبكة التقييم والملاحظة البيداغوجية') }}</h2>
            </div>
            <table class="data-table w-full text-start text-xs border-collapse">
                <thead>
                    <tr class="bg-amber-100/60 border-b border-amber-200">
                        <th class="p-2 text-start">{{ __('المحور البيداغوجي') }}</th>
                        <th class="p-2 text-center" style="width: 100px;">{{ __('النقطة') }}</th>
                        <th class="p-2 text-start">{{ __('الملاحظات والتوجيهات الميدانية') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $axes = [
                            'planning' => __('التحضير والتخطيط البيداغوجي (المذكرة والوثائق)'),
                            'management' => __('إدارة الصف والتفاعل البيداغوجي (التعلم النشط)'),
                            'resources' => __('استثمار الوسائل التعليمية والمخابر والسبورة'),
                            'assessment' => __('المتابعة والتقويم التكويني والتشخيصي'),
                        ];
                    @endphp
                    @foreach ($axes as $key => $title)
                        @php
                            $crit = $report->evaluation_criteria[$key] ?? null;
                            $score = $crit['score'] ?? null;
                            $max = $crit['max'] ?? 5.0;
                            $notes = $crit['notes'] ?? '—';
                        @endphp
                        <tr class="border-b border-gray-200">
                            <td class="p-2 font-medium">{{ $title }}</td>
                            <td class="p-2 text-center font-bold" dir="ltr">
                                {{ $score !== null ? number_format($score, 1) : '—' }} / {{ number_format($max, 1) }}
                            </td>
                            <td class="p-2 text-gray-700">{{ $notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="report-section mt-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3">
                <h3 class="font-bold text-emerald-900 text-sm mb-1 flex items-center gap-1">
                    <span>✓</span> {{ __('نقاط القوة والملاحظات الإيجابية') }}
                </h3>
                <p class="text-xs text-emerald-950 leading-relaxed">{{ $report->strengths ?: __('لم تسجل ملاحظات خاصة.') }}</p>
            </div>
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3">
                <h3 class="font-bold text-blue-900 text-sm mb-1 flex items-center gap-1">
                    <span>⚡</span> {{ __('التوجيهات ومجالات التحسين البيداغوجي') }}
                </h3>
                <p class="text-xs text-blue-950 leading-relaxed">{{ $report->areas_for_improvement ?: __('لم تسجل توجيهات خاصة.') }}</p>
            </div>
        </div>
    </section>

    <div class="signature-strip mt-6 pt-4 border-t border-gray-300 grid grid-cols-3 gap-6 text-center text-xs">
        <div class="signature-box border border-dashed border-gray-300 rounded-xl p-3 h-28 flex flex-col justify-between">
            <p class="font-semibold text-gray-700">{{ __('المشرف على الزيارة') }}</p>
            <p class="text-[10px] text-gray-500">{{ $report->inspector_name }}</p>
        </div>
        <div class="signature-box border border-dashed border-gray-300 rounded-xl p-3 h-28 flex flex-col justify-between">
            <p class="font-semibold text-gray-700">{{ __('الأستاذ(ة) المعني(ة)') }}</p>
            <p class="text-[10px] text-gray-500">{{ __('بالعلم والاطلاع') }}</p>
        </div>
        <div class="signature-box border border-dashed border-gray-300 rounded-xl p-3 h-28 flex flex-col justify-between">
            <p class="font-semibold text-gray-700">{{ __('مدير المتوسطة') }}</p>
            <p class="text-[10px] text-gray-500">{{ $schoolSetting->principal_name ?: __('الختم والتأشيرة') }}</p>
        </div>
    </div>
</body>
</html>

