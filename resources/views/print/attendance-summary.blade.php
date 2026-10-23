@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $formatRate = fn ($value): string => rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');
    $statusLabels = [
        'present' => __('حاضر'),
        'absent' => __('غائب'),
        'late' => __('متأخر'),
        'excused' => __('معفى'),
    ];
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('تقرير الحضور والغيابات المجمع') }} — {{ $classroom->display_name() }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
        }

        .print-sheet {
            width: 210mm;
            min-height: 297mm;
        }

        .print-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .print-table thead th:first-child {
            border-start-start-radius: .75rem;
        }

        .print-table thead th:last-child {
            border-start-end-radius: .75rem;
        }

        .print-table tbody tr:last-child td:first-child {
            border-end-start-radius: .75rem;
        }

        .print-table tbody tr:last-child td:last-child {
            border-end-end-radius: .75rem;
        }

        @media print {
            html,
            body {
                background: #fff !important;
            }

            .no-print {
                display: none !important;
            }

            .print-sheet {
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 antialiased">
    <div class="no-print sticky top-0 z-10 border-b border-stone-200 bg-white/90 px-4 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3">
            <div>
                <p class="text-sm font-extrabold text-stone-900">{{ __('تقرير الحضور والغيابات المجمع') }}</p>
                <p class="text-xs text-stone-500">{{ $classroom->display_name() }}</p>
            </div>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.83h10.56M6.72 17.25h10.56M6.75 6.75h10.5v3.75H6.75V6.75Zm-1.5 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v5.25a1.5 1.5 0 0 1-1.5 1.5h-1.5v-3H6.75v3h-1.5a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z" />
                </svg>
                {{ __('طباعة') }}
            </button>
        </div>
    </div>

    <main class="print-sheet mx-auto my-8 max-w-full border border-amber-200/70 bg-[#fffdf8] p-[12mm] shadow-2xl shadow-stone-300/50">
        <header class="flex items-start justify-between gap-6 border-b-2 border-amber-300 pb-5">
            <div>
                <p class="text-2xl font-black tracking-tight text-amber-700">سجِّل</p>
                <p class="mt-1 text-[11px] font-semibold text-stone-500">{{ __('مساعد أستاذ المعلوماتية — التعليم المتوسط') }}</p>
            </div>

            <div class="text-end">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600">{{ __('تقرير الحضور والغيابات المجمع') }}</p>
                <p class="mt-1 text-lg font-extrabold text-stone-900">{{ $classroom->display_name() }}</p>
                <p class="mt-1 text-[11px] text-stone-500">
                    {{ __('السنة الدراسية') }}:
                    <span class="font-bold text-stone-700">{{ $academicYear }}</span>
                </p>
            </div>
        </header>

        <section class="mt-5 rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-[11px] sm:grid-cols-4">
                <div>
                    <p class="font-bold text-amber-700">{{ __('القسم') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $classroom->name }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المستوى') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $classroom->level?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المجموعة') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $group?->name ?? __('كل المجموعات') }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المادة') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $subject?->name ?? __('كل المواد') }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('الفترة') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $formatDate($from) }} — {{ $formatDate($to) }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المعلم') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">
                        {{ $teachers->pluck('name')->implode(' / ') ?: '—' }}
                    </p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('عدد الحصص') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $summary['sessions'] }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('عدد التلاميذ') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $summary['students'] }}</p>
                </div>
            </div>
        </section>

        <section class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => __('الحضور'), 'value' => $summary['present'], 'classes' => 'border-emerald-200 bg-emerald-50/80 text-emerald-800'],
                ['label' => __('الغياب'), 'value' => $summary['absent'], 'classes' => 'border-rose-200 bg-rose-50/80 text-rose-800'],
                ['label' => __('التأخر'), 'value' => $summary['late'], 'classes' => 'border-amber-200 bg-amber-50/80 text-amber-800'],
                ['label' => __('الإعفاء'), 'value' => $summary['excused'], 'classes' => 'border-sky-200 bg-sky-50/80 text-sky-800'],
            ] as $card)
                <div class="rounded-xl border p-3 {{ $card['classes'] }}">
                    <p class="text-[10px] font-bold opacity-80">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-black">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mt-3 grid grid-cols-2 gap-3 text-[11px]">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                <span class="font-bold text-emerald-700">{{ __('نسبة الحضور') }}:</span>
                <span class="font-black text-emerald-900">{{ $formatRate($summary['attendance_rate']) }}%</span>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3">
                <span class="font-bold text-rose-700">{{ __('نسبة الغياب') }}:</span>
                <span class="font-black text-rose-900">{{ $formatRate($summary['absence_rate']) }}%</span>
            </div>
        </section>

        <section class="mt-5">
            <h2 class="mb-2 text-sm font-extrabold text-stone-900">{{ __('ملخص الحضور') }}</h2>

            <div class="overflow-hidden rounded-2xl border border-stone-200">
                <table class="print-table w-full text-[10px]">
                    <thead>
                        <tr class="bg-stone-800 text-start text-white">
                            <th class="px-2 py-2 text-start font-bold">#</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('الطالب') }}</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('رقم التسجيل') }}</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('المجموعة') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('حاضر') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('غائب') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('متأخر') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('معفى') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('المجموع') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('نسبة الحضور') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('نسبة الغياب') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse ($students as $index => $row)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white/70' : 'bg-amber-50/40' }}">
                                <td class="px-2 py-2 font-bold text-stone-500">{{ $index + 1 }}</td>
                                <td class="px-2 py-2 font-extrabold text-stone-900">{{ $row['student']->full_name }}</td>
                                <td class="px-2 py-2 text-stone-600">{{ $row['student']->registration_number ?? '—' }}</td>
                                <td class="px-2 py-2 text-stone-600">{{ $row['student']->group?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-center font-bold text-emerald-700">{{ $row['present'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-rose-700">{{ $row['absent'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-amber-700">{{ $row['late'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-sky-700">{{ $row['excused'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-stone-900">{{ $row['total'] }}</td>
                                <td class="px-2 py-2 text-center text-stone-600">{{ $formatRate($row['attendance_rate']) }}%</td>
                                <td class="px-2 py-2 text-center text-stone-600">{{ $formatRate($row['absence_rate']) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-10 text-center text-sm font-semibold text-stone-500">
                                    {{ __('لا توجد سجلات حضور في الفترة المحددة.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5">
            <h2 class="mb-2 text-sm font-extrabold text-stone-900">{{ __('تفصيل الحصص') }}</h2>

            <div class="overflow-hidden rounded-2xl border border-stone-200">
                <table class="print-table w-full text-[10px]">
                    <thead>
                        <tr class="bg-stone-800 text-start text-white">
                            <th class="px-2 py-2 text-start font-bold">{{ __('التاريخ') }}</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('المادة') }}</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('المعلم') }}</th>
                            <th class="px-2 py-2 text-start font-bold">{{ __('المجموعة') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('حاضر') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('غائب') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('متأخر') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('معفى') }}</th>
                            <th class="px-2 py-2 text-center font-bold">{{ __('المجموع') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse ($sessions as $index => $session)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white/70' : 'bg-amber-50/40' }}">
                                <td class="px-2 py-2 text-stone-600">
                                    {{ $session['date']?->format('d/m/Y') ?? '—' }}
                                    <span class="block text-[9px] text-stone-400">{{ $session['date']?->translatedFormat('l') }}</span>
                                </td>
                                <td class="px-2 py-2 font-bold text-stone-900">{{ $session['subject']?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-stone-600">{{ $session['teacher']?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-stone-600">{{ $session['group']?->name ?? __('القسم كاملاً') }}</td>
                                <td class="px-2 py-2 text-center font-bold text-emerald-700">{{ $session['counts']['present'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-rose-700">{{ $session['counts']['absent'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-amber-700">{{ $session['counts']['late'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-sky-700">{{ $session['counts']['excused'] }}</td>
                                <td class="px-2 py-2 text-center font-bold text-stone-900">{{ $session['total'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm font-semibold text-stone-500">
                                    {{ __('لا توجد سجلات حضور في الفترة المحددة.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($absences->isNotEmpty())
            <section class="mt-5">
                <h2 class="mb-2 text-sm font-extrabold text-stone-900">{{ __('سجل الغياب والتأخر') }}</h2>

                <div class="overflow-hidden rounded-2xl border border-stone-200">
                    <table class="print-table w-full text-[10px]">
                        <thead>
                            <tr class="bg-stone-800 text-start text-white">
                                <th class="px-2 py-2 text-start font-bold">{{ __('التاريخ') }}</th>
                                <th class="px-2 py-2 text-start font-bold">{{ __('الطالب') }}</th>
                                <th class="px-2 py-2 text-start font-bold">{{ __('الحالة') }}</th>
                                <th class="px-2 py-2 text-start font-bold">{{ __('المادة') }}</th>
                                <th class="px-2 py-2 text-start font-bold">{{ __('المجموعة') }}</th>
                                <th class="px-2 py-2 text-start font-bold">{{ __('ملاحظات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200">
                            @foreach ($absences as $index => $absence)
                                <tr class="{{ $index % 2 === 0 ? 'bg-white/70' : 'bg-amber-50/40' }}">
                                    <td class="px-2 py-2 text-stone-600">{{ $formatDate($absence['date']) }}</td>
                                    <td class="px-2 py-2 font-bold text-stone-900">{{ $absence['student']?->full_name ?? '—' }}</td>
                                    <td class="px-2 py-2 text-stone-600">{{ $absence['status']?->label() ?? '—' }}</td>
                                    <td class="px-2 py-2 text-stone-600">{{ $absence['subject']?->name ?? '—' }}</td>
                                    <td class="px-2 py-2 text-stone-600">{{ $absence['group']?->name ?? __('القسم كاملاً') }}</td>
                                    <td class="px-2 py-2 text-stone-600">{{ $absence['remarks'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <footer class="mt-10">
            <div class="grid grid-cols-2 gap-12 text-center text-[10px] text-stone-500">
                <div>
                    <div class="mx-auto h-12 w-40 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع الأستاذ') }}</p>
                </div>

                <div>
                    <div class="mx-auto h-12 w-40 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع مدير المؤسسة') }}</p>
                </div>
            </div>

            <p class="mt-8 text-center text-[9px] text-stone-400">
                {{ __('تم إنشاء هذا التقرير تلقائياً من منصة سجِّل.') }}
                — {{ now()->format('d/m/Y H:i') }}
            </p>
        </footer>
    </main>

    @if ($autoPrint ?? true)
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
