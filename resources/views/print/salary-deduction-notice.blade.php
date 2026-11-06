@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $year = $school?->academic_year ?? '2026/2027';
    $noticeRef = $absence->deductionNoticeFormatted();
    $teacher = $teacher ?? $absence->teacher;
    $deductionDays = $absence->calculateDeductionDays();
    $notifiedAt = $absence->deduction_notified_at ?? now();
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('إشعار رسمي بالخصم من الراتب') }} — {{ $teacher?->name }} — {{ $noticeRef }}</title>

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
        .btn-rose { background: #be123c; color: #ffffff; }

        .sheet {
            max-width: 190mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border: 2px solid #be123c;
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
            border-bottom: 1px solid #fda4af;
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
            margin: 16px 0 18px 0;
        }
        .document-title h1 {
            display: inline-block;
            font-size: 17px;
            font-weight: 900;
            color: #be123c;
            border-bottom: 2px solid #be123c;
            padding-bottom: 4px;
        }
        .document-title .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        .legal-references {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .statement-box {
            font-size: 11.5px;
            line-height: 1.7;
            color: #1e293b;
            margin-bottom: 18px;
            text-align: justify;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .data-table th {
            width: 32%;
            background: #fff1f2;
            color: #9f1239;
            font-weight: 700;
            text-align: start;
        }
        .data-table td {
            color: #0f172a;
            font-weight: 600;
        }

        .warning-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10px;
            color: #9f1239;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 24px;
            text-align: center;
        }
        .sig-block {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-block .title {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-block .name {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            margin-top: 3px;
        }
        .sig-block .sub {
            font-size: 8.5px;
            color: #94a3b8;
        }

        .footer-note {
            margin-top: 20px;
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
            .sheet { border: 2px solid #be123c !important; box-shadow: none !important; padding: 8mm !important; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <strong style="color: #be123c;">{{ __('معاينة إشعار الخصم من الراتب') }}</strong>
            <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #ffe4e6; color: #9f1239;">
                {{ $noticeRef }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-dark">
                🖨️ {{ __('طباعة الإشعار (A4)') }}
            </button>
            <a href="{{ route('print.teacher-absence.deduction-notice.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id]) }}" class="btn btn-rose">
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
                <div><strong>{{ __('المصلحة') }}:</strong> {{ __('مصلحة الرواتب والمحاسبة والمصالح الاقتصادية') }}</div>
            </div>
            <div class="col-meta" dir="ltr">
                <div><strong>Réf:</strong> {{ $noticeRef }}</div>
                <div><strong>Date:</strong> {{ $formatDate($notifiedAt) }}</div>
                <div><strong>Année:</strong> {{ $year }}</div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="document-title">
            <h1>{{ __('إشـعـار بـالـخـصـم مـن الـراتـب لـغـيـاب غـيـر مـبـرر') }}</h1>
            <div class="subtitle">Avis de Retenue sur Traitement pour Absence non Rémunérée</div>
        </div>

        {{-- Legal Reference Foundations --}}
        <div class="legal-references">
            <strong>{{ __('المراجع القانونية والتنظيمية') }}:</strong><br>
            • {{ __('بمقتضى القانون رقم 90-21 المؤرخ في 15 أوت 1990 المتعلق بالمحاسبة العمومية، لاسيما المادة 32 منه (قاعدة أداء الخدمة).') }}<br>
            • {{ __('بمقتضى الأمر رقم 06-03 المؤرخ في 15 جويلية 2006 المتضمن القانون الأساسي العام للوظيفة العمومية، لاسيما المادتين 207 و208.') }}<br>
            • {{ __('بمقتضى المنشور الوزاري رقم 995 المتعلق بخصم أيام وساعات الغياب غير المبررة قانوناً.') }}
        </div>

        {{-- Body Statement --}}
        <div class="statement-box">
            {{ __('يُنهي مدير المؤسسة إلى علم السيد(ة) المذكورة بياناته(ا) أسفله، أنه تقرر تطبيق إجراء الخصم من الراتب الشهري بحساب جزء من ثلاثين (1/30) عن كل يوم غياب غير مبرر، وذلك لعدم تقديم المبررات القانونية أو عدم قبولها من طرف الإدارة:') }}
        </div>

        {{-- Detailed Data Table --}}
        <table class="data-table">
            <tbody>
                <tr>
                    <th>{{ __('اللقب والاسم الكامل') }}</th>
                    <td><strong>{{ $teacher?->name }}</strong></td>
                </tr>
                <tr>
                    <th>{{ __('الرتبة ومادة التدريس') }}</th>
                    <td>{{ __('أستاذ التعليم المتوسط') }} — {{ $teacher?->primarySubject?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('فترة الغياب موضوع الخصم') }}</th>
                    <td>
                        {{ __('من') }}: <strong>{{ $formatDate($absence->starts_at) }}</strong>
                        {{ __('إلى') }}: <strong>{{ $formatDate($absence->ends_at) }}</strong>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('عدد أيام الخصم المقررة') }}</th>
                    <td>
                        <strong style="color: #be123c; font-size: 13px;">
                            {{ $deductionDays }} {{ $deductionDays === 1 ? __('يوم واحد') : ($deductionDays === 2 ? __('يومان') : __('أيام')) }}
                        </strong>
                        <span style="font-size: 10px; color: #64748b;">({{ __('بنسبة') }} {{ $deductionDays }}/30 {{ __('من الراتب الشهري الخام الخاضع للاقتطاع') }})</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('سبب وتبرير الخصم') }}</th>
                    <td>{{ $absence->notes ?: __('غياب غير مبرر قانوناً لعدم استيفاء الشروط النظامية المنصوص عليها') }}</td>
                </tr>
                <tr>
                    <th>{{ __('تاريخ التبليغ للمعني') }}</th>
                    <td>{{ $formatDate($notifiedAt) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Appeals and Warning Notice --}}
        <div class="warning-box">
            <strong>{{ __('حق التظلم والطعن') }}:</strong>
            {{ __('يحق للأستاذ المعني تقديم مبرر رسمي أو طعن إداري كتابي مشفوع بالوثائق الثبوتية لدى إدارة المؤسسة في أجل أقصاه 48 ساعة من تاريخ استلام هذا الإشعار، وإلا سيُحال الملف نهائياً لمصلحة الرواتب بمديرية التربية لتنفيذ الخصم تلقائياً.') }}
        </div>

        {{-- Tri-party Signatures --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div>
                    <div class="title">{{ __('توقيع المعني بالأمر (بالاستلام)') }}</div>
                    <div class="name">{{ $teacher?->name }}</div>
                </div>
                <div class="sub">({{ __('استلمت نسخة من هذا الإشعار بتاريخ: ................') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('المقتصد / مسير المصالح المالية') }}</div>
                    <div class="name">{{ __('مصلحة الرواتب والنفقات') }}</div>
                </div>
                <div class="sub">({{ __('التأشيرة للمعاينة والتنفيذ المالي') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مدير المتوسطة') }}</div>
                    <div class="name">{{ $school?->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                </div>
                <div class="sub">({{ __('التوقيع والختم الإداري الرسمي') }})</div>
            </div>
        </div>

        {{-- Carbon Copies --}}
        <div class="footer-note">
            <div>
                <strong>{{ __('نسخة موجهة إلى') }}:</strong>
                {{ __('السيد مدير التربية (مصلحة الرواتب والتسيير المالي)') }} — {{ __('الملف الإداري بالمؤسسة') }}
            </div>
            <div>{{ __('سجّل — الإدارة البيداغوجية والمالية') }}</div>
        </div>
    </div>

</body>
</html>
