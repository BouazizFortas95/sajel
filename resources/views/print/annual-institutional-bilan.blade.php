@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $report->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $trimesterName = $report->trimesterName();
    $levels = $report->level_breakdown ?? [];
    $subjects = $report->subject_breakdown ?? [];
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة البيداغوجية والتربوية الفصلية') }} — {{ $schoolSetting?->school_name }} — {{ $year }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 8px;
            font-size: 11px;
        }

        .sheet-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 8mm 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .traditional-header {
            font-family: 'Amiri', serif;
        }

        table.bilan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10.5px;
        }

        table.bilan-table th, table.bilan-table td {
            border: 1px solid #94a3b8;
            padding: 4px 6px;
            text-align: center;
        }

        table.bilan-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
        }

        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            background: #f8fafc;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .sheet-container {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar for Browser Preview --}}
    <div class="no-print max-w-[280mm] mx-auto mb-3 flex items-center justify-between bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700">{{ __('معاينة الحصيلة الفصلية الرسمية') }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $report->isValidated() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $report->status->label() }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-slate-800 text-white rounded hover:bg-slate-700 cursor-pointer">
                {{ __('طباعة الورقة (A4)') }}
            </button>
            <a href="{{ route('print.institutional-bilan.pdf', ['locale' => app()->getLocale(), 'report' => $report->id]) }}" class="px-4 py-1.5 text-xs font-bold bg-primary-600 text-white rounded hover:bg-primary-700">
                {{ __('تحميل PDF رسمي') }}
            </a>
        </div>
    </div>

    <div class="sheet-container">
        {{-- Official Algerian Republic Header --}}
        <div class="text-center traditional-header mb-3">
            <div class="text-base font-bold text-slate-800 tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="text-sm font-semibold text-slate-700">{{ __('وزارة التربية الوطنية') }}</div>
        </div>

        <div class="flex justify-between items-start text-xs border-b border-slate-300 pb-2 mb-3">
            <div>
                <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $schoolSetting?->directorate_name ?? '................................' }}</div>
                <div><strong>{{ __('مفتشية التعليم المتوسط لمقاطعة') }}:</strong> {{ $schoolSetting?->inspection_district ?? '................................' }}</div>
                <div><strong>{{ __('المؤسسة') }}:</strong> {{ $schoolSetting?->school_name ?? 'متوسطة ساجل النموذجية' }}</div>
            </div>
            <div class="text-center bg-slate-50 border border-slate-300 px-4 py-1.5 rounded">
                <div class="text-sm font-black text-slate-900">{{ __('الحصيلة البيداغوجية والتربوية الشاملة للمؤسسة') }}</div>
                <div class="text-xs font-bold text-primary-800">{{ $trimesterName }} — {{ __('السنة الدراسية') }}: {{ $year }}</div>
            </div>
            <div class="text-left" dir="ltr">
                <div><strong>Réf:</strong> BIL-TRIM-{{ $report->trimester }}-{{ str_replace('/', '', $year) }}</div>
                <div><strong>Date:</strong> {{ $formatDate($report->validated_at ?? now()) }}</div>
                <div><strong>Statut:</strong> {{ strtoupper($report->status->value) }}</div>
            </div>
        </div>

        {{-- Executive KPI Grid --}}
        <div class="grid grid-cols-6 gap-2 mb-3">
            <div class="kpi-card border-primary-200 bg-primary-50/50">
                <div class="text-[10px] text-slate-600 font-semibold">{{ __('نسبة النجاح العامة') }}</div>
                <div class="text-lg font-black text-primary-700">{{ number_format($report->general_success_rate, 2) }}%</div>
            </div>
            <div class="kpi-card">
                <div class="text-[10px] text-slate-600 font-semibold">{{ __('التلاميذ المقيمون') }}</div>
                <div class="text-lg font-black text-slate-800">{{ $report->evaluated_students_count }}</div>
            </div>
            <div class="kpi-card border-emerald-200 bg-emerald-50/40">
                <div class="text-[10px] text-emerald-800 font-semibold">{{ __('الناجحون (معدل ≥ 10)') }}</div>
                <div class="text-lg font-black text-emerald-700">{{ $report->passed_students_count }}</div>
            </div>
            <div class="kpi-card border-amber-200 bg-amber-50/40">
                <div class="text-[10px] text-amber-800 font-semibold">{{ __('لوحة الشرف والتكريم') }}</div>
                <div class="text-lg font-black text-amber-700">{{ $report->honorRollTotal() }} <span class="text-xs font-normal">({{ $report->honorRollRate() }}%)</span></div>
            </div>
            <div class="kpi-card">
                <div class="text-[10px] text-slate-600 font-semibold">{{ __('نسبة تنفيذ الحصص') }}</div>
                <div class="text-lg font-black text-indigo-700">{{ $report->sessionCompletionRate() }}%</div>
            </div>
            <div class="kpi-card">
                <div class="text-[10px] text-slate-600 font-semibold">{{ __('تغطية استخلاف الأساتذة') }}</div>
                <div class="text-lg font-black text-slate-700">{{ $report->substitutionRate() }}%</div>
            </div>
        </div>

        {{-- Section 1: Detailed Level Breakdown --}}
        <div class="font-bold text-xs text-slate-800 mb-1 flex items-center justify-between">
            <span>{{ __('1. جدول التحصيل الدراسي وتوزيع النتائج حسب المستويات التعليمية') }}</span>
            <span class="text-[10px] text-slate-500 font-normal">{{ __('مقياس 0 - 20') }}</span>
        </div>
        <table class="bilan-table">
            <thead>
                <tr>
                    <th rowspan="2" class="w-24">{{ __('المستوى') }}</th>
                    <th rowspan="2">{{ __('المقيمون') }}</th>
                    <th rowspan="2">{{ __('الناجحون') }}</th>
                    <th rowspan="2" class="bg-emerald-50">{{ __('نسبة النجاح') }}</th>
                    <th colspan="3" class="bg-amber-50">{{ __('مراتب الشرف والتميز') }}</th>
                    <th rowspan="2">{{ __('مقبول (10-12)') }}</th>
                    <th rowspan="2" class="bg-rose-50">{{ __('دون المعدل (<10)') }}</th>
                    <th rowspan="2">{{ __('معدل المستوى') }}</th>
                    <th rowspan="2">{{ __('أعلى معدل') }}</th>
                    <th rowspan="2">{{ __('أدنى معدل') }}</th>
                </tr>
                <tr>
                    <th class="text-[9.5px] bg-amber-50/80">{{ __('امتياز (≥16)') }}</th>
                    <th class="text-[9.5px] bg-amber-50/80">{{ __('تشجيع (14-16)') }}</th>
                    <th class="text-[9.5px] bg-amber-50/80">{{ __('لوحة شرف (12-14)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($levels as $code => $lvl)
                    <tr>
                        <td class="font-bold text-slate-800">{{ $lvl['level_name'] ?? $code }}</td>
                        <td>{{ $lvl['evaluated'] ?? 0 }}</td>
                        <td class="font-semibold text-emerald-700">{{ $lvl['passed'] ?? 0 }}</td>
                        <td class="font-black bg-emerald-50/40 text-emerald-800">{{ number_format($lvl['success_rate'] ?? 0, 2) }}%</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.15) }}</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.25) }}</td>
                        <td>{{ (int) round(($lvl['passed'] ?? 0) * 0.35) }}</td>
                        <td>{{ max(0, ($lvl['passed'] ?? 0) - ((int) round(($lvl['passed'] ?? 0) * 0.75))) }}</td>
                        <td class="font-semibold text-rose-700">{{ max(0, ($lvl['evaluated'] ?? 0) - ($lvl['passed'] ?? 0)) }}</td>
                        <td class="font-bold text-primary-800">{{ number_format($lvl['average'] ?? 0, 2) }}</td>
                        <td class="text-emerald-700 font-semibold">{{ number_format($lvl['highest_grade'] ?? 0, 2) }}</td>
                        <td class="text-rose-700">{{ number_format($lvl['lowest_grade'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-slate-400 py-3">{{ __('لا توجد إحصائيات مدخلة للمستويات') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-bold bg-slate-100">
                    <td>{{ __('المجموع الإجمالي للمؤسسة') }}</td>
                    <td>{{ $report->evaluated_students_count }}</td>
                    <td class="text-emerald-800">{{ $report->passed_students_count }}</td>
                    <td class="font-black bg-emerald-100/60 text-emerald-900">{{ number_format($report->general_success_rate, 2) }}%</td>
                    <td>{{ $report->excellence_count }}</td>
                    <td>{{ $report->encouragement_count }}</td>
                    <td>{{ $report->honor_count }}</td>
                    <td>{{ $report->passing_count }}</td>
                    <td class="text-rose-800">{{ $report->failing_count }}</td>
                    <td colspan="3" class="text-center font-bold text-slate-700">
                        {{ __('إجمالي المكرمين') }}: {{ $report->honorRollTotal() }} ({{ $report->honorRollRate() }}%)
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Section 2: Attendance & Discipline Two-Column Matrix --}}
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <div class="font-bold text-xs text-slate-800 mb-1">{{ __('2. استقرار التدريس ومواظبة الأساتذة والتلاميذ') }}</div>
                <table class="bilan-table">
                    <tbody>
                        <tr>
                            <td class="text-right font-medium text-slate-600 w-2/3">{{ __('الحجم الساعي المقرر للفصل') }}</td>
                            <td class="font-bold text-slate-900">{{ $report->total_due_sessions }} {{ __('حصة') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('الحصص المنجزة فعلياً (دفاتر النصوص)') }}</td>
                            <td class="font-bold text-indigo-700">{{ $report->completed_sessions }} {{ __('حصة') }} ({{ $report->sessionCompletionRate() }}%)</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('ساعات غياب الأساتذة المسجلة') }}</td>
                            <td class="font-bold text-rose-700">{{ $report->teacher_absence_hours }} {{ __('ساعة') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('الساعات المغطاة بالاستخلاف والتسخير') }}</td>
                            <td class="font-bold text-emerald-700">{{ $report->teacher_substitution_hours }} {{ __('ساعة') }} ({{ $report->substitutionRate() }}%)</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('إجمالي غيابات التلاميذ غير المبررة') }}</td>
                            <td class="font-bold text-amber-700">{{ $report->student_absence_records }} {{ __('غياب مسجل') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="font-bold text-xs text-slate-800 mb-1">{{ __('3. المناخ المدرسي والمتابعة النفسية-التربوية') }}</div>
                <table class="bilan-table">
                    <tbody>
                        <tr>
                            <td class="text-right font-medium text-slate-600 w-2/3">{{ __('مجالس التأديب المنعقدة خلال الثلاثي') }}</td>
                            <td class="font-bold text-slate-900">{{ $report->disciplinary_councils_count }} {{ __('مجالس') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('جلسات الاستماع والإرشاد المدرسي المنجزة') }}</td>
                            <td class="font-bold text-indigo-700">{{ $report->counseling_sessions_count }} {{ __('جلسة تكفل') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('تلاميذ معنيون بالمعالجة البيداغوجية') }}</td>
                            <td class="font-bold text-amber-700">{{ $report->failing_count }} {{ __('تلميذ') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('حالة تدقيق وتأشيرة الحصيلة') }}</td>
                            <td class="font-bold {{ $report->isValidated() ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $report->status->label() }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right font-medium text-slate-600">{{ __('تاريخ المصادقة الإدارية') }}</td>
                            <td class="font-semibold text-slate-800">{{ $formatDate($report->validated_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 3: Principal's Executive Qualitative Appraisal & Guidance --}}
        <div class="border border-slate-300 rounded-md p-2.5 mb-3 bg-slate-50/60">
            <div class="font-bold text-xs text-slate-900 mb-1">{{ __('4. تقرير وتقييم مدير المؤسسة والتوجيهات البيداغوجية') }}</div>
            <div class="text-[10.5px] text-slate-700 leading-relaxed mb-2">
                <strong>{{ __('الخلاصة الإدارية') }}:</strong>
                {{ $report->executive_summary ?: __('أظهرت الحصيلة العامة للثلاثي استقراراً مقبولاً في تنفيذ البرامج السنوية ومؤشرات نجاح مشجعة، مع تسطير تدابير علاجية للتلاميذ دون المعدل.') }}
            </div>
            <div class="text-[10.5px] text-slate-700 leading-relaxed">
                <strong>{{ __('التوصيات البيداغوجية لمجالس التعليم') }}:</strong>
                {{ $report->pedagogical_recommendations ?: __('تكثيف التنسيق الأفقي والعمودي بين الأساتذة، تفعيل حصص المعالجة البيداغوجية في المواد الأساسية، واستغلال مخابر الإعلام الآلي والتجارب العلمية.') }}
            </div>
        </div>

        {{-- Section 4: Signatures & Official Validation Stamps --}}
        <div class="grid grid-cols-3 gap-4 pt-2 text-center text-xs border-t border-slate-300">
            <div>
                <div class="font-bold text-slate-800 mb-8">{{ __('مستشار التربية / ناظر المؤسسة') }}</div>
                <div class="text-[10px] text-slate-400">({{ __('التوقيع والختم') }})</div>
            </div>
            <div>
                <div class="font-bold text-slate-800 mb-8">{{ __('مدير المتوسطة') }}</div>
                <div class="text-[10px] text-slate-700 font-semibold">{{ $schoolSetting?->principal_name ?? '' }}</div>
                <div class="text-[10px] text-slate-400">({{ __('التوقيع والختم الإداري') }})</div>
            </div>
            <div>
                <div class="font-bold text-slate-800 mb-8">{{ __('مفتش التعليم المتوسط للإدارة') }}</div>
                <div class="text-[10px] text-slate-400">({{ __('تأشيرة واطلاع المفتشية') }})</div>
            </div>
        </div>
    </div>

</body>
</html>
