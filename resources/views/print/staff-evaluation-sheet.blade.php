@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('Y/m/d') : '—';
    $school = \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2025/2026');
    $user = $evaluation->user;
    $evaluator = $evaluation->evaluator;
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استمارة التنقيط والتقييم الإداري السنوي') }} — {{ $user?->name }} ({{ $yearStr }})</title>

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
            margin: 8mm 10mm 8mm 10mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 190mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 11px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-print .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary { background: #0284c7; color: #ffffff; }
        .btn-primary:hover { background: #0369a1; }
        .btn-secondary { background: #334155; color: #ffffff; border-color: #475569; }
        .btn-secondary:hover { background: #475569; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
        }

        .sheet-container {
            max-width: 190mm;
            margin: 0 auto;
            border: 1.5px solid #0f172a;
            padding: 10px 14px;
            background: #ffffff;
        }

        /* Official Header */
        .header-section {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .republic-title {
            font-family: 'Amiri', serif;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .ministry-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }

        .sub-header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 6px;
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
        }

        .doc-title-box {
            background: #f8fafc;
            border: 1.5px solid #0f172a;
            padding: 6px 12px;
            margin: 10px 0;
            text-align: center;
            border-radius: 4px;
        }

        .doc-main-title {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }

        .doc-sub-title {
            font-size: 9.5px;
            color: #475569;
            font-weight: 600;
            margin-top: 2px;
        }

        /* Identity Section */
        .section-title {
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 8px;
            margin-top: 10px;
            margin-bottom: 6px;
            border-radius: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 9.5px;
        }

        .info-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .info-label {
            font-weight: 700;
            color: #475569;
            min-width: 95px;
        }

        .info-value {
            font-weight: 800;
            color: #0f172a;
        }

        /* Evaluation Table */
        .eval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9.5px;
        }

        .eval-table th, .eval-table td {
            border: 1px solid #64748b;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .eval-table th {
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
        }

        .eval-table .criteria-desc {
            font-size: 8.5px;
            color: #64748b;
            display: block;
            margin-top: 1px;
        }

        .score-cell {
            text-align: center;
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            background: #fafafa;
        }

        .total-row th, .total-row td {
            background: #e2e8f0;
            font-weight: 900;
            font-size: 11px;
            border-top: 2px solid #0f172a;
        }

        /* Scores Banner */
        .scores-banner {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 10px;
        }

        .score-card {
            border: 1.5px solid #0f172a;
            border-radius: 4px;
            text-align: center;
            padding: 6px;
            background: #f8fafc;
        }

        .score-card-title {
            font-size: 9px;
            font-weight: 700;
            color: #475569;
        }

        .score-card-value {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            margin: 2px 0;
        }

        .score-card-badge {
            display: inline-block;
            font-size: 8.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 3px;
            background: #0284c7;
            color: #ffffff;
        }

        /* Remarks Box */
        .remarks-box {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            background: #f8fafc;
            border-radius: 4px;
            font-size: 9.5px;
            min-height: 48px;
            margin-top: 6px;
        }

        /* Dual Signatures */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1.5px solid #0f172a;
        }

        .signature-col {
            border: 1px dashed #94a3b8;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            min-height: 95px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .signature-title {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
        }

        .signature-status {
            font-size: 9px;
            font-weight: 700;
            color: #0369a1;
            margin-top: 2px;
        }

        .signature-date {
            font-size: 8.5px;
            color: #64748b;
        }

        .stamp-placeholder {
            margin-top: 30px;
            font-size: 8.5px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <strong>{{ __('استمارة التنقيط والتقييم الإداري السنوي') }}</strong>
            <span style="opacity: 0.8; margin-right: 8px; font-size: 10px;">({{ $user?->name }} — {{ $yearStr }})</span>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                {{ __('طباعة الاستمارة (Print)') }}
            </button>
            <a href="{{ route('print.staff-career.evaluation-sheet.pdf', ['locale' => app()->getLocale(), 'evaluation' => $evaluation->id]) }}" class="btn btn-secondary">
                {{ __('تحميل PDF') }}
            </a>
        </div>
    </div>

    <div class="sheet-container">
        <!-- Official Header -->
        <header class="header-section">
            <div class="republic-title">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry-title">{{ __('وزارة التربية الوطنية') }}</div>
            <div class="sub-header-grid">
                <div>{{ $directorate ?? ($school?->directorate_name ?? 'مديرية التربية الوطنية') }}</div>
                <div>{{ $school_name ?? ($school?->school_name ?? 'متوسطة ساجل النموذجية') }}</div>
            </div>
        </header>

        <!-- Document Title -->
        <div class="doc-title-box">
            <div class="doc-main-title">{{ __('بطاقة التنقيط والتقييم الإداري والتربوي السنوي') }}</div>
            <div class="doc-sub-title">
                {{ __('تطبيقاً لأحكام الأمر 06-03 المتضمن القانون الأساسي العام للوظيفة العمومية والمرسوم التنفيذي 08-315') }}
                — {{ __('السنة الدراسية / المالية') }}: <strong>{{ $yearStr }}</strong>
            </div>
        </div>

        <!-- 1. Employee Identity & Civil Service Status -->
        <div class="section-title">
            <span>{{ __('أولاً: بطاقة الهوية والوضعية الإدارية للموظف') }}</span>
            <span style="font-weight: 500; font-size: 9px;">Matricule & Statut</span>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">{{ __('اللقب والاسم') }}:</span>
                <span class="info-value">{{ $user?->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('رقم التعريف الوظيفي') }}:</span>
                <span class="info-value">{{ $user?->registration_number ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('تاريخ ومكان الميلاد') }}:</span>
                <span class="info-value">{{ $formatDate($user?->birth_date) }} {{ $user?->birth_place ? 'بـ '.$user->birth_place : '' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('رقم التعريف الوطني NIN') }}:</span>
                <span class="info-value">{{ $user?->national_id_number ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('الرتبة القانونية والصفة') }}:</span>
                <span class="info-value">{{ $user?->statutory_grade ?? ($user?->role?->label() ?? '—') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('المادة / التخصص') }}:</span>
                <span class="info-value">{{ $user?->primarySubject?->name ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('تاريخ أول تعيين') }}:</span>
                <span class="info-value">{{ $formatDate($user?->recruitment_date) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('تاريخ التعيين في الرتبة') }}:</span>
                <span class="info-value">{{ $formatDate($user?->grade_appointment_date ?? $user?->recruitment_date) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('الدرجة الحالية وتاريخ سريانها') }}:</span>
                <span class="info-value">{{ __('الدرجة') }} {{ $user?->current_step ?? 0 }} (منذ {{ $formatDate($user?->current_step_date) }})</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('الأقدمية العامة') }}:</span>
                <span class="info-value">{{ $general_seniority ?? '—' }}</span>
            </div>
        </div>

        <!-- 2. Administrative Evaluation Criteria -->
        <div class="section-title">
            <span>{{ __('ثانياً: عناصر التنقيط الإداري السنوي من طرف رئيس المؤسسة') }}</span>
            <span style="font-weight: 500; font-size: 9px;">Grille de Notation Administrative (/20)</span>
        </div>
        <table class="eval-table">
            <thead>
                <tr>
                    <th style="width: 8%;">{{ __('الرقم') }}</th>
                    <th style="width: 52%; text-align: right;">{{ __('عناصر التقييم والمردود المهني') }}</th>
                    <th style="width: 20%;">{{ __('السقف القانوني') }}</th>
                    <th style="width: 20%;">{{ __('النقطة الممنوحة') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; font-weight: 700;">1</td>
                    <td>
                        <strong>{{ __('المواظبة والانضباط العام') }} (Assiduité & Ponctualité)</strong>
                        <span class="criteria-desc">{{ __('حضور الحصص، الالتزام بالمواعيد الرسمية، وتجنب الغيابات غير المبررة.') }}</span>
                    </td>
                    <td style="text-align: center; font-weight: 700;">05.00 / 05</td>
                    <td class="score-cell">{{ number_format($evaluation->punctuality_score, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: 700;">2</td>
                    <td>
                        <strong>{{ __('النشاط والمردودية المهنية') }} (Activité & Rendement)</strong>
                        <span class="criteria-desc">{{ __('إنجاز المهام التربوية والإدارية، حجز النقاط في الآجال، ومتابعة دفاتر النصوص.') }}</span>
                    </td>
                    <td style="text-align: center; font-weight: 700;">05.00 / 05</td>
                    <td class="score-cell">{{ number_format($evaluation->performance_score, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: 700;">3</td>
                    <td>
                        <strong>{{ __('السلوك والمظهر المهني') }} (Comportement & Déontologie)</strong>
                        <span class="criteria-desc">{{ __('حسن التعامل مع الأسرة التربوية والتلاميذ، والالتزام بأخلاقيات المهنة.') }}</span>
                    </td>
                    <td style="text-align: center; font-weight: 700;">05.00 / 05</td>
                    <td class="score-cell">{{ number_format($evaluation->conduct_score, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: 700;">4</td>
                    <td>
                        <strong>{{ __('المبادرة والمساهمة في الحياة المدرسية') }} (Initiative & Vie Scolaire)</strong>
                        <span class="criteria-desc">{{ __('المشاركة في المجالس والنوادي، التنسيق البيداغوجي، والابتكار والمبادرة.') }}</span>
                    </td>
                    <td style="text-align: center; font-weight: 700;">05.00 / 05</td>
                    <td class="score-cell">{{ number_format($evaluation->initiative_score, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="text-align: right; padding-right: 12px;">
                        {{ __('مجموع النقطة الإدارية الإجمالية السنوية') }} (Total Note Administrative)
                    </td>
                    <td style="text-align: center;">20.00 / 20</td>
                    <td class="score-cell" style="font-size: 14px; color: #0284c7; background: #e0f2fe;">
                        {{ number_format($evaluation->administrative_score, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 3. Overall Summary & Inspection Data -->
        <div class="scores-banner">
            <div class="score-card">
                <div class="score-card-title">{{ __('النقطة الإدارية') }}</div>
                <div class="score-card-value">{{ number_format($evaluation->administrative_score, 2) }} <span style="font-size: 10px; color: #64748b;">/ 20</span></div>
                <span class="score-card-badge">{{ __('مدير المؤسسة') }}</span>
            </div>
            <div class="score-card">
                <div class="score-card-title">{{ __('نقطة التفتيش البيداغوجي') }}</div>
                <div class="score-card-value">
                    {{ $evaluation->pedagogical_score !== null ? number_format($evaluation->pedagogical_score, 2) : '—' }}
                    <span style="font-size: 10px; color: #64748b;">/ 20</span>
                </div>
                <span class="score-card-badge" style="background: #475569;">
                    {{ $evaluation->last_inspection_date ? $formatDate($evaluation->last_inspection_date) : __('بدون تفتيش') }}
                </span>
            </div>
            <div class="score-card" style="border-color: #0284c7; background: #f0f9ff;">
                <div class="score-card-title" style="color: #0369a1;">{{ __('المعدل المعتمد للترقية') }}</div>
                <div class="score-card-value" style="color: #0284c7;">{{ number_format($evaluation->composite_score, 2) }} <span style="font-size: 10px; color: #0369a1;">/ 20</span></div>
                <span class="score-card-badge" style="background: #0284c7;">
                    {{ $evaluation->appreciation?->label() ?? __('جيد') }}
                </span>
            </div>
        </div>

        <!-- 4. Notes & Directives -->
        <div class="section-title">
            <span>{{ __('ثالثاً: تقديرات وملاحظات مدير المؤسسة') }}</span>
            <span style="font-weight: 500; font-size: 9px;">Appréciation Générale</span>
        </div>
        <div class="remarks-box">
            <strong>{{ __('التقدير العام') }}:</strong> {{ $evaluation->appreciation?->label() ?? __('جيد') }}
            <br>
            <strong>{{ __('الملاحظات والتوجيهات') }}:</strong>
            {{ $evaluation->evaluator_notes ?? __('موظف جاد ومواظب يؤدي مهامه بإخلاص وانضباط.') }}
        </div>

        @if($evaluation->staff_contestation_notes)
            <div class="remarks-box" style="margin-top: 6px; border-color: #fca5a5; background: #fef2f2; color: #991b1b;">
                <strong>{{ __('تحفظات أو طعن الموظف المعني بالأمر') }}:</strong>
                {{ $evaluation->staff_contestation_notes }}
            </div>
        @endif

        <!-- 5. Signatures -->
        <div class="signatures-grid">
            <div class="signature-col">
                <div>
                    <div class="signature-title">{{ __('توقيع المعني بالأمر والتبليغ') }}</div>
                    <div class="signature-status">
                        {{ $evaluation->signature_status_label }}
                    </div>
                </div>
                <div class="stamp-placeholder">{{ __('(اطلعت على النقطة الإدارية وتاريخ التبليغ)') }}</div>
                <div class="signature-date">{{ __('حرر بتاريخ') }}: {{ $formatDate($evaluation->evaluated_at ?? now()) }}</div>
            </div>
            <div class="signature-col">
                <div>
                    <div class="signature-title">{{ __('مدير المؤسسة (رئيس المصلحة)') }}</div>
                    <div class="signature-status">
                        {{ $evaluator?->name ?? 'مدير المتوسطة' }}
                    </div>
                </div>
                <div class="stamp-placeholder">{{ __('(الختم الرسمي وتأشيرة رئيس المؤسسة)') }}</div>
                <div class="signature-date">{{ __('في') }}: {{ $school?->municipality_name ?? 'الجزائر' }} {{ __('بتاريخ') }}: {{ $formatDate($evaluation->evaluated_at ?? now()) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
