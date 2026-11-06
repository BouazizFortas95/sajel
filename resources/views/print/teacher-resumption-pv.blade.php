@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $formatDateTime = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $year = $school?->academic_year ?? '2026/2027';
    $pvNumber = $absence->resumptionPvFormatted();
    $teacher = $teacher ?? $absence->teacher;
    $resumedAt = $absence->resumed_at ?? now();
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('محضر استئناف العمل') }} — {{ $teacher?->name }} — {{ $pvNumber }}</title>

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
            margin: 10mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.5;
            padding: 20px;
        }

        .no-print {
            max-width: 190mm;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .btn {
            display: inline-block;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-dark { background: #1e293b; color: #ffffff; }
        .btn-primary { background: #d97706; color: #ffffff; }

        .sheet {
            max-width: 190mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border: 2px solid #0f172a;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            page-break-inside: avoid !important;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 12px;
        }
        .traditional-header .republic {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .traditional-header .ministry {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }

        .identity-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 8px;
            margin-bottom: 16px;
            font-size: 10.5px;
        }
        .identity-row .col-meta {
            text-align: end;
            font-family: monospace;
        }

        .document-title {
            text-align: center;
            margin: 16px 0 20px 0;
        }
        .document-title h1 {
            display: inline-block;
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .document-title .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        .statement-box {
            font-size: 12px;
            line-height: 1.8;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: justify;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .data-table th {
            width: 32%;
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-align: start;
        }
        .data-table td {
            color: #0f172a;
            font-weight: 600;
        }

        .legal-notice {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 10px;
            color: #92400e;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            text-align: center;
        }
        .sig-block {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-block .title {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-block .name {
            font-size: 10.5px;
            font-weight: 700;
            color: #334155;
            margin-top: 4px;
        }
        .sig-block .sub {
            font-size: 9.5px;
            color: #94a3b8;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body { padding: 0 !important; background: #ffffff !important; }
            .no-print { display: none !important; }
            .sheet { border: 2px solid #000000 !important; box-shadow: none !important; padding: 8mm !important; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <strong style="color: #334155;">{{ __('معاينة محضر استئناف العمل الرسمي') }}</strong>
            <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #dcfce7; color: #166534;">
                {{ $pvNumber }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-dark">
                🖨️ {{ __('طباعة المحضر (A4)') }}
            </button>
            <a href="{{ route('print.teacher-absence.resumption-pv.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}" class="btn btn-primary">
                📥 {{ __('تحميل PDF رسمي') }}
            </a>
        </div>
    </div>

    <div class="sheet">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="republic">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
        </div>

        {{-- Establishment Identification --}}
        <div class="identity-row">
            <div>
                <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $school?->directorate_name ?? '....................................' }}</div>
                <div><strong>{{ __('المؤسسة') }}:</strong> {{ $school?->school_name ?? 'متوسطة ساجل النموذجية' }}</div>
                <div><strong>{{ __('المصلحة') }}:</strong> {{ __('مصلحة الموظفين والتفتيش البيداغوجي') }}</div>
            </div>
            <div class="col-meta" dir="ltr">
                <div><strong>Réf:</strong> {{ $pvNumber }}</div>
                <div><strong>Date:</strong> {{ $formatDate($resumedAt) }}</div>
                <div><strong>Année:</strong> {{ $year }}</div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="document-title">
            <h1>{{ __('مـحـضـر اسـتـئـنـاف الـعـمـل') }}</h1>
            <div class="subtitle">Procès-Verbal de Reprise de Fonction après Congé ou Absence</div>
        </div>

        {{-- Legal Attestation Statement --}}
        <div class="statement-box">
            {{ __('يشهد مدير المؤسسة الموقع أسفله، بأن الأستاذ(ة) المذكورة بياناته(ا) أدناه، قد استأنف(ت) عمله(ا) الرسمي والفعلي بصفة نظامية بعد فترة انقطاع، وذلك طبقاً للأحكام والضوابط القانونية المعمول بها في قطاع التربية الوطنية والوظيفة العمومية:') }}
        </div>

        {{-- Detailed Data Table --}}
        <table class="data-table">
            <tbody>
                <tr>
                    <th>{{ __('اللقب والاسم الكامل') }}</th>
                    <td><strong>{{ $teacher?->name }}</strong></td>
                </tr>
                <tr>
                    <th>{{ __('الرتبة والوظيفة') }}</th>
                    <td>{{ __('أستاذ التعليم المتوسط') }} (P.E.M)</td>
                </tr>
                <tr>
                    <th>{{ __('مادة التدريس') }}</th>
                    <td>{{ $teacher?->primarySubject?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('طبيعة الغياب / العطلة') }}</th>
                    <td>{{ $absence->reason?->label() ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('فترة الانقطاع المرخصة') }}</th>
                    <td>
                        {{ __('من') }}: <strong>{{ $formatDate($absence->starts_at) }}</strong>
                        {{ __('إلى غاية') }}: <strong>{{ $formatDate($absence->ends_at) }}</strong>
                        ({{ $absence->durationLabel() }})
                    </td>
                </tr>
                <tr>
                    <th>{{ __('المرجع الإداري / الطبي') }}</th>
                    <td>{{ $absence->reference_number ?: __('بناءً على الإشعار بالغياب المسجل بالإدارة') }}</td>
                </tr>
                <tr>
                    <th>{{ __('تاريخ وساعة الاستئناف الفعلي') }}</th>
                    <td>
                        <span style="color: #047857; font-size: 13px; font-weight: 800;">
                            {{ $formatDateTime($resumedAt) }}
                        </span>
                    </td>
                </tr>
                @if ($absence->resumption_notes)
                    <tr>
                        <th>{{ __('ملاحظات مدير المؤسسة') }}</th>
                        <td style="font-weight: normal; color: #475569;">{{ $absence->resumption_notes }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Legal Reference Notice --}}
        <div class="legal-notice">
            <strong>{{ __('تنبيه قانوني') }}:</strong>
            {{ __('يُحرر هذا المحضر في ثلاثة نظائر أصلية فور عودة الموظف للعمل، ويُرسل نظير منه لمديرية التربية (مصلحة الموظفين والتفتيش) لتسوية الوضعية الإدارية والمالية، ويُحفظ نظير بالملف الإداري بالمؤسسة، ويُسلّم النظير الثالث للمعني بالأمر.') }}
        </div>

        {{-- Official Signatures & Seal --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div>
                    <div class="title">{{ __('توقيع الأستاذ(ة) المعني(ة)') }}</div>
                    <div class="name">{{ $teacher?->name }}</div>
                </div>
                <div class="sub">({{ __('اطلعت وصادقت على التاريخ الفعلي لاستئناف عملي') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مدير المتوسطة') }}</div>
                    <div class="name">{{ $school?->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                </div>
                <div class="sub">({{ __('التوقيع والختم الرسمي للمؤسسة') }})</div>
            </div>
        </div>

        {{-- Carbon Copies --}}
        <div class="footer-note">
            <div>
                <strong>{{ __('نسخة موجهة إلى') }}:</strong>
                {{ __('السيد مدير التربية (مصلحة الموظفين والتفتيش)') }} — {{ __('ملف المعني بالأرشيف') }}
            </div>
            <div>{{ __('سجّل — المنظومة الرقمية الشاملة للإدارة والرقمنة المدرسية') }}</div>
        </div>
    </div>

</body>
</html>

