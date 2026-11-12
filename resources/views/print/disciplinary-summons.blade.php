@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $record->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $record->student;
    $classroom = $student?->group?->classroom;
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استدعاء للمثول أمام مجلس التأديب') }} — {{ $student?->full_name }} — {{ $year }}</title>

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
            margin: 12mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 15px;
        }

        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 15mm 18mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        .double-border {
            border: 3px double #0f172a;
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
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen Action Bar --}}
    <div class="no-print max-w-[210mm] mx-auto mb-4 flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-800">⚖️ {{ __('استدعاء رسمي لمجلس التأديب') }}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $record->session_number ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow transition flex items-center gap-1.5 cursor-pointer">
                🖨️ {{ __('طباعة الاستدعاء (A4)') }}
            </button>
            <a href="{{ route('print.disciplinary.summons.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📥 {{ __('تحميل كملف PDF') }}
            </a>
            <a href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📝 {{ __('محضر الجلسة') }}
            </a>
        </div>
    </div>

    {{-- Printable A4 Sheet --}}
    <div class="sheet-container">

        {{-- Official Algerian Republic Header --}}
        <div class="text-center pb-3 border-b border-slate-200">
            <h1 class="text-base font-extrabold text-slate-900 tracking-wide font-traditional">
                {{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}
            </h1>
            <h2 class="text-sm font-bold text-slate-800 mt-0.5">
                {{ __('وزارة التربية الوطنية') }}
            </h2>
            <div class="flex items-center justify-between text-xs text-slate-600 mt-2 px-2">
                <div class="text-start">
                    <p>{{ $schoolSetting?->directorate_name ?? __('مديرية التربية لولاية الجزائر وسط') }}</p>
                    <p class="font-extrabold text-slate-900">{{ $schoolSetting?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                </div>
                <div class="text-center font-mono">
                    <p>{{ __('السنة الدراسية') }}: <strong>{{ $year }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('جلسة رقم') }}: <strong>{{ $record->session_number ?? '—' }}</strong></p>
                </div>
                <div class="text-end font-mono">
                    <p>{{ __('تاريخ الإرسال') }}: <strong>{{ $formatDate($record->summons_sent_at ?? now()) }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('تاريخ الواقعة') }}: {{ $formatDate($record->incident_date) }}</p>
                </div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="my-6 text-center">
            <div class="inline-block px-8 py-2 rounded-xl double-border bg-amber-50/50">
                <h3 class="text-lg font-black text-slate-900 tracking-wider">
                    {{ __('استدعاء للمثول أمام مجلس التأديب') }}
                </h3>
                <p class="text-[11px] font-bold text-slate-600 tracking-widest uppercase mt-0.5">
                    Convocation Devant le Conseil de Discipline
                </p>
            </div>
        </div>

        {{-- Addressee Block --}}
        <div class="bg-slate-50/80 border border-slate-200 rounded-xl p-4 mb-5 text-xs text-slate-800 leading-relaxed">
            <p class="text-sm font-extrabold text-slate-900 font-traditional mb-1">
                {{ __('إلى السيد(ة) ولي أمر التلميذ(ة):') }}
            </p>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div>
                    <span class="text-slate-500">{{ __('اسم ولقب التلميذ(ة)') }}:</span>
                    <strong class="text-sm font-extrabold text-slate-950 font-traditional">{{ $student?->full_name }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('القسم والفوج التربوي') }}:</span>
                    <strong class="text-amber-950 font-bold">{{ $classroom?->name ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('رقم القيد والتسجيل') }}:</span>
                    <strong class="font-mono text-slate-700">{{ $student?->matricule_number ?? $student?->registration_number ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('درجة المخالفة المنسوبة') }}:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        {{ $record->infraction_degree?->label() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Hearing Appointment Strip --}}
        <div class="p-4 rounded-xl border-2 border-amber-300 bg-amber-50/60 mb-5 text-xs text-amber-950 leading-relaxed">
            <p class="font-bold text-sm text-slate-950 mb-1">
                📅 {{ __('الموعد ومكان انعقاد الجلسة:') }}
            </p>
            <p>
                {{ __('يُطلب منكم الحضور رفقة ابنكم (ابنتكم) للمثول أمام مجلس التأديب المنعقد بمقر المؤسسة (مكتب المدير)، وذلك يوم:') }}
                <strong class="text-sm font-extrabold text-slate-900 font-mono">{{ $formatDateTime($record->session_date) }}</strong>.
            </p>
        </div>

        {{-- Incident Particulars --}}
        <div class="border border-slate-200 rounded-xl p-4 mb-5 text-xs text-slate-800 leading-relaxed">
            <h4 class="font-black text-slate-900 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                <span>⚠️</span> {{ __('موضوع الإحالة والوقائع المنسوبة:') }}
            </h4>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-slate-800 font-traditional text-sm leading-relaxed">
                {{ $record->incident_description }}
            </div>
        </div>

        {{-- Legal Safeguards & Regulatory Notice --}}
        <div class="bg-slate-50 border border-slate-300 rounded-xl p-4 mb-6 text-xs text-slate-800 leading-relaxed font-traditional space-y-2">
            <p class="font-bold text-slate-950">
                📜 {{ __('الضمانات القانونية وحقوق الدفاع (وفقاً للنظام الداخلي للمؤسسة):') }}
            </p>
            <p>
                • {{ __('إن حضور ولي الأمر إجباري لمرافقة التلميذ وضمان ممارسة حقه في الدفاع والإدلاء بأقواله وتوضيحاته بكل حرية أمام أعضاء المجلس.') }}
            </p>
            <p>
                • {{ __('في حالة الغياب غير المبرر لولي الأمر بعد التبليغ القانوني، فإن مجلس التأديب ينعقد في الموعد المحدد ويبتّ في القضية حضورياً وتكون قراراته نافذة وفقاً للتشريع المدرسي.') }}
            </p>
        </div>

        {{-- Signature Block --}}
        <div class="flex items-center justify-between text-xs text-center mb-6">
            <div>
                <p class="font-bold text-slate-700">{{ __('المستشار الرئيسي للتربية') }}</p>
                <div class="h-12 w-32 mx-auto"></div>
            </div>

            <div>
                <p class="font-bold text-slate-900">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                <p class="text-[10px] text-slate-500">{{ $schoolSetting?->director_name ?? __('الإدارة') }}</p>
                <div class="h-12 w-36 mx-auto"></div>
            </div>
        </div>

        {{-- Tear-off Acknowledgment Slip (وصل الاستلام المقتطع) --}}
        <div class="border-t-2 border-dashed border-slate-400 pt-4 mt-6">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-2">
                <span>✂️ {{ __('وصل استلام استدعاء مجلس التأديب والتأكيد على الحضور (يُعاد فوراً إلى مصلحة التمدرس)') }}</span>
                <span>Ref: SUM-{{ $record->id }}</span>
            </div>

            <div class="bg-slate-50 border border-slate-300 rounded-lg p-3 text-xs leading-relaxed">
                <p class="font-bold text-slate-900">
                    {{ __('أنا الموقع أسفله، السيد(ة): ............................................ ولي أمر التلميذ(ة): :name (قسم: :cls)، أشهد بأنني استلمت الاستدعاء للمثول أمام مجلس التأديب المبرمج بتاريخ :date وألتزم بالحضور في الموعد المحدد.', [
                        'name' => $student?->full_name,
                        'cls' => $classroom?->name ?? '—',
                        'date' => $formatDateTime($record->session_date),
                    ]) }}
                </p>
                <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-200 text-[11px]">
                    <span class="text-slate-500">{{ __('تاريخ الاستلام') }}: ..... / ..... / 2026</span>
                    <span class="font-bold text-slate-800">{{ __('توقيع ولي الأمر') }}: ........................................</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

