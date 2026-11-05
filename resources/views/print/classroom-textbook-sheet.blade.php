@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $sheetCode = 'TB-CLASS-'.$classroom->id.'-'.date('Y');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('سجل تسليم واسترجاع الكتب المدرسية للقسم') }} — {{ $classroom->name }}</title>

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
            padding: 15px;
        }

        .sheet-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            border: 2px solid #e2e8f0;
        }

        .sheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .sheet-table th,
        .sheet-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: right;
            font-size: 10px;
        }

        .sheet-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }

        .sheet-table td.center {
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print max-w-5xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">📋</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('استمارة تسليم واسترجاع الكتب المدرسية للقسم') }}</h1>
                <p class="text-xs text-slate-400">{{ $classroom->name }} — {{ $yearStr }} ({{ $sheetCode }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الاستمارة') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- 1. Official Republic Header --}}
        <div class="pb-3 border-b-2 border-slate-900 mb-3">
            <div class="text-center">
                <p class="text-xs font-bold tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-[11px] font-semibold text-slate-600">{{ __('وزارة التربية الوطنية') }}</p>
            </div>
            <div class="flex justify-between items-center text-xs font-semibold text-slate-700 mt-2">
                <div>
                    <p>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</p>
                    <p class="font-bold text-slate-900">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                </div>
                <div class="text-center">
                    <h2 class="text-sm font-black text-slate-950 uppercase border-b-2 border-amber-500 pb-0.5">
                        {{ __('استمارة تسليم واسترجاع الكتب المدرسية للقسم') }}
                    </h2>
                    <p class="text-xs font-bold text-amber-900 mt-1">
                        {{ __('القسم') }}: <span class="bg-amber-100 px-2 py-0.5 rounded">{{ $classroom->name }}</span>
                    </p>
                </div>
                <div class="text-left text-[11px]">
                    <p>{{ __('السنة الدراسية') }}: <span class="font-bold font-mono">{{ $yearStr }}</span></p>
                    <p class="font-mono text-slate-500">{{ $sheetCode }}</p>
                </div>
            </div>
        </div>

        {{-- 2. Matrix Table --}}
        @if ($students->isNotEmpty() && $textbooks->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 140px;">{{ __('اسم ولقب التلميذ') }}</th>
                            <th style="width: 60px;">{{ __('الفوج') }}</th>
                            @foreach ($textbooks as $tb)
                                <th class="text-[9px]" title="{{ $tb->title }}">{{ $tb->subject?->name }}</th>
                            @endforeach
                            <th style="width: 60px;">{{ __('المجموع') }}</th>
                            <th style="width: 75px;">{{ __('براءة الذمة') }}</th>
                            <th style="width: 80px;">{{ __('توقيع التلميذ/الولي') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $idx => $student)
                            @php
                                $studentLoans = $student->loans;
                                $borrowedCount = $studentLoans->count();
                                $returnedCount = $studentLoans->whereNotNull('returned_at')->count();
                                $allReturned = $borrowedCount > 0 && $borrowedCount === $returnedCount;
                            @endphp
                            <tr>
                                <td class="center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-900">{{ $student->full_name }}</td>
                                <td class="center text-[9px] text-slate-500">{{ $student->group?->name ?? '—' }}</td>
                                @foreach ($textbooks as $tb)
                                    @php
                                        $loan = $studentLoans->firstWhere('textbook_id', $tb->id);
                                    @endphp
                                    <td class="center">
                                        @if ($loan)
                                            @if ($loan->returned_at)
                                                <span class="text-emerald-700 font-bold">✓</span>
                                            @elseif ($loan->distribution_type->value === 'solidarity')
                                                <span class="text-amber-700 font-bold text-[9px]">تضامن</span>
                                            @else
                                                <span class="text-slate-600 font-bold">●</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="center font-mono font-bold text-slate-800">
                                    {{ $returnedCount }} / {{ $borrowedCount }}
                                </td>
                                <td class="center">
                                    @if ($allReturned)
                                        <span class="inline-block px-1 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">✓ بريء</span>
                                    @elseif ($borrowedCount > 0)
                                        <span class="inline-block px-1 rounded text-[9px] font-bold bg-amber-100 text-amber-800">معار</span>
                                    @else
                                        <span class="text-slate-400 text-[9px]">—</span>
                                    @endif
                                </td>
                                <td class="center"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-12 text-center text-slate-500 font-semibold border border-dashed border-slate-300 rounded-lg my-4">
                {{ __('لم يتم العثور على تلاميذ أو كتب مسجلة لهذا القسم.') }}
            </div>
        @endif

        {{-- 3. Legend & Signatures --}}
        <div class="flex items-center justify-between text-[10px] text-slate-500 mt-3 pt-2 border-t border-slate-200">
            <div>
                <strong>{{ __('دليل الرموز:') }}</strong> ● {{ __('مستلم (معار)') }} &nbsp;•&nbsp; <span class="text-emerald-700 font-bold">✓</span> {{ __('مسترجع بحالة حسنة') }} &nbsp;•&nbsp; <strong class="text-amber-700">{{ __('تضامن') }}</strong>: {{ __('حصة التضامن الوطني المجانية') }}
            </div>
            <div>
                <span>{{ __('مستشار التربية') }}: ........................</span> &nbsp;&nbsp;&nbsp;&nbsp;
                <span>{{ __('مسؤول المخزن') }}: ........................</span>
            </div>
        </div>

    </div>

</body>
</html>

