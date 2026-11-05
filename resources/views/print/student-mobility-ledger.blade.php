@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('سجل حركة وتحويلات التلاميذ السنوي') }} — {{ $year }}</title>

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
            padding: 12px;
        }

        .ledger-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.ledger-table th,
        table.ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: start;
        }

        table.ledger-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
        }

        table.ledger-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .ledger-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen Actions Bar --}}
    <div class="no-print max-w-[297mm] mx-auto mb-4 flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-800">📋 {{ __('سجل حركة وتحويلات التلاميذ (A4 عرضي)') }}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $year }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow transition flex items-center gap-1.5 cursor-pointer">
                🖨️ {{ __('طباعة السجل (A4)') }}
            </button>
            <a href="{{ route('print.student-transfer.ledger.pdf', ['locale' => app()->getLocale(), 'year' => $year]) }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📥 {{ __('تحميل كملف PDF') }}
            </a>
        </div>
    </div>

    {{-- Printable Ledger Container --}}
    <div class="ledger-container">

        {{-- Official Header --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-3 mb-3">
            <div>
                <h1 class="text-sm font-bold text-slate-900 font-traditional">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</h1>
                <h2 class="text-xs font-semibold text-slate-800">{{ __('وزارة التربية الوطنية') }} — {{ $schoolSetting?->directorate_name ?? __('مديرية التربية') }}</h2>
                <h3 class="text-xs font-extrabold text-slate-900 mt-0.5">{{ $schoolSetting?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</h3>
            </div>

            <div class="text-center">
                <div class="inline-block px-5 py-1 border-2 border-slate-800 rounded-lg bg-amber-50/50">
                    <h2 class="text-sm font-black text-slate-900 tracking-wider">
                        {{ __('سِجِلُّ حَرَكَةِ التَّلَامِيذِ (الْوَافِدُونَ وَالْمُغَادِرُونَ)') }}
                    </h2>
                    <p class="text-[9px] font-bold text-slate-600 uppercase">
                        Registre de Mouvement et de Mobilité des Élèves
                    </p>
                </div>
            </div>

            <div class="text-end text-xs font-mono">
                <p>{{ __('الموسم الدراسي') }}: <strong>{{ $year }}</strong></p>
                <p class="text-[10px] text-slate-500">{{ __('تاريخ الاستخراج') }}: {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- KPI Ribbon --}}
        <div class="grid grid-cols-6 gap-2 mb-3 text-center">
            <div class="bg-slate-50 border border-slate-200 rounded p-1.5">
                <p class="text-[9px] text-slate-500 font-bold">{{ __('إجمالي الحركات') }}</p>
                <p class="text-sm font-black text-slate-900">{{ $kpis['total_transfers'] ?? $transfers->count() }}</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 rounded p-1.5">
                <p class="text-[9px] text-emerald-800 font-bold">{{ __('الوافدون الجدد') }}</p>
                <p class="text-sm font-black text-emerald-950">{{ $kpis['inward_count'] ?? 0 }}</p>
            </div>
            <div class="bg-rose-50 border border-rose-200 rounded p-1.5">
                <p class="text-[9px] text-rose-800 font-bold">{{ __('المغادرون') }}</p>
                <p class="text-sm font-black text-rose-950">{{ $kpis['outward_count'] ?? 0 }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded p-1.5">
                <p class="text-[9px] text-blue-800 font-bold">{{ __('صافي التدفق') }}</p>
                <p class="text-sm font-black {{ ($kpis['net_flow'] ?? 0) >= 0 ? 'text-blue-950' : 'text-rose-900' }}">
                    {{ ($kpis['net_flow'] ?? 0) > 0 ? '+'.$kpis['net_flow'] : ($kpis['net_flow'] ?? 0) }}
                </p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded p-1.5">
                <p class="text-[9px] text-purple-800 font-bold">{{ __('الملفات المحولة') }}</p>
                <p class="text-sm font-black text-purple-950">{{ $kpis['files_forwarded_count'] ?? 0 }}</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded p-1.5">
                <p class="text-[9px] text-amber-800 font-bold">{{ __('قيد الإجراء') }}</p>
                <p class="text-sm font-black text-amber-950">{{ $kpis['pending_count'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Movement Table --}}
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 65px;">{{ __('رقم القيد') }}</th>
                    <th>{{ __('اسم ولقب التلميذ') }}</th>
                    <th style="width: 80px;">{{ __('الميلاد') }}</th>
                    <th style="width: 65px;">{{ __('القسم') }}</th>
                    <th style="width: 55px;">{{ __('النوع') }}</th>
                    <th>{{ __('المؤسسة المقابلة') }}</th>
                    <th>{{ __('سبب التحويل') }}</th>
                    <th style="width: 80px;">{{ __('مرجع الرخصة') }}</th>
                    <th style="width: 65px;">{{ __('تاريخ التحويل') }}</th>
                    <th style="width: 60px;">{{ __('الملف') }}</th>
                    <th style="width: 60px;">{{ __('الوضعية') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transfers as $index => $row)
                    @php
                        $st = $row->student;
                        $cls = $st?->group?->classroom;
                    @endphp
                    <tr>
                        <td class="text-center font-mono">{{ $index + 1 }}</td>
                        <td class="font-mono font-bold">{{ $st?->matricule_number ?? $st?->registration_number ?? '—' }}</td>
                        <td class="font-extrabold text-slate-900">{{ $st?->full_name }}</td>
                        <td class="text-[9px] font-mono">{{ $formatDate($st?->date_of_birth) }}</td>
                        <td class="font-bold text-amber-950">{{ $cls?->name ?? '—' }}</td>
                        <td class="text-center font-bold">
                            <span class="px-1 py-0.5 rounded {{ $row->isOutward() ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $row->transfer_type?->shortLabel() }}
                            </span>
                        </td>
                        <td>{{ $row->other_school_name }}</td>
                        <td class="text-[9px]">{{ $row->reason?->label() }}</td>
                        <td class="font-mono text-[9px]">{{ $row->reference_number ?? '—' }}</td>
                        <td class="font-mono text-center">{{ $formatDate($row->transfer_date) }}</td>
                        <td class="text-center text-[9px]">
                            @if ($row->school_file_forwarded)
                                <span class="text-emerald-700 font-bold">✓ {{ __('مُرسل') }}</span>
                            @else
                                <span class="text-slate-400">⏳ {{ __('معلق') }}</span>
                            @endif
                        </td>
                        <td class="text-center text-[9px] font-bold">
                            <span class="px-1 py-0.5 rounded {{ $row->isApproved() ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $row->status?->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-6 text-slate-400 italic">
                            {{ __('لا توجد حركات أو تحويلات مسجلة لهذا الموسم حتى الآن.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Verification Block --}}
        <div class="mt-4 pt-3 border-t border-slate-300 flex items-center justify-between text-xs">
            <div class="text-[10px] text-slate-500">
                <p>• {{ __('يُحفظ هذا السجل لدى مصلحة التمدرس ويُعرض على مفتش التربية الوطنية للإدارة عند كل زيارة تفتيشية.') }}</p>
                <p>• {{ __('عدد التلاميذ المسجلين في هذا الكشف') }}: <strong>{{ $transfers->count() }}</strong> {{ __('تلميذ(ة)') }}.</p>
            </div>

            <div class="flex items-center gap-12 text-center text-xs">
                <div>
                    <p class="font-bold text-slate-800">{{ __('مستشار التربية') }}</p>
                    <div class="h-8 border-b border-dashed border-slate-300 w-24 mx-auto"></div>
                </div>

                <div>
                    <p class="font-bold text-slate-900">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                    <div class="h-8 border-b border-dashed border-slate-300 w-28 mx-auto"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

