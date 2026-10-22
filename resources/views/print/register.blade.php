<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('دفتر النصوص الرسمي') }} — {{ $teacherName }}</title>

    @vite('resources/css/app.css')

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            font-family: "Noto Naskh Arabic", "Amiri", "Traditional Arabic", "Segoe UI", Tahoma, serif;
        }

        .rich-text p {
            margin-bottom: 0.35rem;
        }

        .rich-text ul {
            margin: 0.25rem 0;
            padding-right: 1.25rem;
            list-style: disc;
        }

        .rich-text ol {
            margin: 0.25rem 0;
            padding-right: 1.25rem;
            list-style: decimal;
        }

        .rich-text h2,
        .rich-text h3,
        .rich-text h4 {
            margin: 0.5rem 0 0.25rem;
            font-weight: 700;
        }

        @media print {
            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased print:bg-white">
    @php
        $allowedTags = '<p><br><strong><em><u><s><ul><ol><li><h2><h3><h4><blockquote>';
    @endphp

    <main class="mx-auto my-6 w-[210mm] max-w-full bg-white p-[12mm] shadow-xl print:my-0 print:w-auto print:max-w-none print:p-0 print:shadow-none">
        <div class="no-print mb-4 flex justify-end">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                </svg>
                {{ __('طباعة') }}
            </button>
        </div>

        <header class="border-b-4 border-double border-slate-800 pb-4 text-center">
            <p class="text-sm font-bold">الجمهورية الجزائرية الديمقراطية الشعبية</p>
            <p class="mt-1 text-xs text-slate-600">وزارة التربية الوطنية</p>
            <h1 class="mt-4 text-2xl font-bold tracking-wide">دفتر النصوص الرسمي</h1>
            <p class="mt-1 text-sm text-slate-600">السجل الرسمي للحصص المنجزة</p>
        </header>

        <section class="mt-5 grid grid-cols-2 gap-x-8 gap-y-2 border-b-2 border-slate-700 pb-3 text-sm">
            <p><span class="font-bold">الأستاذ(ة):</span> {{ $teacherName }}</p>
            <p><span class="font-bold">السنة الدراسية:</span> {{ $academicYear }}</p>
            <p><span class="font-bold">عدد السجلات:</span> {{ $logs->count() }}</p>
            <p><span class="font-bold">تاريخ الطباعة:</span> {{ now()->format('Y/m/d') }}</p>
        </section>

        <table class="mt-6 w-full table-fixed border-collapse border border-slate-700 text-right text-sm">
            <thead>
                <tr class="bg-slate-100">
                    <th class="w-[13%] border border-slate-700 px-2 py-2 font-bold">التاريخ / التوقيت</th>
                    <th class="w-[15%] border border-slate-700 px-2 py-2 font-bold">القسم</th>
                    <th class="w-[20%] border border-slate-700 px-2 py-2 font-bold">عنوان الدرس</th>
                    <th class="w-[34%] border border-slate-700 px-2 py-2 font-bold">المحتوى المنجز</th>
                    <th class="w-[18%] border border-slate-700 px-2 py-2 font-bold">الواجب</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="align-top">
                        <td class="border border-slate-700 px-2 py-2">
                            <div class="font-semibold">{{ $log->date?->format('Y/m/d') ?? '—' }}</div>
                            @if ($log->schedule?->start_time)
                                <div class="mt-1 text-xs text-slate-600">
                                    {{ $log->schedule->start_time->format('H:i') }}
                                    @if ($log->schedule->end_time)
                                        — {{ $log->schedule->end_time->format('H:i') }}
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="border border-slate-700 px-2 py-2">
                            <div class="font-semibold">{{ $log->schedule?->classroom?->name ?? '—' }}</div>
                            @if ($log->schedule?->subject?->name)
                                <div class="mt-1 text-xs text-slate-600">{{ $log->schedule->subject->name }}</div>
                            @endif
                            @if ($log->schedule?->group?->name)
                                <div class="mt-1 text-xs text-slate-600">{{ $log->schedule->group->name }}</div>
                            @endif
                        </td>

                        <td class="border border-slate-700 px-2 py-2 font-semibold">
                            {{ $log->title }}
                        </td>

                        <td class="border border-slate-700 px-2 py-2 leading-7">
                            @if (filled($log->actual_content))
                                <div class="rich-text">
                                    {!! strip_tags((string) $log->actual_content, $allowedTags) !!}
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="border border-slate-700 px-2 py-2 leading-7">
                            @if (filled($log->homework))
                                {!! nl2br(e($log->homework)) !!}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border border-slate-700 px-3 py-10 text-center text-slate-500">
                            لا توجد سجلات منجزة مطابقة للاختيار.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="mt-10 grid grid-cols-2 gap-8 text-sm">
            <div>
                <p class="font-bold">توقيع الأستاذ(ة):</p>
                <div class="mt-12 border-t border-slate-400"></div>
            </div>
            <div>
                <p class="font-bold">ختم المؤسسة:</p>
                <div class="mt-12 border-t border-slate-400"></div>
            </div>
        </footer>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => window.print(), 300);
        });
    </script>
</body>
</html>
