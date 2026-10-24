<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('دفتر النصوص الرسمي') }} — {{ $teacherName }}</title>

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

        .screen-canvas {
            background-color: #f4efe6;
            background-image:
                radial-gradient(70rem 42rem at 50% -12%, rgba(245, 158, 11, .16), transparent 62%),
                linear-gradient(to left, rgba(120, 53, 15, .035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(120, 53, 15, .035) 1px, transparent 1px);
            background-size: auto, 32px 32px, 32px 32px;
        }

        .register-sheet {
            display: flex;
            flex-direction: column;
        }

        .register-sheet-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .register-sheet .signatures {
            margin-top: auto;
        }

        .book-paper {
            background-color: #fffdf8;
            background-image:
                radial-gradient(42rem 26rem at 100% 0%, rgba(245, 158, 11, .10), transparent 58%),
                linear-gradient(to left, rgba(146, 64, 14, .045) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(146, 64, 14, .045) 1px, transparent 1px);
            background-size: auto, 24px 24px, 24px 24px;
        }

        .register-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .register-table thead th:first-child {
            border-top-right-radius: 1rem;
        }

        .register-table thead th:last-child {
            border-top-left-radius: 1rem;
        }

        .register-table tbody tr:last-child td:first-child {
            border-bottom-right-radius: 1rem;
        }

        .register-table tbody tr:last-child td:last-child {
            border-bottom-left-radius: 1rem;
        }

        .rich-text {
            font-size: 10.5px;
            line-height: 1.8;
        }

        .rich-text > :first-child {
            margin-top: 0;
        }

        .rich-text > :last-child {
            margin-bottom: 0;
        }

        .rich-text p {
            margin: 0 0 .4rem;
            orphans: 3;
            widows: 3;
        }

        .rich-text p:last-child {
            margin-bottom: 0;
        }

        .rich-text h2,
        .rich-text h3,
        .rich-text h4 {
            margin: .55rem 0 .3rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .rich-text h2 {
            color: #78350f;
            font-size: 12px;
        }

        .rich-text h3 {
            display: inline-block;
            padding: .1rem .4rem .1rem .45rem;
            border-inline-start: 3px solid #f59e0b;
            border-radius: .35rem;
            background: linear-gradient(90deg, rgba(254, 243, 199, .85), rgba(254, 243, 199, .12));
            color: #92400e;
            font-size: 11px;
        }

        .rich-text h4 {
            color: #b45309;
            font-size: 10.5px;
        }

        .rich-text ul,
        .rich-text ol {
            margin: .25rem 0 .45rem;
            padding-inline-start: 1.15rem;
        }

        .rich-text ul {
            list-style: disc;
        }

        .rich-text ol {
            list-style: decimal;
        }

        .rich-text li {
            margin-bottom: .15rem;
            padding-inline-start: .1rem;
        }

        .rich-text li::marker {
            color: #d97706;
            font-weight: 700;
        }

        .rich-text strong {
            color: #78350f;
            font-weight: 800;
        }

        .rich-text blockquote {
            margin: .4rem 0;
            padding: .35rem .55rem;
            border-inline-start: 3px solid #fcd34d;
            border-radius: .35rem;
            background: rgba(255, 251, 235, .9);
            color: #57534e;
        }

        @media print {
            html,
            body {
                background: #fffdf8 !important;
            }

            .screen-canvas {
                background: #fffdf8 !important;
            }

            .no-print {
                display: none !important;
            }

            .register-sheet {
                break-after: page;
                page-break-after: always;
            }

            .register-sheet:last-child {
                break-after: auto;
                page-break-after: auto;
            }

            .register-sheet {
                width: auto !important;
                max-width: none !important;
                min-height: 276mm !important;
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            thead {
                display: table-header-group;
            }

            .signatures {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="screen-canvas min-h-screen text-slate-800 antialiased">
    @php
        $allowedTags = '<p><br><strong><em><u><s><ul><ol><li><h2><h3><h4><blockquote>';

        // Official print output must stay Arabic even when the app locale is English.
        $arabicWeekdays = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        $filamentColorMap = [
            'primary' => '#d97706',
            'secondary' => '#64748b',
            'success' => '#16a34a',
            'danger' => '#dc2626',
            'warning' => '#d97706',
            'info' => '#0284c7',
            'gray' => '#64748b',
            'slate' => '#475569',
        ];

        $subjectColor = static function (?string $color) use ($filamentColorMap): string {
            $color = trim((string) $color);

            if ($color === '') {
                return '#d97706';
            }

            if (preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $matches) === 1) {
                $hex = strtolower($matches[1]);

                if (strlen($hex) === 3) {
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                }

                return '#'.$hex;
            }

            return $filamentColorMap[strtolower($color)] ?? '#d97706';
        };

        $subjectTextColor = static function (string $hex): string {
            $hex = ltrim($hex, '#');

            if (strlen($hex) !== 6) {
                return '#ffffff';
            }

            $channels = array_map(
                static fn (string $channel): float => hexdec($channel) / 255,
                str_split($hex, 2),
            );

            $linear = array_map(
                static fn (float $channel): float => $channel <= 0.03928
                    ? $channel / 12.92
                    : (($channel + 0.055) / 1.055) ** 2.4,
                $channels,
            );

            $luminance = (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);

            return $luminance > 0.46 ? '#1e293b' : '#ffffff';
        };
    @endphp

    {{-- Browser-only toolbar: never printed and never included in the PDF. --}}
    <div class="no-print sticky top-0 z-50 border-b border-amber-200/80 bg-[#fffdf8]/90 shadow-sm backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-col items-stretch justify-between gap-3 px-4 py-3 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-lg font-black text-white shadow-md shadow-amber-500/25">
                    س
                </span>
                <div>
                    <p class="text-sm font-black text-slate-900">معاينة دفتر النصوص</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500">دفتر مستقل لكل قسم — جاهز للطباعة أو الحفظ كـ PDF</p>
                </div>
            </div>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                </svg>
                طباعة / حفظ كـ PDF
            </button>
        </div>
    </div>

    <main class="mx-auto w-[210mm] max-w-[calc(100%-1.5rem)] space-y-6 print:w-auto print:max-w-none print:space-y-0">
        @foreach ($registers as $register)
            @php
                $classroom = $register['classroom'];
                $registerLogs = $register['logs'];
                $phase = $register['phase'];
                $groups = $register['groups'];
                $assignments = $register['teachers'];
                $periodFrom = $register['from'];
                $periodTo = $register['to'];
                $registerCount = $register['count'];
                $registerTitleId = 'classroom-register-title-'.$loop->index;
            @endphp

            <section
                id="classroom-register-{{ $classroom?->getKey() ?? 'unassigned' }}-{{ $loop->index }}"
                class="register-sheet book-paper relative min-h-[277mm] overflow-hidden rounded-[26px] border-4 border-double border-amber-500/80 p-[7mm] shadow-2xl shadow-amber-900/10 print:my-0 print:w-auto print:max-w-none print:rounded-none print:p-[4mm] print:shadow-none {{ ! $loop->last ? 'break-after-page' : '' }}"
                aria-labelledby="{{ $registerTitleId }}"
            >
                <div class="pointer-events-none absolute inset-[3mm] rounded-[18px] border border-dashed border-amber-300/80 print:inset-[2mm]"></div>
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-l from-amber-300 via-amber-500 to-amber-700"></div>

                <div class="register-sheet-content relative">
                    {{-- ==================== Tier A: general official information ==================== --}}
                    <header class="relative overflow-hidden rounded-2xl border border-amber-200/90 bg-white/75 shadow-sm">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l from-amber-300 via-amber-500 to-amber-700"></div>

                        <div class="grid grid-cols-[1fr_1.25fr_1fr] gap-3 p-4">
                            {{-- Right / start: institutional context --}}
                            <div class="text-right">
                                <p class="text-[8px] font-extrabold tracking-wide text-amber-700/80">المعلومات العامة الرسمية</p>

                                <div class="mt-2 space-y-1.5">
                                    <div>
                                        <p class="text-[8px] font-semibold text-slate-400">الطور التعليمي</p>
                                        <p class="text-[10px] font-extrabold leading-4 text-slate-800">{{ $phase?->name ?? 'التعليم المتوسط' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[8px] font-semibold text-slate-400">السنة الدراسية</p>
                                        <p class="text-[10px] font-extrabold leading-4 text-slate-800">{{ $register['academicYear'] }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[8px] font-semibold text-slate-400">المؤسسة التربوية / متوسطة</p>
                                        <p class="text-[9px] font-bold leading-4 text-slate-700">مؤسسة التربية والتعليم</p>
                                        <div class="mt-1 border-b border-dotted border-amber-300"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Center: republic and document title --}}
                            <div class="border-x border-amber-200/70 px-3 text-center">
                                <p class="text-[10px] font-extrabold tracking-wide text-slate-900">الجمهورية الجزائرية الديمقراطية الشعبية</p>
                                <p class="mt-0.5 text-[8px] font-semibold text-slate-500">وزارة التربية الوطنية</p>

                                <h1
                                    id="{{ $registerTitleId }}"
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-gradient-to-l from-amber-500 to-amber-600 px-4 py-1.5 text-[17px] font-black tracking-wide text-white shadow-md shadow-amber-500/20"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                    دفتر النصوص الرسمي
                                </h1>

                                <p class="mt-1.5 text-[8.5px] font-bold text-amber-800/80">السجل الرسمي للحصص المنجزة الخاص بالقسم</p>
                            </div>

                            {{-- Left / end: document control and period --}}
                            <div class="text-left">
                                <p class="text-[8px] font-extrabold tracking-wide text-amber-700/80">بيانات الوثيقة</p>

                                <div class="mt-2 space-y-1.5">
                                    <div>
                                        <p class="text-[8px] font-semibold text-slate-400">الفترة الزمنية</p>
                                        <p class="text-[9px] font-extrabold leading-4 text-slate-800">
                                            من <span dir="ltr" class="tabular-nums">{{ $periodFrom?->format('Y/m/d') ?? '—' }}</span>
                                            إلى <span dir="ltr" class="tabular-nums">{{ $periodTo?->format('Y/m/d') ?? '—' }}</span>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-[8px] font-semibold text-slate-400">تاريخ الاستخراج</p>
                                        <p class="text-[9px] font-extrabold leading-4 text-slate-800">{{ now()->format('Y/m/d') }}</p>
                                    </div>

                                    <div>
                                        <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-[9px] font-extrabold text-white shadow-sm">
                                            إجمالي الحصص المنجزة: {{ $registerCount }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    {{-- ==================== Tier B: class identity and assigned teachers ==================== --}}
                    <section class="mt-3 grid grid-cols-2 gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4">
                        <div class="col-span-2 flex items-center justify-between gap-3 border-b border-amber-200/70 pb-2">
                            <span class="rounded-full border border-amber-200 bg-white/80 px-2.5 py-0.5 text-[8px] font-extrabold text-amber-800">هوية القسم</span>
                            <p class="text-[9px] font-extrabold tracking-wide text-amber-900/80">بطاقة الهوية الخاصة بالقسم والأساتذة المسندين</p>
                        </div>

                        {{-- Right / start: high-emphasis class identity --}}
                        <div class="text-right">
                            <div class="flex items-end justify-end gap-3">
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-slate-500">المستوى الدراسي</p>
                                    <p class="mt-0.5 text-[11px] font-extrabold text-slate-800">{{ $register['levelName'] ?? '—' }}</p>
                                </div>

                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-slate-500">القسم</p>
                                    <span class="mt-0.5 inline-flex rounded-2xl bg-slate-900 px-5 py-2 text-2xl font-black tracking-wide text-white shadow-md shadow-slate-900/15">
                                        {{ $classroom?->name ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2">
                                <p class="text-[9px] font-bold text-slate-500">الأفواج التربوية</p>
                                <div class="mt-1 flex flex-wrap justify-end gap-1.5">
                                    @forelse ($groups as $group)
                                        <span class="rounded-full border border-amber-200 bg-white/80 px-2 py-0.5 text-[9px] font-bold text-slate-600">{{ $group->name }}</span>
                                    @empty
                                        <span class="rounded-full border border-amber-200 bg-white/80 px-2 py-0.5 text-[9px] font-bold text-slate-600">كامل القسم</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Left / end: assigned teachers and subjects --}}
                        <div class="border-s border-amber-200/80 ps-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[8px] font-extrabold text-white">{{ $assignments->count() }}</span>
                                <p class="text-[9px] font-extrabold text-amber-900/80">الأساتذة المتدخلون والمواد المسندة</p>
                            </div>

                            <div class="mt-2 space-y-1">
                                @forelse ($assignments as $assignment)
                                    @php
                                        $assignmentTeacher = $assignment['teacher'];
                                        $assignmentSubject = $assignment['subject'];
                                        $assignmentColor = $subjectColor($assignmentSubject?->color);
                                        $assignmentForeground = $subjectTextColor($assignmentColor);
                                    @endphp

                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-amber-200/80 bg-white/85 px-2 py-1.5">
                                        <div class="min-w-0 text-right">
                                            <p class="truncate text-[9px] font-extrabold text-slate-800">{{ $assignmentTeacher?->name ?? 'غير محدد' }}</p>

                                            @if ($assignmentSubject?->name)
                                                <span
                                                    class="mt-0.5 inline-flex max-w-full items-center truncate rounded px-1.5 py-0.5 text-[8px] font-extrabold"
                                                    style="background-color: {{ $assignmentColor }}; color: {{ $assignmentForeground }};"
                                                >
                                                    {{ $assignmentSubject->name }}
                                                </span>
                                            @else
                                                <span class="text-[8px] font-semibold text-slate-400">—</span>
                                            @endif
                                        </div>

                                        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[8px] font-extrabold text-amber-900">{{ $assignment['count'] }} حصة</span>
                                    </div>
                                @empty
                                    <p class="text-[9px] font-semibold text-slate-400">لا توجد حصص مسجلة.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    {{-- ==================== Class register entries ==================== --}}
                    <section class="mt-5">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-xs font-black text-slate-800">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                سجل الحصص المنجزة — {{ $classroom?->name ?? 'قسم غير محدد' }}
                            </h2>
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[9px] font-bold text-amber-800">
                                {{ $registerCount }} سجل
                            </span>
                        </div>

                        {{-- overflow stays visible so the table can fragment cleanly across A4 pages. --}}
                        <div class="overflow-visible rounded-2xl border border-amber-200 bg-white/60 shadow-sm">
                            <table class="register-table w-full table-fixed text-right text-[11px]">
                                <thead>
                                    <tr class="bg-slate-900 text-white">
                                        <th class="w-[12%] border-b-2 border-amber-400 border-e border-white/10 px-2.5 py-3 text-center text-[9px] font-extrabold">
                                            التاريخ والتوقيت
                                        </th>
                                        <th class="w-[21%] border-b-2 border-amber-400 border-e border-white/10 px-2.5 py-3 text-[9px] font-extrabold">
                                            المادة والأستاذ(ة) والفوج
                                        </th>
                                        <th class="w-[18%] border-b-2 border-amber-400 border-e border-white/10 px-2.5 py-3 text-[9px] font-extrabold">
                                            عنوان الدرس
                                        </th>
                                        <th class="w-[32%] border-b-2 border-amber-400 border-e border-white/10 px-2.5 py-3 text-[9px] font-extrabold">
                                            المحتوى المنجز (سيرورة الحصة)
                                        </th>
                                        <th class="w-[17%] border-b-2 border-amber-400 px-2.5 py-3 text-[9px] font-extrabold">
                                            الواجب والملاحظات
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($registerLogs as $log)
                                        @php
                                            $schedule = $log->schedule;
                                            $rowSubject = $schedule?->subject;
                                            $rowTeacher = $schedule?->user;
                                            $rowGroup = $schedule?->group;
                                            $rowClassroom = $schedule?->classroom ?? $rowGroup?->classroom ?? $classroom;
                                            $rowSubjectColor = $subjectColor($rowSubject?->color);
                                            $rowSubjectForeground = $subjectTextColor($rowSubjectColor);
                                            $rowDay = $schedule?->day_of_week;
                                            $weekdayLabel = $rowDay instanceof \App\Enums\DayOfWeek ? ($arabicWeekdays[$rowDay->value] ?? '—') : '—';
                                            $hasHomework = filled($log->homework);
                                            $hasObservations = filled($log->observations);
                                        @endphp

                                        <tr class="register-entry {{ $loop->even ? 'bg-amber-50/35' : 'bg-white/80' }} align-top">
                                            <td class="border-b border-e border-amber-100 px-2.5 py-3">
                                                <div class="inline-flex items-center rounded-md bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-900">
                                                    {{ $log->date?->format('Y/m/d') ?? '—' }}
                                                </div>

                                                <p class="mt-1 text-[9px] font-extrabold text-amber-800">
                                                    {{ $weekdayLabel }}
                                                </p>

                                                @if ($schedule?->start_time)
                                                    <div class="mt-1 inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white/80 px-2 py-0.5 text-[9px] font-semibold text-slate-600">
                                                        <svg class="h-3 w-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                        </svg>
                                                        <span dir="ltr" class="tabular-nums">
                                                            {{ $schedule->start_time->format('H:i') }}
                                                            @if ($schedule->end_time)
                                                                — {{ $schedule->end_time->format('H:i') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="border-b border-e border-amber-100 px-2.5 py-3">
                                                @if ($rowSubject?->name)
                                                    <span
                                                        class="inline-flex max-w-full items-center rounded-md border px-2 py-1 text-[9px] font-extrabold leading-4 shadow-sm"
                                                        style="background-color: {{ $rowSubjectColor }}; border-color: {{ $rowSubjectColor }}; color: {{ $rowSubjectForeground }};"
                                                    >
                                                        {{ $rowSubject->name }}
                                                    </span>
                                                @endif

                                                <p class="mt-1 text-[9px] font-semibold leading-4 text-slate-600">
                                                    الأستاذ(ة):
                                                    <span class="font-extrabold text-slate-800">{{ $rowTeacher?->name ?? '—' }}</span>
                                                </p>

                                                <p class="mt-0.5 rounded-md border border-amber-100 bg-amber-50/70 px-1.5 py-0.5 text-[8px] font-bold text-amber-900">
                                                    @if ($rowGroup)
                                                        {{ $rowClassroom?->name ?? $classroom?->name ?? '—' }} — {{ $rowGroup->name }}
                                                    @else
                                                        كامل القسم: {{ $rowClassroom?->name ?? $classroom?->name ?? '—' }}
                                                    @endif
                                                </p>
                                            </td>

                                            <td class="border-b border-e border-amber-100 px-2.5 py-3">
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500 shadow-sm shadow-amber-500/40"></span>
                                                    <span class="font-extrabold leading-6 text-slate-900">{{ $log->title }}</span>
                                                </div>
                                            </td>

                                            <td class="border-b border-e border-amber-100 px-2.5 py-3">
                                                @if (filled($log->actual_content))
                                                    <div class="rich-text text-slate-700">
                                                        {!! strip_tags((string) $log->actual_content, $allowedTags) !!}
                                                    </div>
                                                @else
                                                    <span class="inline-flex rounded-md bg-slate-50 px-2 py-1 text-[10px] font-semibold text-slate-400">—</span>
                                                @endif

                                                @if ($log->isReviewed())
                                                    <div class="mt-2 rounded-lg border border-emerald-300 bg-emerald-50 px-2 py-1.5 text-[9px] text-emerald-950">
                                                        <p class="font-extrabold">
                                                            {{ __('تأشيرة الإدارة') }}: {{ $log->reviewer?->name ?? '—' }}
                                                            — {{ __('التاريخ') }}: {{ $log->reviewed_at?->format('Y-m-d') }}
                                                        </p>
                                                        @if (filled($log->admin_notes))
                                                            <p class="mt-1 whitespace-pre-line font-semibold">
                                                                {{ __('ملاحظات الإدارة / التوجيه التربوي') }}: {{ $log->admin_notes }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="border-b border-amber-100 px-2.5 py-3">
                                                @if ($hasHomework)
                                                    <div class="rounded-lg border border-amber-100 bg-amber-50/60 px-2.5 py-2">
                                                        <p class="text-[8px] font-extrabold text-amber-800">الواجب</p>
                                                        <p class="mt-0.5 text-[10px] font-semibold leading-5 text-slate-700">
                                                            {!! nl2br(e($log->homework)) !!}
                                                        </p>
                                                    </div>
                                                @endif

                                                @if ($hasObservations)
                                                    <div class="mt-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2">
                                                        <p class="text-[8px] font-extrabold text-slate-500">ملاحظات</p>
                                                        <p class="mt-0.5 text-[10px] font-semibold leading-5 text-slate-600">
                                                            {!! nl2br(e($log->observations)) !!}
                                                        </p>
                                                    </div>
                                                @endif

                                                @unless ($hasHomework || $hasObservations)
                                                    <span class="inline-flex rounded-md bg-slate-50 px-2 py-1 text-[10px] font-semibold text-slate-400">—</span>
                                                @endunless
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-10 text-center">
                                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2 rounded-2xl border border-dashed border-amber-300 bg-amber-50/70 px-6 py-8">
                                                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-500 text-xl font-black text-white shadow-md shadow-amber-500/25">س</span>
                                                    <p class="text-sm font-extrabold text-amber-900">لا توجد سجلات منجزة مطابقة للاختيار.</p>
                                                    <p class="text-[10px] font-semibold text-amber-700/70">اختر سجلات منجزة ثم أعد إنشاء الدفتر.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- ==================== Official signatures ==================== --}}
                    <footer class="signatures mt-5 break-inside-avoid">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-dashed border-amber-300 bg-white/70 p-3 text-center shadow-sm">
                                <p class="text-[10px] font-extrabold text-amber-900">إمضاء الأستاذ(ة)</p>
                                <div class="mt-8 border-t border-dotted border-slate-400"></div>
                                <p class="mt-1 text-[8px] font-semibold text-slate-400">التاريخ</p>
                            </div>

                            <div class="rounded-xl border border-dashed border-amber-300 bg-white/70 p-3 text-center shadow-sm">
                                <p class="text-[10px] font-extrabold text-amber-900">تأشيرة السيد(ة) المدير(ة)</p>
                                <div class="mt-8 border-t border-dotted border-slate-400"></div>
                                <p class="mt-1 text-[8px] font-semibold text-slate-400">الختم والإمضاء</p>
                            </div>

                            <div class="rounded-xl border border-dashed border-amber-300 bg-white/70 p-3 text-center shadow-sm">
                                <p class="text-[10px] font-extrabold text-amber-900">تأشيرة السيد(ة) المفتش(ة)</p>
                                <div class="mt-8 border-t border-dotted border-slate-400"></div>
                                <p class="mt-1 text-[8px] font-semibold text-slate-400">الختم والإمضاء</p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-dashed border-amber-200 pt-2 text-[8px] font-semibold text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="grid h-4 w-4 place-items-center rounded bg-amber-500 text-[8px] font-black text-white">س</span>
                                دفتر قسم {{ $classroom?->name ?? '—' }} — وثيقة رسمية من منصة سجِّل
                            </span>
                            <span>{{ now()->format('Y/m/d — H:i') }}</span>
                        </div>
                    </footer>
                </div>
            </section>
        @endforeach
    </main>

    @if ($autoPrint ?? true)
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 300);
            });
        </script>
    @endif
</body>
</html>
