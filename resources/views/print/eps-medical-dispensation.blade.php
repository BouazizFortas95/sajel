@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $student = $student ?? $record->student;
    $classroom = $student?->group?->classroom;
    $level = $classroom?->level;
    $yearStr = $record->academic_year ?? ($school?->academic_year ?? '2026/2027');
    $certRef = $record->eps_medical_certificate_ref ?: 'CERT-EPS-'.date('Ymd').'-'.$student?->id;
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادة الإعفاء من التربية البدنية') }} — {{ $student?->full_name }}</title>

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
            margin: 8mm 12mm 8mm 12mm;
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
            max-width: 180mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 11px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 10.5px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-amber { background: #d97706; color: #ffffff; }
        .btn-slate { background: #334155; color: #f1f5f9; }

        .sheet-container {
            width: 100%;
            max-width: 180mm;
            margin: 0 auto;
            padding: 4mm 6mm;
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 6px;
            line-height: 1.25;
        }
        .traditional-header .country {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministries {
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }

        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 12px;
            font-size: 9.5px;
        }
        .header-col-left {
            display: table-cell !important;
            width: 50% !important;
            text-align: start;
            line-height: 1.4;
        }
        .header-col-right {
            display: table-cell !important;
            width: 50% !important;
            text-align: end;
            line-height: 1.4;
        }

        .title-box {
            text-align: center;
            margin: 10px 0 16px;
            padding: 6px 16px;
            background: #fffbeb;
            border: 1.5px solid #d97706;
            border-radius: 6px;
        }
        .main-title {
            font-size: 14px;
            font-weight: 900;
            color: #92400e;
            text-transform: uppercase;
        }
        .sub-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #451a03;
            margin-top: 2px;
        }

        .legal-notice {
            font-size: 8.5px;
            color: #64748b;
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.3;
            font-style: italic;
        }

        .attestation-body {
            font-size: 11px;
            line-height: 1.8;
            color: #0f172a;
            text-align: justify;
            margin-bottom: 16px;
        }

        .highlight-val {
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px dashed #64748b;
            padding: 0 4px;
        }

        .dispensation-card {
            border: 1.5px solid #b45309;
            border-radius: 6px;
            padding: 10px 14px;
            background: #fffdfa;
            margin-bottom: 18px;
        }
        .dispensation-title {
            font-size: 11px;
            font-weight: 800;
            color: #92400e;
            margin-bottom: 6px;
            border-bottom: 1px solid #fde68a;
            padding-bottom: 4px;
        }

        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.5px solid #0f172a;
            padding-top: 8px;
            margin-top: 18px;
            text-align: center;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top;
            padding: 0 6px;
        }
        .sig-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 24px;
        }
        .sig-stamp {
            font-size: 8px;
            color: #64748b;
        }

        .footer-note {
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 12px;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
            .sheet-container { padding: 4mm 6mm !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        <div class="no-print">
            <div>
                <strong>{{ __('شهادة الإعفاء الطبي من التربية البدنية والرياضية') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8;">{{ $student?->full_name }} — {{ $certRef }}</span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button onclick="window.print()" class="btn btn-amber">
                    🖨️ {{ __('طباعة الشهادة') }}
                </button>
                <button onclick="window.history.back()" class="btn btn-slate">
                    {{ __('رجوع') }}
                </button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Official Algerian Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministries">{{ __('وزارة التربية الوطنية') }} — {{ __('وزارة الصحة والسكان وإصلاح المستشفيات') }}</div>
        </div>

        {{-- Meta Strip --}}
        <div class="header-meta-table">
            <div class="header-col-left">
                <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $school?->directorate_name ?? 'ولاية الوادي' }}</div>
                <div><strong>{{ __('المؤسسة التعليمية') }}:</strong> {{ $school?->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</div>
                <div><strong>{{ __('وحدة الكشف والمتابعة') }}:</strong> {{ $record->uds_name ?? 'UDS المؤسسة' }}</div>
            </div>
            <div class="header-col-right">
                <div><strong>{{ __('رقم القيد / المرجع') }}:</strong> <span style="font-family: monospace; font-weight: 800;">{{ $certRef }}</span></div>
                <div><strong>{{ __('الموسم الدراسي') }}:</strong> {{ $yearStr }}</div>
                <div><strong>{{ __('حرر بتاريخ') }}:</strong> {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>

        {{-- Title Box --}}
        <div class="title-box">
            <div class="main-title">{{ __('شهادة الإعفاء الطبي من ممارسة التربية البدنية والرياضية') }}</div>
            <div class="sub-title">CERTIFICAT MÉDICAL DE DISPENSE D'ÉDUCATION PHYSIQUE ET SPORTIVE (EPS)</div>
        </div>

        <div class="legal-notice">
            {{ __('تطبيقاً لأحكام القرار الوزاري المشترك المحدد لشروط وكيفيات الإعفاء من التربية البدنية والرياضية في مؤسسات التعليم والتكوين.') }}
        </div>

        {{-- Attestation Body --}}
        <div class="attestation-body">
            {{ __('يشهد الدكتور') }}: <span class="highlight-val">{{ $record->doctor_name ?: __('طبيب الصحة المدرسية بوحدة الكشف والمتابعة (UDS)') }}</span><br>
            {{ __('أنه بعد إجراء الفحص الطبي الدقيق والمطابقة العيادية للتلميذ(ة)') }}:
            <span class="highlight-val" style="font-size: 12px; font-weight: 900;">{{ $student?->full_name }}</span>،<br>
            {{ __('المولود(ة) بتاريخ') }}: <span class="highlight-val">{{ $student?->birth_date?->format('d/m/Y') ?? '—' }}</span>
            {{ __('بـ') }}: <span class="highlight-val">{{ $student?->birth_place ?? '—' }}</span>،<br>
            {{ __('المسجل(ة) بمستوى') }}: <span class="highlight-val">{{ $level?->name ?? 'التعليم المتوسط' }}</span>،
            {{ __('الفوج التربوي') }}: <span class="highlight-val">{{ $student?->group?->name ?? '—' }}</span>.<br><br>

            {{ __('تبيّن أن حالته(ا) الصحية الراهنة تقتضي إعفاءه(ا) من ممارسة حصص وأنشطة التربية البدنية والرياضية المقررة رسمياً.') }}
        </div>

        {{-- Dispensation Specifications --}}
        <div class="dispensation-card">
            <div class="dispensation-title">{{ __('طبيعة ومدى الإعفاء الطبي الممنوح') }}</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9.5px;">
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 700; color: #475569; padding: 3px 0;">{{ __('نوع الإعفاء:') }}</td>
                        <td style="width: 75%; font-weight: 900; color: #b45309; font-size: 11px;">
                            {{ $record->eps_dispensation_status?->label() ?? __('إعفاء سنوي كامل') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569; padding: 3px 0;">{{ __('فترة وسريان الإعفاء:') }}</td>
                        <td style="font-weight: 800; font-family: monospace;">
                            {{ __('من') }}: {{ $formatDate($record->eps_dispensation_start_date ?? now()) }}
                            {{ __('إلى غاية') }}: {{ $record->eps_dispensation_end_date ? $formatDate($record->eps_dispensation_end_date) : __('نهاية الموسم الدراسي ('.$yearStr.')') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569; padding: 3px 0;">{{ __('التعليل / الملاحظة الطبية:') }}</td>
                        <td style="color: #334155;">
                            {{ $record->eps_dispensation_reason ?: __('حالة صحية مانعة من المجهود البدني المكثف وفقاً لما تقتضيه السلامة الجسدية للتلميذ.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="font-size: 9px; line-height: 1.5; color: #475569; margin-bottom: 8px;">
            ⚠️ <strong>{{ __('التزام التمدرس والتقييم البيداغوجي') }}:</strong>
            {{ __('لا يعفي هذا الإجراء التلميذ من الحضور والمشاركة في الأنشطة النظرية للتربية البدنية إن وُجدت، ويُدرج بيان "معفى (Dispensé)" رسمياً في خانة تقويم المادة في كشوف النقاط وشهادات التعليم المتوسط دون الإخلال بالمعدل العام.') }}
        </div>

        {{-- Tripartite Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('أستاذ التربية البدنية والرياضية') }}</div>
                <div class="sig-stamp">({{ __('الاطلاع والتسجيل في سجل المادة') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('طبيب الصحة المدرسية (UDS)') }}</div>
                <div class="sig-stamp">({{ __('الختم الطبي والتوقيع') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة التعليمية') }}</div>
                <div class="sig-stamp">({{ __('التأشيرة والختم الإداري الرسمي') }})</div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — شهادة الإعفاء من التربية البدنية') }} · {{ $certRef }}
        </div>
    </div>

</body>
</html>
