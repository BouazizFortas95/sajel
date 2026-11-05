@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $transfer->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادة تحويل وانتقال مدرسية') }} — {{ $student?->full_name }} — {{ $year }}</title>

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

        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12mm 15mm;
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
            <span class="text-sm font-bold text-slate-800">📄 {{ __('شهادة تحويل مدرسية رسمية') }}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $student?->registration_number ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow transition flex items-center gap-1.5 cursor-pointer">
                🖨️ {{ __('طباعة الشهادة (A4)') }}
            </button>
            <a href="{{ route('print.student-transfer.certificate.pdf', ['locale' => app()->getLocale(), 'transfer' => $transfer->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📥 {{ __('تحميل كملف PDF') }}
            </a>
            <a href="{{ route('print.student-transfer.forwarding-slip', ['locale' => app()->getLocale(), 'transfer' => $transfer->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📬 {{ __('إرسالية الملف المدرسي') }}
            </a>
        </div>
    </div>

    {{-- Main Printable A4 Document --}}
    <div class="sheet-container">

        {{-- Official Algerian Republic Header --}}
        <div class="text-center pb-4 border-b border-slate-200">
            <h1 class="text-base font-extrabold text-slate-900 tracking-wide font-traditional">
                {{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}
            </h1>
            <h2 class="text-sm font-bold text-slate-800 mt-0.5">
                {{ __('وزارة التربية الوطنية') }}
            </h2>
            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mt-2 px-2">
                <div>
                    <p>{{ $schoolSetting?->directorate_name ?? __('مديرية التربية لولاية الجزائر وسط') }}</p>
                    <p class="font-extrabold text-slate-900 mt-0.5">{{ $schoolSetting?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                </div>
                <div class="text-center font-mono">
                    <p>{{ __('السنة الدراسية') }}: <strong>{{ $year }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('رقم القيد') }}: <strong>{{ $student?->matricule_number ?? $student?->registration_number ?? '—' }}</strong></p>
                </div>
                <div class="text-end font-mono">
                    <p>{{ __('الرقم التسلسلي') }}: <strong>{{ $transfer->reference_number ?? ('ش.ت/'.$transfer->id.'/'.substr($year, 0, 4)) }}</strong></p>
                    <p class="text-[11px] text-slate-500">{{ __('التاريخ') }}: {{ $formatDate($transfer->transfer_date) }}</p>
                </div>
            </div>
        </div>

        {{-- Certificate Banner Title --}}
        <div class="my-6 text-center">
            <div class="inline-block px-8 py-2 rounded-xl double-border bg-amber-50/40">
                <h3 class="text-lg font-black text-slate-900 tracking-wider">
                    {{ __('شَهَادَةُ تَحْوِيلٍ وَانْتِقَالٍ مَدْرَسِيَّةٍ') }}
                </h3>
                <p class="text-[11px] font-bold text-slate-600 tracking-widest uppercase mt-0.5">
                    Certificat de Transfert et de Changement d'Établissement
                </p>
            </div>
            <div class="mt-2">
                <span class="inline-block px-3 py-0.5 text-xs font-bold rounded-full {{ $transfer->isOutward() ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
                    {{ $transfer->transfer_type?->label() }}
                </span>
            </div>
        </div>

        {{-- Student Identification Block --}}
        <div class="bg-slate-50/80 border border-slate-200 rounded-xl p-4 mb-5">
            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <span>👤</span> {{ __('معلومات التلميذ(ة) الشخصية والمدرسية') }}
            </h4>
            <div class="grid grid-cols-2 gap-y-2.5 gap-x-6 text-xs text-slate-800 leading-relaxed">
                <div>
                    <span class="text-slate-500">{{ __('الاسم واللقب') }}:</span>
                    <strong class="text-sm font-extrabold text-slate-950 font-traditional">{{ $student?->full_name }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('تاريخ ومكان الازدياد') }}:</span>
                    <strong>{{ $formatDate($student?->date_of_birth) }}</strong>
                    @if ($student?->place_of_birth)
                        <span>{{ __('بـ') }}: <strong>{{ $student->place_of_birth }}</strong></span>
                    @endif
                </div>
                <div>
                    <span class="text-slate-500">{{ __('المستوى والقسم المسجل به') }}:</span>
                    <strong class="text-amber-900 font-bold">{{ $level?->name ?? '—' }} ({{ $classroom?->name ?? '—' }})</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('رقم التعريف الوطني (NIN)') }}:</span>
                    <strong class="font-mono text-slate-700">{{ $student?->national_id_number ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('تاريخ أول دخول للمؤسسة') }}:</span>
                    <strong>{{ $formatDate($student?->enrollment_date) }}</strong>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('رقم القيد العام (Matricule)') }}:</span>
                    <strong class="font-mono text-slate-900">{{ $student?->matricule_number ?? $student?->registration_number ?? '—' }}</strong>
                </div>
            </div>
        </div>

        {{-- Transfer Particulars Table --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden mb-5">
            <div class="bg-slate-100/80 px-4 py-2 border-b border-slate-200">
                <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">
                    🏛️ {{ __('بيانات التحويل والمؤسسة المقابلة') }}
                </h4>
            </div>
            <table class="w-full text-xs text-slate-800">
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600 w-1/3">{{ __('المؤسسة الأصلية (الحالية)') }}:</td>
                        <td class="p-2.5 font-extrabold text-slate-900">{{ $transfer->isOutward() ? ($schoolSetting?->school_name ?? __('متوسطتنا')) : $transfer->other_school_name }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600">{{ __('المؤسسة المستقبلة') }}:</td>
                        <td class="p-2.5 font-extrabold text-slate-900 text-amber-950">{{ $transfer->isOutward() ? $transfer->other_school_name : ($schoolSetting?->school_name ?? __('متوسطتنا')) }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600">{{ __('مديرية التربية المستقبلة') }}:</td>
                        <td class="p-2.5">{{ $transfer->other_directorate_name ?? $schoolSetting?->directorate_name }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600">{{ __('سبب التحويل القانوني') }}:</td>
                        <td class="p-2.5 font-bold text-slate-900">{{ $transfer->reason?->label() ?? '—' }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600">{{ __('رقم وتاريخ رخصة/مقرر التحويل') }}:</td>
                        <td class="p-2.5 font-mono">{{ $transfer->reference_number ?? '—' }} {{ $transfer->reference_date ? ('— بتاريخ: '.$formatDate($transfer->reference_date)) : '' }}</td>
                    </tr>
                    <tr>
                        <td class="p-2.5 bg-slate-50 font-bold text-slate-600">{{ __('وضعية الملف وبراءة الذمة') }}:</td>
                        <td class="p-2.5">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="{{ $transfer->clearance_attached ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
                                    ✓ {{ __('براءة الذمة (Quitus): مستوفاة') }}
                                </span>
                                &nbsp;•&nbsp;
                                <span class="{{ $transfer->school_file_forwarded ? 'text-emerald-700 font-bold' : 'text-amber-700 font-semibold' }}">
                                    {{ $transfer->school_file_forwarded ? __('الملف المدرسي: تم إرساله رسمياً') : __('الملف المدرسي: يُرسل لاحقاً عبر البريد الرسمي') }}
                                </span>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Legal Administrative Attestation --}}
        <div class="my-5 p-4 rounded-xl border border-slate-300 bg-amber-50/20 leading-relaxed text-xs text-slate-900 font-traditional">
            <p class="font-bold text-sm mb-1.5 text-slate-950">
                📜 {{ __('إشهاد إداري رسمي:') }}
            </p>
            <p>
                {{ __('يشهد مدير مؤسسة :school بأن التلميذ(ة) المذكور(ة) أعلاه، كان(ت) مسجلاً(ة) بانتظام بمؤسستنا خلال الموسم الدراسي :year بقسم :classroom، وقد غادر(ت) المؤسسة بتاريخ :date بناءً على موافقة الوصاية والوثائق الثبوتية المرفقة، وتُسلّم له(ا) هذه الشهادة لتمكينه(ا) من مواصلة دراسته(ا) النظامية بالمؤسسة المستقبلة وفق التشريع المدرسي المعمول به.', [
                    'school' => $schoolSetting?->school_name ?? __('المؤسسة'),
                    'year' => $year,
                    'classroom' => $classroom?->name ?? '—',
                    'date' => $formatDate($transfer->transfer_date),
                ]) }}
            </p>
            @if ($transfer->notes)
                <p class="mt-2 text-[11px] font-sans text-slate-600 border-t border-amber-200/60 pt-1.5">
                    <strong>{{ __('ملاحظات إضافية') }}:</strong> {{ $transfer->notes }}
                </p>
            @endif
        </div>

        {{-- Notice Warning --}}
        <div class="text-[10px] text-slate-500 mb-6 bg-slate-50 p-2.5 rounded-lg border border-dashed border-slate-200">
            ⚠️ <strong>{{ __('تنبيه نظامي هام') }}:</strong>
            {{ __('لا يُعتبر هذا التحويل نهائياً إلا بعد تأكيد التسجيل الفعلي لدى المؤسسة المستقبلة وإرسال الملف المدرسي الأصلي عبر البريد الإداري الرسمي مصحوباً بجدول الإرسال.') }}
        </div>

        {{-- Signatures Block (Tripartite: Student Service, Studies Director, Principal & Seal) --}}
        <div class="pt-4 border-t-2 border-slate-900 grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <p class="font-bold text-slate-800">{{ __('مصلحة التمدرس والتوثيق') }}</p>
                <p class="text-[10px] text-slate-500 mb-8">{{ __('مستشار التربية') }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
            </div>

            <div>
                <p class="font-bold text-slate-800">{{ __('ناظر الدروس') }}</p>
                <p class="text-[10px] text-slate-500 mb-8">{{ __('رئيس مصلحة التدريس') }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
            </div>

            <div>
                <p class="font-bold text-slate-900">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                <p class="text-[10px] text-slate-500 mb-8">{{ $schoolSetting?->director_name ?? __('الإدارة') }}</p>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto"></div>
                <p class="text-[9px] text-slate-400 font-mono mt-1">{{ __('حرر في') }}: {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Footer Stamp --}}
        <div class="mt-8 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-mono">
            <span>{{ __('منظومة سجل الرقمية لإدارة المتوسطة — شهادة التحويل المدرسي') }}</span>
            <span>{{ __('رقم التحويل') }}: #{{ $transfer->id }}</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>

