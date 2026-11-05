@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $recordsCollection = collect($records ?? []);
    $statsData = $stats ?? [
        'total' => $recordsCollection->count(),
        'deliberated' => $recordsCollection->where('status', \App\Enums\DisciplinaryStatus::Deliberated)->count(),
        'scheduled' => $recordsCollection->where('status', \App\Enums\DisciplinaryStatus::Scheduled)->count(),
        'exclusions' => $recordsCollection->whereIn('sanction', [\App\Enums\DisciplinarySanction::TemporaryExclusion, \App\Enums\DisciplinarySanction::DefinitiveExclusion])->count(),
        'warnings' => $recordsCollection->whereIn('sanction', [\App\Enums\DisciplinarySanction::Warning, \App\Enums\DisciplinarySanction::Reprimand])->count(),
    ];
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('سجل مجالس التأديب السنوي') }} — {{ $year }}</title>

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

        .sheet-container {
            max-width: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 8mm 10mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .traditional-header {
            font-family: 'Amiri', serif;
        }

        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.ledger-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            border: 1px solid #94a3b8;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
        }

        table.ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: middle;
        }

        table.ledger-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .official-seal {
            border: 2px dashed #94a3b8;
            border-radius: 6px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 10px;
            text-align: center;
            padding: 4px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .sheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            table.ledger-table th {
                background-color: #e2e8f0 !important;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    {{-- Browser Action Toolbar --}}
    <div class="no-print max-w-[297mm] mx-auto mb-3 flex items-center justify-between bg-white border border-slate-200 px-4 py-2.5 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-slate-800"></span>
            <span class="font-bold text-sm text-slate-800">{{ __('السجل السنوي لمجالس التأديب') }}</span>
            <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded font-mono font-bold">{{ $year }}</span>
            <span class="text-xs text-slate-500">({{ $recordsCollection->count() }} {{ __('حالة مسجلة') }})</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('print.disciplinary.ledger.pdf', ['locale' => app()->getLocale(), 'academic_year' => $year]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-rose-700 text-white hover:bg-rose-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('تحميل PDF رسمي') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-slate-800 text-white hover:bg-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة السجل') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- Official Header --}}
        <div class="flex justify-between items-center traditional-header pb-2 border-b-2 border-slate-800 text-xs">
            <div class="text-right">
                <p class="font-bold text-slate-900">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-slate-700 font-bold">{{ __('وزارة التربية الوطنية') }}</p>
                <p class="text-slate-700">{{ __('مديرية التربية لولاية') }}: <span class="font-bold text-slate-900">{{ $schoolSetting?->directorate ?? '................................' }}</span></p>
            </div>
            <div class="text-center font-sans">
                <h1 class="text-base font-black text-slate-900">{{ __('سِجِلّ جَلَسَات مَجْلِس التَّأْدِيب وَالقَرَارَات السُّلوكِيَّة') }}</h1>
                <p class="text-[10px] text-slate-600 font-semibold tracking-wide">Registre Annuel des Conseils de Discipline et Sanctions</p>
            </div>
            <div class="text-left font-sans">
                <p class="font-bold text-slate-800">{{ __('متوسطة') }}: <span class="font-black">{{ $schoolSetting?->school_name ?? '................................' }}</span></p>
                <p class="text-slate-700">{{ __('السنة الدراسية') }}: <span class="font-mono font-bold">{{ $year }}</span></p>
                <p class="text-slate-500 text-[10px]">{{ __('تاريخ الاستخراج') }}: {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Statistical Summary Bar --}}
        <div class="grid grid-cols-5 gap-2 my-2.5 text-center text-[10px]">
            <div class="bg-slate-100 p-1.5 rounded border border-slate-300">
                <span class="text-slate-600 block">{{ __('إجمالي القضايا المسجلة') }}</span>
                <span class="font-mono font-black text-sm text-slate-900">{{ $statsData['total'] ?? $recordsCollection->count() }}</span>
            </div>
            <div class="bg-emerald-50 p-1.5 rounded border border-emerald-300">
                <span class="text-emerald-700 block">{{ __('جلسات مفصول فيها') }}</span>
                <span class="font-mono font-black text-sm text-emerald-900">{{ $statsData['deliberated'] ?? 0 }}</span>
            </div>
            <div class="bg-amber-50 p-1.5 rounded border border-amber-300">
                <span class="text-amber-700 block">{{ __('جلسات مبرمجة قيد النظر') }}</span>
                <span class="font-mono font-black text-sm text-amber-900">{{ $statsData['scheduled'] ?? 0 }}</span>
            </div>
            <div class="bg-blue-50 p-1.5 rounded border border-blue-300">
                <span class="text-blue-700 block">{{ __('عقوبات الإنذار والتوبيخ') }}</span>
                <span class="font-mono font-black text-sm text-blue-900">{{ $statsData['warnings'] ?? 0 }}</span>
            </div>
            <div class="bg-rose-50 p-1.5 rounded border border-rose-300">
                <span class="text-rose-700 block">{{ __('قرارات الإقصاء (مؤقت/نهائي)') }}</span>
                <span class="font-mono font-black text-sm text-rose-900">{{ $statsData['exclusions'] ?? 0 }}</span>
            </div>
        </div>

        {{-- Main Ledger Table --}}
        <div class="overflow-x-auto my-2">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="w-7">#</th>
                        <th class="w-16">{{ __('رقم المحضر') }}</th>
                        <th class="w-20">{{ __('تاريخ الجلسة') }}</th>
                        <th class="w-36">{{ __('اسم ولقب التلميذ') }}</th>
                        <th class="w-20">{{ __('القسم / الفوج') }}</th>
                        <th class="w-24">{{ __('تاريخ الميلاد') }}</th>
                        <th>{{ __('طبيعة المخالفة ودرجتها') }}</th>
                        <th class="w-32">{{ __('العقوبة المقررة') }}</th>
                        <th class="w-24">{{ __('الإقصاء / التعويض') }}</th>
                        <th class="w-24">{{ __('المُحيل / التبليغ') }}</th>
                        <th class="w-20">{{ __('الحالة') }}</th>
                        <th class="w-24">{{ __('ملاحظات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recordsCollection as $index => $record)
                        @php
                            $student = $record->student;
                            $classroom = $student?->group?->classroom;
                        @endphp
                        <tr>
                            <td class="text-center font-mono font-bold">{{ $index + 1 }}</td>
                            <td class="text-center font-mono font-semibold text-slate-700">{{ $record->session_number ?? 'CD-'.$record->id }}</td>
                            <td class="text-center font-mono">{{ $formatDate($record->hearing_date) }}</td>
                            <td class="font-bold text-slate-900">
                                <div>{{ $student?->full_name ?? '—' }}</div>
                                <div class="text-[9px] text-slate-500 font-mono">{{ $student?->registration_number }}</div>
                            </td>
                            <td class="text-center font-medium">{{ $classroom?->name ?? '—' }}</td>
                            <td class="text-center font-mono text-[9px]">{{ $formatDate($student?->birth_date) }}</td>
                            <td class="text-right">
                                <div class="font-bold text-slate-800 text-[9px]">{{ $record->degree?->getLabel() ?? '—' }}</div>
                                <div class="text-slate-600 text-[9px] line-clamp-2" title="{{ $record->incident_description }}">
                                    {{ \Illuminate\Support\Str::limit($record->incident_description, 60) }}
                                </div>
                            </td>
                            <td class="text-center font-bold">
                                @if($record->sanction)
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px]
                                        {{ in_array($record->sanction->value, ['temporary_exclusion', 'definitive_exclusion']) ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800' }}">
                                        {{ $record->sanction->getLabel() }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[9px]">{{ __('قيد المداولة') }}</span>
                                @endif
                            </td>
                            <td class="text-center text-[9px]">
                                @if($record->exclusion_days)
                                    <div class="font-bold text-rose-700">{{ $record->exclusion_days }} {{ __('أيام') }}</div>
                                @endif
                                @if($record->restitution_amount && $record->restitution_amount > 0)
                                    <div class="font-mono text-amber-800 font-semibold">{{ number_format($record->restitution_amount, 0) }} د.ج</div>
                                @endif
                                @if(!$record->exclusion_days && (!$record->restitution_amount || $record->restitution_amount <= 0))
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-center text-[9px]">
                                <div class="text-slate-700">{{ $record->reportedBy?->name ?? __('الإدارة') }}</div>
                                <div class="text-[8px] text-slate-500">
                                    {{ $record->guardian_notified ? __('تم إشعار الولي') : __('لم يُشعر بعد') }}
                                </div>
                            </td>
                            <td class="text-center font-semibold text-[9px]">
                                <span class="inline-block px-1.5 py-0.5 rounded
                                    {{ $record->status === \App\Enums\DisciplinaryStatus::Deliberated ? 'bg-emerald-100 text-emerald-800' :
                                       ($record->status === \App\Enums\DisciplinaryStatus::Scheduled ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800') }}">
                                    {{ $record->status?->getLabel() ?? '—' }}
                                </span>
                            </td>
                            <td class="text-[9px] text-slate-600">
                                {{ \Illuminate\Support\Str::limit($record->decision_notes ?? $record->defense_summary ?? '—', 40) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-6 text-slate-400 italic text-xs">
                                {{ __('لا توجد سجلات مجالس تأديب مقيدة للموسم الدراسي') }} {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Legal Authentication Block & Signatures --}}
        <div class="grid grid-cols-3 gap-6 mt-4 pt-2 border-t border-slate-300 text-center text-xs">
            <div>
                <p class="font-bold text-slate-700 mb-1">{{ __('مستشار(ة) التربية (متابعة السجلات)') }}</p>
                <div class="official-seal">
                    <span>{{ __('التأشيرة والتاريخ') }}</span>
                </div>
            </div>
            <div>
                <p class="font-bold text-slate-700 mb-1">{{ __('ملاحظات وتوجيهات المديرية') }}</p>
                <div class="official-seal text-[9px]">
                    <span>{{ __('مخصص للتفتيش الإداري والتربوي') }}</span>
                </div>
            </div>
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('مدير(ة) المؤسسة — رئيس المجلس') }}</p>
                <div class="official-seal font-bold">
                    <span>{{ __('الختم الرسمي والإمضاء') }}<br><span class="text-[10px] text-slate-500">{{ $schoolSetting?->principal_name }}</span></span>
                </div>
            </div>
        </div>

        {{-- Ledger Footer Metadata --}}
        <div class="flex justify-between items-center text-[8px] text-slate-400 font-mono mt-2 pt-1 border-t border-slate-100">
            <span>{{ __('منظومة سجل الرقمية لإدارة المتوسطة — سجل مجالس التأديب الرسمي المطابق للتنظيم الوزاري') }}</span>
            <span>{{ __('الصفحة 1 من 1') }}</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>
