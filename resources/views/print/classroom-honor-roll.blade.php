@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('لوحة الشرف الرسمية') }} — {{ $classroom->name }} — {{ $period['name'] }}</title>

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

        .sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .honor-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .honor-table th,
        .honor-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            text-align: center;
            font-size: 11px;
        }

        .honor-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
        }

        .honor-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print max-w-4xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">📜</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('لوحة الشرف والتفوق الدراسي الرسمية') }}</h1>
                <p class="text-xs text-slate-400">{{ $classroom->name }} — {{ $period['name'] }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة لوحة الشرف') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="sheet">
        {{-- Republic Official Header --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-3 mb-4">
            <div class="text-right">
                <p class="text-xs font-bold text-slate-900">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-[11px] text-slate-700">{{ __('وزارة التربية الوطنية') }}</p>
                <p class="text-[11px] text-slate-700">
                    {{ __('مديرية التربية لولاية') }} {{ $school?->wilaya ?? 'الجزائر' }}
                </p>
            </div>

            <div class="text-center">
                <h1 class="text-base font-extrabold text-slate-900 uppercase">{{ $school?->school_name ?? __('المتوسطة') }}</h1>
                <p class="text-xs font-bold text-amber-700 mt-1">
                    {{ __('الموسم الدراسي') }}: {{ $year }}
                </p>
            </div>

            <div class="text-left">
                <p class="text-xs font-bold text-slate-900">{{ $classroom->name }}</p>
                <p class="text-[11px] text-slate-700">{{ $period['name'] }}</p>
                <p class="text-[10px] text-slate-500">{{ $formatDate($generatedAt) }}</p>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="text-center my-4 bg-amber-50 border-2 border-amber-300 rounded-lg p-2.5">
            <h2 class="text-lg font-black text-amber-950">
                📜 {{ __('قائمة لوحة الشرف والمتفوقين للفصل الدراسي') }}
            </h2>
            <p class="text-xs text-amber-800 font-semibold mt-0.5">
                {{ __('بناءً على نتائج ومداولات مجلس أساتذة القسم') }} — {{ $classroom->name }}
            </p>
        </div>

        {{-- KPI Summary Bar --}}
        <div class="grid grid-cols-4 gap-2 mb-4 text-center">
            <div class="bg-slate-50 border border-slate-200 p-2 rounded">
                <p class="text-[10px] text-slate-500">{{ __('إجمالي المكرمين') }}</p>
                <p class="text-base font-bold text-slate-900">{{ $kpis['total_honored'] }}</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-2 rounded">
                <p class="text-[10px] text-amber-700">{{ __('شهادات الامتياز') }}</p>
                <p class="text-base font-bold text-amber-900">{{ $kpis['excellence_count'] }}</p>
            </div>
            <div class="bg-sky-50 border border-sky-200 p-2 rounded">
                <p class="text-[10px] text-sky-700">{{ __('شهادات التشجيع') }}</p>
                <p class="text-base font-bold text-sky-900">{{ $kpis['encouragement_count'] }}</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 p-2 rounded">
                <p class="text-[10px] text-emerald-700">{{ __('أعلى معدل بالقسم') }}</p>
                <p class="text-base font-bold text-emerald-900">
                    {{ $kpis['highest_average'] !== null ? number_format($kpis['highest_average'], 2) : '—' }} / 20
                </p>
            </div>
        </div>

        {{-- Honored Students Roster Table --}}
        <table class="honor-table">
            <thead>
                <tr>
                    <th style="width: 50px;">{{ __('الترتيب') }}</th>
                    <th style="width: 85px;">{{ __('رقم التسجيل') }}</th>
                    <th style="text-align: right;">{{ __('لقب واسم التلميذ(ة)') }}</th>
                    <th style="width: 70px;">{{ __('الفوج') }}</th>
                    <th style="width: 80px;">{{ __('المعدل العام') }}</th>
                    <th style="width: 130px;">{{ __('التكريم المستحق') }}</th>
                    <th style="width: 140px;">{{ __('ملاحظة مجلس الأساتذة') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($allRankedStudents->filter(fn ($s) => $s['has_awards']) as $row)
                    @php
                        $award = $row['primary_award'];
                    @endphp
                    <tr>
                        <td class="font-bold text-slate-800">
                            {{ $row['rank'] ?? '—' }}
                        </td>
                        <td class="font-mono text-slate-600">
                            {{ $row['registration_number'] ?? '—' }}
                        </td>
                        <td class="text-right font-bold text-slate-900">
                            {{ $row['full_name'] }}
                        </td>
                        <td class="text-slate-600">
                            {{ $row['group'] ?? '—' }}
                        </td>
                        <td class="font-bold text-amber-900 bg-amber-50/50">
                            {{ $row['general_average'] !== null ? number_format($row['general_average'], 2) : '—' }}
                        </td>
                        <td>
                            @if ($award)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold inline-block
                                    {{ match ($award) {
                                        \App\Enums\StudentAwardType::Excellence => 'bg-amber-100 text-amber-800 border border-amber-300',
                                        \App\Enums\StudentAwardType::Encouragement => 'bg-sky-100 text-sky-800 border border-sky-300',
                                        \App\Enums\StudentAwardType::HonorRoll => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
                                        \App\Enums\StudentAwardType::GoodConduct => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                                    } }}">
                                    {{ $award->label() }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-[10px] text-slate-600">
                            @if ($award === \App\Enums\StudentAwardType::Excellence)
                                {{ __('تهنئة ومباركة بتفوق استثنائي') }}
                            @elseif ($award === \App\Enums\StudentAwardType::Encouragement)
                                {{ __('تشجيع ومواصلة العمل الجاد') }}
                            @elseif ($award === \App\Enums\StudentAwardType::HonorRoll)
                                {{ __('تسجيل مستحق في لوحة الشرف') }}
                            @elseif ($award === \App\Enums\StudentAwardType::GoodConduct)
                                {{ __('قدوة في السلوك والانضباط') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-slate-500 font-semibold">
                            {{ __('لا توجد علامات مرصودة أو تكريمات مستحقة لهذا الفصل حتى الآن.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Tripartite Official Signatures Block --}}
        <div class="grid grid-cols-3 gap-6 text-center mt-8 pt-4 border-t-2 border-slate-300">
            <div>
                <p class="text-xs font-bold text-slate-800">{{ __('مندوب أساتذة القسم') }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-800">{{ __('ناظر الدروس / مستشار التربية') }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-800">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                <p class="text-[10px] text-slate-500">{{ $school?->director_name ?? '—' }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
            </div>
        </div>
    </div>

</body>
</html>
