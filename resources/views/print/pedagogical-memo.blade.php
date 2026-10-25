@php
    $isRtl = app()->getLocale() === 'ar';
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $activities = is_array($memo->activities) ? array_values($memo->activities) : [];
    $formatDate = fn ($value): string => $value?->format('d/m/Y H:i') ?? '—';
    $text = function ($value): string {
        if (is_array($value)) {
            $value = implode('، ', array_filter($value, 'is_scalar'));
        }

        return is_scalar($value) ? (string) $value : '';
    };
    $multiline = fn ($value): string => nl2br(e($text($value)));
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('مذكرة بيداغوجية') }} — {{ $memo->title }}</title>

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
            min-height: 297mm;
        }

        .memo-section {
            break-inside: avoid;
            page-break-inside: avoid;
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
                <p class="text-sm font-extrabold text-stone-900">{{ __('مذكرة بيداغوجية') }}</p>
                <p class="text-xs text-stone-500">{{ $memo->title }}</p>
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

    <main class="print-sheet mx-auto my-8 w-[210mm] max-w-full border border-amber-200/70 bg-[#fffdf8] p-[12mm] shadow-2xl shadow-stone-300/50">
        <header class="flex items-start justify-between gap-6 border-b-2 border-amber-300 pb-5">
            <div>
                <p class="text-2xl font-black tracking-tight text-amber-700">سجِّل</p>
                <p class="mt-1 text-[11px] font-semibold text-stone-500">{{ __('مساعد أستاذ المعلوماتية — التعليم المتوسط') }}</p>
                <p class="mt-1 text-[11px] font-bold text-stone-800">{{ $schoolSetting->school_name }}</p>
                <p class="text-[10px] font-semibold text-stone-500">{{ $schoolSetting->directorate_name }}</p>
            </div>

            <div class="text-end">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600">{{ __('مذكرة بيداغوجية') }}</p>
                <p class="mt-1 text-lg font-extrabold text-stone-900">{{ $memo->title }}</p>
                <p class="mt-1 text-[11px] text-stone-500">
                    {{ __('السنة الدراسية') }}:
                    <span class="font-bold text-stone-700">{{ $schoolSetting->academic_year ?: $academicYear }}</span>
                </p>
            </div>
        </header>

        <section class="memo-section mt-5 rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-[11px] sm:grid-cols-4">
                <div>
                    <p class="font-bold text-amber-700">{{ __('المادة') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $subject?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المستوى') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $level?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('الطور التعليمي') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $educationPhase?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('المقطع التعلمي') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $memo->phase ?: '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('الأستاذ') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $teacher?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('السنة الدراسية') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $schoolSetting->academic_year ?: $academicYear }}</p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('نوع المذكرة') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">
                        {{ $memo->is_template ? __('نموذج قابل لإعادة الاستخدام') : __('مذكرة عادية') }}
                    </p>
                </div>

                <div>
                    <p class="font-bold text-amber-700">{{ __('آخر تحديث') }}</p>
                    <p class="mt-1 font-extrabold text-stone-900">{{ $formatDate($memo->updated_at) }}</p>
                </div>
            </div>
        </section>

        <section class="memo-section mt-5 rounded-2xl border border-stone-200 bg-white/80 p-4">
            <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('الكفاءة المستهدفة') }}</h2>
            <p class="mt-2 text-[12px] leading-7 text-stone-800">{!! $multiline($memo->competence) !!}</p>
        </section>

        <section class="memo-section mt-4 rounded-2xl border border-stone-200 bg-white/80 p-4">
            <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('الوضعية الانطلاقية') }}</h2>
            <p class="mt-2 text-[12px] leading-7 text-stone-800">
                @if (filled($memo->starting_situation))
                    {!! $multiline($memo->starting_situation) !!}
                @else
                    —
                @endif
            </p>
        </section>

        <section class="memo-section mt-5">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('سير الأنشطة') }}</h2>
                <span class="text-[10px] font-bold text-stone-500">
                    {{ __('عدد الأنشطة') }}: {{ count($activities) }}
                </span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-stone-200">
                <table class="print-table w-full text-[11px]">
                    <thead>
                        <tr class="bg-stone-800 text-start text-white">
                            <th class="w-10 px-3 py-2.5 text-start font-bold">#</th>
                            <th class="px-3 py-2.5 text-start font-bold">{{ __('النشاط') }}</th>
                            <th class="w-20 px-3 py-2.5 text-start font-bold">{{ __('المدة') }}</th>
                            <th class="px-3 py-2.5 text-start font-bold">{{ __('الاستراتيجية') }}</th>
                            <th class="px-3 py-2.5 text-start font-bold">{{ __('المحتوى') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse ($activities as $activity)
                            <tr class="{{ $loop->even ? 'bg-amber-50/40' : 'bg-white/70' }}">
                                <td class="px-3 py-2.5 font-bold text-stone-500">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 font-extrabold text-stone-900">
                                    {{ $text($activity['name'] ?? null) ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-stone-600">
                                    @if (filled($activity['duration'] ?? null))
                                        {{ $text($activity['duration']) }} {{ __('دقيقة') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-stone-600">
                                    {{ $text($activity['strategy'] ?? null) ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-stone-700">
                                    @if (filled($activity['content'] ?? null))
                                        {!! nl2br(e($text($activity['content']))) !!}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm font-semibold text-stone-500">
                                    {{ __('لا توجد أنشطة مسجلة') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="memo-section mt-4 grid grid-cols-2 gap-4">
            <div class="rounded-2xl border border-stone-200 bg-white/80 p-4">
                <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('الموارد المعرفية') }}</h2>
                <p class="mt-2 text-[12px] leading-7 text-stone-800">
                    @if (filled($memo->resources))
                        {!! $multiline($memo->resources) !!}
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white/80 p-4">
                <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('التقويم') }}</h2>
                <p class="mt-2 text-[12px] leading-7 text-stone-800">
                    @if (filled($memo->evaluation))
                        {!! $multiline($memo->evaluation) !!}
                    @else
                        —
                    @endif
                </p>
            </div>
        </section>

        @if (filled($memo->notes))
            <section class="memo-section mt-4 rounded-2xl border border-amber-200 bg-amber-50/50 p-4">
                <h2 class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">{{ __('ملاحظات المذكرة') }}</h2>
                <p class="mt-2 text-[12px] leading-7 text-stone-800">{!! $multiline($memo->notes) !!}</p>
            </section>
        @endif

        <footer class="memo-section mt-10">
            <div class="grid grid-cols-2 gap-12 text-center text-[10px] text-stone-500">
                <div>
                    <div class="mx-auto h-12 w-40 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع الأستاذ') }}</p>
                </div>

                <div>
                    <div class="mx-auto h-12 w-40 border-b border-dashed border-stone-400"></div>
                    <p class="mt-2 font-bold">{{ __('توقيع مدير المؤسسة') }}</p>
                    @if ($schoolSetting->principal_name)
                        <p class="text-[10px] font-semibold text-stone-600">{{ $schoolSetting->principal_name }}</p>
                    @endif
                </div>
            </div>

            <p class="mt-8 text-center text-[9px] text-stone-400">
                {{ __('تم إنشاء هذه المذكرة تلقائياً من منصة سجِّل.') }}
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
