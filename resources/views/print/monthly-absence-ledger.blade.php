@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $year ?? ($school?->academic_year ?? '2026/2027');
    $ledgerCode = 'ABS-LEDGER-'.$year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT);
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الكشف الشهري الإجمالي لغيابات الأساتذة وحصص الاستخلاف') }} — {{ $month_name }}</title>

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
            padding: 15px;
        }

        .ledger-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            border: 2px solid #e2e8f0;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .ledger-table th,
        .ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: right;
            font-size: 11px;
        }

        .ledger-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }

        .ledger-table td.center {
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

            .ledger-container {
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
            <span class="text-xl">📊</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('الكشف الإجمالي لغيابات الأساتذة وحصص الاستخلاف والتعويض البيداغوجي') }}</h1>
                <p class="text-xs text-slate-400">{{ $month_name }} — {{ $school?->school_name }} ({{ $ledgerCode }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الكشف كاملاً') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="ledger-container">

        {{-- 1. Official Republic Header --}}
        <div class="pb-3 border-b-2 border-slate-900 mb-4">
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
                    <h2 class="text-base font-black text-slate-950 uppercase border-b-2 border-amber-500 pb-0.5">
                        {{ __('الكشف الإجمالي الشهري لغيابات الأساتذة وحصص الاستخلاف البيداغوجي') }}
                    </h2>
                    <p class="text-xs font-bold text-amber-900 mt-1">
                        {{ __('شهر') }}: {{ $month_name }}
                    </p>
                </div>
                <div class="text-left text-[11px]">
                    <p>{{ __('السنة الدراسية') }}: <span class="font-bold font-mono">{{ $school?->academic_year ?? '2026/2027' }}</span></p>
                    <p class="font-mono text-slate-500">{{ $ledgerCode }}</p>
                </div>
            </div>
        </div>

        {{-- 2. Statistical Header Strip --}}
        <div class="grid grid-cols-4 gap-3 my-3 text-center">
            <div class="bg-slate-50 border border-slate-200 p-2 rounded">
                <p class="text-[10px] text-slate-500 font-semibold">{{ __('إجمالي حالات الغياب المسجلة') }}</p>
                <p class="text-sm font-black text-slate-900">{{ $total_absences }} {{ __('حالة') }}</p>
            </div>
            <div class="bg-rose-50 border border-rose-200 p-2 rounded">
                <p class="text-[10px] text-rose-700 font-semibold">{{ __('إجمالي الساعات المتأثرة (الضائعة)') }}</p>
                <p class="text-sm font-black text-rose-900">{{ $total_hours_missed }} {{ __('سا') }}</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 p-2 rounded">
                <p class="text-[10px] text-emerald-700 font-semibold">{{ __('الساعات المعوضة بالاستخلاف') }}</p>
                <p class="text-sm font-black text-emerald-900">{{ $total_hours_covered }} {{ __('سا') }}</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-2 rounded">
                <p class="text-[10px] text-amber-800 font-semibold">{{ __('نسبة التغطية والاستمرارية') }}</p>
                <p class="text-sm font-black text-amber-900 font-mono">{{ $overall_coverage_rate }}%</p>
            </div>
        </div>

        {{-- 3. Detailed Audit Ledger Table --}}
        @if ($records->isNotEmpty())
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th style="width: 130px;">{{ __('الأستاذ المتغيب') }}</th>
                        <th style="width: 100px;">{{ __('المادة التعليمية') }}</th>
                        <th style="width: 140px;">{{ __('فترة الغياب') }}</th>
                        <th style="width: 50px;">{{ __('المدة') }}</th>
                        <th style="width: 120px;">{{ __('طبيعة الغياب / المرجع') }}</th>
                        <th style="width: 60px;">{{ __('الحصص') }}</th>
                        <th style="width: 60px;">{{ __('الضائعة') }}</th>
                        <th style="width: 60px;">{{ __('المعوضة') }}</th>
                        <th>{{ __('الأساتذة المكلفون بالاستخلاف') }}</th>
                        <th style="width: 85px;">{{ __('الحالة') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $index => $row)
                        <tr>
                            <td class="center font-bold text-slate-700">{{ $index + 1 }}</td>
                            <td class="font-bold text-slate-900">{{ $row['teacher']?->name ?? '—' }}</td>
                            <td class="text-slate-700">{{ $row['subject'] }}</td>
                            <td class="center font-mono text-[10px]">
                                {{ $formatDate($row['starts_at']) }} ← {{ $formatDate($row['ends_at']) }}
                            </td>
                            <td class="center font-bold text-slate-800">{{ $row['duration_days'] }} ي</td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $row['reason_label'] }}</div>
                                <div class="text-[9px] font-mono text-slate-500">{{ $row['reference'] }}</div>
                            </td>
                            <td class="center font-bold text-slate-800">{{ $row['affected_sessions_count'] }}</td>
                            <td class="center font-bold text-rose-700 font-mono">{{ $row['hours_missed'] }} سا</td>
                            <td class="center font-bold text-emerald-700 font-mono">{{ $row['hours_covered'] }} سا</td>
                            <td class="text-xs text-slate-800 font-medium">
                                {{ $row['substitutes_names'] }}
                            </td>
                            <td class="center">
                                @if ($row['status']->value === 'covered')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        ✓ {{ __('مغطى') }}
                                    </span>
                                @elseif ($row['status']->value === 'partially_covered')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                        ⚡ {{ __('جزئي') }}
                                    </span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                        ⚠️ {{ __('شاغر') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold">
                        <td colspan="7" class="text-left pl-3">{{ __('المجموع الإجمالي للشهر:') }}</td>
                        <td class="center font-mono text-rose-700">{{ $total_hours_missed }} سا</td>
                        <td class="center font-mono text-emerald-700">{{ $total_hours_covered }} سا</td>
                        <td colspan="2" class="text-center font-bold text-slate-700 font-mono">
                            {{ __('نسبة التغطية') }}: {{ $overall_coverage_rate }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="py-12 text-center text-slate-500 font-semibold border border-dashed border-slate-300 rounded-lg my-4">
                {{ __('لم تسجل أية غيابات للأساتذة خلال هذا الشهر.') }}
            </div>
        @endif

        {{-- 4. Official Quadripartite Signatures Block --}}
        <div class="grid grid-cols-3 gap-6 text-center text-xs mt-8 pt-4 border-t-2 border-slate-800">
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('مستشار التربية') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('متابعة الانضباط وسجل الدخول والخروج') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2"></div>
            </div>

            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('ناظر الدروس') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('متابعة الاستمرارية البيداغوجية') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2"></div>
            </div>

            <div>
                <p class="font-bold text-slate-900 mb-1">{{ __('مدير المؤسسة') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('المصادقة والتوجيه لمصلحة الموظفين') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2">
                    <span class="inline-block mt-3 text-[10px] text-slate-400 font-semibold border border-slate-300 rounded-full px-3 py-1">
                        {{ __('الختم الرسمي للمؤسسة') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center text-[10px] text-slate-400 font-mono">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — الكشف الشهري لغيابات الأساتذة والاستخلاف') }} · {{ $ledgerCode }}
        </div>

    </div>

</body>
</html>

