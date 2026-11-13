@php
    $isRtl = app()->getLocale() === 'ar';
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $teacher = $teacher ?? $memo->user;
    $subject = $subject ?? $memo->subject;
    $level = $level ?? $memo->level;
    $educationPhase = $educationPhase ?? $level?->educationPhase;
    $academicYear = $academicYear ?? $schoolSetting->academic_year ?? '2026 / 2027';
    $activities = is_array($memo->activities) ? array_values($memo->activities) : [];

    // Helper to extract or structure methodological fields
    $parseActivity = function (array $act, int $index) use ($memo): array {
        $name = trim((string) ($act['name'] ?? ''));
        $duration = (int) ($act['duration'] ?? 15);
        $strategy = trim((string) ($act['strategy'] ?? ''));
        $content = trim((string) ($act['content'] ?? ''));

        $teacherRole = trim((string) ($act['teacher_role'] ?? ''));
        $studentRole = trim((string) ($act['student_role'] ?? ''));
        $writtenTrace = trim((string) ($act['written_trace'] ?? ''));
        $evalIndicator = trim((string) ($act['evaluation_indicator'] ?? ''));

        // If structured roles are not present, parse or derive them intelligently from content
        if ($teacherRole === '' || $studentRole === '') {
            if (str_contains($content, 'يطرح الأستاذ') || str_contains($content, 'يطلب الأستاذ') || str_contains($content, 'يوجه الأستاذ') || str_contains($content, 'يقسم الأستاذ')) {
                $sentences = preg_split('/(?<=[.؛\n])\s+/u', $content);
                $tSentences = [];
                $sSentences = [];
                foreach ($sentences as $s) {
                    if (str_contains($s, 'أستاذ') || str_contains($s, 'توجيه') || str_contains($s, 'تعليمات') || str_contains($s, 'يقسم') || str_contains($s, 'يطرح')) {
                        $tSentences[] = $s;
                    } else {
                        $sSentences[] = $s;
                    }
                }
                if (! empty($tSentences)) {
                    $teacherRole = implode(' ', $tSentences);
                }
                if (! empty($sSentences)) {
                    $studentRole = implode(' ', $sSentences);
                }
            } elseif ($content !== '') {
                $studentRole = $content;
            }
        }

        // Fallbacks if still empty
        if ($teacherRole === '') {
            $teacherRole = match ($index) {
                0 => 'يطرح الوضعية الانطلاقية المشكلة، يوجه أسئلة دافعة، ويسير الحوار لضبط تمثلات التلاميذ.',
                1 => 'يقدم السند والتعليمات بدقة، ييسر العمل ويوجه التلاميذ نحو استثمار المعطيات واستنتاج المفاهيم.',
                2 => 'يشرف على بناء الخلاصة وإرساء المورد، يصحح الأخطاء المفاهيمية ويوثق النتائج على السبورة.',
                default => 'يطرح تمرين التقويم أو وضعية الإدماج الجزئي، يرصد التعثرات الفردية ويقدم التغذية الراجعة.',
            };
        }

        if ($studentRole === '') {
            $studentRole = match ($index) {
                0 => 'يلاحظ السند، يناقش الإشكالية، يعبر عن تصوراته ويقترح فرضيات أولية للحل.',
                1 => 'يحلل السند، يتناظر في إطار العمل الفوجي أو الثنائي، ويستخلص العلاقات والنتائج.',
                2 => 'يشارك في صياغة القاعدة وتركيب المفاهيم، ويقارن النتائج بالفرضيات السابقة.',
                default => 'يحل التطبيق فردياً، يتحقق من مكتسباته، ويدون الحل النموذجي في كراسه.',
            };
        }

        if ($writtenTrace === '') {
            $writtenTrace = match ($index) {
                0 => 'تدوين الإشكالية والفرضيات الأولية على السبورة.',
                1 => 'استخلاص العناصر الأساسية للنشاط وتسجيل النتائج الجزئية.',
                2 => 'إرساء المورد: تدوين القاعدة / النتيجة المهيكلة في كراس الدروس.',
                default => 'حل الوضعية التطبيقية / تسليم بطاقة التحقق والتقويم.',
            };
        }

        if ($evalIndicator === '') {
            $evalIndicator = match ($index) {
                0 => 'يسترجع المكتسبات القبلية ويطرح فرضيات وجيهة.',
                1 => 'يحلل السند ويوظف أدوات المادة بأسلوب سليم.',
                2 => 'يستنتج القاعدة ويميز بين المفاهيم بدقة.',
                default => 'يحل التمرين بنجاح ويحقق معايير الإتقان.',
            };
        }

        if ($strategy === '') {
            $strategy = match ($index) {
                0 => 'العصف الذهني (حوار موجه)',
                1 => 'التفكير — المشاركة — العرض (أفواج)',
                2 => 'الخرائط المفاهيمية (عمل جماعي)',
                default => 'بطاقة الخروج (عمل فردي)',
            };
        }

        return [
            'name' => $name ?: 'المحطة '.($index + 1),
            'duration' => $duration > 0 ? $duration : 15,
            'strategy' => $strategy,
            'teacher_role' => $teacherRole,
            'student_role' => $studentRole,
            'written_trace' => $writtenTrace,
            'evaluation_indicator' => $evalIndicator,
            'content' => $content,
        ];
    };

    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? date('d/m/Y');
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('مذكرة بيداغوجية رسمية') }} — {{ $memo->title }} — سجِّل</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 5mm 6mm 5mm 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 7.8pt;
            line-height: 1.25;
            direction: rtl;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .no-print-inner {
            max-width: 210mm;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #1e40af;
        }

        .sheet {
            width: 100%;
            max-width: 200mm;
            margin: 10px auto;
            background: #ffffff;
            padding: 5mm 6mm;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        @media print {
            body {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
        }

        /* 1. National Official Header */
        .national-header {
            display: grid;
            grid-template-columns: 1.2fr 1.6fr 1.2fr;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 4px;
            margin-bottom: 6px;
            align-items: center;
        }

        .header-col {
            line-height: 1.3;
        }

        .header-right {
            text-align: right;
            font-size: 7.2pt;
            color: #1e293b;
            font-weight: 700;
        }

        .header-center {
            text-align: center;
        }

        .title-badge {
            display: inline-block;
            border: 1.5px solid #1e3a8a;
            background: #f0f7ff;
            border-radius: 6px;
            padding: 2px 14px;
        }

        .main-title {
            font-size: 11pt;
            font-weight: 900;
            color: #1e3a8a;
            margin: 0;
            line-height: 1.2;
        }

        .sub-title {
            font-size: 7.4pt;
            font-weight: 800;
            color: #b45309;
            margin-top: 1px;
        }

        .memo-meta-line {
            font-size: 7.4pt;
            font-weight: 800;
            color: #334155;
            margin-top: 2px;
        }

        .header-left {
            text-align: left;
            font-size: 7.2pt;
            color: #1e293b;
            font-weight: 700;
        }

        /* 2. Pedagogical Identification Cartouche */
        .cartouche-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #1e3a8a;
            margin-bottom: 6px;
            font-size: 7.6pt;
        }

        .cartouche-table th, .cartouche-table td {
            border: 1px solid #64748b;
            padding: 2.5px 4px;
            vertical-align: middle;
        }

        .cartouche-table .label-cell {
            background-color: #f1f5f9;
            color: #1e3a8a;
            font-weight: 800;
            width: 14%;
            text-align: center;
            white-space: nowrap;
        }

        .cartouche-table .value-cell {
            color: #0f172a;
            font-weight: 700;
            width: 36%;
        }

        .topic-banner-row td {
            background-color: #eff6ff !important;
            padding: 4px 6px !important;
            border: 1.2px solid #1e3a8a !important;
        }

        .topic-title {
            font-size: 8.8pt;
            font-weight: 900;
            color: #1e3a8a;
        }

        /* 3. Starting Situation Box */
        .situation-box {
            border: 1.2px solid #0284c7;
            background: #f0f9ff;
            border-radius: 4px;
            padding: 4px 7px;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .box-heading {
            font-size: 8pt;
            font-weight: 900;
            color: #0369a1;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .box-body {
            font-size: 7.6pt;
            color: #0f172a;
            text-align: justify;
        }

        /* 4. Core Methodological Learning Progression Table */
        .section-header-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1e3a8a;
            color: #ffffff;
            padding: 3px 8px;
            font-weight: 900;
            font-size: 8.2pt;
            border-radius: 3px 3px 0 0;
            margin-top: 4px;
        }

        .methodological-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #0f172a;
            font-size: 7.5pt;
            margin-bottom: 6px;
        }

        .methodological-table th, .methodological-table td {
            border: 1px solid #475569;
            padding: 3px 4px;
            vertical-align: top;
        }

        .methodological-table thead th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 800;
            text-align: center;
            font-size: 7.8pt;
            padding: 3px 2px;
        }

        .col-stage {
            width: 95px;
            text-align: center;
            background-color: #f8fafc;
            font-weight: 800;
        }

        .stage-title {
            color: #1e3a8a;
            font-size: 8pt;
            font-weight: 900;
            line-height: 1.2;
        }

        .stage-duration {
            display: inline-block;
            margin-top: 3px;
            background: #e2e8f0;
            color: #334155;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 6.8pt;
            font-weight: 800;
        }

        .col-roles {
            text-align: right;
            line-height: 1.35;
        }

        .role-section {
            margin-bottom: 4px;
        }

        .role-section:last-child {
            margin-bottom: 0;
        }

        .role-badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 7pt;
            margin-left: 3px;
        }

        .role-teacher {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .role-student {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .col-strategy {
            width: 110px;
            text-align: center;
            font-size: 7.3pt;
            font-weight: 700;
            background-color: #fcfdfe;
            line-height: 1.3;
        }

        .col-trace {
            width: 140px;
            text-align: right;
            font-size: 7.3pt;
            background-color: #fefce8;
            color: #713f12;
            line-height: 1.35;
            font-weight: 600;
        }

        .col-eval {
            width: 115px;
            text-align: right;
            font-size: 7.2pt;
            background-color: #fbfcfe;
            color: #334155;
            line-height: 1.3;
        }

        /* 5. Evaluation and Pedagogical Notes Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 8px;
        }

        .info-card {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #ffffff;
            padding: 4px 6px;
            line-height: 1.3;
        }

        .info-card-header {
            font-weight: 800;
            font-size: 7.8pt;
            color: #1e3a8a;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .info-card-content {
            font-size: 7.4pt;
            color: #1e293b;
            text-align: justify;
        }

        /* 6. Clean Official Visas Grid */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
        }

        .signature-col {
            padding: 3px;
        }

        .signature-role {
            font-size: 8pt;
            font-weight: 900;
            color: #1e3a8a;
            margin-bottom: 26px;
        }

        .signature-dots {
            border-bottom: 1.2px dotted #94a3b8;
            width: 70%;
            margin: 0 auto;
        }

        .footer-system-tag {
            text-align: center;
            font-size: 6.8pt;
            color: #94a3b8;
            margin-top: 6px;
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div class="no-print-inner">
            <div>
                <strong style="color: #1e3a8a; font-size: 10pt;">المذكرة البيداغوجية الرسمية (الجيل الثاني)</strong>
                <span style="color: #64748b; font-size: 8pt; margin-right: 8px;">{{ $memo->title }}</span>
            </div>
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.83h10.56M6.72 17.25h10.56M6.75 6.75h10.5v3.75H6.75V6.75Zm-1.5 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v5.25a1.5 1.5 0 0 1-1.5 1.5h-1.5v-3H6.75v3h-1.5a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                <span>طباعة المذكرة</span>
            </button>
        </div>
    </div>

    <main class="sheet">
        {{-- 1. National Official Header --}}
        <header class="national-header">
            <div class="header-col header-right">
                <p>الجمهورية الجزائرية الديمقراطية الشعبية</p>
                <p>وزارة التربية الوطنية</p>
                <p>مديرية التربية لولاية: <strong>{{ $schoolSetting->directorate_name ?: 'المغير' }}</strong></p>
                <p>المؤسسة: <strong>متوسطة {{ $schoolSetting->school_name ?: 'حي 940 مسكن' }}</strong></p>
            </div>

            <div class="header-col header-center">
                <div class="title-badge">
                    <h1 class="main-title">مُذَكَّرَة بِيـدَاغُوجِيَّة تَعْلِيمِيَّة</h1>
                    <p class="sub-title">وفق المقاربة بالكفاءات — منهاج الجيل الثاني</p>
                </div>
                <p class="memo-meta-line">رقم المذكرة: <strong>{{ sprintf('%02d', $memo->id) }}</strong> | الحجم الساعي: <strong>01 ساعة</strong></p>
            </div>

            <div class="header-col header-left">
                <p>السنة الدراسية: <strong>{{ $schoolSetting->academic_year ?: '2026 / 2027' }}</strong></p>
                <p>الأستاذ(ة): <strong>{{ $teacher?->name ?: 'فرطاس بوعزيز' }}</strong></p>
                <p>المادة: <strong>{{ $subject?->name ?: 'الإعلام الآلي' }}</strong></p>
                <p>المستوى: <strong>{{ $level?->name ?: 'السنة الرابعة متوسط' }}</strong></p>
            </div>
        </header>

        {{-- 2. Pedagogical Cartouche --}}
        <table class="cartouche-table">
            <tbody>
                <tr class="topic-banner-row">
                    <td class="label-cell" style="background:#e0f2fe; color:#0369a1;">المورد المعرفي:</td>
                    <td colspan="3" class="topic-title">{{ $memo->title }}</td>
                </tr>
                <tr>
                    <td class="label-cell">الميدان التعلمي:</td>
                    <td class="value-cell">{{ $memo->domain_name ?: ($subject?->name.' — '.($level?->name ?? 'التعليم المتوسط')) }}</td>
                    <td class="label-cell">المقطع التعلمي:</td>
                    <td class="value-cell">{{ $memo->phase ?: 'المقطع البيداغوجي المعني' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">الكفاءة المستهدفة:</td>
                    <td class="value-cell">{{ $memo->competence }}</td>
                    <td class="label-cell">نوع الحصة:</td>
                    <td class="value-cell">إرساء موارد معرفية وبناء كفاءات</td>
                </tr>
                <tr>
                    <td class="label-cell">السندات والوسائل:</td>
                    <td colspan="3" class="value-cell">{{ $memo->resources ?: 'الكتاب المدرسي، المنهاج، السبورة، عارض البيانات (Data Show)، بطاقات الأنشطة.' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- 3. Starting Situation --}}
        @if (filled($memo->starting_situation))
            <div class="situation-box">
                <div class="box-heading">
                    <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <span>الوضعية المشكلة الانطلاقية (السياق — السند — المهمة والتعليمة):</span>
                </div>
                <div class="box-body">
                    {!! nl2br(e($memo->starting_situation)) !!}
                </div>
            </div>
        @endif

        {{-- 4. Methodological Progression Table --}}
        <div class="section-header-badge">
            <span>سيرورة التعلمات ومحطات الحصة البيداغوجية</span>
            <span style="font-size: 7.2pt; font-weight: 700;">عدد المحطات: {{ count($activities) }} محطات</span>
        </div>

        <table class="methodological-table">
            <thead>
                <tr>
                    <th class="col-stage">المحطة والزمن</th>
                    <th class="col-roles">سيرورة الأنشطة والمهام التعليمية التعلمية (نشاط الأستاذ / نشاط المتعلم)</th>
                    <th class="col-strategy">استراتيجية التعلم ونمط العمل</th>
                    <th class="col-trace">الأثر الكتابي المستخلص</th>
                    <th class="col-eval">مؤشرات التقويم والتعديل</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $index => $rawAct)
                    @php
                        $act = $parseActivity($rawAct, $index);
                    @endphp
                    <tr>
                        <td class="col-stage">
                            <div class="stage-title">{{ $act['name'] }}</div>
                            <span class="stage-duration">{{ $act['duration'] }} دقيقة</span>
                        </td>

                        <td class="col-roles">
                            <div class="role-section">
                                <span class="role-badge role-teacher">دور ونشاط الأستاذ:</span>
                                <span>{{ $act['teacher_role'] }}</span>
                            </div>
                            <div class="role-section" style="margin-top: 3px;">
                                <span class="role-badge role-student">نشاط ومهمات المتعلم:</span>
                                <span>{{ $act['student_role'] }}</span>
                            </div>
                        </td>

                        <td class="col-strategy">
                            {{ $act['strategy'] }}
                        </td>

                        <td class="col-trace">
                            {{ $act['written_trace'] }}
                        </td>

                        <td class="col-eval">
                            {{ $act['evaluation_indicator'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 10px; color: #64748b;">
                            {{ __('لا توجد أنشطة مسجلة في هذه المذكرة.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- 5. Evaluation and Pedagogical Notes Grid --}}
        <div class="bottom-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <span>شبكة معايير ومؤشرات التقويم (معايير التحقق)</span>
                    <span style="font-size: 6.8pt; color: #64748b;">م1: الوجاهة | م2: الاستعمال السليم | م3: الانسجام | م4: الإتقان</span>
                </div>
                <div class="info-card-content">
                    {!! nl2br(e($memo->evaluation ?: 'تقويم تكويني مرحلي لرصد التحكم في المورد، وتقويم ختامي بحل وضعية إدماج جزئي فردية.')) !!}
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <span>المعالجة والتوجيهات البيداغوجية (تسيير الفوارق الفردية)</span>
                    <span style="font-size: 6.8pt; color: #64748b;">الدعم البيداغوجي والمرافقة</span>
                </div>
                <div class="info-card-content">
                    {!! nl2br(e($memo->notes ?: 'مراعاة الفروق الفردية، تقديم تغذية راجعة فورية، مرافقة الأفواج المتعثرة، والحرص على تدبير التوقيت بدقة.')) !!}
                </div>
            </div>
        </div>

        {{-- 6. Official Visas / Signatures --}}
        <footer class="signatures-grid">
            <div class="signature-col">
                <p class="signature-role">تأشيرة أستاذ(ة) المادة</p>
                <div class="signature-dots"></div>
            </div>

            <div class="signature-col">
                <p class="signature-role">تأشيرة السيد مدير المؤسسة</p>
                <div class="signature-dots"></div>
            </div>

            <div class="signature-col">
                <p class="signature-role">تأشيرة السيد مفتش التربية الوطنية</p>
                <div class="signature-dots"></div>
            </div>
        </footer>

        <p class="footer-system-tag">
            منصة سجِّل لإدارة التعليم المتوسط — تم توليد هذه المذكرة البيداغوجية بتاريخ {{ $formatDate($memo->updated_at) }}
        </p>
    </main>

    @if ($autoPrint ?? true)
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
