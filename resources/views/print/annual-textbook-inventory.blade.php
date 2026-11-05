@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $inventoryCode = 'ONPS-INV-'.date('Y');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة السنوية وجرد الكتاب المدرسي') }} — {{ $yearStr }}</title>

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

        .inventory-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            border: 2px solid #e2e8f0;
        }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .inv-table th,
        .inv-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: right;
            font-size: 10.5px;
        }

        .inv-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }

        .inv-table td.center {
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

            .inventory-container {
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
                <h1 class="text-sm font-bold">{{ __('الحصيلة السنوية الشاملة لتسيير وحركة الكتاب المدرسي (ONPS / CRDDP)') }}</h1>
                <p class="text-xs text-slate-400">{{ $school?->school_name }} — {{ $yearStr }} ({{ $inventoryCode }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الحصيلة كاملاً') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="inventory-container">

        {{-- 1. Official Republic Header --}}
        <div class="pb-3 border-b-2 border-slate-900 mb-4">
            <div class="text-center">
                <p class="text-xs font-bold tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-[11px] font-semibold text-slate-600">{{ __('وزارة التربية الوطنية') }} — {{ __('الديوان الوطني للمطبوعات المدرسية (ONPS)') }}</p>
            </div>
            <div class="flex justify-between items-center text-xs font-semibold text-slate-700 mt-2">
                <div>
                    <p>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</p>
                    <p class="font-bold text-slate-900">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('مركز التوزيع والتوثيق البيداغوجي (CRDDP)') }}</p>
                </div>
                <div class="text-center">
                    <h2 class="text-base font-black text-slate-950 uppercase border-b-2 border-amber-500 pb-0.5">
                        {{ __('الحصيلة السنوية الشاملة وجرد حركة الكتاب المدرسي') }}
                    </h2>
                    <p class="text-xs font-bold text-amber-900 mt-1">
                        {{ __('الموسم الدراسي') }}: {{ $yearStr }}
                    </p>
                </div>
                <div class="text-left text-[11px]">
                    <p>{{ __('حرر بتاريخ') }}: <span class="font-bold font-mono">{{ now()->format('d/m/Y') }}</span></p>
                    <p class="font-mono text-slate-500">{{ $inventoryCode }}</p>
                </div>
            </div>
        </div>

        {{-- 2. KPI Summary Cards Strip --}}
        <div class="grid grid-cols-4 gap-3 my-3 text-center">
            <div class="bg-slate-50 border border-slate-200 p-2 rounded">
                <p class="text-[10px] text-slate-500 font-semibold">{{ __('إجمالي النسخ المستلمة') }}</p>
                <p class="text-sm font-black text-slate-900 font-mono">{{ number_format($totalReceived) }} {{ __('نسخة') }}</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 p-2 rounded">
                <p class="text-[10px] text-emerald-700 font-semibold">{{ __('الموزعة (تضامن + بيع)') }}</p>
                <p class="text-sm font-black text-emerald-900 font-mono">{{ number_format($totalDistributed) }} ({{ number_format($totalSolidarity) }} {{ __('مجاني') }})</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-2 rounded">
                <p class="text-[10px] text-amber-800 font-semibold">{{ __('الرصيد المتبقي بالمخزن') }}</p>
                <p class="text-sm font-black text-amber-900 font-mono">{{ number_format($totalInStock) }} {{ __('نسخة') }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 p-2 rounded">
                <p class="text-[10px] text-blue-700 font-semibold">{{ __('القيمة المالية الإجمالية') }}</p>
                <p class="text-sm font-black text-blue-900 font-mono">{{ number_format($totalFinancialValue, 2) }} {{ __('دج') }}</p>
            </div>
        </div>

        {{-- 3. Detailed Inventory Table --}}
        @if ($rows->isNotEmpty())
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>{{ __('عنوان الكتاب المقرر') }}</th>
                        <th style="width: 70px;">{{ __('المستوى') }}</th>
                        <th style="width: 90px;">{{ __('المادة') }}</th>
                        <th style="width: 60px;">{{ __('المستلم') }}</th>
                        <th style="width: 60px;">{{ __('الموزع') }}</th>
                        <th style="width: 65px;">{{ __('التضامن') }}</th>
                        <th style="width: 60px;">{{ __('البيع') }}</th>
                        <th style="width: 60px;">{{ __('المسترجع') }}</th>
                        <th style="width: 65px;">{{ __('بالمخزن') }}</th>
                        <th style="width: 65px;">{{ __('السعر (دج)') }}</th>
                        <th style="width: 85px;">{{ __('المجموع المالي') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $r)
                        <tr>
                            <td class="center font-bold text-slate-700">{{ $index + 1 }}</td>
                            <td class="font-bold text-slate-900">{{ $r['textbook']?->title }}</td>
                            <td class="center text-slate-700 text-[10px]">{{ $r['level'] }}</td>
                            <td class="text-slate-700 text-[10px]">{{ $r['subject'] }}</td>
                            <td class="center font-mono font-bold">{{ $r['received'] }}</td>
                            <td class="center font-mono font-bold text-emerald-700">{{ $r['distributed'] }}</td>
                            <td class="center font-mono text-emerald-800">{{ $r['solidarity'] }}</td>
                            <td class="center font-mono text-slate-700">{{ $r['sold'] }}</td>
                            <td class="center font-mono text-blue-700">{{ $r['returned'] }}</td>
                            <td class="center font-mono font-bold text-amber-800">{{ $r['in_stock'] }}</td>
                            <td class="center font-mono">{{ number_format((float) $r['unit_price'], 2) }}</td>
                            <td class="center font-mono font-bold">{{ number_format($r['financial_value'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold">
                        <td colspan="4" class="text-left pl-3">{{ __('المجموع الإجمالي العام:') }}</td>
                        <td class="center font-mono">{{ number_format($totalReceived) }}</td>
                        <td class="center font-mono text-emerald-700">{{ number_format($totalDistributed) }}</td>
                        <td class="center font-mono text-emerald-800">{{ number_format($totalSolidarity) }}</td>
                        <td class="center font-mono">{{ number_format($totalSold) }}</td>
                        <td class="center font-mono text-blue-700">{{ number_format($totalReturned) }}</td>
                        <td class="center font-mono text-amber-800">{{ number_format($totalInStock) }}</td>
                        <td class="center">—</td>
                        <td class="center font-mono text-blue-900">{{ number_format($totalFinancialValue, 2) }} دج</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="py-12 text-center text-slate-500 font-semibold border border-dashed border-slate-300 rounded-lg my-4">
                {{ __('لم يتم تسجيل أية كتب في المخزن بعد.') }}
            </div>
        @endif

        {{-- 4. Official Quadripartite Signatures Block --}}
        <div class="grid grid-cols-3 gap-6 text-center text-xs mt-6 pt-4 border-t-2 border-slate-800">
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('المسؤول عن مخزن الكتب (المسير)') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('الجرد الفعلي والمطابقة العينية') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2"></div>
            </div>

            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('المقتصد / مسير المصالح المالية') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('التدقيق المالي والمحاسبي') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2"></div>
            </div>

            <div>
                <p class="font-bold text-slate-900 mb-1">{{ __('مدير المؤسسة') }}</p>
                <p class="text-[10px] text-slate-500">{{ __('المصادقة والتوجيه للديوان (ONPS)') }}</p>
                <div class="h-16 border-b border-dashed border-slate-300 mt-2">
                    <span class="inline-block mt-3 text-[10px] text-slate-400 font-semibold border border-slate-300 rounded-full px-3 py-1">
                        {{ __('الختم الرسمي للمؤسسة') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center text-[10px] text-slate-400 font-mono">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — الحصيلة السنوية لجرد الكتاب المدرسي') }} · {{ $inventoryCode }}
        </div>

    </div>

</body>
</html>

