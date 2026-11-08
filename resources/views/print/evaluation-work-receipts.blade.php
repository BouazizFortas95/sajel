<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وثيقة تسليم الأعمال التقويمية — {{ $selectedSubject ? 'مادة ' . $selectedSubject->name . ' — ' : '' }}{{ $school->school_name ?? 'متوسطة حي 900 مسكن' }}</title>
    
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
            padding: 12mm 18mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            break-after: page;
            position: relative;
            background: #ffffff;
            box-sizing: border-box;
        }

        .cover-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 0;
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

        .cover-doc-meta {
            text-align: left;
            min-width: 240px;
            font-family: 'Cairo', sans-serif;
            font-size: 10pt;
            color: #475569;
            line-height: 1.5;
        }

        .cover-main-body {
            text-align: center;
            margin: auto 0;
            padding: 20px 0;
        }

        .doc-title-wrapper {
            display: inline-block;
            border: 2px solid #1e3a8a;
            padding: 26px 60px;
            background-color: #ffffff;
            width: 82%;
            box-sizing: border-box;
        }

        .doc-title-ar {
            font-family: 'Amiri', serif;
            font-size: 28pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-badge {
            margin-top: 14px;
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            color: #475569;
            font-weight: 600;
        }

        .doc-subject-highlight {
            display: block;
            margin-top: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 16pt;
            font-weight: 700;
            color: #1e3a8a;
        }

        .cover-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-bottom: 8px;
        }

        .academic-year-box {
            text-align: center;
            border: 2px solid #1e3a8a;
            background: #ffffff;
            padding: 8px 45px;
            font-family: 'Cairo', sans-serif;
            font-size: 16pt;
            font-weight: 800;
            color: #0f172a;
            display: inline-block;
            min-width: 320px;
        }

        /* Page 2+: Content & Subject Pages (الصفحة الثانية والصفحات اللاحقة - كل مادة في صفحة مستقلة) */
        .content-page {
            width: 100%;
            background: #ffffff;
            box-sizing: border-box;
        }

        .subject-page {
            min-height: 190mm;
            padding: 10mm 12mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-before: always;
            break-before: page;
            page-break-after: always;
            break-after: page;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
        }

        .subject-page:last-child {
            page-break-after: auto;
            break-after: auto;
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
            page-break-inside: avoid !important;
            break-inside: avoid !important;
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
            font-size: 8.5pt;
            background-color: #ffffff;
            border: 1px solid #1e293b;
            margin-bottom: 6px;
        }

        .ledger-table thead {
            display: table-header-group !important;
        }

        .ledger-table tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .ledger-table th,
        .ledger-table td {
            border: 1px solid #334155;
            padding: 4px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .ledger-table thead tr:first-child th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Cairo', sans-serif;
            font-weight: 800;
            font-size: 9pt;
        }

        .ledger-table thead tr:last-child th {
            background-color: #e2e8f0;
            color: #1e293b;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 2px 1px;
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
            font-size: 13pt;
            font-weight: 700;
            color: #0f172a;
        }

        .col-diag {
            width: 8.5%;
            background-color: #f8fafc;
        }

        .col-sub {
            width: 5.8%;
        }

        .col-group {
            font-family: 'Cairo', sans-serif;
            font-weight: 800;
        }

        .col-notes {
            width: 18%;
            font-size: 8.5pt;
            text-align: right !important;
            padding-right: 6px !important;
            color: #334155;
            font-family: 'Amiri', serif;
        }

        /* Submission Box & Date Styling - On ONE single line */
        .check-container {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 3px;
            white-space: nowrap;
        }

        .check-mark {
            display: inline-block;
            font-size: 11pt;
            font-weight: 900;
            line-height: 1;
        }

        .check-mark.checked {
            color: #15803d;
        }

        .check-mark.empty {
            color: #94a3b8;
            font-size: 10pt;
            font-weight: 400;
        }

        .submission-date {
            font-family: 'Cairo', sans-serif;
            font-size: 7.5pt;
            font-weight: 700;
            color: #0369a1;
            line-height: 1;
        }

        .teacher-name {
            font-family: 'Amiri', serif;
            font-size: 13pt;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }

        .teacher-sub-badge {
            font-family: 'Cairo', sans-serif;
            font-size: 8pt;
            color: #0284c7;
            font-weight: 600;
            margin-top: 1px;
        }

        /* Clean Ministerial Footer */
        .ministerial-footer-clean {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .footer-principal-block {
            text-align: center;
            min-width: 220px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-date {
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-align: center;
        }

        .footer-title {
            font-family: 'Amiri', serif;
            font-size: 14pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0;
            text-align: center;
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
                min-height: 185mm;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .subject-page {
                page-break-before: always;
                break-before: page;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
                min-height: auto;
                height: auto;
            }
            .subject-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            .ledger-table {
                page-break-inside: auto;
            }
            .ledger-table thead {
                display: table-header-group !important;
            }
            .ledger-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .ministerial-footer-clean {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Toolbar for Screen View -->
    <div class="no-print-bar">
        <a href="{{ url('/admin/evaluation-work-receipts') }}" class="btn-action btn-back">
            <span>&larr;</span> لوحة الاستلام
        </a>

        <button onclick="window.print()" class="btn-action btn-print">
            <span>&#128438;</span> طباعة الوثيقة
        </button>

        <a href="{{ route('print.evaluation-work-receipts.pdf', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => $layout]) }}" class="btn-action btn-pdf">
            <span>&#128196;</span> PDF
        </a>

        <a href="{{ route('print.evaluation-work-receipts.word', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubject?->id, 'layout' => $layout]) }}" class="btn-action btn-word">
            <span>&#128190;</span> Word (.docx)
        </a>

        <!-- Subject Switcher -->
        <select class="select-subject" onchange="window.location.href = this.value">
            <option value="{{ route('print.evaluation-work-receipts', ['locale' => app()->getLocale()]) }}" {{ !$selectedSubject ? 'selected' : '' }}>
                -- جميع المواد التعليمية (كل مادة في صفحة) --
            </option>
            @foreach ($allSubjects as $sub)
                <option value="{{ route('print.evaluation-work-receipts', ['locale' => app()->getLocale(), 'subject_id' => $sub->id]) }}" {{ $selectedSubject?->id === $sub->id ? 'selected' : '' }}>
                    مادة {{ $sub->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="screen-container">

        <!-- ==================================================== -->
        <!-- Page 1: Official Front Cover (الصفحة الأولى: صفحة رئيسية) -->
        <!-- ==================================================== -->
        <div class="cover-page">
            <!-- Top Header -->
            <div class="cover-header">
                <!-- Directorate & School (Right) -->
                <div class="cover-school-meta">
                    <div>مديرية التربية لولاية: {{ $school->municipality_name ?? 'المغير' }}</div>
                    <div>المؤسسة: {{ $school->school_name ?? 'متوسطة حي 900 مسكن' }}</div>
                </div>

                <!-- Republic & Ministry (Center) -->
                <div class="cover-republic">
                    <div class="republic-title">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                    <div class="ministry-title">وزارة التربية الوطنية</div>
                </div>

                <!-- Document Meta & Issue Date (Left) -->
                <div class="cover-doc-meta">
                    <div>سجل المتابعة البيداغوجية</div>
                    <div>تاريخ الإصدار: {{ $printed_at }}</div>
                </div>
            </div>

            <!-- Vertically Centered Document Title Box (Middle) -->
            <div class="cover-main-body">
                <div class="doc-title-wrapper">
                    <h1 class="doc-title-ar">وثيقة تسليم الأعمال التقويمية</h1>
                    @if ($selectedSubject)
                        <div class="doc-subject-highlight">مادة: {{ $selectedSubject->name }}</div>
                    @else
                        <div class="doc-badge">سجل المتابعة والتأشير الإداري لتسليم الأعمال والوثائق التقويمية</div>
                    @endif
                </div>
            </div>

            <!-- Bottom Margin Academic Year Box (Bottom) -->
            <div class="cover-footer">
                <div class="academic-year-box">
                    السنة الدراسية: {{ $academic_year }}
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- Page 2+: Unified 13-Column RTL Tables (كل مادة في صفحة مستقلة بترتيب كامل) -->
        <!-- ==================================================== -->
        @foreach ($groupedTeachers as $subjectName => $teachers)
            <div class="content-page subject-page">
                <!-- Page Header -->
                <div class="content-header">
                    <div class="content-header-right">
                        {{ $school->school_name ?? 'متوسطة حي 900 مسكن' }} | مديرية التربية لولاية {{ $school->municipality_name ?? 'المغير' }}
                    </div>
                    <div class="content-header-center">
                        وثيقة تسليم الأعمال التقويمية
                    </div>
                    <div class="content-header-left">
                        السنة الدراسية: {{ $academic_year }}
                    </div>
                </div>

                <!-- Subject Table Section -->
                <div class="subject-section">
                    <div class="subject-badge-bar">
                        <div>المادة: {{ $subjectName }}</div>
                        <div class="subject-count">{{ $teachers->count() }} أساتذة</div>
                    </div>

                    <table class="ledger-table">
                        <thead>
                            <!-- Row 1: Main Headers -->
                            <tr>
                                <th rowspan="2" class="col-num">الرقم</th>
                                <th rowspan="2" class="col-name">اسم ولقب الأستاذ</th>
                                <th rowspan="2" class="col-diag">اختبار كشف المستوى</th>
                                <th colspan="3" class="col-group" style="background-color: #e0f2fe; color: #0369a1;">الواجب المنزلي</th>
                                <th colspan="3" class="col-group" style="background-color: #e0e7ff; color: #3730a3;">الفروض المحروسة</th>
                                <th colspan="3" class="col-group" style="background-color: #f3e8ff; color: #6b21a8;">الاختبار</th>
                                <th rowspan="2" class="col-notes">الملاحظة</th>
                            </tr>
                            <!-- Row 2: Sub-columns 1, 2, 3 -->
                            <tr>
                                <!-- Homework 1, 2, 3 -->
                                <th class="col-sub">1</th>
                                <th class="col-sub">2</th>
                                <th class="col-sub">3</th>
                                <!-- Quizzes 1, 2, 3 -->
                                <th class="col-sub">1</th>
                                <th class="col-sub">2</th>
                                <th class="col-sub">3</th>
                                <!-- Exams 1, 2, 3 -->
                                <th class="col-sub">1</th>
                                <th class="col-sub">2</th>
                                <th class="col-sub">3</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($teachers as $teacher)
                                @php
                                    $receipt = $teacher->evaluationWorkReceipts->first();
                                @endphp
                                <tr>
                                    <!-- 1. Number -->
                                    <td class="col-num">{{ $loop->iteration }}</td>

                                    <!-- 2. Teacher Name -->
                                    <td class="col-name">
                                        <div class="teacher-name">{{ $teacher->name }}</div>
                                        @if ($teacher->secondarySubject)
                                            <div class="teacher-sub-badge">+ {{ $teacher->secondarySubject->name }}</div>
                                        @endif
                                    </td>

                                    <!-- 3. Diagnostic Test -->
                                    <td class="col-diag">
                                        <div class="check-container">
                                            @if ($receipt?->diagnostic_test_submitted)
                                                <span class="check-mark checked">&#10004;</span>
                                                <span class="submission-date">{{ $receipt->diagnostic_test_date ? \Carbon\Carbon::parse($receipt->diagnostic_test_date)->format('d/m') : '' }}</span>
                                            @else
                                                <span class="check-mark empty">&#9633;</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 4. Homework 1, 2, 3 -->
                                    @foreach ([1, 2, 3] as $term)
                                        @php
                                            $hwKey = "homework_{$term}";
                                            $isHw = $receipt ? $receipt->isItemSubmitted($hwKey) : false;
                                            $hwDate = $receipt ? $receipt->getItemDate($hwKey) : null;
                                        @endphp
                                        <td class="col-sub">
                                            <div class="check-container">
                                                @if ($isHw)
                                                    <span class="check-mark checked">&#10004;</span>
                                                    <span class="submission-date">{{ $hwDate ? \Carbon\Carbon::parse($hwDate)->format('d/m') : '' }}</span>
                                                @else
                                                    <span class="check-mark empty">&#9633;</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 5. Quizzes 1, 2, 3 -->
                                    @foreach ([1, 2, 3] as $term)
                                        @php
                                            $quizKey = "quiz_{$term}";
                                            $isQuiz = $receipt ? $receipt->isItemSubmitted($quizKey) : false;
                                            $quizDate = $receipt ? $receipt->getItemDate($quizKey) : null;
                                        @endphp
                                        <td class="col-sub">
                                            <div class="check-container">
                                                @if ($isQuiz)
                                                    <span class="check-mark checked">&#10004;</span>
                                                    <span class="submission-date">{{ $quizDate ? \Carbon\Carbon::parse($quizDate)->format('d/m') : '' }}</span>
                                                @else
                                                    <span class="check-mark empty">&#9633;</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 6. Exams 1, 2, 3 -->
                                    @foreach ([1, 2, 3] as $term)
                                        @php
                                            $examKey = "exam_{$term}";
                                            $isExam = $receipt ? $receipt->isItemSubmitted($examKey) : false;
                                            $examDate = $receipt ? $receipt->getItemDate($examKey) : null;
                                        @endphp
                                        <td class="col-sub">
                                            <div class="check-container">
                                                @if ($isExam)
                                                    <span class="check-mark checked">&#10004;</span>
                                                    <span class="submission-date">{{ $examDate ? \Carbon\Carbon::parse($examDate)->format('d/m') : '' }}</span>
                                                @else
                                                    <span class="check-mark empty">&#9633;</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 7. Notes -->
                                    <td class="col-notes">
                                        {{ $receipt?->notes ?? '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Clean Ministerial Footer (ذيل التوقيع الوزاري النظيف) -->
                <div class="ministerial-footer-clean">
                    <div class="footer-principal-block">
                        <div class="footer-date">{{ $school->municipality_name ?? 'المغير' }} في:</div>
                        <div class="footer-title">مدير المؤسسة</div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

</body>
</html>
