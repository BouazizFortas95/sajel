<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ') }} — {{ $student->full_name }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        body { font-family: Arial, sans-serif; color: #0f172a; background: #fff; }
        .report-card { max-width: 190mm; margin: 0 auto; }
        .report-section { break-inside: avoid; }
        .print-muted { color: #475569; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: start; }
        .report-table th { background: #f1f5f9; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-white text-slate-900">
    <main class="report-card mx-auto space-y-5" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <header class="rounded-2xl border-2 border-amber-500 bg-amber-50 p-5 text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-800">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
            <p class="mt-1 text-xs font-semibold text-slate-700">{{ __('وزارة التربية الوطنية') }}</p>
            <h1 class="mt-2 text-2xl font-black text-slate-950">{{ __('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ') }}</h1>
            <p class="mt-1 text-sm font-semibold text-slate-700">{{ __('للاجتماع مع الولي') }}</p>
        </header>

        <section class="report-section rounded-xl border border-slate-300 p-4">
            <h2 class="mb-3 text-base font-extrabold">{{ __('معلومات التلميذ') }}</h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
                <p><strong>{{ __('اسم التلميذ') }}:</strong> {{ $studentIdentity['full_name'] }}</p>
                <p><strong>{{ __('رقم التسجيل') }}:</strong> {{ $studentIdentity['registration_number'] ?: '—' }}</p>
                <p><strong>{{ __('تاريخ الميلاد') }}:</strong> {{ $studentIdentity['date_of_birth']?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>{{ __('المستوى') }}:</strong> {{ $studentIdentity['level'] ?? '—' }}</p>
                <p><strong>{{ __('القسم') }}:</strong> {{ $studentIdentity['classroom'] ?? '—' }}</p>
                <p><strong>{{ __('الفوج') }}:</strong> {{ $studentIdentity['group'] ?? '—' }}</p>
                <p><strong>{{ __('السنة الدراسية') }}:</strong> {{ $studentIdentity['academic_year'] }}</p>
                <p><strong>{{ __('تاريخ الإصدار') }}:</strong> {{ $generatedAt->format('Y-m-d') }}</p>
            </div>
        </section>

        <section class="report-section rounded-xl border border-slate-300 p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-extrabold">{{ __('ملخص الحضور والغياب') }}</h2>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-extrabold text-emerald-900">
                    {{ __('نسبة الحضور') }}: {{ number_format($attendanceRate, 1) }}%
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ([
                    'present' => ['حاضر', 'bg-emerald-50 text-emerald-900'],
                    'absent' => ['غائب', 'bg-rose-50 text-rose-900'],
                    'late' => ['متأخر', 'bg-amber-50 text-amber-900'],
                    'excused' => ['معفى', 'bg-sky-50 text-sky-900'],
                ] as $status => [$label, $classes])
                    <div class="rounded-lg px-3 py-2 text-center {{ $classes }}">
                        <p class="text-xs font-bold">{{ __($label) }}</p>
                        <p class="text-xl font-black">{{ $attendanceCounts[$status] }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs print-muted">{{ __('عدد حصص الحضور المسجلة') }}: {{ $attendanceTotal }}</p>
            <h3 class="mt-4 mb-2 text-sm font-bold">{{ __('آخر الغيابات والتأخرات') }}</h3>
            @if ($recentAttendanceAlerts->isNotEmpty())
                <table class="report-table text-xs">
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
                                <td>{{ $attendance->dailyLog?->date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $attendance->status?->label() ?? '—' }}</td>
                                <td>{{ $attendance->dailyLog?->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $attendance->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-xs print-muted">{{ __('لا توجد غيابات أو تأخرات مسجلة') }}</p>
            @endif
        </section>

        <section class="report-section rounded-xl border border-slate-300 p-4">
            <h2 class="mb-3 text-base font-extrabold">{{ __('النتائج والتقييمات حسب المادة') }}</h2>
            @forelse ($gradesBySubject as $subjectGroup)
                <div class="mb-4 last:mb-0">
                    <h3 class="mb-2 font-bold text-amber-900">{{ $subjectGroup['subject']?->name ?? '—' }}</h3>
                    <p class="mb-2 text-xs font-bold">{{ __('معدل المادة على 20') }}: {{ $subjectGroup['average'] === null ? '—' : number_format($subjectGroup['average'], 2) }}</p>
                    <table class="report-table text-xs">
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
                                    <td>{{ $assessmentRow['assessment']->title }}</td>
                                    <td>{{ $assessmentRow['assessment']->type?->label() ?? '—' }}</td>
                                    <td>{{ $assessmentRow['assessment']->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $assessmentRow['grade']?->score ?? '—' }} / {{ $assessmentRow['assessment']->max_score }}</td>
                                    <td>{{ $assessmentRow['grade']?->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-sm print-muted">{{ __('لا توجد تقييمات مسجلة') }}</p>
            @endforelse
        </section>

        <section class="report-section rounded-xl border border-slate-300 p-4">
            <h2 class="mb-3 text-base font-extrabold">{{ __('السجل السلوكي والانضباطي') }}</h2>
            @forelse ($observations as $observation)
                <article class="mb-2 rounded-lg border border-slate-200 p-3 last:mb-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-bold">{{ __(match ($observation->category) {
                            'positive' => 'إيجابي',
                            'negative' => 'سلبي',
                            'disciplinary' => 'انضباطي',
                            default => 'ملاحظة',
                        }) }}</span>
                        <span class="print-muted">{{ $observation->date?->format('Y-m-d') }}</span>
                        <span>{{ $observation->is_followed_up ? __('تمت المتابعة') : __('قيد المتابعة') }}</span>
                        @if ($observation->schedule?->subject)
                            <span>{{ $observation->schedule->subject->name }}</span>
                        @endif
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $observation->notes }}</p>
                    @if ($observation->follow_up_notes)
                        <p class="mt-1 text-xs print-muted">{{ __('ملاحظات المتابعة') }}: {{ $observation->follow_up_notes }}</p>
                    @endif
                </article>
            @empty
                <p class="text-sm print-muted">{{ __('لا توجد ملاحظات سلوكية مسجلة') }}</p>
            @endforelse
        </section>

        <section class="report-section rounded-xl border border-slate-300 p-4">
            <h2 class="mb-3 text-base font-extrabold">{{ __('حصص المعالجة البيداغوجية') }}</h2>
            @if ($remediations->isNotEmpty())
                <table class="report-table text-xs">
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
                                <td>{{ $remediation->session_date?->format('Y-m-d') }}</td>
                                <td>{{ $remediation->schedule?->subject?->name ?? '—' }}</td>
                                <td>{{ $remediation->reason }}</td>
                                <td>{{ $remediation->activities }}</td>
                                <td>{{ $remediation->attended ? __('حضر') : __('لم يحضر') }}</td>
                                <td>{{ __(match ($remediation->status) {
                                    'completed' => 'منجز',
                                    'ongoing' => 'مستمر',
                                    default => 'مخطط',
                                }) }}</td>
                                <td>{{ $remediation->follow_up ?: __('لا توجد متابعة مسجلة') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm print-muted">{{ __('لا توجد حصص معالجة مسجلة') }}</p>
            @endif
        </section>

        <footer class="report-section grid grid-cols-3 gap-6 pt-5 text-center text-sm">
            <div>
                <p class="font-bold">{{ __('إمضاء الأستاذ(ة)') }}</p>
                <div class="mt-12 border-t border-dotted border-slate-500"></div>
            </div>
            <div>
                <p class="font-bold">{{ __('تأشيرة الإدارة') }}</p>
                <div class="mt-12 border-t border-dotted border-slate-500"></div>
            </div>
            <div>
                <p class="font-bold">{{ __('إطلاع وتوقيع ولي الأمر') }}</p>
                <div class="mt-12 border-t border-dotted border-slate-500"></div>
            </div>
        </footer>

        <p class="text-center text-[10px] print-muted">{{ __('وثيقة سرية للاستخدام في المتابعة التربوية') }}</p>
    </main>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => window.setTimeout(() => window.print(), 300));
        </script>
    @endif
</body>
</html>
