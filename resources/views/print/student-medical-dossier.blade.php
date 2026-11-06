@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $student = $student ?? $record->student;
    $classroom = $student?->group?->classroom;
    $level = $classroom?->level;
    $yearStr = $record->academic_year ?? ($school?->academic_year ?? '2026/2027');
    $dossierCode = 'UDS-DOS-'.str_replace('/', '', $yearStr).'-'.$student?->id;
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('بطاقة المتابعة الصحية المدرسية') }} — {{ $student?->full_name }} — {{ $yearStr }}</title>

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
            margin: 6mm 8mm 6mm 8mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 9px;
            line-height: 1.25;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 195mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-emerald { background: #059669; color: #ffffff; }
        .btn-slate { background: #334155; color: #f1f5f9; }

        .sheet-container {
            width: 100%;
            max-width: 195mm;
            margin: 0 auto;
            padding: 2mm 3mm;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 3px;
            line-height: 1.2;
        }
        .traditional-header .country {
            font-size: 11.5px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministries {
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
        }

        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 4px;
            font-size: 8.5px;
        }
        .header-col-uds {
            display: table-cell !important;
            width: 38% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.3;
        }
        .header-col-title {
            display: table-cell !important;
            width: 40% !important;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 4px 8px;
        }
        .header-col-meta {
            display: table-cell !important;
            width: 22% !important;
            text-align: end;
            vertical-align: middle;
            font-family: monospace;
            font-size: 8px;
            line-height: 1.3;
        }

        .main-title {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
        }
        .sub-title {
            font-size: 8.5px;
            font-weight: 700;
            color: #059669;
        }

        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 4px 6px;
            margin-bottom: 4px;
            background: #ffffff;
        }
        .section-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 2.2px 4px;
            line-height: 1.2;
        }
        .data-table th {
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
            text-align: start;
        }
        .data-table td.center { text-align: center; }
        .data-table td.bold { font-weight: 700; }
        .data-table td.mono { font-family: monospace; font-size: 8.5px; }

        .kpi-row {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-spacing: 3px 0 !important;
            margin: 3px 0 !important;
        }
        .kpi-item {
            display: table-cell !important;
            vertical-align: middle !important;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px;
            text-align: center;
            background: #f8fafc;
        }
        .kpi-item.alert { background: #fef2f2; border-color: #fca5a5; }
        .kpi-item.ok { background: #ecfdf5; border-color: #a7f3d0; }

        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.5px solid #0f172a;
            padding-top: 3px;
            margin-top: 5px;
            text-align: center;
        }
        .sig-col {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top;
            padding: 0 10px;
        }
        .sig-title {
            font-size: 9px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 18px;
        }
        .sig-stamp {
            font-size: 7.5px;
            color: #64748b;
        }

        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 4px;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
            .sheet-container { padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        <div class="no-print">
            <div>
                <strong>{{ __('بطاقة الفحص الطبي والمتابعة الصحية المدرسية للتلميذ (UDS)') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8;">{{ $student?->full_name }} — {{ $yearStr }}</span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button onclick="window.print()" class="btn btn-emerald">
                    🖨️ {{ __('طباعة البطاقة') }}
                </button>
                <button onclick="window.history.back()" class="btn btn-slate">
                    {{ __('رجوع') }}
                </button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Official Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministries">{{ __('وزارة التربية الوطنية') }} — {{ __('وزارة الصحة والسكان وإصلاح المستشفيات') }}</div>
        </div>

        {{-- Meta Header Table --}}
        <div class="header-meta-table">
            <div class="header-col-uds">
                <div><strong>{{ __('مديرية التربية') }}:</strong> {{ $school?->directorate_name ?? 'مديرية التربية لولاية الوادي' }}</div>
                <div><strong>{{ __('المؤسسة') }}:</strong> {{ $school?->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</div>
                <div style="color: #059669; font-weight: 700;">{{ $record->uds_name ?? 'وحدة الكشف والمتابعة الصحية المدرسية (UDS)' }}</div>
            </div>
            <div class="header-col-title">
                <div class="main-title">{{ __('بطاقة الفحص الطبي والمتابعة الصحية') }}</div>
                <div class="sub-title">{{ __('الموسم الدراسي') }}: {{ $yearStr }}</div>
            </div>
            <div class="header-col-meta">
                <div><strong>Réf:</strong> {{ $dossierCode }}</div>
                <div><strong>{{ __('تاريخ الفحص') }}:</strong> {{ $formatDate($record->screening_date) }}</div>
                <div><strong>{{ __('طبيب الفحص') }}:</strong> {{ $record->doctor_name ?? 'د. الصحة المدرسية' }}</div>
            </div>
        </div>

        {{-- 1. Student Identity --}}
        <div class="section-box">
            <div class="section-title">
                <span>{{ __('1. الهوية المدنية والتمدرس') }}</span>
                <span style="font-family: monospace; font-size: 7.5px;">ID: {{ $student?->id }}</span>
            </div>
            <table class="data-table">
                <tbody>
                    <tr>
                        <th style="width: 15%;">{{ __('اسم ولقب التلميذ') }}</th>
                        <td style="width: 35%; font-weight: 800; font-size: 9px; color: #0f172a;">{{ $student?->full_name }}</td>
                        <th style="width: 15%;">{{ __('تاريخ ومكان الميلاد') }}</th>
                        <td style="width: 35%;">{{ $student?->birth_date?->format('d/m/Y') }} ({{ $student?->birth_place ?? '—' }})</td>
                    </tr>
                    <tr>
                        <th>{{ __('المستوى والفوج') }}</th>
                        <td class="bold">{{ $level?->name ?? 'التعليم المتوسط' }} — {{ $student?->group?->name ?? 'فوج عام' }}</td>
                        <th>{{ __('الجنس والزمرة الدموية') }}</th>
                        <td class="bold">
                            {{ $student?->isMale() ? __('ذكر') : __('أنثى') }} |
                            <span style="color: #be123c; font-weight: 900; font-family: monospace;">{{ $record->blood_type?->label() ?? 'غير محدد' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 2. Biometrics & Vital Constants --}}
        <div class="section-box">
            <div class="section-title">
                <span>{{ __('2. القياسات البيومترية ومؤشر كتلة الجسم (Biométrie & IMC)') }}</span>
            </div>
            <div class="kpi-row">
                <div class="kpi-item">
                    <div style="font-size: 7.5px; color: #64748b;">{{ __('القامة (الطول)') }}</div>
                    <div style="font-size: 12px; font-weight: 900; font-family: monospace;">{{ $record->height_cm ? $record->height_cm.' cm' : '—' }}</div>
                </div>
                <div class="kpi-item">
                    <div style="font-size: 7.5px; color: #64748b;">{{ __('الوزن') }}</div>
                    <div style="font-size: 12px; font-weight: 900; font-family: monospace;">{{ $record->weight_kg ? $record->weight_kg.' kg' : '—' }}</div>
                </div>
                <div class="kpi-item {{ $record->bmi() && ($record->bmi() < 18.5 || $record->bmi() > 25) ? 'alert' : 'ok' }}">
                    <div style="font-size: 7.5px; color: #64748b;">{{ __('مؤشر كتلة الجسم (IMC)') }}</div>
                    <div style="font-size: 12px; font-weight: 900; font-family: monospace;">{{ $record->bmi() ?? '—' }}</div>
                    <div style="font-size: 7px; font-weight: 700;">{{ $record->bmiCategory() }}</div>
                </div>
                <div class="kpi-item">
                    <div style="font-size: 7.5px; color: #64748b;">{{ __('الدفتر الصحي والتلقيحات') }}</div>
                    <div style="font-size: 10px; font-weight: 800; color: {{ $record->vaccinations_up_to_date ? '#047857' : '#b45309' }};">
                        {{ $record->vaccinations_up_to_date ? __('محدث ومكتمل') : __('غير مكتمل') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Sensory & Dental Examination --}}
        <div class="section-box">
            <div class="section-title">
                <span>{{ __('3. الفحص الحسي (البصر والسمع) وصحة الفم والأسنان') }}</span>
            </div>
            <table class="data-table">
                <tbody>
                    <tr>
                        <th style="width: 20%;">{{ __('حدة البصر (العين اليمنى)') }}</th>
                        <td style="width: 30%;" class="mono bold">{{ $record->visual_acuity_right ?? '10/10' }}</td>
                        <th style="width: 20%;">{{ __('حدة البصر (العين اليسرى)') }}</th>
                        <td style="width: 30%;" class="mono bold">{{ $record->visual_acuity_left ?? '10/10' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('ارتداء نظارات طبية') }}</th>
                        <td class="bold">{{ $record->wears_glasses ? __('نعم (حامل لنظارات)') : __('لا') }}</td>
                        <th>{{ __('حاسة السمع') }}</th>
                        <td class="bold">{{ $record->hearing_condition?->label() ?? __('سمع سليم') }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('عدد الأسنان المنخورة (تسوس)') }}</th>
                        <td class="mono bold" style="color: {{ $record->dental_caries_count > 0 ? '#b91c1c' : '#047857' }};">
                            {{ $record->dental_caries_count }} {{ __('أسنان مسوسة') }}
                        </td>
                        <th>{{ __('نظافة الفم واللثة') }}</th>
                        <td class="bold">{{ $record->dental_hygiene?->label() ?? __('جيدة') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 4. Chronic Conditions & Emergency PAI Protocol --}}
        <div class="section-box" style="{{ $record->has_chronic_illness ? 'border-color: #fca5a5; background: #fffbfb;' : '' }}">
            <div class="section-title" style="{{ $record->has_chronic_illness ? 'color: #991b1b; border-color: #fecaca;' : '' }}">
                <span>{{ __('4. الأمراض المزمنة وبروتوكول الطوارئ بالمؤسسة (Affections & PAI)') }}</span>
                @if ($record->has_chronic_illness)
                    <span style="background: #fee2e2; color: #991b1b; padding: 1px 6px; border-radius: 9999px; font-weight: 800; font-size: 7.5px;">⚠️ {{ __('حالة صحية خاصة') }}</span>
                @endif
            </div>
            <table class="data-table">
                <tbody>
                    <tr>
                        <th style="width: 20%;">{{ __('تصنيف المرض المزمن') }}</th>
                        <td style="width: 80%; font-weight: 800; color: {{ $record->has_chronic_illness ? '#b91c1c' : '#047857' }};">
                            {{ $record->chronic_condition_type?->label() ?? __('سليم') }}
                        </td>
                    </tr>
                    @if ($record->has_chronic_illness)
                        <tr>
                            <th>{{ __('تفاصيل الحالة والتشخيص') }}</th>
                            <td>{{ $record->chronic_illness_details ?: __('متابع دورياً لدى الطبيب المختص.') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('إجراءات الطوارئ والإسعاف (PAI)') }}</th>
                            <td style="font-weight: 700; color: #991b1b;">
                                {{ $record->emergency_protocol_notes ?: __('إشعار المصحة المدرسية ومستشار التربية فوراً عند ظهور أي عارض طارئ.') }}
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('دواء مستعجل مودع بالمصحة') }}</th>
                            <td class="bold">
                                {{ $record->emergency_medication_at_school ? __('نعم، الدواء متوفر بمصحة المؤسسة مع تعليمات الاستعمال') : __('غير مودع بالمصحة') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- 5. Physical Education Exemption (EPS Dispensation) --}}
        <div class="section-box" style="{{ $record->isExemptFromEps() ? 'border-color: #fde68a; background: #fffdf5;' : '' }}">
            <div class="section-title" style="{{ $record->isExemptFromEps() ? 'color: #92400e; border-color: #fef3c7;' : '' }}">
                <span>{{ __('5. ممارسة التربية البدنية والرياضية (Aptitude EPS)') }}</span>
                <span style="font-weight: 800;">{{ $record->eps_dispensation_status?->label() ?? __('لائق') }}</span>
            </div>
            @if ($record->isExemptFromEps())
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th style="width: 20%;">{{ __('رقم الشهادة الطبية الرسمية') }}</th>
                            <td style="width: 30%;" class="mono bold">{{ $record->eps_medical_certificate_ref ?? 'CERT-EPS-'.$record->id }}</td>
                            <th style="width: 20%;">{{ __('فترة الإعفاء') }}</th>
                            <td style="width: 30%;" class="bold">
                                {{ $formatDate($record->eps_dispensation_start_date) }} ➔ {{ $formatDate($record->eps_dispensation_end_date) }}
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('الداعي الطبي للإعفاء') }}</th>
                            <td colspan="3">{{ $record->eps_dispensation_reason ?: __('إعفاء معلل بشهادة طبية معتمدة من طبيب الصحة المدرسية.') }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>

        {{-- 6. Clinical Assessment & Specialist Referrals --}}
        <div class="section-box">
            <div class="section-title">
                <span>{{ __('6. الخلاصة الطبية والتوجيه المتخصص (Conclusions & Orientation)') }}</span>
                @if ($record->follow_up_required)
                    <span style="color: #b45309; font-weight: 800; font-size: 7.5px;">⚡ {{ __('متابعة دورية مطلوبة') }}</span>
                @endif
            </div>
            <div style="font-size: 8px; line-height: 1.3; color: #334155; margin-bottom: 3px;">
                <strong>{{ __('الملاحظات السريرية العامة') }}:</strong>
                {{ $record->general_clinical_notes ?: __('الحالة العامة للتلميذ مرضية ومستقرة، ولا توجد موانع طبية تحول دون التمدرس العادي.') }}
            </div>
            @if ($record->specialist_referral)
                <div style="font-size: 8px; line-height: 1.3; color: #047857; font-weight: 700;">
                    <strong>{{ __('التوجيه نحو الفحص المتخصص') }}:</strong> {{ $record->specialist_referral }}
                </div>
            @endif
        </div>

        {{-- Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('طبيب الصحة المدرسية (وحدة UDS)') }}</div>
                <div class="sig-stamp">({{ __('التوقيع والختم الطبي') }})</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة التعليمية') }}</div>
                <div class="sig-stamp">({{ __('التأشيرة والختم الإداري') }})</div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — بطاقة الصحة المدرسية') }} · {{ $dossierCode }}
        </div>
    </div>

</body>
</html>

