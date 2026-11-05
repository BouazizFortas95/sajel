@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $teachersList = $teacher ? $teacher->name : ($stats['distinct_teachers']->isNotEmpty() ? $stats['distinct_teachers']->implode(' / ') : __('الأساتذة المشرفون'));
    $subjectsList = $subject ? $subject->name : ($stats['distinct_subjects']->isNotEmpty() ? $stats['distinct_subjects']->implode(' — ') : __('كافة المواد التعليمية'));
    $allowedTags = '<p><br><strong><b><em><u><s><ul><ol><li><h2><h3><h4><h5><h6><blockquote><span>';
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('دفتر النصوص السنوي المجلد') }} — {{ $classroom->name }} — {{ $year }}</title>

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
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 15px;
        }

        .book-container {
            max-width: 210mm;
            margin: 0 auto;
        }

        .page-sheet {
            background: #ffffff;
            padding: 12mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            page-break-after: always;
            position: relative;
            min-height: 275mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .page-sheet:last-child {
            page-break-after: auto;
            margin-bottom: 0;
        }

        .font-traditional {
            font-family: 'Amiri', 'Cairo', serif;
        }

        /* Cover Page Styling */
        .cover-outer-border {
            position: absolute;
            top: 6mm;
            bottom: 6mm;
            left: 6mm;
            right: 6mm;
            border: 3px double #d97706;
            pointer-events: none;
        }

        .cover-inner-border {
            position: absolute;
            top: 8mm;
            bottom: 8mm;
            left: 8mm;
            right: 8mm;
            border: 1px solid #f59e0b;
            pointer-events: none;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: right;
            font-size: 11.5px;
        }

        .data-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }

        .data-table td.center {
            text-align: center;
        }

        .log-entry-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 12px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        .rich-text {
            font-size: 11px;
            line-height: 1.7;
            color: #1e293b;
        }

        .rich-text > :first-child {
            margin-top: 0;
        }

        .rich-text > :last-child {
            margin-bottom: 0;
        }

        .rich-text p {
            margin: 0 0 .35rem;
        }

        .rich-text p:last-child {
            margin-bottom: 0;
        }

        .rich-text h2,
        .rich-text h3,
        .rich-text h4 {
            margin: .45rem 0 .25rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .rich-text h2 {
            color: #78350f;
            font-size: 12px;
        }

        .rich-text h3 {
            display: inline-block;
            padding: .1rem .4rem;
            border-inline-start: 3px solid #f59e0b;
            border-radius: .35rem;
            background: rgba(254, 243, 199, .6);
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
            padding-inline-start: 1.25rem;
        }

        .rich-text ul {
            list-style: disc;
        }

        .rich-text ol {
            list-style: decimal;
        }

        .rich-text li {
            margin-bottom: .2rem;
            padding-inline-start: .1rem;
        }

        .rich-text li::marker {
            color: #d97706;
            font-weight: 700;
        }

        .rich-text strong {
            color: #0f172a;
            font-weight: 800;
        }

        .rich-text blockquote {
            margin: .35rem 0;
            padding: .3rem .5rem;
            border-inline-start: 3px solid #fcd34d;
            border-radius: .35rem;
            background: rgba(255, 251, 235, .8);
            color: #57534e;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .book-container {
                max-width: 100% !important;
            }

            .page-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: 100vh !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print max-w-4xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">📚</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('الكتاب السنوي المجلد لدفتر النصوص الرسمي') }}</h1>
                <p class="text-xs text-slate-400">{{ $classroom->name }} — {{ $subjectsList }} ({{ $logs->count() }} {{ __('حصة منجزة') }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الكتاب كاملاً') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="book-container">

        {{-- ========================================== --}}
        {{-- 1. OFFICIAL COVER PAGE (صفحة الغلاف الرسمية) --}}
        {{-- ========================================== --}}
        <div class="page-sheet text-center relative justify-between py-12 px-10">
            <div class="cover-outer-border"></div>
            <div class="cover-inner-border"></div>

            {{-- Republic Header --}}
            <div class="relative z-10 pt-4">
                <p class="text-base font-bold text-slate-900 tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-sm font-semibold text-slate-700 mt-1">{{ __('وزارة التربية الوطنية') }}</p>
                <p class="text-xs text-slate-600 mt-0.5">{{ __('مديرية التربية لولاية') }} {{ $school?->wilaya ?? 'الجزائر' }}</p>
                <div class="w-24 h-0.5 bg-amber-500 mx-auto my-3"></div>
                <h3 class="text-lg font-black text-amber-900 uppercase tracking-wider">{{ $school?->school_name ?? __('المتوسطة') }}</h3>
            </div>

            {{-- Main Title --}}
            <div class="relative z-10 my-8 py-8 border-y-2 border-amber-400/60 bg-amber-50/40 rounded-xl">
                <span class="text-4xl block mb-2">📖</span>
                <h1 class="text-3xl font-black text-amber-950 font-traditional tracking-wide leading-tight">
                    {{ __('دَفْتَرُ النُّصُوصِ السَّنَوِيُّ الرَّسْمِيُّ') }}
                </h1>
                <p class="text-xs font-bold text-slate-600 tracking-widest uppercase mt-2">
                    REGISTRE OFFICIEL DES TEXTES DE LA CLASSE
                </p>
                <p class="text-sm font-semibold text-amber-800 mt-2">
                    {{ $period['name'] }}
                </p>
            </div>

            {{-- Class and Pedagogical Metadata --}}
            <div class="relative z-10 max-w-lg mx-auto bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3 text-right">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('المستوى والطور التعليمي') }}:</span>
                    <span class="text-sm font-extrabold text-slate-900">{{ $classroom->level?->name }} ({{ $classroom->level?->educationPhase?->name }})</span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('القسم التربوي') }}:</span>
                    <span class="text-base font-black text-amber-900">{{ $classroom->name }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('المادة التعليمية') }}:</span>
                    <span class="text-sm font-extrabold text-slate-900">{{ $subjectsList }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('الأستاذ(ة) المشرف(ة)') }}:</span>
                    <span class="text-sm font-bold text-slate-800">{{ $teachersList }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500">{{ __('الموسم الدراسي') }}:</span>
                    <span class="text-sm font-mono font-bold text-slate-900">{{ $year }}</span>
                </div>
            </div>

            {{-- Footer Archival Code --}}
            <div class="relative z-10 pb-4">
                <div class="inline-block px-4 py-1.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-mono font-semibold border border-slate-200">
                    {{ __('رمز الأرشفة الرقمية') }}: <strong class="text-slate-900">{{ $archivalCode }}</strong>
                </div>
                <p class="text-[10px] text-slate-400 mt-2">{{ __('تم التوليد والحفظ عبر منصة سجِّل الرقمية') }} · {{ $formatDate($generatedAt) }}</p>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- 2. CURRICULUM & DOMAIN PROGRESS INDEX (فهرس الميادين والمقاطع) --}}
        {{-- ============================================================== --}}
        <div class="page-sheet">
            <div>
                {{-- Header Strip --}}
                <div class="flex justify-between items-center border-b-2 border-slate-800 pb-3 mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">{{ __('فهرس الميادين والمقاطع التعلمية المنجزة') }}</h2>
                        <p class="text-xs text-slate-500">{{ $classroom->name }} — {{ $subjectsList }} — {{ $year }}</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                        {{ $curriculumIndex['taught_resources_count'] }} {{ __('مورد معرفي منجز') }}
                    </span>
                </div>

                {{-- Coverage Stats Banner --}}
                @if ($curriculumIndex['completion_rate'] !== null)
                    <div class="bg-amber-50/70 border border-amber-200 rounded-lg p-3 mb-4 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-900">{{ __('نسبة تغطية المنهاج الوزاري السنوي للمادة') }}:</span>
                            <span class="text-xs text-slate-600 mr-2">({{ $curriculumIndex['taught_resources_count'] }} {{ __('من أصل') }} {{ $curriculumIndex['total_official_resources'] }} {{ __('مورد مقرّر') }})</span>
                        </div>
                        <span class="text-base font-black text-amber-950">{{ $curriculumIndex['completion_rate'] }}%</span>
                    </div>
                @endif

                {{-- Domains & Sequences Table --}}
                @if (!empty($curriculumIndex['domains']))
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 160px;">{{ __('الميدان التعلمي') }}</th>
                                <th style="width: 170px;">{{ __('المقطع التعلمي') }}</th>
                                <th>{{ __('الموارد المعرفية المنجزة وتاريخ التدريس') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $dIdx = 1; @endphp
                            @foreach ($curriculumIndex['domains'] as $domainData)
                                @php $seqs = array_values($domainData['sequences']); @endphp
                                @foreach ($seqs as $sIdx => $seqData)
                                    <tr>
                                        @if ($sIdx === 0)
                                            <td rowspan="{{ count($seqs) }}" class="center font-bold text-slate-700 bg-slate-50">
                                                {{ $dIdx++ }}
                                            </td>
                                            <td rowspan="{{ count($seqs) }}" class="font-bold text-slate-900 bg-slate-50">
                                                {{ $domainData['name'] }}
                                            </td>
                                        @endif
                                        <td class="font-semibold text-slate-800">
                                            {{ $seqData['name'] }}
                                        </td>
                                        <td>
                                            <ul class="list-disc list-inside space-y-1 text-xs text-slate-700">
                                                @foreach ($seqData['resources'] as $res)
                                                    <li>
                                                        <span>{{ $res['name'] }}</span>
                                                        <span class="text-[10px] text-slate-400 font-mono">({{ $formatDate($res['first_taught_at']) }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 text-center text-slate-500 font-semibold border border-dashed border-slate-300 rounded-lg">
                        {{ __('لم يتم ربط الحصص المسجلة بموارد المنهاج الرسمي بعد.') }}
                    </div>
                @endif
            </div>

            <div class="pt-3 border-t border-slate-200 text-center text-[10px] text-slate-400">
                {{ __('دفتر النصوص السنوي — صفحة الفهرس البيداغوجي') }} · {{ $archivalCode }}
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- 3. CHRONOLOGICAL REGISTER LOGS (سجل الحصص والدروس اليومية)      --}}
        {{-- ============================================================== --}}
        @foreach ($groupedLogs as $trimesterTitle => $months)
            <div class="page-sheet">
                <div>
                    {{-- Trimester Header --}}
                    <div class="flex justify-between items-center border-b-2 border-slate-800 pb-2 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🗓️</span>
                            <h2 class="text-base font-black text-slate-900">{{ $trimesterTitle }}</h2>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">{{ $classroom->name }} — {{ $year }}</span>
                    </div>

                    {{-- Months Iteration --}}
                    @foreach ($months as $monthTitle => $monthLogs)
                        <div class="mb-5">
                            <h3 class="text-xs font-extrabold text-amber-900 bg-amber-100/60 px-3 py-1.5 rounded-lg mb-3 inline-block">
                                📅 {{ $monthTitle }} ({{ $monthLogs->count() }} {{ __('حصة') }})
                            </h3>

                            <div class="space-y-3">
                                @foreach ($monthLogs as $log)
                                    @php
                                        $schedule = $log->schedule;
                                        $timeSlot = ($schedule && $schedule->start_time && $schedule->end_time)
                                            ? $schedule->start_time->format('H:i').' - '.$schedule->end_time->format('H:i')
                                            : '—';
                                        $resource = $log->curriculumResource;
                                    @endphp

                                    <div class="log-entry-card">
                                        {{-- Lesson Header Bar --}}
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                                                    {{ $log->date?->translatedFormat('l') }} {{ $formatDate($log->date) }}
                                                </span>
                                                <span class="text-[11px] font-mono text-slate-500">
                                                    ⏰ {{ $timeSlot }}
                                                </span>
                                                @if ($schedule?->subject)
                                                    <span class="text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                                        {{ $schedule->subject->name }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2 text-[10px]">
                                                <span class="text-slate-500">{{ __('الأستاذ') }}: <strong class="text-slate-800">{{ $schedule?->user?->name }}</strong></span>
                                                @if ($log->reviewed_at)
                                                    <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-bold border border-emerald-200">
                                                        ✓ {{ __('مؤشر إدارياً') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Lesson Title & Resource --}}
                                        <h4 class="text-sm font-extrabold text-slate-900 mb-1">
                                            {{ $log->title }}
                                        </h4>

                                        @if ($resource)
                                            <p class="text-[11px] text-amber-900 font-semibold mb-2">
                                                📍 {{ __('الميدان') }}: {{ $resource->sequence?->domain?->name }} &nbsp;•&nbsp; {{ __('المورد') }}: {{ $resource->name }}
                                            </p>
                                        @endif

                                        {{-- Actual Content / Lesson Phases --}}
                                        <div class="text-xs text-slate-700 leading-relaxed bg-slate-50/60 p-2.5 rounded border border-slate-100 my-2">
                                            @if ($log->actual_content)
                                                <div class="rich-text text-slate-800">
                                                    @if (\Illuminate\Support\Str::contains((string) $log->actual_content, ['<p', '<br', '<div', '<ul', '<ol', '<strong', '<h', '<span']))
                                                        {!! strip_tags((string) $log->actual_content, $allowedTags) !!}
                                                    @else
                                                        {!! nl2br(e($log->actual_content)) !!}
                                                    @endif
                                                </div>
                                            @elseif (!empty($log->content_data))
                                                <div class="space-y-1">
                                                    @foreach ($log->content_data as $block)
                                                        <p class="text-[11px]">
                                                            <strong>{{ $block['title'] ?? $block['phase'] ?? '' }}:</strong>
                                                            <span>{{ $block['content'] ?? '' }}</span>
                                                        </p>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic">{{ __('لا توجد تفاصيل إضافية مسجلة') }}</p>
                                            @endif
                                        </div>

                                        {{-- Homework & Attendance Strip --}}
                                        <div class="flex items-center justify-between text-[11px] text-slate-600 mt-2 pt-1 border-t border-slate-100">
                                            <div>
                                                @if ($log->homework)
                                                    <span>📝 <strong>{{ __('العمل المنزلي') }}:</strong> {{ $log->homework }}</span>
                                                @endif
                                            </div>
                                            <div>
                                                @php
                                                    $presentCount = $log->attendances->where('status', \App\Enums\AttendanceStatus::Present)->count();
                                                    $absentCount = $log->attendances->where('status', \App\Enums\AttendanceStatus::Absent)->count();
                                                @endphp
                                                @if ($presentCount > 0 || $absentCount > 0)
                                                    <span class="text-emerald-700 font-semibold">{{ $presentCount }} {{ __('حاضر') }}</span>
                                                    @if ($absentCount > 0)
                                                        &nbsp;•&nbsp; <span class="text-rose-600 font-bold">{{ $absentCount }} {{ __('غائب') }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-slate-200 text-center text-[10px] text-slate-400">
                    {{ __('دفتر النصوص السنوي — سجل الحصص والدروس اليومية') }} · {{ $archivalCode }}
                </div>
            </div>
        @endforeach

        {{-- ============================================================== --}}
        {{-- 4. ADMINISTRATIVE & INSPECTION VISAS (سجل التأشيرات الإدارية)    --}}
        {{-- ============================================================== --}}
        <div class="page-sheet">
            <div>
                <div class="flex justify-between items-center border-b-2 border-slate-800 pb-3 mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">{{ __('سجل التأشيرات الإدارية والتفتيشية البيداغوجية') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('سجل المتابعة والمراقبة الدورية لدفتر النصوص من طرف الإدارة وهيئة التفتيش') }}</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                        {{ $visas->count() }} {{ __('تأشيرة مسجلة') }}
                    </span>
                </div>

                @if ($visas->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th style="width: 90px;">{{ __('تاريخ التأشيرة') }}</th>
                                <th style="width: 130px;">{{ __('المسؤول / الصفة') }}</th>
                                <th style="width: 140px;">{{ __('الحصة المؤشر عليها') }}</th>
                                <th>{{ __('الملاحظات والتوجيهات البيداغوجية') }}</th>
                                <th style="width: 80px;">{{ __('الحالة') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visas as $vIdx => $visa)
                                <tr>
                                    <td class="center font-bold text-slate-700">{{ $vIdx + 1 }}</td>
                                    <td class="center font-mono">{{ $formatDate($visa['reviewed_at']) }}</td>
                                    <td class="font-bold text-slate-800">{{ $visa['reviewer_name'] }}</td>
                                    <td>
                                        <div class="font-semibold text-slate-900">{{ $visa['lesson_title'] }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $visa['subject'] }} — {{ $formatDate($visa['date']) }}</div>
                                    </td>
                                    <td class="text-xs text-slate-700">
                                        {{ $visa['notes'] ?: __('تمت المعاينة والمصادقة على إنجاز الحصة وفق المنهاج.') }}
                                    </td>
                                    <td class="center font-bold text-emerald-700">
                                        ✓ {{ __('مؤشر') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 text-center text-slate-500 font-semibold border border-dashed border-slate-300 rounded-lg">
                        {{ __('لم يتم تسجيل تأشيرات إدارية على حصص هذا القسم بعد.') }}
                    </div>
                @endif
            </div>

            <div class="pt-3 border-t border-slate-200 text-center text-[10px] text-slate-400">
                {{ __('دفتر النصوص السنوي — سجل التأشيرات الإدارية') }} · {{ $archivalCode }}
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- 5. ANNUAL BALANCE & CLOSING ATTESTATION (الحصيلة والمحضر الختامي) --}}
        {{-- ============================================================== --}}
        <div class="page-sheet justify-between">
            <div>
                <div class="flex justify-between items-center border-b-2 border-slate-800 pb-3 mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">{{ __('الحصيلة الإحصائية السنوية والمحضر الختامي') }}</h2>
                        <p class="text-xs text-slate-500">{{ $classroom->name }} — {{ $year }}</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-700">{{ $archivalCode }}</span>
                </div>

                {{-- Key Statistical Rollup Grid --}}
                <div class="grid grid-cols-3 gap-3 my-4 text-center">
                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-slate-500">{{ __('إجمالي الحصص المنجزة') }}</p>
                        <p class="text-xl font-black text-slate-900 mt-1">{{ $stats['total_sessions'] }}</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-amber-800">{{ __('ساعات التدريس المنجزة') }}</p>
                        <p class="text-xl font-black text-amber-950 mt-1">{{ $stats['total_hours'] }} {{ __('ساعة') }}</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-emerald-800">{{ __('نسبة المواظبة والحضور') }}</p>
                        <p class="text-xl font-black text-emerald-950 mt-1">{{ $stats['presence_rate'] }}%</p>
                    </div>
                    <div class="bg-sky-50 border border-sky-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-sky-800">{{ __('الواجبات والمهام المنزلية') }}</p>
                        <p class="text-xl font-black text-sky-950 mt-1">{{ $stats['homework_count'] }}</p>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-purple-800">{{ __('الموارد المعرفية المغطاة') }}</p>
                        <p class="text-xl font-black text-purple-950 mt-1">{{ $curriculumIndex['taught_resources_count'] }}</p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-slate-500">{{ __('التأشيرات الإدارية المنجزة') }}</p>
                        <p class="text-xl font-black text-slate-900 mt-1">{{ $stats['visas_count'] }}</p>
                    </div>
                </div>

                {{-- Official Archival Legal Attestation --}}
                <div class="my-6 p-4 bg-amber-50/50 border-2 border-amber-300 rounded-xl">
                    <h3 class="text-sm font-extrabold text-amber-950 mb-1">
                        📜 {{ __('محضر الإغلاق والإيداع في الأرشيف الرسمي للمؤسسة') }}
                    </h3>
                    <p class="text-xs font-semibold text-slate-800 leading-relaxed font-traditional">
                        {{ __('يشهد الموقعون أسفله بانتهاء تدريس المنهاج الدراسي المبرمج لهذا القسم، ومطابقة السجلات والحصص المدونة في هذا الدفتر للواقع الميداني والتربوي المعاش طيلة الموسم الدراسي :year، وعليه يُقفل هذا السجل نهائياً ويُودع لدى مصلحة الأرشيف الإداري والتربوي للمؤسسة وفقاً للتشريع المدرسي المعمول به.', ['year' => $year]) }}
                    </p>
                </div>
            </div>

            {{-- 4 Official Signatures Block --}}
            <div class="pt-6 border-t-2 border-slate-800 grid grid-cols-4 gap-4 text-center">
                <div>
                    <p class="text-xs font-bold text-slate-800">{{ __('أستاذ(ة) المادة') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ $teachersList }}</p>
                    <div class="h-10 border-b border-dashed border-slate-300 w-24 mx-auto"></div>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-800">{{ __('ناظر الدروس') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ __('مصلحة التمدرس') }}</p>
                    <div class="h-10 border-b border-dashed border-slate-300 w-24 mx-auto"></div>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-800">{{ __('مفتش التعليم المتوسط') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ __('المقاطعة التفتيشية') }}</p>
                    <div class="h-10 border-b border-dashed border-slate-300 w-24 mx-auto"></div>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-800">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                    <p class="text-[10px] text-slate-500 mb-6">{{ $school?->director_name ?? __('الإدارة') }}</p>
                    <div class="h-10 border-b border-dashed border-slate-300 w-24 mx-auto"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

