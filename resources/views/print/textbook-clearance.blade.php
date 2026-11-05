@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $quitusCode = 'QUITUS-'.($student->registration_number ?: $student->id).'-'.date('Y');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادة براءة الذمة لاسترجاع الكتب المدرسية') }} — {{ $student->full_name }}</title>

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
            padding: 20px;
        }

        .print-sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            border: 2px solid #e2e8f0;
            position: relative;
        }

        .outer-border {
            border: 2px solid #0f172a;
            padding: 20px;
            border-radius: 6px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: right;
            font-size: 11px;
        }

        .data-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
        }

        .data-table td.center {
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

            .print-sheet {
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
    <div class="no-print max-w-3xl mx-auto mb-4 bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">📜</span>
            <div>
                <h1 class="text-sm font-bold">{{ __('شهادة براءة الذمة الرسمية (Quitus)') }}</h1>
                <p class="text-xs text-slate-400">{{ $student->full_name }} — {{ $classroom?->name }} ({{ $quitusCode }})</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الشهادة') }}
            </button>
            <button onclick="window.history.back()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-lg transition">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="print-sheet">
        <div class="outer-border">

            {{-- 1. Official Republic Header --}}
            <div class="text-center pb-3 border-b-2 border-slate-900 mb-6">
                <p class="text-sm font-bold tracking-wide">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ __('وزارة التربية الوطنية') }}</p>
                <div class="flex justify-between items-center text-xs font-semibold text-slate-700 mt-2 px-2">
                    <div>
                        <p>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</p>
                        <p class="font-bold text-slate-900">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</p>
                    </div>
                    <div class="text-center">
                        <span class="inline-block px-3 py-1 bg-slate-100 rounded border border-slate-300 font-mono text-[11px] font-bold">
                            {{ $quitusCode }}
                        </span>
                    </div>
                    <div class="text-left">
                        <p>{{ __('السنة الدراسية') }}: <span class="font-bold font-mono">{{ $yearStr }}</span></p>
                        <p>{{ __('حرر بتاريخ') }}: <span class="font-mono">{{ now()->format('d/m/Y') }}</span></p>
                    </div>
                </div>
            </div>

            {{-- 2. Document Title --}}
            <div class="text-center my-6">
                <h2 class="text-xl font-black text-slate-950 uppercase tracking-wide inline-block pb-1 border-b-2 border-emerald-600">
                    {{ __('شهادة براءة الذمة لاسترجاع الكتب المدرسية') }}
                </h2>
                <p class="text-xs text-slate-600 mt-1">
                    {{ __('تُسلّم هذه الشهادة لتأكيد إرجاع كافة الكتب والوسائل البيداغوجية قبل تسليم الوثائق المدرسية') }}
                </p>
            </div>

            {{-- 3. Student Identification Card --}}
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-5">
                <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-xs">
                    <div>
                        <span class="text-slate-500">{{ __('اسم ولقب التلميذ(ة)') }}:</span>
                        <strong class="text-sm text-slate-900 mr-1">{{ $student->full_name }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500">{{ __('رقم التسجيل') }}:</span>
                        <strong class="font-mono text-slate-900 mr-1">{{ $student->registration_number ?: '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500">{{ __('القسم والفوج') }}:</span>
                        <strong class="text-slate-900 mr-1">{{ $classroom?->name }} @if ($student->group) ({{ $student->group->name }}) @endif</strong>
                    </div>
                    <div>
                        <span class="text-slate-500">{{ __('تاريخ ومكان الازدياد') }}:</span>
                        <span class="font-mono text-slate-800 mr-1">{{ $student->birth_date?->format('d/m/Y') ?: '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- 4. Borrowed Textbooks Table --}}
            <div class="mb-5">
                <h3 class="text-xs font-extrabold text-slate-900 mb-2 flex items-center justify-between">
                    <span>📚 {{ __('سجل الكتب المدرسية المعارة والمسترجعة:') }}</span>
                    <span class="text-[11px] font-mono font-bold text-slate-600">
                        {{ $status['total_returned'] }} / {{ $status['total_borrowed'] }} {{ __('مسترجع') }}
                    </span>
                </h3>

                @if ($status['loans']->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th>{{ __('عنوان الكتاب المقرر') }}</th>
                                <th style="width: 100px;">{{ __('المادة') }}</th>
                                <th style="width: 110px;">{{ __('طبيعة الاستفادة') }}</th>
                                <th style="width: 85px;">{{ __('تاريخ الاستلام') }}</th>
                                <th style="width: 85px;">{{ __('تاريخ الإرجاع') }}</th>
                                <th style="width: 90px;">{{ __('الحالة عند الإرجاع') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status['loans'] as $idx => $loan)
                                <tr>
                                    <td class="center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                    <td class="font-bold text-slate-900">{{ $loan->textbook?->title }}</td>
                                    <td class="text-slate-700">{{ $loan->textbook?->subject?->name ?? '—' }}</td>
                                    <td class="center text-[10px]">
                                        <span class="font-semibold {{ $loan->distribution_type->color() === 'emerald' ? 'text-emerald-700 font-bold' : 'text-slate-700' }}">
                                            {{ $loan->distribution_type->label() }}
                                        </span>
                                    </td>
                                    <td class="center font-mono">{{ $formatDate($loan->distributed_at) }}</td>
                                    <td class="center font-mono">{{ $formatDate($loan->returned_at) }}</td>
                                    <td class="center">
                                        @if ($loan->return_condition)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold {{ $loan->return_condition->value === 'good' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                {{ $loan->return_condition->label() }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-semibold text-[10px]">{{ __('في الانتظار') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-xs text-slate-500 italic p-3 text-center border border-dashed border-slate-200 rounded">
                        {{ __('لم يتم تسجيل أية إعارات للكتب المدرسية لهذا التلميذ.') }}
                    </p>
                @endif
            </div>

            {{-- 5. Official Attestation Verdict --}}
            <div class="p-3.5 rounded-lg border my-4 {{ $status['is_cleared'] ? 'bg-emerald-50/70 border-emerald-300 text-emerald-950' : 'bg-amber-50/70 border-amber-300 text-amber-950' }}">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">{{ $status['is_cleared'] ? '✅' : '⚠️' }}</span>
                    <strong class="text-xs font-black">
                        {{ $status['is_cleared'] ? __('إقرار براءة الذمة التامة والمصادقة على الإرجاع') : __('إشعار بعدم استكمال براءة الذمة') }}
                    </strong>
                </div>
                <p class="text-[11px] leading-relaxed">
                    @if ($status['is_cleared'])
                        {{ __('يشهد مسير مخزن الكتب المدرسية وإدارة المؤسسة بأن التلميذ(ة) المذكور(ة) أعلاه قد أرجع(ت) كافة الكتب المدرسية المسلمة له(ا) خلال السنة الدراسية :year بحالة سليمة، ولا يوجد بذمته(ا) أي التزام أو مستحقات تجاه مصلحة الكتب بالمؤسسة، وتُسلّم له(ا) هذه الشهادة لتمكينه(ا) من استلام وثائقه المدرسية وكشف النقاط النهائي.', ['year' => $yearStr]) }}
                    @else
                        {{ __('يُخطر ولي التلميذ(ة) بأن براءة الذمة لم تكتمل بعد، نظراً لوجود كتب معارة لم يتم إرجاعها أو كتب بحاجة إلى تسوية وتعويض مالي قبل تسليم الوثائق المدرسية.') }}
                    @endif
                </p>
            </div>

            {{-- 6. Official Tripartite Signatures & Stamps --}}
            <div class="grid grid-cols-3 gap-4 text-center text-xs mt-6 pt-4 border-t-2 border-slate-800">
                <div>
                    <p class="font-bold text-slate-800 mb-1">{{ __('مسؤول مخزن الكتب المدرسية') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('المعاينة والمصادقة على سلامة الكتب') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2"></div>
                </div>

                <div>
                    <p class="font-bold text-slate-800 mb-1">{{ __('مستشار التربية') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('التأشيرة الإدارية والتسجيل') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2"></div>
                </div>

                <div>
                    <p class="font-bold text-slate-900 mb-1">{{ __('مدير المؤسسة') }}</p>
                    <p class="text-[10px] text-slate-500">{{ __('المصادقة والختم الرسمي') }}</p>
                    <div class="h-20 border-b border-dashed border-slate-300 mt-2">
                        <span class="inline-block mt-4 text-[10px] text-slate-400 font-semibold border border-slate-300 rounded-full px-3 py-1">
                            {{ __('الختم الرسمي للمؤسسة') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center text-[10px] text-slate-400 font-mono">
                {{ __('منظومة سجل الرقمية لإدارة المتوسطة — شهادة براءة الذمة') }} · {{ $quitusCode }}
            </div>

        </div>
    </div>

</body>
</html>

