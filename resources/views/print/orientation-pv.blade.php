<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('محضر جلسة مجلس القبول والتوجيه لما بعد مرحلة التعليم الأساسي - السنة الدراسية :year', ['year' => $academic_year]) }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 11pt;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0 0 2px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
        }
        .title-banner {
            text-align: center;
            background: #f1f5f9;
            border: 1.5px solid #334155;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .title-banner h3 {
            font-size: 12pt;
            margin: 0 0 2px 0;
            font-weight: bold;
            color: #0f172a;
        }
        .title-banner p {
            font-size: 8.5pt;
            margin: 0;
            color: #475569;
        }
        .summary-bar {
            display: flex;
            justify-content: space-between;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 6px 12px;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .summary-item {
            display: flex;
            gap: 4px;
        }
        .summary-item strong {
            color: #1e40af;
        }
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.ledger-table th, table.ledger-table td {
            border: 1px solid #64748b;
            padding: 3.5px 4px;
            text-align: center;
        }
        table.ledger-table th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e293b;
        }
        .text-start {
            text-align: right !important;
            padding-right: 6px !important;
        }
        .signatures {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            text-align: center;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }
        .sign-col {
            width: 22%;
        }
        .sign-space {
            height: 45px;
        }
        @media print {
            body { font-size: 8pt; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>الجمهورية الجزائرية الديمقراطية الشعبية</h1>
        <h2>وزارة التربية الوطنية</h2>
        <div class="meta">
            <span>{{ $school?->directorate_name ?? 'مديرية التربية الوطنية' }}</span>
            <span>{{ $school?->school_name ?? 'متوسطة سِجلّ النموذجية' }}</span>
            <span><strong>السنة الدراسية:</strong> {{ $academic_year }}</span>
            <span><strong>تاريخ المداولة:</strong> {{ now()->locale('ar')->translatedFormat('d F Y') }}</span>
        </div>
    </div>

    <div class="title-banner">
        <h3>محضر جلسة مجلس القبول والتوجيه لما بعد مرحلة التعليم الأساسي</h3>
        <p>المنعقد قانوناً بالمؤسسة لدراسة ملفات تلاميذ السنة الرابعة متوسط وتوجيههم إلى الجذوع المشتركة للتعليم الثانوي العام والتكنولوجي</p>
    </div>

    @php $k = $kpis; @endphp
    <div class="summary-bar">
        <div class="summary-item"><span>إجمالي المرشحين:</span> <strong>{{ $k['total_candidates'] }}</strong></div>
        <div class="summary-item"><span>المقبولون بالثانوي:</span> <strong style="color:#16a34a;">{{ $k['admitted_count'] }} ({{ $k['admission_rate'] }}%)</strong></div>
        <div class="summary-item"><span>جذع مشترك علوم وتكنولوجيا:</span> <strong>{{ $k['streams']['science_tech']['count'] }} ({{ $k['streams']['science_tech']['percentage'] }}%)</strong></div>
        <div class="summary-item"><span>جذع مشترك آداب:</span> <strong>{{ $k['streams']['arts_humanities']['count'] }} ({{ $k['streams']['arts_humanities']['percentage'] }}%)</strong></div>
        <div class="summary-item"><span>التكوين المهني:</span> <strong>{{ $k['streams']['vocational_training']['count'] }}</strong></div>
        <div class="summary-item"><span>إعادة السنة:</span> <strong>{{ $k['streams']['repeat_year']['count'] }}</strong></div>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 22px;">#</th>
                <th style="width: 65px;">رقم القيد</th>
                <th class="text-start" style="width: 140px;">اللقب والاسم</th>
                <th style="width: 65px;">تاريخ الميلاد</th>
                <th style="width: 60px;">القسم</th>
                <th style="width: 45px; background:#eff6ff;">م.العلمي</th>
                <th style="width: 45px; background:#fef3c7;">م.الأدبي</th>
                <th style="width: 45px;">م.السنوي</th>
                <th style="width: 45px;">م.BEM</th>
                <th style="width: 50px; background:#f1f5f9;">م.القبول</th>
                <th style="width: 70px;">الرغبة 1</th>
                <th style="width: 70px;">الرغبة 2</th>
                <th style="width: 110px; background:#f0fdf4; font-weight:black;">قرار المجلس المعتمد</th>
                <th>الملاحظات والتوجيهات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($candidates as $idx => $row)
                @php
                    $st = $row['student'];
                    $or = $row['orientation'];
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td style="font-family:monospace; font-size:7.5pt;">{{ $row['matricule'] }}</td>
                    <td class="text-start" style="font-weight:bold;">{{ $st->full_name }}</td>
                    <td>{{ $st->date_of_birth?->format('Y/m/d') }}</td>
                    <td>{{ $row['classroom_name'] }}</td>
                    <td style="font-weight:bold; color:#1e40af;">{{ $row['scientific_score'] ?? '—' }}</td>
                    <td style="font-weight:bold; color:#92400e;">{{ $row['literary_score'] ?? '—' }}</td>
                    <td style="font-weight:bold;">{{ $row['general_average'] ?? '—' }}</td>
                    <td style="font-weight:bold; color:#047857;">{{ $row['bem_score'] ?? '—' }}</td>
                    <td style="font-weight:black; font-size:8.5pt;">{{ $row['admission_average'] ?? '—' }}</td>
                    <td>{{ $or->first_choice?->shortLabel() ?? '—' }}</td>
                    <td>{{ $or->second_choice?->shortLabel() ?? '—' }}</td>
                    <td style="font-weight:bold; font-size:8.5pt; color:#14532d; background:#f0fdf4;">
                        {{ $or->effectiveDecision()?->shortLabel() ?? '—' }}
                        @if($or->appeal_status === \App\Enums\OrientationAppealStatus::Accepted)
                            <span style="font-size:6.5pt; color:#d97706;">(طعن)</span>
                        @endif
                    </td>
                    <td style="font-size:7.5pt; text-align:right;">
                        {{ $or->counselor_notes ?? ($or->is_admitted ? 'ناجح ومقبول بالثانوي' : '—') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="padding: 15px;">لا يوجد تلاميذ مسجلين في أقسام السنة الرابعة متوسط.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Official Signatures Block --}}
    <div class="signatures">
        <div class="sign-col">
            <strong>مستشار(ة) التوجيه والإرشاد المدرسي</strong>
            <div class="sign-space"></div>
            <span>الاسم والختم والتأشيرة</span>
        </div>
        <div class="sign-col">
            <strong>ممثلو أساتذة المواد الأساسية</strong>
            <div class="sign-space"></div>
            <span>التوقيعات</span>
        </div>
        <div class="sign-col">
            <strong>مستشار التربية</strong>
            <div class="sign-space"></div>
            <span>التوقيع والتأشيرة</span>
        </div>
        <div class="sign-col">
            <strong>رئيس المجلس (مدير المؤسسة)</strong>
            <div class="sign-space"></div>
            <span>الختم والتوقيع الرسمي</span>
        </div>
    </div>

</body>
</html>

