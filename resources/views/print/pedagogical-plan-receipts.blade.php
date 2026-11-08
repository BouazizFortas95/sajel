<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وثيقة استلام المخططات السنوية والشهرية — {{ $selectedSubject ? 'مادة ' . $selectedSubject->name . ' — ' : '' }}{{ $school->school_name ?? 'متوسطة حي 900 مسكن' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Amiri', 'Cairo', serif, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            direction: rtl;
        }

        .screen-container {
            max-width: 297mm;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Print Actions Floating Bar */
        .no-print-bar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            padding: 8px 14px;
            border-radius: 9999px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.25);
            max-width: calc(100vw - 30px);
            overflow-x: auto;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 13px;
            border-radius: 9999px;
            font-family: 'Cairo', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-print {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #0369a1;
        }

        .btn-pdf {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background-color: #059669;
        }

        .btn-word {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-word:hover {
            background-color: #1d4ed8;
        }

        .btn-mode {
            background-color: #334155;
            color: #f1f5f9;
        }
        .btn-mode:hover {
            background-color: #475569;
        }

        .btn-mode.active {
            background-color: #f59e0b;
            color: #0f172a;
        }

        .btn-back {
            background-color: #1e293b;
            color: #94a3b8;
            border: 1px solid #334155;
        }
        .btn-back:hover {
            background-color: #334155;
            color: #ffffff;
        }

        .select-subject {
            background-color: #1e293b;
            color: #ffffff;
            border: 1px solid #334155;
            padding: 5px 10px;
            border-radius: 9999px;
            font-family: 'Cairo', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            outline: none;
        }

        /* Page 1: Official Front Cover (الصفحة الأولى: صفحة رئيسية) */
        .cover-page {
            width: 100%;
            min-height: 190mm;
            height: 100vh;
            max-height: 195mm;
            padding: 14mm 16mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            break-after: page;
            position: relative;
            background: #ffffff;
            border: 4px double #1e3a8a;
            box-sizing: border-box;
        }

        .cover-inner-border {
            border: 1px solid #94a3b8;
            height: 100%;
            padding: 12mm 16mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .cover-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
        }

        .cover-republic {
            text-align: center;
            flex: 1;
        }

        .republic-title {
            font-family: 'Amiri', serif;
            font-size: 17pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .ministry-title {
            font-family: 'Amiri', serif;
            font-size: 14pt;
            font-weight: 700;
            color: #1e3a8a;
            margin: 4px 0 0 0;
        }

        .cover-school-meta {
            text-align: right;
            font-family: 'Amiri', serif;
            font-size: 12pt;
            color: #1e293b;
            line-height: 1.5;
            min-width: 240px;
        }

        .cover-emblem-box {
            text-align: left;
            min-width: 240px;
            font-family: 'Cairo', sans-serif;
            font-size: 10pt;
            color: #64748b;
        }

        .cover-main-body {
            text-align: center;
            margin: auto 0;
            padding: 20px 0;
        }

        .doc-title-wrapper {
            display: inline-block;
            border: 3px double #1e3a8a;
            padding: 18px 45px;
            background-color: #f8fafc;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.08);
        }

        .doc-title-ar {
            font-family: 'Amiri', serif;
            font-size: 26pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-title-fr {
            font-family: 'Cairo', sans-serif;
            font-size: 12pt;
            font-weight: 600;
            color: #1e3a8a;
            margin: 8px 0 0 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .doc-badge {
            margin-top: 14px;
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            color: #475569;
            font-weight: 600;
        }

        .doc-subject-highlight {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 18px;
            background-color: #1e3a8a;
            color: #ffffff;
            border-radius: 9999px;
            font-family: 'Cairo', sans-serif;
            font-size: 12pt;
            font-weight: 800;
        }

        .cover-footer {
            border-top: 2px solid #0f172a;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .academic-year-box {
            margin: 0 auto;
            text-align: center;
            border: 2px solid #1e3a8a;
            background: #ffffff;
            padding: 6px 28px;
            border-radius: 6px;
        }

        .academic-year-label {
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            font-weight: 600;
            color: #64748b;
        }

        .academic-year-value {
            font-family: 'Cairo', sans-serif;
            font-size: 16pt;
            font-weight: 900;
            color: #0f172a;
        }

        /* Page 2+: Content & Subject Pages (الصفحة الثانية والصفحات اللاحقة) */
        .content-page {
            width: 100%;
            background: #ffffff;
            box-sizing: border-box;
        }

        /* When every subject is on its own separate page */
        .subject-page {
            min-height: 190mm;
            padding: 10mm 12mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            break-after: page;
            box-sizing: border-box;
        }

        .subject-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* Continuous mode (دمج المواد المتتابعة) */
        .continuous-container {
            padding: 10mm 12mm;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .content-header-right {
            font-family: 'Amiri', serif;
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
        }

        .content-header-center {
            text-align: center;
            font-family: 'Amiri', serif;
            font-size: 14pt;
            font-weight: 700;
            color: #1e3a8a;
        }

        .content-header-left {
            font-family: 'Cairo', sans-serif;
            font-size: 10pt;
            font-weight: 700;
            color: #475569;
        }

        /* Subject Section Banner */
        .subject-section {
            margin-bottom: 12px;
            flex: 1;
        }

        .subject-badge-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 4px 4px 0 0;
            font-family: 'Cairo', sans-serif;
            font-size: 10.5pt;
            font-weight: 700;
        }

        .subject-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 1px 8px;
            border-radius: 9999px;
            font-size: 9pt;
        }

        /* The 13-Column Ledger Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            background-color: #ffffff;
            border: 1px solid #1e293b;
            margin-bottom: 6px;
        }

        .ledger-table th,
        .ledger-table td {
            border: 1px solid #334155;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .ledger-table thead tr:first-child th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Cairo', sans-serif;
            font-weight: 800;
            font-size: 9.5pt;
        }

        .ledger-table thead tr:last-child th {
            background-color: #e2e8f0;
            color: #1e293b;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 3px 2px;
        }

        .col-num {
            width: 4%;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
        }

        .col-name {
            width: 18%;
            text-align: right !important;
            padding-right: 8px !important;
            font-family: 'Amiri', serif;
            font-size: 10.5pt;
            font-weight: 700;
            color: #0f172a;
        }

        .col-annual {
            width: 9%;
            background-color: #f8fafc;
        }

        .col-month {
            width: 6%;
        }

        .col-notes {
            width: 15%;
            font-size: 8.5pt;
            text-align: right !important;
            padding-right: 6px !important;
            color: #334155;
        }

        /* Submission Box & Date Styling */
        .check-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1px;
            min-height: 28px;
        }

        .check-mark {
            display: inline-block;
            font-size: 13pt;
            font-weight: 900;
            line-height: 1;
        }

        .check-mark.checked {
            color: #15803d;
        }

        .check-mark.empty {
            color: #94a3b8;
            font-size: 11pt;
            font-weight: 400;
        }

        .submission-date {
            font-family: 'Cairo', sans-serif;
            font-size: 7.5pt;
            font-weight: 700;
            color: #0369a1;
            line-height: 1;
        }

        .submission-date.muted {
            color: #cbd5e1;
        }

        /* Signatures Footer */
        .signatures-area {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-card {
            border: 1px solid #1e3a8a;
            border-radius: 4px;
            width: 32%;
            min-height: 75px;
            padding: 6px 10px;
            background: #ffffff;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sig-title {
            font-family: 'Cairo', sans-serif;
            font-size: 9.5pt;
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
        }

        .sig-name {
            font-family: 'Amiri', serif;
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: auto;
        }

        .sig-date {
            font-family: 'Cairo', sans-serif;
            font-size: 7.5pt;
            color: #64748b;
        }

        /* Print Media Query */
        @media print {
            body {
                background: #ffffff;
            }
            .screen-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
            .cover-page {
                height: 190mm;
                page-break-after: always;
                break-after: page;
            }
            .subject-page {
                min-height: 190mm;
                height: 190mm;
                page-break-after: always;
                break-after: page;
            }
            .subject-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            .ledger-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Floating Action Bar -->
    <div class="no-print-bar">
        <!-- Select Subject On The Fly -->
        <select onchange="window.location.href = this.value" class="select-subject">
            <option value="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'layout' => $layout]) }}" {{ ! $selectedSubject ? 'selected' : '' }}>
                جميع المواد ({{ $groupedTeachers->count() }} مادة)
            </option>
            @foreach ($allSubjects as $subj)
                <option value="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'subject_id' => $subj->id, 'layout' => $layout]) }}" {{ ($selectedSubject && $selectedSubject->id == $subj->id) ? 'selected' : '' }}>
                    مادة {{ $subj->name }}
                </option>
            @endforeach
        </select>

        <!-- Layout Mode Toggle (كل مادة في صفحة vs عرض مدمج) -->
        @if ($layout === 'continuous')
            <a href="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => 'separate']) }}" class="btn-action btn-mode">
                📑 تفعيل: كل مادة في صفحة مستقلة
            </a>
        @else
            <a href="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => 'continuous']) }}" class="btn-action btn-mode">
                📄 تفعيل: عرض مدمج للمواد
            </a>
        @endif

        <button onclick="window.print()" class="btn-action btn-print">
            🖨 طباعة الوثيقة
        </button>

        <a href="{{ route('print.pedagogical-plan-receipts.pdf', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => $layout]) }}" class="btn-action btn-pdf">
            📄 تحميل PDF
        </a>

        <a href="{{ route('print.pedagogical-plan-receipts.word', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => $layout]) }}" class="btn-action btn-word">
            📘 تصدير Word (.docx)
        </a>

        <a href="{{ url('/admin/pedagogical-plan-receipts') }}" class="btn-action btn-back">
            ↩ العودة للوحة
        </a>
    </div>

    <div class="screen-container">
        <!-- ========================================== -->
        <!-- الصفحة الأولى: صفحة رئيسية (Official Cover) -->
        <!-- ========================================== -->
        <div class="cover-page">
            <div class="cover-inner-border">
                
                <!-- رأس الصفحة الأولى: الجمهورية والوزارة والمديرية والمؤسسة -->
                <div class="cover-header">
                    <!-- يمين: مديرية التربية والمؤسسة -->
                    <div class="cover-school-meta">
                        <div><strong>مديرية التربية لولاية:</strong> {{ $school->municipality_name ?? 'المغير' }}</div>
                        <div><strong>المؤسسة:</strong> {{ $school->school_name ?? 'متوسطة حي 900 مسكن' }}</div>
                        <div style="font-size: 10pt; color: #64748b; margin-top: 2px;">الرمز الإداري: {{ $school->school_code ?? '00-0000-0001' }}</div>
                    </div>

                    <!-- وسط: الجمهورية والوزارة -->
                    <div class="cover-republic">
                        <div class="republic-title">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                        <div class="ministry-title">وزارة التربية الوطنية</div>
                    </div>

                    <!-- يسار: السجل والأرشيف -->
                    <div class="cover-emblem-box">
                        <div><strong>سجل الرقمنة والمتابعة البيداغوجية</strong></div>
                        <div>وثيقة رسمية للإدارة التربوية</div>
                        <div>تاريخ الإصدار: {{ $printed_at }}</div>
                    </div>
                </div>

                <!-- وسط الصفحة الأولى: اسم الوثيقة بخط عريض -->
                <div class="cover-main-body">
                    <div class="doc-title-wrapper">
                        <h1 class="doc-title-ar">وثيقة استلام المخططات السنوية والشهرية</h1>
                        <div class="doc-title-fr">Procès-Verbal de Réception des Plans et Progressions Pédagogiques</div>
                        
                        @if ($selectedSubject)
                            <div class="doc-subject-highlight">
                                مادة: {{ $selectedSubject->name }}
                            </div>
                        @else
                            <div class="doc-badge">سجل المتابعة والتأشير الإداري لتدرجات ومخططات السادة أساتذة التعليم المتوسط</div>
                        @endif
                    </div>
                </div>

                <!-- أسفل الصفحة الأولى في الوسط: تحديد السنة الدراسية -->
                <div class="cover-footer">
                    <div style="width: 200px;"></div>
                    
                    <div class="academic-year-box">
                        <div class="academic-year-label">السنة الدراسية</div>
                        <div class="academic-year-value">{{ $academic_year }}</div>
                    </div>

                    <div style="width: 200px; text-align: left; font-family: 'Amiri', serif; font-size: 11pt; color: #334155;">
                        <div><strong>مدير المؤسسة:</strong> {{ $school->principal_name ?? 'أحمد بوزقاق' }}</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- الصفحة الثانية وما بعدها: الجداول حسب المواد -->
        <!-- ========================================== -->
        @if ($pagePerSubject)
            <!-- النمط المقترح المفضل: كل مادة في صفحة مستقلة كاملة مع تأشيراتها -->
            @foreach ($groupedTeachers as $subjectName => $teachers)
                @php $teacherIndex = 1; @endphp
                <div class="content-page subject-page">
                    
                    <!-- ترويسة صفحة المادة -->
                    <div class="content-header">
                        <div class="content-header-right">
                            {{ $school->school_name ?? 'متوسطة حي 900 مسكن' }} | مديرية التربية لولاية {{ $school->municipality_name ?? 'المغير' }}
                        </div>
                        <div class="content-header-center">
                            وثيقة استلام المخططات والتدرجات البيداغوجية السنوية والشهرية
                        </div>
                        <div class="content-header-left">
                            السنة الدراسية: {{ $academic_year }}
                        </div>
                    </div>

                    <!-- جسم جدول المادة -->
                    <div class="subject-section">
                        <div class="subject-badge-bar">
                            <span>المادة: <strong>{{ $subjectName }}</strong></span>
                            <span class="subject-count">{{ $teachers->count() }} أساتذة</span>
                        </div>

                        <table class="ledger-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="col-num">الرقم</th>
                                    <th rowspan="2" class="col-name">اسم ولقب الأستاذ</th>
                                    <th rowspan="2" class="col-annual">المخطط السنوي</th>
                                    <th colspan="9">المخططات والتدرجات الشهرية</th>
                                    <th rowspan="2" class="col-notes">ملاحظات</th>
                                </tr>
                                <tr>
                                    @foreach ($months as $monthKey => $monthLabel)
                                        <th class="col-month">{{ $monthLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teachers as $teacher)
                                    @php
                                        $receipt = $teacher->pedagogicalPlanReceipts->first();
                                    @endphp
                                    <tr>
                                        <!-- 1. الرقم -->
                                        <td class="col-num">{{ $teacherIndex++ }}</td>

                                        <!-- 2. اسم ولقب الأستاذ -->
                                        <td class="col-name">
                                            {{ $teacher->name }}
                                            @if ($teacher->secondarySubject)
                                                <div style="font-size: 7.5pt; font-family: 'Cairo', sans-serif; color: #0284c7; font-weight: normal;">
                                                    + {{ $teacher->secondarySubject->name }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- 3. المخطط السنوي -->
                                        <td class="col-annual">
                                            <div class="check-container">
                                                @if ($receipt && $receipt->annual_plan_submitted)
                                                    <span class="check-mark checked">✔</span>
                                                    <span class="submission-date">
                                                        {{ $receipt->annual_plan_date ? \Carbon\Carbon::parse($receipt->annual_plan_date)->format('d/m') : 'تم' }}
                                                    </span>
                                                @else
                                                    <span class="check-mark empty">□</span>
                                                    <span class="submission-date muted">—</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- 4-12. المخططات والتدرجات الشهرية (من سبتمبر حتى ماي) -->
                                        @foreach ($months as $monthKey => $monthLabel)
                                            @php
                                                $isSub = $receipt ? $receipt->isMonthSubmitted($monthKey) : false;
                                                $subDate = $receipt ? $receipt->getMonthDate($monthKey) : null;
                                            @endphp
                                            <td class="col-month">
                                                <div class="check-container">
                                                    @if ($isSub)
                                                        <span class="check-mark checked">✔</span>
                                                        <span class="submission-date">
                                                            {{ $subDate ? \Carbon\Carbon::parse($subDate)->format('d/m') : 'تم' }}
                                                        </span>
                                                    @else
                                                        <span class="check-mark empty">□</span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach

                                        <!-- 13. ملاحظات -->
                                        <td class="col-notes">
                                            {{ $receipt?->notes ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ذيل التأشيرات والأختام الرسمية المخصص لصفحة كل مادة -->
                    <div class="signatures-area">
                        <div class="sig-card">
                            <div class="sig-title">تأشيرة ناظر الدروس</div>
                            <div class="sig-name">السيد(ة): ........................................</div>
                            <div class="sig-date">التاريخ: ...... / ...... / 2026 م</div>
                        </div>

                        <div class="sig-card">
                            <div class="sig-title">ملاحظات وقرارات الإدارة</div>
                            <div class="sig-name">مادة: {{ $subjectName }}</div>
                            <div class="sig-date">حُرِّر بـ {{ $school->municipality_name ?? 'المغير' }} في: {{ $printed_at }}</div>
                        </div>

                        <div class="sig-card">
                            <div class="sig-title">تأشيرة وختم مدير المؤسسة</div>
                            <div class="sig-name">{{ $school->principal_name ?? 'أحمد بوزقاق' }}</div>
                            <div class="sig-date">الختم والتوقيع الرسمي</div>
                        </div>
                    </div>

                </div>
            @endforeach
        @else
            <!-- النمط المدمج المستمر (جميع المواد متتابعة) -->
            <div class="content-page continuous-container">
                <div class="content-header">
                    <div class="content-header-right">
                        {{ $school->school_name ?? 'متوسطة حي 900 مسكن' }} | مديرية التربية لولاية {{ $school->municipality_name ?? 'المغير' }}
                    </div>
                    <div class="content-header-center">
                        وثيقة استلام المخططات والتدرجات البيداغوجية السنوية والشهرية (عرض مدمج)
                    </div>
                    <div class="content-header-left">
                        السنة الدراسية: {{ $academic_year }}
                    </div>
                </div>

                @php $globalCounter = 1; @endphp

                @foreach ($groupedTeachers as $subjectName => $teachers)
                    <div class="subject-section" style="page-break-inside: avoid; break-inside: avoid; margin-bottom: 16px;">
                        <div class="subject-badge-bar">
                            <span>مادة: <strong>{{ $subjectName }}</strong></span>
                            <span class="subject-count">{{ $teachers->count() }} أساتذة</span>
                        </div>

                        <table class="ledger-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="col-num">الرقم</th>
                                    <th rowspan="2" class="col-name">اسم ولقب الأستاذ</th>
                                    <th rowspan="2" class="col-annual">المخطط السنوي</th>
                                    <th colspan="9">المخططات والتدرجات الشهرية</th>
                                    <th rowspan="2" class="col-notes">ملاحظات</th>
                                </tr>
                                <tr>
                                    @foreach ($months as $monthKey => $monthLabel)
                                        <th class="col-month">{{ $monthLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teachers as $teacher)
                                    @php
                                        $receipt = $teacher->pedagogicalPlanReceipts->first();
                                    @endphp
                                    <tr>
                                        <td class="col-num">{{ $globalCounter++ }}</td>
                                        <td class="col-name">
                                            {{ $teacher->name }}
                                            @if ($teacher->secondarySubject)
                                                <div style="font-size: 7.5pt; font-family: 'Cairo', sans-serif; color: #0284c7; font-weight: normal;">
                                                    + {{ $teacher->secondarySubject->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="col-annual">
                                            <div class="check-container">
                                                @if ($receipt && $receipt->annual_plan_submitted)
                                                    <span class="check-mark checked">✔</span>
                                                    <span class="submission-date">
                                                        {{ $receipt->annual_plan_date ? \Carbon\Carbon::parse($receipt->annual_plan_date)->format('d/m') : 'تم' }}
                                                    </span>
                                                @else
                                                    <span class="check-mark empty">□</span>
                                                    <span class="submission-date muted">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach ($months as $monthKey => $monthLabel)
                                            @php
                                                $isSub = $receipt ? $receipt->isMonthSubmitted($monthKey) : false;
                                                $subDate = $receipt ? $receipt->getMonthDate($monthKey) : null;
                                            @endphp
                                            <td class="col-month">
                                                <div class="check-container">
                                                    @if ($isSub)
                                                        <span class="check-mark checked">✔</span>
                                                        <span class="submission-date">
                                                            {{ $subDate ? \Carbon\Carbon::parse($subDate)->format('d/m') : 'تم' }}
                                                        </span>
                                                    @else
                                                        <span class="check-mark empty">□</span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                        <td class="col-notes">
                                            {{ $receipt?->notes ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="signatures-area" style="margin-top: 20px;">
                    <div class="sig-card">
                        <div class="sig-title">تأشيرة ناظر الدروس</div>
                        <div class="sig-name">السيد(ة): ........................................</div>
                        <div class="sig-date">التاريخ: ...... / ...... / 2026 م</div>
                    </div>

                    <div class="sig-card">
                        <div class="sig-title">ملاحظات وقرارات الإدارة</div>
                        <div class="sig-name">سجل المتابعة البيداغوجية المعتمد</div>
                        <div class="sig-date">حُرِّر بـ {{ $school->municipality_name ?? 'المغير' }} في: {{ $printed_at }}</div>
                    </div>

                    <div class="sig-card">
                        <div class="sig-title">تأشيرة وختم مدير المؤسسة</div>
                        <div class="sig-name">{{ $school->principal_name ?? 'أحمد بوزقاق' }}</div>
                        <div class="sig-date">الختم والتوقيع الرسمي</div>
                    </div>
                </div>

            </div>
        @endif

    </div>

</body>
</html>
