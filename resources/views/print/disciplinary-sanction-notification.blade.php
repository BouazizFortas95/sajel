@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y على الساعة H:i') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $record->academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $student = $record->student;
    $classroom = $student?->group?->classroom;
    $guardian = $student?->primaryGuardian ?? $student?->guardians?->first();
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('إشعار بقرار مجلس التأديب') }} — {{ $student?->full_name }} — {{ $year }}</title>

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
            padding: 14mm 16mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .traditional-header {
            font-family: 'Amiri', serif;
        }

        .official-seal {
            border: 2px dashed #94a3b8;
            border-radius: 8px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 11px;
            text-align: center;
            padding: 4px;
        }

        .tear-off-line {
            border-top: 2px dashed #cbd5e1;
            position: relative;
            margin: 25px 0 15px 0;
        }

        .tear-off-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            padding: 0 14px;
            font-size: 10px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
<body class="antialiased text-slate-800">

    {{-- Action Toolbar for Browser Screen --}}
    <div class="no-print max-w-[210mm] mx-auto mb-4 flex items-center justify-between bg-white border border-slate-200 px-4 py-3 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-rose-600"></span>
            <span class="font-bold text-sm text-slate-700">{{ __('إشعار رسمي بقرار مجلس التأديب (مع حق الطعن)') }}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $record->session_number ?? 'CD-'.$record->id }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('print.disciplinary.notification.pdf', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-rose-700 text-white hover:bg-rose-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('تحميل PDF رسمي') }}
            </a>
            <a href="{{ route('print.disciplinary.minutes', ['locale' => app()->getLocale(), 'record' => $record->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 transition">
                {{ __('محضر الجلسة') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-slate-800 text-white hover:bg-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة') }}
            </button>
        </div>
    </div>

    <div class="sheet-container text-[12px] leading-relaxed">

        {{-- Official Algerian Ministry Header --}}
        <div class="text-center traditional-header pb-3 border-b-2 border-slate-800">
            <p class="text-sm font-bold tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
            <p class="text-xs font-bold text-slate-700">{{ __('وزارة التربية الوطنية') }}</p>
            <div class="flex justify-between items-center text-xs mt-1 text-slate-700 px-2 font-sans font-semibold">
                <div>
                    <span>{{ __('مديرية التربية لولاية') }}: </span>
                    <span class="font-bold text-slate-900">{{ $schoolSetting?->directorate ?? '................................' }}</span>
                </div>
                <div>
                    <span>{{ __('السنة الدراسية') }}: </span>
                    <span class="font-bold font-mono text-slate-900">{{ $year }}</span>
                </div>
                <div>
                    <span>{{ __('متوسطة') }}: </span>
                    <span class="font-bold text-slate-900">{{ $schoolSetting?->school_name ?? '................................' }}</span>
                </div>
            </div>
        </div>

        {{-- Reference and Date Bar --}}
        <div class="flex justify-between items-center text-[11px] mt-3 pb-2 text-slate-600 border-b border-slate-200">
            <div>
                <span class="font-bold">{{ __('رقم الإشعار / المرجع') }}: </span>
                <span class="font-mono font-bold text-slate-800">{{ $record->session_number ?? 'قرار-CD-'.$record->id }}</span>
            </div>
            <div>
                <span>{{ $schoolSetting?->city ?? '................' }} {{ __('في') }}: </span>
                <span class="font-bold font-mono text-slate-800">{{ $formatDate($record->deliberated_at ?? now()) }}</span>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="text-center my-4">
            <div class="inline-block bg-rose-50 border-2 border-rose-700 text-rose-950 px-6 py-2 rounded-md shadow-sm">
                <h1 class="text-lg font-black tracking-wide">{{ __('إشعار رسمي بقرار مجلس التأديب') }}</h1>
                <p class="text-[10px] text-rose-800 font-sans mt-0.5">Notification Officielle de Décision du Conseil de Discipline</p>
            </div>
        </div>

        {{-- Recipient Address Box --}}
        <div class="bg-slate-50 border border-slate-300 rounded p-3 mb-3">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-slate-500 font-bold block text-[11px]">{{ __('إلى السيد(ة) الوليّ الشرعي / الأب / الأم:') }}</span>
                    <span class="text-sm font-bold text-slate-900">
                        {{ $guardian?->full_name ?? ($student?->guardian_name ?? 'ولي أمر التلميذ(ة)') }}
                    </span>
                    @if($guardian?->phone || $student?->guardian_phone)
                        <span class="text-xs text-slate-600 font-mono block mt-0.5">
                            {{ __('الهاتف') }}: {{ $guardian?->phone ?? $student?->guardian_phone }}
                        </span>
                    @endif
                </div>
                <div class="text-left font-sans">
                    <span class="text-[11px] text-slate-500 block">{{ __('العنوان الشخصي:') }}</span>
                    <span class="text-xs font-semibold text-slate-800">
                        {{ $guardian?->address ?? ($student?->address ?? 'العنوان المسجل بالملف المدرسي') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Student Identification Details --}}
        <div class="border border-slate-200 rounded p-2.5 mb-3 bg-white">
            <table class="w-full text-xs">
                <tbody>
                    <tr>
                        <td class="py-1 text-slate-600 w-1/4"><strong>{{ __('التلميذ(ة):') }}</strong></td>
                        <td class="py-1 font-bold text-slate-900 text-sm">{{ $student?->full_name ?? '—' }}</td>
                        <td class="py-1 text-slate-600 w-1/4"><strong>{{ __('رقم التعريف المدرسي:') }}</strong></td>
                        <td class="py-1 font-mono font-bold text-slate-900">{{ $student?->national_id ?? $student?->registration_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-slate-600"><strong>{{ __('تاريخ ومكان الميلاد:') }}</strong></td>
                        <td class="py-1 text-slate-800">{{ $formatDate($student?->birth_date) }} {{ __('بـ') }} {{ $student?->birth_place ?? '—' }}</td>
                        <td class="py-1 text-slate-600"><strong>{{ __('القسم والفوج التربوي:') }}</strong></td>
                        <td class="py-1 font-bold text-slate-900">{{ $classroom?->name ?? '—' }} {{ $student?->group?->name ? '('.$student->group->name.')' : '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Formal Notification Preamble --}}
        <div class="mb-3 text-justify text-xs leading-relaxed text-slate-700">
            <p>
                {{ __('يُنهي السيد مدير المتوسطة إلى علمكم أنه، وبمقتضى النصوص التشريعية والتنظيمية المعمول بها لا سيما القرار الوزاري المنظم لمجالس التأديب في مؤسسات التعليم المتوسط، اجتمع مجلس التأديب بتاريخ') }}
                <strong class="font-mono text-slate-900">{{ $formatDate($record->hearing_date) }}</strong>
                {{ __('للنظر في الوقائع المنسوبة للتلميذ(ة) المذكور(ة) أعلاه والمصنفة ضمن:') }}
                <strong class="text-rose-900 font-bold">« {{ $record->degree?->getLabel() ?? __('مخالفة تأديبية') }} »</strong>.
            </p>
            <div class="mt-1.5 p-2 bg-amber-50/70 border-r-2 border-amber-600 text-[11px] text-amber-900 font-medium">
                <strong>{{ __('بيان الواقعة:') }}</strong> {{ $record->incident_description }}
            </div>
        </div>

        {{-- The Formal Sanction Decision Box --}}
        <div class="border-2 border-rose-600 bg-rose-50/40 rounded-lg p-3.5 mb-3">
            <div class="text-center font-bold text-rose-900 text-xs mb-2 border-b border-rose-200 pb-1">
                {{ __('مقرر العقوبة المتخذة طبقا للمداولة القانونية') }}
            </div>

            <div class="flex items-center justify-between my-2">
                <div>
                    <span class="text-slate-600 text-xs block">{{ __('العقوبة المصادق عليها:') }}</span>
                    <span class="text-base font-black text-rose-800">{{ $record->sanction?->getLabel() ?? __('لم تُحدد عقوبة بعد') }}</span>
                </div>
                @if($record->exclusion_days)
                    <div class="bg-rose-100 text-rose-900 font-bold px-3 py-1.5 rounded border border-rose-300 text-xs text-center">
                        <div>{{ __('مدة الإقصاء المؤقت:') }}</div>
                        <div class="text-sm font-black">{{ $record->exclusion_days }} {{ __('أيام دراسة فعلية') }}</div>
                    </div>
                @endif
            </div>

            @if($record->exclusion_start_date || $record->exclusion_end_date)
                <div class="grid grid-cols-2 gap-2 text-xs bg-white p-2 rounded border border-rose-200 mt-2">
                    <div>
                        <span class="text-slate-500 font-bold">{{ __('تاريخ سريان الإقصاء:') }}</span>
                        <span class="font-mono font-bold text-slate-900">{{ $formatDate($record->exclusion_start_date) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-bold">{{ __('تاريخ استئناف الدراسة:') }}</span>
                        <span class="font-mono font-bold text-emerald-800">
                            {{ $record->exclusion_end_date ? \Carbon\Carbon::parse($record->exclusion_end_date)->addDay()->format('d/m/Y') : '—' }}
                        </span>
                    </div>
                </div>
            @endif

            @if($record->restitution_amount && $record->restitution_amount > 0)
                <div class="mt-2 text-xs bg-amber-100/60 p-2 rounded border border-amber-300 flex justify-between items-center text-amber-900">
                    <span class="font-bold">{{ __('التعويض المالي عن الإتلاف المادي:') }}</span>
                    <span class="font-mono font-black text-sm">{{ number_format($record->restitution_amount, 2) }} {{ __('د.ج') }}</span>
                </div>
            @endif

            @if($record->decision_notes)
                <div class="mt-2 text-[11px] text-slate-700 bg-white p-2 rounded border border-slate-200">
                    <strong>{{ __('ملاحظات وتوجيهات المجلس:') }}</strong>
                    <span>{{ $record->decision_notes }}</span>
                </div>
            @endif
        </div>

        {{-- Legal Appeal Rights Notice --}}
        <div class="border border-indigo-200 bg-indigo-50/60 rounded p-2.5 mb-3 text-[11px] text-indigo-950 leading-normal">
            <div class="font-bold flex items-center gap-1.5 text-indigo-900 mb-1">
                <svg class="w-4 h-4 text-indigo-700 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ __('مقتضى حق الطعن القانوني (القرار الوزاري المنظم لمجالس التأديب):') }}</span>
            </div>
            <p>
                {{ __('يُخطر السيد(ة) الوليّ الشرعي بأن له الحق في الطعن في هذا القرار أمام «لجنة الطعن الولائية» المعتمدة بمديرية التربية، وذلك في أجل أقصاه ثمانية (08) أيام تسري ابتداء من تاريخ استلامه هذا الإشعار، مع تقديم عريضة طعن معللة. وفي حال عدم الطعن داخل الأجل المحدد يعتبر القرار نافذاً ونهائياً ويدرج في الملف المدرسي للتلميذ.') }}
            </p>
        </div>

        {{-- Principal Signature --}}
        <div class="grid grid-cols-2 gap-4 my-3">
            <div class="text-center">
                <p class="text-xs font-bold text-slate-600 mb-1">{{ __('مستشار(ة) التربية / المتابعة السلوكية') }}</p>
                <div class="official-seal">
                    <span>{{ __('تأشيرة مصلحة التربية') }}</span>
                </div>
            </div>
            <div class="text-center">
                <p class="text-xs font-bold text-slate-800 mb-1">{{ __('مدير(ة) المؤسسة — رئيس المجلس') }}</p>
                <div class="official-seal font-bold">
                    <span>{{ __('الختم الرسمي والإمضاء') }}<br><span class="text-[10px] text-slate-500">{{ $record->president?->name ?? $schoolSetting?->principal_name }}</span></span>
                </div>
            </div>
        </div>

        {{-- Tear-Off Slip for School Archives --}}
        <div class="tear-off-line">
            <span class="tear-off-badge">
                ✂ {{ __('وصل استلام إشعار بقرار مجلس التأديب (يُعاد للمؤسسة بعد التوقيع)') }} ✂
            </span>
        </div>

        <div class="bg-slate-50 border border-slate-300 p-2.5 rounded text-[11px] leading-relaxed">
            <div class="flex justify-between items-center mb-1 pb-1 border-b border-slate-200">
                <span class="font-bold">{{ __('متوسطة:') }} {{ $schoolSetting?->school_name }}</span>
                <span class="font-mono text-slate-500">Ref: REC-CD-{{ $record->id }}</span>
            </div>
            <p>
                {{ __('أنا الموقع أسفله السيد(ة):') }} ................................................................
                {{ __('صفتي: ولي أمر التلميذ(ة):') }} <strong class="font-bold text-slate-900">{{ $student?->full_name }}</strong>
                {{ __('المتمدرس بقسم:') }} <strong class="font-mono font-bold">{{ $classroom?->name }}</strong>
            </p>
            <p class="mt-1">
                {{ __('أقرّ وأعترف بأنني تسلمت نسخة من الإشعار الرسمي الصادر عن مجلس التأديب المنعقد بتاريخ') }}
                <strong class="font-mono">{{ $formatDate($record->hearing_date) }}</strong>
                {{ __('المتضمن عقوبة:') }} <strong class="text-rose-800">{{ $record->sanction?->getLabel() ?? '..................' }}</strong>
                {{ __('واطلعت على أحكام حق الطعن القانوني.') }}
            </p>
            <div class="grid grid-cols-3 gap-2 mt-2 pt-1 border-t border-slate-200 text-center text-[10px]">
                <div>{{ __('حرر بـ') }}: .............................</div>
                <div>{{ __('في تاريخ') }}: ..... / ..... / 202...</div>
                <div><strong>{{ __('إمضاء وبصمة الوليّ:') }}</strong></div>
            </div>
        </div>

    </div>

</body>
</html>
