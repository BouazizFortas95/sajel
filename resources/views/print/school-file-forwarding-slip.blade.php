@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $transfer->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $transfer->student;
    $classroom = $student?->group?->classroom;
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('إرسالية تحويل الملف المدرسي') }} — {{ $student?->full_name }} — {{ $year }}</title>

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

        .letter-container {
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

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .letter-container {
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
    <div class="no-print max-w-[210mm] mx-auto mb-4 flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-800">📬 {{ __('إرسالية تحويل الملف المدرسي الأصلي') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow transition flex items-center gap-1.5 cursor-pointer">
                🖨️ {{ __('طباعة الإرسالية (A4)') }}
            </button>
            <a href="{{ route('print.student-transfer.forwarding-slip.pdf', ['locale' => app()->getLocale(), 'transfer' => $transfer->id]) }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-sm transition flex items-center gap-1.5">
                📥 {{ __('تحميل كملف PDF') }}
            </a>
        </div>
    </div>

    {{-- Printable Letter --}}
    <div class="letter-container">

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
                    <p class="font-bold text-slate-900">{{ $schoolSetting?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                </div>
                <div class="text-end font-mono">
                    <p>{{ __('الرقم') }}: <strong>إ.م/{{ $transfer->id }}/{{ substr($year, 0, 4) }}</strong></p>
                    <p>{{ __('التاريخ') }}: {{ $formatDate($transfer->school_file_forwarded_at ?? now()) }}</p>
                </div>
            </div>
        </div>

        {{-- Destination Address Block --}}
        <div class="my-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-center">
            <p class="text-xs font-bold text-slate-500 uppercase">{{ __('إلى السيد(ة) مدير(ة):') }}</p>
            <h3 class="text-base font-extrabold text-slate-900 mt-1 font-traditional">
                {{ $transfer->isOutward() ? $transfer->other_school_name : ($schoolSetting?->school_name ?? __('مؤسستنا المستقبلة')) }}
            </h3>
            <p class="text-xs font-semibold text-slate-600 mt-0.5">
                {{ $transfer->isOutward() ? ($transfer->other_directorate_name ?? __('مديرية التربية')) : $schoolSetting?->directorate_name }}
            </p>
        </div>

        {{-- Subject & Reference Strip --}}
        <div class="space-y-1.5 text-xs text-slate-800 mb-5">
            <p>
                <strong class="text-slate-900">{{ __('الموضوع') }}:</strong>
                {{ __('إرسال الملف المدرسي الأصلي للتلميذ(ة):') }}
                <strong class="text-sm font-extrabold text-amber-950 font-traditional">{{ $student?->full_name }}</strong>
            </p>
            <p>
                <strong class="text-slate-900">{{ __('المرجع') }}:</strong>
                {{ __('رخصة التحويل رقم :ref المؤرخة في :date.', [
                    'ref' => $transfer->reference_number ?? ('ش.ت/'.$transfer->id),
                    'date' => $formatDate($transfer->reference_date ?? $transfer->transfer_date),
                ]) }}
            </p>
        </div>

        {{-- Body Content --}}
        <div class="text-xs text-slate-800 leading-relaxed font-traditional space-y-3 mb-6">
            <p class="text-sm font-bold text-slate-900">
                {{ __('سلام ختام، وبعد:') }}
            </p>
            <p>
                {{ __('تبعاً لرخصة التحويل المذكورة في المرجع أعلاه، يشرفني أن أوافيكم طيه بالملف المدرسي الأصلي والكامل للتلميذ(ة):') }}
                <strong>{{ $student?->full_name }}</strong>،
                {{ __('المولود(ة) بتاريخ :birth بـ :place، والمسجل(ة) بالسنة الدراسية :year بقسم :classroom تحت رقم القيد :mat.', [
                    'birth' => $formatDate($student?->date_of_birth),
                    'place' => $student?->place_of_birth ?? '—',
                    'year' => $year,
                    'classroom' => $classroom?->name ?? '—',
                    'mat' => $student?->matricule_number ?? $student?->registration_number ?? '—',
                ]) }}
            </p>
            <p>
                {{ __('ويحتوي الملف الإداري والبيداغوجي المرفق على الوثائق التالية:') }}
            </p>
        </div>

        {{-- Checklist of Documents in Dossier --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden mb-6 text-xs">
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-2 text-center w-12">#</th>
                        <th class="p-2 text-start">{{ __('الوثيقة المرفقة بالملف المدرسي') }}</th>
                        <th class="p-2 text-center w-28">{{ __('الحالة') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <tr>
                        <td class="p-2 text-center font-mono">1</td>
                        <td class="p-2 font-bold">{{ __('الدفتر المدرسي / دفتر الملاحظات والمتابعة البيداغوجية') }}</td>
                        <td class="p-2 text-center font-bold text-emerald-700">✓ {{ __('مرفق') }}</td>
                    </tr>
                    <tr>
                        <td class="p-2 text-center font-mono">2</td>
                        <td class="p-2 font-bold">{{ __('الملف الصحي المدرسي وبطاقة التلقيحات (UDS)') }}</td>
                        <td class="p-2 text-center font-bold text-emerald-700">✓ {{ __('مرفق') }}</td>
                    </tr>
                    <tr>
                        <td class="p-2 text-center font-mono">3</td>
                        <td class="p-2 font-bold">{{ __('كشوف النقاط السنوية والفصلية لجميع سنوات التمدرس') }}</td>
                        <td class="p-2 text-center font-bold text-emerald-700">✓ {{ __('مرفق') }}</td>
                    </tr>
                    <tr>
                        <td class="p-2 text-center font-mono">4</td>
                        <td class="p-2 font-bold">{{ __('شهادة براءة الذمة الرسمية (Quitus) للكتاب والوسائل المدرسية') }}</td>
                        <td class="p-2 text-center font-bold {{ $transfer->clearance_attached ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $transfer->clearance_attached ? '✓ '.__('مستوفاة ومرفقة') : __('قيد التسوية') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="p-2 text-center font-mono">5</td>
                        <td class="p-2 font-bold">{{ __('شهادة التحويل والانتقال الأصلية') }}</td>
                        <td class="p-2 text-center font-bold text-emerald-700">✓ {{ __('مرفقة') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Closing Formula --}}
        <div class="text-xs text-slate-800 leading-relaxed font-traditional mb-8">
            <p>
                {{ __('رجاء التفضل بتأكيد استلام هذا الملف بإعادة وصل الاستلام المرفق أسفله بعد التأشير عليه بالختم الرسمي للمؤسسة.') }}
            </p>
            <p class="mt-2 text-end font-bold text-sm text-slate-950">
                {{ __('وتفضلوا بقبول فائق عبارات التقدير والاحترام.') }}
            </p>
        </div>

        {{-- Signatures --}}
        <div class="flex items-center justify-between text-xs text-center mb-8">
            <div>
                <p class="font-bold text-slate-700">{{ __('مستشار التربية المكلف') }}</p>
                <div class="h-10 border-b border-dashed border-slate-300 w-28 mx-auto"></div>
            </div>

            <div>
                <p class="font-bold text-slate-900">{{ __('مدير المؤسسة والختم الرسمي') }}</p>
                <p class="text-[10px] text-slate-500">{{ $schoolSetting?->director_name ?? __('الإدارة') }}</p>
                <div class="h-10 border-b border-dashed border-slate-300 w-36 mx-auto"></div>
            </div>
        </div>

        {{-- Tear-off Acknowledgment Slip (وصل الاستلام المقتطع) --}}
        <div class="border-t-2 border-dashed border-slate-400 pt-4 mt-6">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-2">
                <span>✂️ {{ __('وصل استلام الملف المدرسي (يُقتطع ويُعاد إلى المؤسسة الأصلية عبر البريد الرسمي)') }}</span>
                <span>Ref: ARC-REC-{{ $transfer->id }}</span>
            </div>

            <div class="bg-slate-50 border border-slate-300 rounded-lg p-3 text-xs">
                <p class="font-bold text-slate-900 mb-1">
                    {{ __('يشهد مدير مؤسسة :dest بأنه استلم بتاريخ :date الملف المدرسي الأصلي للتلميذ(ة): :name (قسم: :cls).', [
                        'dest' => $transfer->isOutward() ? $transfer->other_school_name : ($schoolSetting?->school_name ?? __('المؤسسة')),
                        'date' => '..... / ..... / 2026',
                        'name' => $student?->full_name,
                        'cls' => $classroom?->name ?? '—',
                    ]) }}
                </p>
                <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-200 text-[11px]">
                    <span class="text-slate-500">{{ __('رقم التسجيل الجديد بالمؤسسة المستقبلة') }}: ........................</span>
                    <span class="font-bold text-slate-800">{{ __('توقيع وخاتم مدير المؤسسة المستقبلة') }}: ........................</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

