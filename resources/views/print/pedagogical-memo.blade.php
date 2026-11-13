@php
    $isRtl = app()->getLocale() === 'ar';
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $teacher = $teacher ?? $memo->user;
    $subject = $subject ?? $memo->subject;
    $level = $level ?? $memo->level;
    $educationPhase = $educationPhase ?? $level?->educationPhase;
    $academicYear = $academicYear ?? $schoolSetting->academic_year ?? '2026 / 2027';
    $activities = is_array($memo->activities) ? array_values($memo->activities) : [];

    // Helper to derive structured pedagogical memo parts according to Sajel's new methodology
    $topicName = $memo->topic ?: ($memo->title ?: 'موضوع الدرس');

    // References
    $referencesText = filled($memo->notes)
        ? $memo->notes
        : "• المنهاج والمخططات السنوية\n• الوثيقة المرافقة للأستاذ\n• معارف بيداغوجية ومراجع رقمية";

    // Used tools/resources
    $resourcesText = filled($memo->resources)
        ? $memo->resources
        : 'السبورة، أجهزة الحواسيب، عارض البيانات (Data Show)، مطبوعات وبطاقات تقويمية.';

    // Extract target knowledge items (المعارف المستهدفة)
    $targetKnowledge = [];
    if (str_contains($topicName, 'تعريف') || str_contains($topicName, 'الحاسوب') || str_contains($topicName, 'المعلوماتية')) {
        $targetKnowledge = [
            'تعريف المعلوماتية وأصل الكلمة',
            'الفرق بين العتاد (المادي) والبرمجيات (اللامادية)',
            'تعريف الحاسوب ووظائفه الأساسية',
            'مخطط عمل الحاسوب (إدخال ⬅ معالجة وتخزين ⬅ إخراج)',
        ];
    } elseif (! empty($activities)) {
        foreach ($activities as $act) {
            if (! empty($act['written_trace'])) {
                $targetKnowledge[] = Str::limit(strip_tags($act['written_trace']), 55);
            }
        }
    }

    if (empty($targetKnowledge)) {
        $targetKnowledge = [
            "المفاهيم الأساسية حول «{$topicName}»",
            'الخصائص والقواعد المنظمة للمورد المعرفي',
            'مخطط عمل وتطبيق المكتسبات في وضعيات دالة',
        ];
    }

    // Standardize 3 official stages (وضعية الانطلاق، العرض / إرساء الموارد، التقويم والمعالجة)
    // If activities has 3-4 items, map them gracefully
    $mappedStages = [];

    // 1. Starting Stage (وضعية الانطلاق)
    $act0 = $activities[0] ?? null;
    $startingDuration = (int) ($act0['duration'] ?? 10);
    $startingIndicator = trim((string) ($act0['evaluation_indicator'] ?? $act0['indicator'] ?? 'يستمع، ينتبه، ويسترجع المكتسبات القبلية ذات الصلة.'));
    $startingProblem = filled($memo->starting_situation)
        ? $memo->starting_situation
        : (string) ($act0['content'] ?? "شهد العالم تطوراً غير مسبوق في عالم التكنولوجيا والمعلوماتية، فأصبحت الحواسيب والتطبيقات ضرورة في شتى مجالات الحياة كالمؤسسات والتعليم والبنوك والصحة.\nفما المقصود بالمعلوماتية؟ وكيف يؤدي الحاسوب وظائفه المتنوعة؟");

    $mappedStages[] = [
        'type' => 'start',
        'name' => 'وضعية الانطلاق',
        'badge' => 'التهيئة والإشكالية',
        'duration' => $startingDuration > 0 ? $startingDuration : 10,
        'indicator' => $startingIndicator,
        'problem' => $startingProblem,
        'activity' => $act0['teacher_role'] ?? null,
        'trace' => $act0['written_trace'] ?? 'تدوين الإشكالية والفرضيات الأولية المقترحة على السبورة.',
    ];

    // 2. Acquisition & Presentation Stage (العرض / إرساء الموارد)
    // Could combine intermediate activities (1, 2)
    $acquisitionDuration = 0;
    $acquisitionIndicator = '';
    $acquisitionActivities = [];
    $acquisitionTraces = [];

    for ($i = 1; $i < max(1, count($activities) - 1); $i++) {
        $actItem = $activities[$i];
        $acquisitionDuration += (int) ($actItem['duration'] ?? 15);
        if (empty($acquisitionIndicator) && ! empty($actItem['evaluation_indicator'])) {
            $acquisitionIndicator = $actItem['evaluation_indicator'];
        }
        if (! empty($actItem['content'])) {
            $acquisitionActivities[] = $actItem['content'];
        }
        if (! empty($actItem['teacher_role'])) {
            $acquisitionActivities[] = $actItem['teacher_role'];
        }
        if (! empty($actItem['written_trace'])) {
            $acquisitionTraces[] = $actItem['written_trace'];
        }
    }

    if ($acquisitionDuration === 0) {
        $acquisitionDuration = 35;
    }
    if (empty($acquisitionIndicator)) {
        $acquisitionIndicator = 'يناقش، يتبادل الأفكار ويستنتج. يستوعب المفاهيم ويثبت الأثر الكتابي المنظم.';
    }

    // Default instructional activity text
    $acquisitionActivityText = ! empty($acquisitionActivities)
        ? implode("\n\n", array_unique($acquisitionActivities))
        : "استناداً إلى السندات والوضعيات التعليمية والمناقشة الموجهة في القسم:\n• ما المقصود بهذا المفهوم؟ وما هي عناصره الأساسية؟\n• اقترح نموذجاً ومخططاً يوضح العلاقات بين المكونات.\n(يمكن للأستاذ توظيف نشاط تفاعلي أو عمل فوجي مكافئ وفق خصوصية الفوج).";

    $mappedStages[] = [
        'type' => 'main',
        'name' => 'العرض (إرساء الموارد)',
        'badge' => 'بناء التعلمات',
        'duration' => $acquisitionDuration,
        'indicator' => $acquisitionIndicator,
        'activity' => $acquisitionActivityText,
        'traces' => $acquisitionTraces,
    ];

    // 3. Evaluation & Remediation Stage (التقويم والمعالجة)
    $lastAct = count($activities) > 1 ? end($activities) : null;
    $evalDuration = (int) ($lastAct['duration'] ?? 15);
    $evalIndicator = trim((string) ($lastAct['evaluation_indicator'] ?? $lastAct['indicator'] ?? 'يستثمر مكتسباته، يحل الوضعية التطبيقية بدقة، ويتحقق من التحكم في المورد.'));
    $evalContent = filled($memo->evaluation)
        ? $memo->evaluation
        : (string) ($lastAct['content'] ?? 'تطبيق فوري لتقويم التحكم في المورد المعرفي، رصد التعثرات الفردية وتقديم المعالجة البيداغوجية الفورية مع تثبيت الحل النموذجي.');

    $mappedStages[] = [
        'type' => 'eval',
        'name' => 'التقويم والمعالجة',
        'badge' => 'استثمار المكتسبات',
        'duration' => $evalDuration > 0 ? $evalDuration : 15,
        'indicator' => $evalIndicator,
        'eval' => $evalContent,
        'trace' => $lastAct['written_trace'] ?? 'تسجيل الحل النموذجي للتمرين التقويمي في كراس الدروس.',
    ];

    $formatDate = fn ($value): string => $value?->format('d/m/Y') ?? now()->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('مذكرة بيداغوجية') }} — {{ $topicName }}</title>
    @if ($inlineCss ?? false)
        {{-- Inlined styles handled by print service --}}
    @endif
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 6mm;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Cairo', 'Amiri', 'Traditional Arabic', 'Segoe UI', Tahoma, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            color: #fff;
            padding: 8px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11pt;
            font-weight: 700;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #d97706;
            color: #fff;
            font-size: 9.5pt;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .toolbar-btn:hover {
            background: #b45309;
        }

        .sheet {
            width: 210mm;
            max-width: 100%;
            margin: 10px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 6mm 7mm;
        }

        /* 1. Header (الترويسة) */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #1e3a8a;
            margin-bottom: 5px;
            background: #ffffff;
        }

        .header-cell {
            padding: 5px 8px;
            vertical-align: middle;
            font-size: 7.8pt;
        }

        .header-right {
            width: 38%;
            text-align: right;
            border-left: 1px solid #cbd5e1;
            line-height: 1.45;
        }

        .header-center {
            width: 24%;
            text-align: center;
            border-left: 1px solid #cbd5e1;
            background: linear-gradient(180deg, #f8fafc 0%, #f0f7ff 100%);
        }

        .header-left {
            width: 38%;
            text-align: right;
            line-height: 1.45;
        }

        .memo-badge-box {
            display: inline-block;
            border: 1.8px solid #1e3a8a;
            background: #ffffff;
            border-radius: 6px;
            padding: 4px 12px;
            box-shadow: 0 1px 3px rgba(30, 58, 138, 0.1);
        }

        .memo-badge-title {
            font-size: 8.5pt;
            font-weight: 900;
            color: #1e3a8a;
            margin: 0;
            white-space: nowrap;
        }

        .memo-sajel-tag {
            font-size: 6.8pt;
            font-weight: 700;
            color: #b45309;
            margin-top: 1px;
            display: block;
        }

        .meta-field {
            margin-bottom: 2px;
            color: #334155;
        }

        .meta-field:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            font-weight: 800;
            color: #0f172a;
        }

        .meta-val {
            font-weight: 700;
            color: #1e3a8a;
        }

        /* 2. Prominent Competence Banner */
        .competence-banner {
            border: 1.5px solid #1e3a8a;
            background: linear-gradient(90deg, #eff6ff 0%, #f8fafc 100%);
            border-radius: 4px;
            padding: 5px 12px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
        }

        .competence-title {
            font-size: 9.5pt;
            font-weight: 900;
            color: #b45309;
            white-space: nowrap;
        }

        .competence-body {
            font-size: 9pt;
            font-weight: 800;
            color: #1e3a8a;
        }

        /* 3. Three-column Summary Card (المراجع | الوسائل | المعارف المستهدفة) */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #1e3a8a;
            margin-bottom: 6px;
            background: #ffffff;
            font-size: 7.8pt;
        }

        .summary-table th {
            background: #f1f5f9;
            color: #1e3a8a;
            font-weight: 900;
            text-align: center;
            padding: 3.5px 6px;
            border: 1px solid #94a3b8;
            font-size: 8pt;
        }

        .summary-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            vertical-align: top;
            line-height: 1.4;
        }

        .summary-col-ref {
            width: 30%;
            background: #fafafa;
        }

        .summary-col-tools {
            width: 30%;
            background: #ffffff;
        }

        .summary-col-knowledge {
            width: 40%;
            background: #fdfdfd;
        }

        .knowledge-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .knowledge-item {
            position: relative;
            padding-right: 12px;
            margin-bottom: 2px;
            color: #0f172a;
            font-weight: 600;
        }

        .knowledge-item::before {
            content: "•";
            position: absolute;
            right: 0;
            color: #d97706;
            font-weight: 900;
        }

        /* 4. Main Progression Table (الجدول المنهجي لسير الحصة) */
        .main-progression-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #1e3a8a;
            margin-bottom: 6px;
            font-size: 7.6pt;
            background: #ffffff;
        }

        .main-progression-table th {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 900;
            text-align: center;
            padding: 4.5px 6px;
            border: 1px solid #1e40af;
            font-size: 8pt;
            letter-spacing: 0.2px;
        }

        .main-progression-table td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            vertical-align: top;
            line-height: 1.4;
        }

        .col-stage {
            width: 13%;
            text-align: center;
            background: #f8fafc;
        }

        .stage-title {
            font-weight: 900;
            color: #1e3a8a;
            font-size: 8.2pt;
            line-height: 1.25;
            margin-bottom: 3px;
        }

        .stage-badge-pill {
            display: inline-block;
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 6.8pt;
            font-weight: 800;
        }

        .col-time {
            width: 7%;
            text-align: center;
            vertical-align: middle !important;
            font-weight: 900;
            color: #b45309;
            background: #fffbeb;
            font-size: 8.5pt;
        }

        .col-indicator {
            width: 22%;
            background: #ffffff;
            color: #1e293b;
            font-weight: 600;
            padding: 5px 8px;
        }

        .indicator-header-tag {
            font-weight: 800;
            color: #0369a1;
            margin-bottom: 3px;
            display: block;
            font-size: 7.2pt;
        }

        .col-activities {
            width: 58%;
            background: #ffffff;
            color: #0f172a;
            padding: 5px 8px;
        }

        /* Modern didactic badges within content */
        .didactic-block {
            margin-bottom: 6px;
        }

        .didactic-block:last-child {
            margin-bottom: 0;
        }

        .didactic-badge {
            display: inline-block;
            font-weight: 900;
            border-radius: 3px;
            padding: 1px 6px;
            font-size: 7.5pt;
            margin-left: 4px;
            margin-bottom: 2px;
        }

        .badge-problematic {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-activity {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .badge-knowledge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-eval {
            background: #faf5ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .didactic-note {
            font-size: 7pt;
            color: #64748b;
            font-style: italic;
            margin: 2px 0 4px;
        }

        /* Flow process boxes (e.g. مخطط عمل الحاسوب) */
        .process-flow {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 6px;
            margin: 4px 0;
            padding: 4px;
            background: #f8fafc;
            border-radius: 4px;
            border: 1px dashed #cbd5e1;
        }

        .process-node {
            background: #ffffff;
            border: 1.2px solid #0284c7;
            color: #0369a1;
            border-radius: 4px;
            padding: 2px 8px;
            font-weight: 800;
            font-size: 7.2pt;
            text-align: center;
        }

        .process-arrow {
            color: #e11d48;
            font-weight: 900;
            font-size: 9pt;
        }

        .item-num {
            font-weight: 800;
            color: #b45309;
            margin-left: 2px;
        }

        /* 5. Institutional Visas / Signatures */
        .visas-grid {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-top: 4px;
            background: #ffffff;
        }

        .visa-cell {
            width: 33.333%;
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: center;
        }

        .visa-role {
            font-size: 7.8pt;
            font-weight: 900;
            color: #1e3a8a;
            margin-bottom: 24px;
        }

        .visa-dots {
            border-bottom: 1px dashed #94a3b8;
            width: 75%;
            margin: 0 auto 3px;
        }

        .footer-system-text {
            text-align: center;
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 3px;
        }

        @media print {
            .screen-toolbar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <div class="toolbar-info">
            <span>{{ __('مذكرة بيداغوجية:') }} {{ $topicName }}</span>
            <span style="font-size: 9pt; color: #fbbf24;">({{ $subject?->name }} — {{ $level?->name }})</span>
        </div>
        <div>
            <button onclick="window.print()" class="toolbar-btn">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>{{ __('طباعة المذكرة') }}</span>
            </button>
        </div>
    </div>

    <main class="sheet">
        {{-- 1. Official Header Table --}}
        <table class="header-table">
            <tr>
                <td class="header-cell header-right">
                    <div class="meta-field"><span class="meta-label">المؤسسة :</span> متوسطة {{ $schoolSetting->school_name ?: 'حي 940 مسكن' }} — {{ $schoolSetting->directorate_name ?: 'المغير' }}</div>
                    <div class="meta-field"><span class="meta-label">الأستاذ :</span> <span class="meta-val">{{ $teacher?->name ?: 'فرطاس بوعزيز' }}</span></div>
                    <div class="meta-field"><span class="meta-label">الميدان :</span> <span class="meta-val">{{ $memo->domain_name ?: 'استخدام الحاسوب' }}</span></div>
                    <div class="meta-field"><span class="meta-label">المقطع التعلمي :</span> <span class="meta-val">{{ $memo->phase ?: 'بيئة التعامل مع الحاسوب' }}</span></div>
                </td>

                <td class="header-cell header-center">
                    <div class="memo-badge-box">
                        <div class="memo-badge-title">رقم المذكرة : {{ sprintf('%02d', $memo->id) }}</div>
                        <span class="memo-sajel-tag">منصة سجِّل — الجيل الثاني</span>
                    </div>
                </td>

                <td class="header-cell header-left">
                    <div class="meta-field"><span class="meta-label">السنة الدراسية :</span> <span class="meta-val">{{ $schoolSetting->academic_year ?: '2026 / 2027' }}</span></div>
                    <div class="meta-field"><span class="meta-label">المادة :</span> <span class="meta-val">{{ $subject?->name ?: 'المعلوماتية' }}</span></div>
                    <div class="meta-field"><span class="meta-label">المستوى :</span> <span class="meta-val">{{ $level?->name ?: 'السنة الأولى متوسط' }}</span></div>
                    <div class="meta-field"><span class="meta-label">الحجم الساعي :</span> <span class="meta-val">01 ساعة</span></div>
                </td>
            </tr>
        </table>

        {{-- 2. Prominent Competence Banner --}}
        <div class="competence-banner">
            <span class="competence-title">الكفاءة المستهدفة :</span>
            <span class="competence-body">{{ $memo->competence }}</span>
        </div>

        {{-- 3. Three-column Summary Card (المراجع | الوسائل المستعملة | المعارف المستهدفة) --}}
        <table class="summary-table">
            <thead>
                <tr>
                    <th class="summary-col-ref">المراجع</th>
                    <th class="summary-col-tools">الوسائل المستعملة</th>
                    <th class="summary-col-knowledge">المعارف المستهدفة</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="summary-col-ref">
                        {!! nl2br(e($referencesText)) !!}
                    </td>
                    <td class="summary-col-tools">
                        {!! nl2br(e($resourcesText)) !!}
                    </td>
                    <td class="summary-col-knowledge">
                        <ul class="knowledge-list">
                            @foreach ($targetKnowledge as $kItem)
                                <li class="knowledge-item">{{ $kItem }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- 4. Main Progression Table (الجدول المنهجي لسير الحصة) --}}
        <table class="main-progression-table">
            <thead>
                <tr>
                    <th class="col-stage">المراحل</th>
                    <th class="col-time">الزمن</th>
                    <th class="col-indicator">مؤشرات الكفاءة</th>
                    <th class="col-activities">النشاطات و المعارف المكتسبة ( سير الدرس )</th>
                </tr>
            </thead>
            <tbody>
                {{-- Stage 1: وضعية الانطلاق --}}
                @php $stg1 = $mappedStages[0]; @endphp
                <tr>
                    <td class="col-stage">
                        <div class="stage-title">{{ $stg1['name'] }}</div>
                        <span class="stage-badge-pill">{{ $stg1['badge'] }}</span>
                    </td>
                    <td class="col-time">{{ $stg1['duration'] }} د</td>
                    <td class="col-indicator">
                        <span class="indicator-header-tag">مؤشر الانطلاق والملاحظة:</span>
                        {{ $stg1['indicator'] }}
                    </td>
                    <td class="col-activities">
                        <div class="didactic-block">
                            <span class="didactic-badge badge-problematic">الإشكالية :</span>
                            <span>{!! nl2br(e($stg1['problem'])) !!}</span>
                        </div>
                        @if (! empty($stg1['activity']))
                            <div class="didactic-block" style="margin-top: 3px;">
                                <span class="didactic-badge badge-activity">توجيه الأستاذ :</span>
                                <span>{{ $stg1['activity'] }}</span>
                            </div>
                        @endif
                        <div class="didactic-block" style="margin-top: 3px;">
                            <span class="didactic-badge badge-knowledge">الأثر الأولي :</span>
                            <span>{{ $stg1['trace'] }}</span>
                        </div>
                    </td>
                </tr>

                {{-- Stage 2: العرض (إرساء الموارد) --}}
                @php $stg2 = $mappedStages[1]; @endphp
                <tr>
                    <td class="col-stage">
                        <div class="stage-title">{{ $stg2['name'] }}</div>
                        <span class="stage-badge-pill">{{ $stg2['badge'] }}</span>
                    </td>
                    <td class="col-time">{{ $stg2['duration'] }} د</td>
                    <td class="col-indicator">
                        <span class="indicator-header-tag">مؤشرات إرساء الموارد:</span>
                        {{ $stg2['indicator'] }}
                    </td>
                    <td class="col-activities">
                        {{-- Didactic Activity --}}
                        <div class="didactic-block">
                            <span class="didactic-badge badge-activity">النشاط والتعليمات :</span>
                            <span>{!! nl2br(e($stg2['activity'])) !!}</span>
                            <div class="didactic-note">(يمكن للأستاذ توظيف نشاط بيداغوجي مكافئ وفق خصوصية المتعلمين).</div>
                        </div>

                        {{-- Didactic Knowledge & Written Trace --}}
                        <div class="didactic-block" style="border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                            <span class="didactic-badge badge-knowledge">المعارف المكتسبة والأثر الكتابي :</span>

                            @if (str_contains($topicName, 'تعريف') || str_contains($topicName, 'الحاسوب') || str_contains($topicName, 'المعلوماتية'))
                                <div style="margin-top: 3px; line-height: 1.45;">
                                    <p><strong class="item-num">1- تعريف المعلوماتية :</strong> هي علم يسمح بمعالجة المعلومات بطريقة آلية باستعمال الحاسوب، وتعتمد على عنصرين أساسيين: <strong>العتاد</strong> و<strong>البرمجيات</strong>.</p>
                                    <p style="margin-right: 12px; font-size: 7.2pt; color: #b45309;">* أصل التسمية: <strong>معلومات + آلية ⬅ معلوماتية (Informatique = Information + Automatique)</strong>.</p>

                                    <p style="margin-top: 2px;"><strong class="item-num">2- الفرق بين العتاد والبرمجيات :</strong></p>
                                    <p style="margin-right: 10px;"><strong>أ) العتاد (Matériel) :</strong> العناصر المادية الملموسة للحاسوب والأجهزة المتصلة به (الوحدة المركزية، الشاشة، الفأرة، لوحة المفاتيح...).</p>
                                    <p style="margin-right: 10px;"><strong>ب) البرمجيات (Logiciels) :</strong> العناصر غير المادية والبرامج والأوامر التي توجه العتاد لإنجاز المهام المطلوبة.</p>

                                    <p style="margin-top: 2px;"><strong class="item-num">3- تعريف الحاسوب :</strong> جهاز إلكتروني مبرمج يستقبل البيانات (إدخال)، يعالجها ويخزنها، ثم يخرج النتائج في شكل معلومات دقيقة.</p>

                                    <p style="margin-top: 3px;"><strong class="item-num">4- مخطط عمل الحاسوب :</strong></p>
                                    <div class="process-flow">
                                        <div class="process-node">إدخال المعلومات (Input)</div>
                                        <div class="process-arrow">⬅</div>
                                        <div class="process-node" style="border-color: #b45309; background: #fffbeb; color: #b45309;">المعالجة والتخزين (Process & Storage)</div>
                                        <div class="process-arrow">⬅</div>
                                        <div class="process-node" style="border-color: #059669; background: #ecfdf5; color: #047857;">إخراج المعلومات (Output)</div>
                                    </div>
                                </div>
                            @elseif (! empty($stg2['traces']))
                                <div style="margin-top: 3px;">
                                    @foreach ($stg2['traces'] as $trIdx => $traceItem)
                                        <p style="margin-bottom: 2px;"><strong class="item-num">{{ $trIdx + 1 }}-</strong> {!! nl2br(e($traceItem)) !!}</p>
                                    @endforeach
                                </div>
                            @else
                                <div style="margin-top: 3px;">
                                    <p><strong class="item-num">1- المفهوم المحوري :</strong> ضبط التعريف الإجرائي والقاعدة الأساسية للمورد المعرفي في كراس المتعلم.</p>
                                    <p><strong class="item-num">2- العلاقات والخصائص :</strong> تصنيف عناصر الدرس وتحديد القواعد التطبيقية والاستخدام السليم لأدوات المادة.</p>
                                    <p><strong class="item-num">3- الخلاصة التركيبية :</strong> رسم المخطط المفاهيمي الهيكل للتعلمات لتثبيت المكتسبات بوضوح.</p>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Stage 3: التقويم والمعالجة --}}
                @php $stg3 = $mappedStages[2]; @endphp
                <tr>
                    <td class="col-stage">
                        <div class="stage-title">{{ $stg3['name'] }}</div>
                        <span class="stage-badge-pill">{{ $stg3['badge'] }}</span>
                    </td>
                    <td class="col-time">{{ $stg3['duration'] }} د</td>
                    <td class="col-indicator">
                        <span class="indicator-header-tag">مؤشر التقويم والتحقق:</span>
                        {{ $stg3['indicator'] }}
                    </td>
                    <td class="col-activities">
                        <div class="didactic-block">
                            <span class="didactic-badge badge-eval">التقويم والتطبيق :</span>
                            @if (str_contains($topicName, 'تعريف') || str_contains($topicName, 'الحاسوب') || str_contains($topicName, 'المعلوماتية'))
                                <span>صنّف العناصر التالية في جدول ذي عمودين (<strong>العتاد</strong> / <strong>البرمجيات</strong>):</span>
                                <div style="margin: 3px 0; font-weight: 700; color: #1e3a8a; background: #f8fafc; padding: 3px 6px; border: 1px dashed #cbd5e1; border-radius: 4px;">
                                    لوحة المفاتيح — برنامج الرسام — الفأرة — فوتوشوب (Photoshop) — شاشة العرض — نظام ويندوز — الطابعة — معالج النصوص Word.
                                </div>
                            @else
                                <span>{!! nl2br(e($stg3['eval'])) !!}</span>
                            @endif
                        </div>
                        <div class="didactic-block" style="margin-top: 3px;">
                            <span class="didactic-badge badge-knowledge">المعالجة الفورية :</span>
                            <span>{{ $stg3['trace'] }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- 5. Official Visas / Signatures --}}
        <table class="visas-grid">
            <tr>
                <td class="visa-cell">
                    <div class="visa-role">تأشيرة أستاذ(ة) المادة</div>
                    <div class="visa-dots"></div>
                </td>
                <td class="visa-cell">
                    <div class="visa-role">تأشيرة السيد مدير المؤسسة</div>
                    <div class="visa-dots"></div>
                </td>
                <td class="visa-cell">
                    <div class="visa-role">تأشيرة السيد مفتش التربية الوطنية</div>
                    <div class="visa-dots"></div>
                </td>
            </tr>
        </table>

        {{-- System Footer --}}
        <div class="footer-system-text">
            منصة سجِّل لإدارة التعليم المتوسط — تم توليد واعتماد المذكرة البيداغوجية بتاريخ {{ $formatDate($memo->updated_at) }}
        </div>
    </main>

    @if ($autoPrint ?? true)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.print();
            });
        </script>
    @endif
</body>
</html>
