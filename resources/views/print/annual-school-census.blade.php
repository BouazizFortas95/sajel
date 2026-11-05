@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $academic_year ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $demographicsData = $demographics ?? ['levels' => [], 'totals' => []];
    $staffingData = $staffing ?? ['subjects' => [], 'total_teachers' => 0, 'total_weekly_hours' => 0];
    $censusRecord = $record ?? null;
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استمارة الإحصاء الشامل للدخول المدرسي 15 أكتوبر') }} — {{ $schoolSetting?->school_name }} — {{ $year }}</title>

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
            padding: 10px;
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

        table.census-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.census-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            border: 1px solid #94a3b8;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        table.census-table td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: middle;
        }

        table.census-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .official-seal {
            border: 2px dashed #94a3b8;
            border-radius: 6px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 9px;
            text-align: center;
            padding: 3px;
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
            table.census-table th {
                background-color: #e2e8f0 !important;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    {{-- Browser Toolbar --}}
    <div class="no-print max-w-[297mm] mx-auto mb-3 flex items-center justify-between bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-emerald-600"></span>
            <span class="font-bold text-sm text-slate-800">{{ __('استمارة الإحصاء السنوي الشامل للدخول المدرسي (15 أكتوبر)') }}</span>
            <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded font-mono font-bold">{{ $year }}</span>
            @if($censusRecord?->isCertified())
                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">✓ {{ __('مصادق عليه رسمياً') }}</span>
            @else
                <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold">⏳ {{ __('مسودة قيد الإعداد') }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('print.school-census.pdf', ['locale' => app()->getLocale(), 'academic_year' => $year]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-rose-700 text-white hover:bg-rose-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('تحميل PDF رسمي') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-slate-800 text-white hover:bg-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الكشف') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- Official Algerian Ministry Header --}}
        <div class="flex justify-between items-center traditional-header pb-2 border-b-2 border-slate-800 text-xs">
            <div class="text-right">
                <p class="font-bold text-slate-900">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p class="text-slate-700 font-bold">{{ __('وزارة التربية الوطنية') }}</p>
                <p class="text-slate-600">{{ __('مديرية التخطيط ونظم المعلومات') }}</p>
                <p class="text-slate-700">{{ __('مديرية التربية لولاية') }}: <strong class="text-slate-900">{{ $schoolSetting?->directorate_name ?? '................................' }}</strong></p>
            </div>
            <div class="text-center font-sans">
                <div class="inline-block bg-slate-50 border border-slate-300 px-4 py-1.5 rounded">
                    <h1 class="text-sm font-black text-slate-900">{{ __('كَشْفُ الإِحْصَاءِ الشَّامِل لِلدُّخُولِ المَدْرَسِيّ (إِلَى غَايَة 15 أُكْتُوبَر)') }}</h1>
                    <p class="text-[9px] text-slate-500 font-bold tracking-wide mt-0.5">ENQUÊTE STATISTIQUE OFFICIELLE DE RENTRÉE SCOLAIRE (AU 15 OCTOBRE)</p>
                </div>
            </div>
            <div class="text-left font-sans">
                <p class="font-bold text-slate-800">{{ __('متوسطة') }}: <span class="font-black">{{ $schoolSetting?->school_name ?? '................................' }}</span></p>
                <p class="text-slate-700 font-mono">{{ __('رمز المؤسسة') }}: <span class="font-bold">{{ $schoolSetting?->school_code ?? '................' }}</span></p>
                <p class="text-slate-700 font-mono">{{ __('السنة الدراسية') }}: <span class="font-bold">{{ $year }}</span></p>
                <p class="text-slate-500 text-[9px] font-mono">{{ __('مرجع الإحصاء') }}: {{ $censusRecord?->reference_number ?? 'STAT-'.$year }}</p>
            </div>
        </div>

        {{-- GRID OF MAIN STATISTICAL TABLES --}}
        <div class="grid grid-cols-12 gap-3 my-2.5">

            {{-- SECTION 1: STUDENT ENROLLMENT & DIVISIONS (Left/Center Col 7) --}}
            <div class="col-span-7 space-y-2">
                <div class="text-xs font-bold text-slate-800 border-r-2 border-emerald-600 pr-2">
                    {{ __('1. حركة وتوزيع التلاميذ والأفواج التربوية حسب المستويات والجنس والنظام:') }}
                </div>

                <table class="census-table">
                    <thead>
                        <tr>
                            <th rowspan="2">{{ __('المستوى التعليمي') }}</th>
                            <th rowspan="2" class="w-12">{{ __('الأفواج') }}</th>
                            <th colspan="3">{{ __('تعداد التلاميذ') }}</th>
                            <th colspan="3">{{ __('صفة التمدرس (النظام)') }}</th>
                            <th rowspan="2" class="w-14">{{ __('المعدل / فوج') }}</th>
                        </tr>
                        <tr>
                            <th class="w-12 bg-slate-100">{{ __('الكلي') }}</th>
                            <th class="w-10">{{ __('ذكور') }}</th>
                            <th class="w-10">{{ __('إناث') }}</th>
                            <th class="w-12">{{ __('خارجي') }}</th>
                            <th class="w-14">{{ __('نصف داخلي') }}</th>
                            <th class="w-10">{{ __('داخلي') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($demographicsData['levels'] ?? [] as $lvl)
                            <tr>
                                <td class="font-bold text-slate-800">{{ $lvl['level_name'] }}</td>
                                <td class="text-center font-mono font-bold">{{ $lvl['divisions_count'] }}</td>
                                <td class="text-center font-mono font-black text-slate-900 bg-slate-50">{{ $lvl['students_count'] }}</td>
                                <td class="text-center font-mono text-blue-700">{{ $lvl['boys_count'] }}</td>
                                <td class="text-center font-mono text-rose-700">{{ $lvl['girls_count'] }}</td>
                                <td class="text-center font-mono">{{ $lvl['externes_count'] }}</td>
                                <td class="text-center font-mono font-semibold">{{ $lvl['demi_count'] }}</td>
                                <td class="text-center font-mono">{{ $lvl['internes_count'] }}</td>
                                <td class="text-center font-mono font-bold bg-slate-50">{{ $lvl['avg_per_division'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php $totals = $demographicsData['totals'] ?? []; @endphp
                        <tr class="bg-slate-200 font-bold border-t-2 border-slate-800">
                            <td class="text-center font-black">{{ __('المجموع العام') }}</td>
                            <td class="text-center font-mono font-black">{{ $totals['divisions_count'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black text-sm bg-slate-300">{{ $totals['students_count'] ?? 0 }}</td>
                            <td class="text-center font-mono text-blue-900 font-black">{{ $totals['boys_count'] ?? 0 }}</td>
                            <td class="text-center font-mono text-rose-900 font-black">{{ $totals['girls_count'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black">{{ $totals['externes_count'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black">{{ $totals['demi_count'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black">{{ $totals['internes_count'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black">{{ $totals['avg_per_division'] ?? 0 }}</td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Physical Facilities & Capacity Table --}}
                <div class="pt-1">
                    <div class="text-xs font-bold text-slate-800 border-r-2 border-blue-600 pr-2 mb-1">
                        {{ __('2. المرافق والهياكل القاعدية للمؤسسة:') }}
                    </div>
                    <table class="census-table">
                        <thead>
                            <tr>
                                <th>{{ __('حجرات التدريس') }}</th>
                                <th>{{ __('القاعات المتخصصة') }}</th>
                                <th>{{ __('مخابر العلوم') }}</th>
                                <th>{{ __('قاعة الإعلام الآلي') }}</th>
                                <th>{{ __('طاقة المطعم') }}</th>
                                <th>{{ __('سعة المكتبة') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center font-mono font-bold">
                                <td>{{ $censusRecord?->ordinary_classrooms_count ?? 12 }} {{ __('قاعة') }}</td>
                                <td>{{ $censusRecord?->specialized_rooms_count ?? 2 }} {{ __('قاعات') }}</td>
                                <td>{{ $censusRecord?->science_labs_count ?? 2 }} {{ __('مخابر') }}</td>
                                <td>{{ $censusRecord?->computer_labs_count ?? 1 }} {{ __('مخبر') }}</td>
                                <td class="text-emerald-800">{{ $censusRecord?->canteen_capacity ?? 250 }} {{ __('وجبة') }}</td>
                                <td>{{ $censusRecord?->library_capacity ?? 40 }} {{ __('مقعد') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECTION 2: TEACHER STAFFING & SUPERVISION (Right Col 5) --}}
            <div class="col-span-5 space-y-2">
                <div class="text-xs font-bold text-slate-800 border-r-2 border-indigo-600 pr-2">
                    {{ __('3. التأطير التربوي وتوزيع الأساتذة حسب المواد:') }}
                </div>

                <table class="census-table">
                    <thead>
                        <tr>
                            <th>{{ __('المادة التعليمية') }}</th>
                            <th class="w-16">{{ __('عدد الأساتذة') }}</th>
                            <th class="w-16">{{ __('الحجم الساعي') }}</th>
                            <th class="w-16">{{ __('تلميذ / أستاذ') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($staffingData['subjects'] ?? [], 0, 10) as $subj)
                            <tr>
                                <td class="font-bold text-slate-800">{{ $subj['subject_name'] }}</td>
                                <td class="text-center font-mono font-bold">{{ $subj['teachers_count'] }}</td>
                                <td class="text-center font-mono">{{ $subj['weekly_hours'] }} {{ __('سا') }}</td>
                                <td class="text-center font-mono font-semibold">{{ $subj['ratio'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-200 font-bold border-t-2 border-slate-800">
                            <td>{{ __('إجمالي التأطير التربوي') }}</td>
                            <td class="text-center font-mono font-black text-sm">{{ $staffingData['total_teachers'] ?? 0 }}</td>
                            <td class="text-center font-mono font-black">{{ $staffingData['total_weekly_hours'] ?? 0 }} {{ __('سا') }}</td>
                            <td class="text-center font-mono font-black">{{ $staffingData['overall_ratio'] ?? 0 }}</td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Administrative & Service Personnel --}}
                <div class="bg-slate-50 p-2 rounded border border-slate-200 text-[10px] space-y-1">
                    <span class="font-bold text-slate-700 block border-b border-slate-200 pb-0.5">{{ __('التأطير الإداري والعمال المهنيون:') }}</span>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <span class="text-slate-500 block">{{ __('الإدارة والتسيير:') }}</span>
                            <span class="font-mono font-bold">{{ $censusRecord?->administrative_staff_count ?? 4 }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">{{ __('مساعدو التربية:') }}</span>
                            <span class="font-mono font-bold">{{ $censusRecord?->education_supervisors_count ?? 6 }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">{{ __('العمال المهنيون:') }}</span>
                            <span class="font-mono font-bold">{{ $censusRecord?->service_workers_count ?? 8 }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Legal Authentication & Signatures Block --}}
        <div class="grid grid-cols-4 gap-3 mt-3 pt-2 border-t-2 border-slate-800 text-center text-xs">
            <div>
                <p class="font-bold text-slate-700 mb-1 text-[11px]">{{ __('رئيس مصلحة التمدرس / مستشار التربية') }}</p>
                <div class="official-seal">
                    <span>{{ __('التأشيرة والتاريخ') }}</span>
                </div>
            </div>
            <div>
                <p class="font-bold text-slate-800 mb-1 text-[11px]">{{ __('مدير(ة) المؤسسة — المصادقة الرسمية') }}</p>
                <div class="official-seal font-bold">
                    <span>{{ __('الختم الرسمي والإمضاء') }}<br><span class="text-[9px] text-slate-500">{{ $schoolSetting?->principal_name }}</span></span>
                </div>
            </div>
            <div>
                <p class="font-bold text-slate-700 mb-1 text-[11px]">{{ __('مفتش الإدارة والمالية') }}</p>
                <div class="official-seal">
                    <span>{{ __('تأشيرة مفتش المقاطعة') }}</span>
                </div>
            </div>
            <div>
                <p class="font-bold text-slate-700 mb-1 text-[11px]">{{ __('مصلحة التخطيط — مديرية التربية') }}</p>
                <div class="official-seal">
                    <span>{{ __('تأشيرة الاستلام والرقابة') }}</span>
                </div>
            </div>
        </div>

        {{-- Footer Metadata --}}
        <div class="flex justify-between items-center text-[8px] text-slate-400 font-mono mt-2 pt-1 border-t border-slate-100">
            <span>{{ __('منظومة سجل الرقمية لإدارة المتوسطة — الكشف الإحصائي السنوي الشامل للدخول المدرسي إلى غاية 15 أكتوبر') }}</span>
            <span>Ref: CENSUS-{{ str_replace('/', '-', $year) }}</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>
