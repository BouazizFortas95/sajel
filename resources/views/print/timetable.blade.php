@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $weekStart = $weekDates->first();
    $weekEnd = $weekDates->last();
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('جدول التوقيت الأسبوعي') }} — {{ $teacher?->name ?? $classroom?->display_name() ?? __('جميع المعلمين') }}</title>

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
            size: A4 landscape;
            margin: 8mm;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
        }

        .print-sheet {
            width: 297mm;
            min-height: 210mm;
        }

        .timetable-table {
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .timetable-table th,
        .timetable-table td {
            word-break: break-word;
        }

        .timetable-table thead th:first-child {
            border-start-start-radius: .75rem;
        }

        .timetable-table thead th:last-child {
            border-start-end-radius: .75rem;
        }

        .timetable-table tbody tr:last-child td:first-child {
            border-end-start-radius: .75rem;
        }

        .timetable-table tbody tr:last-child td:last-child {
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
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
            <div>
                <p class="text-sm font-extrabold text-stone-900">{{ __('جدول التوقيت الأسبوعي') }}</p>
                <p class="text-xs text-stone-500">{{ $teacher?->name ?? $classroom?->display_name() ?? __('جميع المعلمين') }}</p>
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

    <main class="print-sheet mx-auto my-8 max-w-full border border-amber-200/70 bg-[#fffdf8] p-[10mm] shadow-2xl shadow-stone-300/50">
        <header class="flex items-start justify-between gap-6 border-b-2 border-amber-300 pb-4">
            <div>
                <p class="text-2xl font-black tracking-tight text-amber-700">سجِّل</p>
                <p class="mt-1 text-[11px] font-semibold text-stone-500">{{ __('مساعد أستاذ المعلوماتية — التعليم المتوسط') }}</p>
            </div>

            <div class="text-end">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600">{{ __('جدول التوقيت الأسبوعي') }}</p>
                <p class="mt-1 text-lg font-extrabold text-stone-900">
                    {{ $teacher?->name ?? $classroom?->display_name() ?? __('جميع المعلمين') }}
                </p>
                <p class="mt-1 text-[11px] text-stone-500">
                    {{ __('السنة الدراسية') }}:
                    <span class="font-bold text-stone-700">{{ $academicYear }}</span>
                </p>
            </div>
        </header>

        <section class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-[11px] md:grid-cols-5">
                <div>
                    <p class="font-bold text-amber-700">{{ __('المعلم') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $teacher?->name ?? __('جميع المعلمين') }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('القسم') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $classroom?->name ?? __('كل الأقسام') }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المستوى') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $classroom?->level?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('عدد الحصص') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $scheduleCount }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('الأسبوع') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">
                        {{ $formatDate($weekStart) }} — {{ $formatDate($weekEnd) }}
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-4">
            <div class="overflow-hidden rounded-2xl border border-stone-200">
                <table class="timetable-table w-full text-[10px]">
                    <thead>
                        <tr class="bg-stone-800 text-white">
                            <th class="w-24 px-2 py-2.5 text-start font-bold">{{ __('الوقت') }}</th>
                            @foreach ($days as $day)
                                <th class="px-2 py-2.5 text-start font-bold">
                                    {{ $day->label() }}
                                    <span class="block text-[9px] font-semibold text-stone-300">
                                        {{ $weekDates[$day->value]?->format('d/m') }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse ($timeSlots as $slot)
                            <tr class="{{ $loop->even ? 'bg-amber-50/40' : 'bg-white/70' }}">
                                <th class="bg-stone-50/80 px-2 py-2 text-start align-top font-bold text-stone-600">
                                    <span class="block text-[11px] text-stone-900">{{ $slot['start'] }}</span>
                                    <span class="block text-[9px] text-stone-400">{{ $slot['end'] }}</span>
                                </th>

                                @foreach ($days as $day)
                                    @php
                                        $cellSchedules = $grid[$day->value][$slot['key']] ?? [];
                                    @endphp

                                    <td class="px-1.5 py-1.5 align-top">
                                        @forelse ($cellSchedules as $schedule)
                                            <div
                                                class="mb-1 rounded-lg border border-stone-200 border-s-4 bg-white/90 px-2 py-1.5 last:mb-0"
                                                style="border-inline-start-color: {{ $schedule->subject?->color ?? '#f59e0b' }}"
                                            >
                                                <p class="font-extrabold text-stone-900">{{ $schedule->subject?->name ?? '—' }}</p>
                                                <p class="text-[9px] text-stone-500">
                                                    {{ $schedule->placeLabel() ?? '—' }}
                                                </p>
                                                @if (! $teacher || $classroom)
                                                    <p class="text-[9px] text-stone-400">{{ $schedule->user?->name }}</p>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-stone-300">—</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $days->count() + 1 }}" class="px-4 py-12 text-center text-sm font-semibold text-stone-500">
                                    {{ __('لا توجد حصص مطابقة للفلاتر المحددة.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="mt-8">
            <div class="grid grid-cols-2 gap-12 text-center text-[10px] text-stone-500">
                <div>
                    <div class="mx-auto h-10 w-44 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع الأستاذ') }}</p>
                </div>

                <div>
                    <div class="mx-auto h-10 w-44 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع مدير المؤسسة') }}</p>
                </div>
            </div>

            <p class="mt-6 text-center text-[9px] text-stone-400">
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
